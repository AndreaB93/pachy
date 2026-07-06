<?php
declare(strict_types=1);

namespace App\Jobs;

use Core\{Job, DB};

class ProcessImportQueue extends Job
{
    private const CHUNK = 100;
    private const STATE_KEY = 'process_import_queue_offset';

    public function run(): void
    {
        $this->log('Processing import queue (chunked).');

        $this->processChunked(
            stateKey:   self::STATE_KEY,
            chunkSize:  self::CHUNK,
            fetchChunk: fn(int $offset, int $limit) => DB::query(
                "SELECT * FROM import_queue WHERE processed = 0 ORDER BY id ASC LIMIT ? OFFSET ?",
                [$limit, $offset]
            ),
            processRow: function(array $row) {
                // Process each row; mark as done
                DB::execute(
                    "UPDATE import_queue SET processed = 1, processed_at = NOW() WHERE id = ?",
                    [$row['id']]
                );
                $this->log("Processed import row #{$row['id']}");
            },
        );

        $this->log('Import queue processing complete.');
    }
}
