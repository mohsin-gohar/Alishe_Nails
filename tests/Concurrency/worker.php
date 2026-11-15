<?php

/**
 * One concurrent reviewer. Waits for the runner's start marker, then calls the
 * real SellerSubscriptionReviewService against a real MySQL/MariaDB row lock.
 *
 * Arguments: <name> <activate|reject> <sellerId> <subscriptionId> <barrierDir>
 */
$name = (string) ($argv[1] ?? 'worker');
$action = (string) ($argv[2] ?? 'activate');
$sellerId = (int) ($argv[3] ?? 0);
$subscriptionId = (int) ($argv[4] ?? 0);
$barrierDir = (string) ($argv[5] ?? '');

require __DIR__.'/bootstrap.php';

use App\Exceptions\SubscriptionNotPendingException;
use App\Models\Seller;
use App\Models\SellerSubscription;
use App\Services\SellerSubscriptionReviewService;

$writeResult = function (array $result) use ($barrierDir, $name): void {
    $result['name'] = $name;
    file_put_contents(
        $barrierDir.'/result-'.$name.'.json',
        json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );
};

$deadline = microtime(true) + 30;
file_put_contents($barrierDir.'/ready-'.$name, (string) microtime(true));

while (! file_exists($barrierDir.'/start')) {
    if (microtime(true) > $deadline) {
        $writeResult(['ok' => false, 'exception' => 'Timeout', 'message' => 'Start marker never appeared.']);
        exit(1);
    }
    usleep(5000);
}

$seller = Seller::findOrFail($sellerId);
$service = app(SellerSubscriptionReviewService::class);

$started = microtime(true);

try {
    $subscription = $action === 'reject'
        ? $service->reject($seller, null, $subscriptionId)
        : $service->activate($seller, null, $subscriptionId);

    $finished = microtime(true);

    $writeResult([
        'ok' => true,
        'action' => $action,
        'target' => $subscriptionId,
        'resulting_status' => $subscription->fresh()->status,
        'active_count' => SellerSubscription::where('seller_id', $sellerId)->where('status', 'active')->count(),
        'started_at' => $started,
        'finished_at' => $finished,
        'duration_seconds' => round($finished - $started, 4),
    ]);

    exit(0);
} catch (SubscriptionNotPendingException $e) {
    $finished = microtime(true);

    $writeResult([
        'ok' => false,
        'action' => $action,
        'target' => $subscriptionId,
        'exception' => SubscriptionNotPendingException::class,
        'message' => $e->getMessage(),
        'active_count' => SellerSubscription::where('seller_id', $sellerId)->where('status', 'active')->count(),
        'started_at' => $started,
        'finished_at' => $finished,
        'duration_seconds' => round($finished - $started, 4),
    ]);

    exit(0);
} catch (Throwable $e) {
    $finished = microtime(true);

    $writeResult([
        'ok' => false,
        'action' => $action,
        'target' => $subscriptionId,
        'exception' => get_class($e),
        'message' => $e->getMessage(),
        'active_count' => null,
        'started_at' => $started,
        'finished_at' => $finished,
        'duration_seconds' => round($finished - $started, 4),
    ]);

    exit(1);
}
