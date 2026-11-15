<?php

/**
 * Real row-locking concurrency proof for seller subscription review.
 *
 * This script is deliberately NOT part of PHPUnit. It must be pointed at a
 * MySQL/MariaDB database (SELECT ... FOR UPDATE is a no-op on SQLite), and it
 * drives two separate PHP processes against the real service:
 *
 *   $env:DB_CONNECTION='mysql'; $env:DB_HOST='127.0.0.1'; $env:DB_PORT='3307'
 *   $env:DB_DATABASE='alishe_concurrency'; $env:DB_USERNAME='root'; $env:DB_PASSWORD=''
 *   php tests/Concurrency/run.php
 *
 * The runner creates a disposable schema (migrate:fresh) on that database.
 */
require __DIR__.'/bootstrap.php';

use App\Exceptions\SubscriptionNotPendingException;
use App\Models\Seller;
use App\Models\SellerSubscription;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

const HOLD_MS = 2500;
const MIN_BLOCKED_SECONDS = 1.5;
const RELEASE_DELAY_MICROSECONDS = 2000000;

$failures = [];
$checks = 0;

function check(string $label, bool $condition, string $detail = ''): void
{
    global $failures, $checks;

    $checks++;

    if ($condition) {
        echo "  PASS  {$label}".($detail !== '' ? "  ({$detail})" : '').PHP_EOL;

        return;
    }

    $failures[] = $label.($detail !== '' ? " :: {$detail}" : '');
    echo "  FAIL  {$label}".($detail !== '' ? "  ({$detail})" : '').PHP_EOL;
}

function freshBarrier(): string
{
    $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'alishe_concurrency_'.getmypid().'_'.Str::random(6);

    if (! mkdir($dir, 0777, true) && ! is_dir($dir)) {
        throw new RuntimeException("Could not create barrier directory {$dir}");
    }

    return $dir;
}

function waitForFile(string $path, float $timeoutSeconds = 20.0): bool
{
    $deadline = microtime(true) + $timeoutSeconds;

    while (microtime(true) < $deadline) {
        if (file_exists($path)) {
            return true;
        }
        usleep(5000);
    }

    return false;
}

/**
 * @return array{0: resource, 1: array<int, resource>}
 */
function spawn(string $script, array $args, string $barrierDir, string $logName): array
{
    $command = '"'.PHP_BINARY.'" "'.__DIR__.DIRECTORY_SEPARATOR.$script.'"';

    foreach ($args as $arg) {
        $command .= ' '.escapeshellarg((string) $arg);
    }

    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['file', $barrierDir.DIRECTORY_SEPARATOR.$logName.'.out', 'w'],
        2 => ['file', $barrierDir.DIRECTORY_SEPARATOR.$logName.'.err', 'w'],
    ];

    $env = getenv();

    $process = proc_open($command, $descriptors, $pipes, null, is_array($env) ? $env : null);

    if (! is_resource($process)) {
        throw new RuntimeException("Could not spawn {$script}");
    }

    // The workers never read stdin; close the pipe so proc_close() cannot block.
    if (isset($pipes[0]) && is_resource($pipes[0])) {
        fclose($pipes[0]);
    }

    return [$process, $pipes];
}

function readResult(string $barrierDir, string $name): ?array
{
    $path = $barrierDir.DIRECTORY_SEPARATOR.'result-'.$name.'.json';

    if (! file_exists($path)) {
        return null;
    }

    $decoded = json_decode((string) file_get_contents($path), true);

    return is_array($decoded) ? $decoded : null;
}

function dumpLogs(string $barrierDir, string $name): void
{
    foreach (['out', 'err'] as $stream) {
        $path = $barrierDir.DIRECTORY_SEPARATOR.$name.'.'.$stream;

        if (file_exists($path) && filesize($path) > 0) {
            echo "    [{$name}.{$stream}] ".trim((string) file_get_contents($path)).PHP_EOL;
        }
    }
}

function makeSeller(string $label): Seller
{
    return Seller::create([
        'name' => 'Concurrency Seller '.$label,
        'email' => 'concurrency-'.$label.'-'.Str::random(8).'@example.com',
        'instagram_handle' => 'conc_'.Str::lower($label).'_'.Str::random(4),
        'product_details' => 'Concurrency test',
        'password' => Hash::make('SellerPass123'),
        'status' => 'approved',
        'approved_at' => now(),
    ]);
}

