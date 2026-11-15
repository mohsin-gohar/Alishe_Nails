<?php

/**
 * Holds a write lock on one seller row for a fixed time.
 *
 * Used by run.php to prove that the review service really blocks on the
 * seller row (and not merely on the selected pending payment row).
 *
 * Arguments: <sellerId> <barrierDir> <holdMilliseconds>
 */
$sellerId = (int) ($argv[1] ?? 0);
$barrierDir = (string) ($argv[2] ?? '');
$holdMs = (int) ($argv[3] ?? 2500);

require __DIR__.'/bootstrap.php';

use Illuminate\Support\Facades\DB;

$locked = false;

try {
    DB::beginTransaction();

    $row = DB::table('sellers')->where('id', $sellerId)->lockForUpdate()->first();

    if (! $row) {
        throw new RuntimeException("Seller {$sellerId} not found.");
    }

    $locked = true;
    file_put_contents($barrierDir.'/holder-locked', (string) microtime(true));

    // Hold the lock until the runner has released both workers into the
    // service call, so the wait they experience is measured from a known
    // starting point. $holdMs is only a safety timeout.
    $deadline = microtime(true) + ($holdMs / 1000) + 30;

    while (! file_exists($barrierDir.'/holder-release')) {
        if (microtime(true) > $deadline) {
            throw new RuntimeException('Runner never released the lock holder.');
        }
        usleep(5000);
    }

    DB::commit();

    file_put_contents($barrierDir.'/holder-done', (string) microtime(true));
    exit(0);
} catch (Throwable $e) {
    if ($locked) {
        DB::rollBack();
    }

    file_put_contents($barrierDir.'/holder-error', get_class($e).': '.$e->getMessage());
    exit(1);
}
