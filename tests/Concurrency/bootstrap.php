<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

/**
 * Minimal Laravel bootstrap shared by the concurrency harness scripts.
 *
 * The harness is intentionally NOT part of the PHPUnit suites: it must run on
 * a database engine that honours SELECT ... FOR UPDATE (MySQL/MariaDB), which
 * SQLite does not. Run it with:
 *
 *   php tests/Concurrency/run.php
 *
 * while DB_CONNECTION=mysql points at a disposable database.
 */
require __DIR__.'/../../vendor/autoload.php';

/** @var Application $app */
$app = require __DIR__.'/../../bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

return $app;