function makePendingSubscription(Seller $seller, SubscriptionPlan $plan, string $ref): SellerSubscription
{
    return SellerSubscription::create([
        'seller_id' => $seller->id,
        'subscription_plan_id' => $plan->id,
        'price' => 1000,
        'duration_days' => 30,
        'status' => 'pending',
        'payment_method' => 'bank_transfer',
        'transaction_reference' => $ref,
    ]);
}

/**
 * Runs one scenario: hold the seller row, start N workers at one barrier,
 * release the holder, collect results.
 *
 * @param  array<int, array{name: string, action: string, subscription: SellerSubscription}>  $workers
 * @return array<string, array|null>
 */
function runScenario(string $title, Seller $seller, array $workers, bool $withHolder): array
{
    global $failures;

    echo PHP_EOL.'== '.$title.' =='.PHP_EOL;

    $barrierDir = freshBarrier();
    $processes = [];
    $holder = null;

    if ($withHolder) {
        [$holder] = spawn('lock_holder.php', [$seller->id, $barrierDir, HOLD_MS], $barrierDir, 'holder');

        if (! waitForFile($barrierDir.DIRECTORY_SEPARATOR.'holder-locked')) {
            $failures[] = $title.' :: lock holder never acquired the seller row';
            dumpLogs($barrierDir, 'holder');

            return [];
        }

        echo '  INFO  seller row locked by holder process for '.HOLD_MS.'ms'.PHP_EOL;
    }

    foreach ($workers as $worker) {
        [$process] = spawn(
            'worker.php',
            [$worker['name'], $worker['action'], $seller->id, $worker['subscription']->id, $barrierDir],
            $barrierDir,
            $worker['name']
        );
        $processes[$worker['name']] = $process;
    }

    $allReady = true;
    foreach ($workers as $worker) {
        $allReady = $allReady && waitForFile($barrierDir.DIRECTORY_SEPARATOR.'ready-'.$worker['name']);
    }

    if (! $allReady) {
        $failures[] = $title.' :: workers did not reach the barrier';

        foreach (array_keys($processes) as $name) {
            dumpLogs($barrierDir, $name);
        }
    }

    // Release both workers at the same instant.
    file_put_contents($barrierDir.DIRECTORY_SEPARATOR.'start', (string) microtime(true));

    if ($withHolder) {
        // Give the workers time to reach the seller row and block on it, then
        // let the holder commit. Their measured duration should therefore be at
        // least about this delay.
        usleep(RELEASE_DELAY_MICROSECONDS);
        file_put_contents($barrierDir.DIRECTORY_SEPARATOR.'holder-release', (string) microtime(true));
    }

    $exitCodes = [];
    foreach ($processes as $name => $process) {
        $exitCodes[$name] = proc_close($process);
    }

    if ($holder !== null) {
        $holderExit = proc_close($holder);

        if ($holderExit !== 0) {
            $failures[] = $title.' :: lock holder failed with exit code '.$holderExit;
            dumpLogs($barrierDir, 'holder');
        }
    }

    $results = [];

    foreach ($workers as $worker) {
        $result = readResult($barrierDir, $worker['name']);
        $results[$worker['name']] = $result;

        if ($result === null) {
            $failures[] = $title.' :: no result from '.$worker['name'];
            dumpLogs($barrierDir, $worker['name']);

            continue;
        }

        echo '  INFO  '.$worker['name'].' => '
            .json_encode($result, JSON_UNESCAPED_SLASHES).PHP_EOL;

        check(
            $title.' :: '.$worker['name'].' exited cleanly',
            ($exitCodes[$worker['name']] ?? 1) === 0,
            'exit='.($exitCodes[$worker['name']] ?? '?')
        );
    }

    foreach ($results as $name => $result) {
        if ($withHolder && $result !== null) {
            check(
                $title.' :: '.$name.' actually waited on the seller row',
                ($result['duration_seconds'] ?? 0) >= MIN_BLOCKED_SECONDS,
                'duration='.($result['duration_seconds'] ?? '?').'s'
            );
        }
    }

    return $results;
}

echo 'Database: '.DB::connection()->getDriverName()
    .' / '.DB::connection()->getDatabaseName().PHP_EOL;
echo 'Server: '.DB::selectOne('select version() as v')->v.PHP_EOL;

echo PHP_EOL.'== Preparing disposable schema =='.PHP_EOL;
Artisan::call('migrate:fresh', ['--force' => true]);
echo Artisan::output();

$plan = SubscriptionPlan::create([
    'name' => 'Concurrency Monthly',
    'duration_days' => 30,
    'price' => 1000,
    'is_active' => true,
]);

