<?php
declare(strict_types=1);

namespace App\Jobs;

use Core\{Job, DB};

class CleanExpiredSessions extends Job
{
    public function run(): void
    {
        $this->log('Cleaning expired sessions.');

        // If using DB-backed sessions, delete entries older than 24 hours
        $affected = DB::execute(
            "DELETE FROM sessions WHERE last_activity < ?",
            [time() - 86400]
        );

        $this->log("Removed $affected expired session(s).");
    }
}
