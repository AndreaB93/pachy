<?php
declare(strict_types=1);

namespace Core;

class Scheduler
{
    private array $jobs = [];

    public function everyMinute(string $jobClass): self
    {
        return $this->register($jobClass, '* * * * *');
    }

    public function everyMinutes(int $n, string $jobClass): self
    {
        return $this->register($jobClass, "*/$n * * * *");
    }

    public function hourly(string $jobClass): self
    {
        return $this->register($jobClass, '0 * * * *');
    }

    public function daily(string $time, string $jobClass): self
    {
        [$h, $m] = explode(':', $time);
        return $this->register($jobClass, "$m $h * * *");
    }

    public function weekly(string $day, string $jobClass): self
    {
        $days = ['SUN' => 0, 'MON' => 1, 'TUE' => 2, 'WED' => 3, 'THU' => 4, 'FRI' => 5, 'SAT' => 6];
        return $this->register($jobClass, "0 0 * * " . $days[$day]);
    }

    private function register(string $jobClass, string $cron): self
    {
        $this->jobs[] = ['class' => $jobClass, 'cron' => $cron];
        return $this;
    }

    public function run(): void
    {
        $now = new \DateTime();
        foreach ($this->jobs as $job) {
            if ($this->isDue($job['cron'], $now)) {
                $this->execute($job['class']);
            }
        }
    }

    private function isDue(string $cron, \DateTime $now): bool
    {
        [$min, $hour, $dom, $month, $dow] = explode(' ', $cron);
        return $this->matches($min,   (int)$now->format('i'))
            && $this->matches($hour,  (int)$now->format('G'))
            && $this->matches($dom,   (int)$now->format('j'))
            && $this->matches($month, (int)$now->format('n'))
            && $this->matches($dow,   (int)$now->format('w'));
    }

    private function matches(string $expr, int $value): bool
    {
        if ($expr === '*') return true;
        if (is_numeric($expr)) return (int)$expr === $value;
        if (str_starts_with($expr, '*/')) return $value % (int)substr($expr, 2) === 0;
        return false;
    }

    private function execute(string $jobClass): void
    {
        $lockFile = dirname(__DIR__) . '/storage/locks/' . md5($jobClass) . '.lock';

        if (file_exists($lockFile) && (time() - filemtime($lockFile) < 3600)) {
            return; // still running from previous invocation
        }

        file_put_contents($lockFile, (string)getmypid());
        try {
            (new $jobClass)->run();
        } finally {
            @unlink($lockFile);
        }
    }
}