// ---------------------------------------------------------------- scenario 1
$sellerOne = makeSeller('one');
$subOneA = makePendingSubscription($sellerOne, $plan, 'CONC-1-A');
$subOneB = makePendingSubscription($sellerOne, $plan, 'CONC-1-B');

$results = runScenario(
    'Two different pending payments for one seller activated at the same time',
    $sellerOne,
    [
        ['name' => 'a', 'action' => 'activate', 'subscription' => $subOneA],
        ['name' => 'b', 'action' => 'activate', 'subscription' => $subOneB],
    ],
    true
);

$activeCount = SellerSubscription::where('seller_id', $sellerOne->id)->where('status', 'active')->count();
$statuses = SellerSubscription::where('seller_id', $sellerOne->id)->orderBy('id')->pluck('status', 'id')->all();

check('Concurrent activation A succeeded', $results['a']['ok'] ?? false, json_encode($results['a'] ?? null));
check('Concurrent activation B succeeded', $results['b']['ok'] ?? false, json_encode($results['b'] ?? null));
check('Exactly one subscription is active afterwards', $activeCount === 1, 'active='.$activeCount);
check('No second active row was left behind', count(array_filter($statuses, fn ($s) => $s === 'active')) === 1, json_encode($statuses));

// ---------------------------------------------------------------- scenario 2
$sellerTwo = makeSeller('two');
$subTwo = makePendingSubscription($sellerTwo, $plan, 'CONC-2-A');

$results = runScenario(
    'Two admins activate the SAME pending payment at the same time',
    $sellerTwo,
    [
        ['name' => 'a', 'action' => 'activate', 'subscription' => $subTwo],
        ['name' => 'b', 'action' => 'activate', 'subscription' => $subTwo],
    ],
    true
);

$successes = count(array_filter($results, fn ($r) => ($r['ok'] ?? false) === true));
$notPending = count(array_filter(
    $results,
    fn ($r) => ($r['ok'] ?? true) === false && ($r['exception'] ?? '') === SubscriptionNotPendingException::class
));
$activeCount = SellerSubscription::where('seller_id', $sellerTwo->id)->where('status', 'active')->count();

check('Exactly one activation succeeds', $successes === 1, 'successes='.$successes);
check('The loser gets SubscriptionNotPendingException (not a disguised DB error)', $notPending === 1, 'not_pending='.$notPending);
check('The payment is active exactly once', $activeCount === 1, 'active='.$activeCount);
check('The reviewed payment is active', $subTwo->fresh()->status === 'active', 'status='.$subTwo->fresh()->status);

// ---------------------------------------------------------------- scenario 3
$sellerThree = makeSeller('three');
$subThree = makePendingSubscription($sellerThree, $plan, 'CONC-3-A');

$results = runScenario(
    'One admin activates while another rejects the same pending payment',
    $sellerThree,
    [
        ['name' => 'act', 'action' => 'activate', 'subscription' => $subThree],
        ['name' => 'rej', 'action' => 'reject', 'subscription' => $subThree],
    ],
    true
);

$successes = count(array_filter($results, fn ($r) => ($r['ok'] ?? false) === true));
$notPending = count(array_filter(
    $results,
    fn ($r) => ($r['ok'] ?? true) === false && ($r['exception'] ?? '') === SubscriptionNotPendingException::class
));
$finalStatus = $subThree->fresh()->status;
$activeCount = SellerSubscription::where('seller_id', $sellerThree->id)->where('status', 'active')->count();

check('Only one of activate/reject wins', $successes === 1, 'successes='.$successes);
check('The loser gets SubscriptionNotPendingException', $notPending === 1, 'not_pending='.$notPending);
check('The payment left pending exactly once', in_array($finalStatus, ['active', 'rejected'], true), 'status='.$finalStatus);
check('Active state matches the winning action', $finalStatus !== 'active' || $activeCount === 1, 'active='.$activeCount);
check('A rejected payment did not activate anything', $finalStatus !== 'rejected' || $activeCount === 0, 'active='.$activeCount);

// ------------------------------------------------------------------- summary
echo PHP_EOL.'== Summary =='.PHP_EOL;
echo 'Checks: '.$checks.'  Failures: '.count($failures).PHP_EOL;

if ($failures !== []) {
    foreach ($failures as $failure) {
        echo ' - '.$failure.PHP_EOL;
    }

    echo PHP_EOL.'RESULT: FAIL'.PHP_EOL;
    exit(1);
}

echo PHP_EOL.'RESULT: PASS (real MySQL/MariaDB row locks, '.HOLD_MS.'ms holder window)'.PHP_EOL;
exit(0);
