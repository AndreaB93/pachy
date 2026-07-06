<?php
declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    exit(1);
}

require_once __DIR__ . '/../bootstrap.php';

use Core\Scheduler;
use App\Jobs\{SendDailyReport, CleanExpiredSessions, ProcessImportQueue};

$scheduler = new Scheduler();
$scheduler
    ->daily('07:00', SendDailyReport::class)
    ->everyMinutes(5, ProcessImportQueue::class)
    ->daily('02:00', CleanExpiredSessions::class);

$scheduler->run();
