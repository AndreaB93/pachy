# Scheduler & Batch Jobs

## Crontab Setup

Add one entry to your crontab (shared hosting control panel or `crontab -e`):

```
* * * * * php /home/user/public_html/cli/scheduler.php >> /home/user/storage/logs/scheduler.log 2>&1
```

The scheduler runs every minute and decides which jobs are due.

## Registering Jobs

Edit `cli/scheduler.php`:

```php
$scheduler
    ->daily('07:00', SendDailyReport::class)      // every day at 07:00
    ->everyMinutes(5, ProcessImportQueue::class)   // every 5 minutes
    ->daily('02:00', CleanExpiredSessions::class)  // every day at 02:00
    ->hourly(ArchiveOldLogs::class)                // every hour
    ->weekly('MON', WeeklyDigest::class)           // every Monday midnight
    ->everyMinute(HeartbeatCheck::class);          // every minute
```

## Creating a Job

```php
namespace App\Jobs;

use Core\{Job, DB};

class MyJob extends Job
{
    public function run(): void
    {
        $this->log('Job started.');

        // Your logic here
        $rows = DB::query("SELECT ...");
        foreach ($rows as $row) {
            // process
        }

        $this->log('Job complete.');
    }
}
```

Place jobs in `app/Jobs/` and use namespace `App\Jobs`.

## Chunked Processing

For large datasets that span multiple cron ticks:

```php
$this->processChunked(
    stateKey:   'my_job_offset',
    chunkSize:  500,
    fetchChunk: fn(int $offset, int $limit) => DB::query(
        "SELECT * FROM big_table ORDER BY id ASC LIMIT ? OFFSET ?",
        [$limit, $offset]
    ),
    processRow: function(array $row) {
        // process one row
    },
);
```

State is persisted in the `job_state` table between runs.

## Lock Files

Each job uses a lock file in `storage/locks/` to prevent concurrent execution. If the lock is older than 1 hour, it is removed and the job re-runs.

## Logs

Job logs are written to `storage/logs/jobs.log`. Each line includes a timestamp and class name.
