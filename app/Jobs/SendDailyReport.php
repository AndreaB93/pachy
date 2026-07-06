<?php
declare(strict_types=1);

namespace App\Jobs;

use Core\{Job, DB};
use App\Schema\UserSchema as S;

class SendDailyReport extends Job
{
    public function run(): void
    {
        $this->log('Starting daily report dispatch.');

        $users = DB::query(
            "SELECT " . S::ID . ", " . S::EMAIL . ", " . S::NAME . " FROM " . S::TABLE .
            " WHERE " . S::ACTIVE . " = 1",
            []
        );

        foreach ($users as $user) {
            // In a real project, inject a MailService here
            $this->log("Report dispatched to {$user[S::EMAIL]}");
        }

        $this->log('Daily report dispatch complete.');
    }
}
