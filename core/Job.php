<?php
declare(strict_types=1);

namespace Core;

abstract class Job
{
    abstract public function run(): void;

    /** Chunked processing with resumable state via job_state table */
    protected function processChunked(
        string   $stateKey,
        int      $chunkSize,
        callable $fetchChunk,
        callable $processRow,
    ): void {
        $offset = (int)(DB::value(
            "SELECT value FROM job_state WHERE job_key = ?",
            [$stateKey]
        ) ?? 0);

        do {
            $rows = $fetchChunk($offset, $chunkSize);
            foreach ($rows as $row) {
                $processRow($row);
            }
            $offset += count($rows);

            DB::execute(
                "INSERT INTO job_state (job_key, value, updated_at) VALUES (?, ?, NOW())
                 ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = NOW()",
                [$stateKey, $offset]
            );

            gc_collect_cycles();
        } while (count($rows) === $chunkSize);

        // Clear state on completion
        DB::execute("DELETE FROM job_state WHERE job_key = ?", [$stateKey]);
    }

    protected function log(string $message): void
    {
        $line = '[' . date('Y-m-d H:i:s') . '] ' . static::class . ': ' . $message . PHP_EOL;
        file_put_contents(dirname(__DIR__) . '/storage/logs/jobs.log', $line, FILE_APPEND | LOCK_EX);
    }
}
