<?php
declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    exit(1);
}

require_once __DIR__ . '/../bootstrap.php';

use Core\DB;

// Ensure migrations tracking table exists
DB::execute("CREATE TABLE IF NOT EXISTS migrations (
    filename   VARCHAR(255) PRIMARY KEY,
    applied_at DATETIME NOT NULL
)");

$applied = array_column(DB::query("SELECT filename FROM migrations"), 'filename');
$files   = glob(__DIR__ . '/../migrations/*.sql');
sort($files);

$count = 0;
foreach ($files as $file) {
    $name = basename($file);
    if (in_array($name, $applied, true)) {
        echo "Skipped (already applied): $name\n";
        continue;
    }

    $sql = file_get_contents($file);
    DB::execute($sql);
    DB::execute("INSERT INTO migrations (filename, applied_at) VALUES (?, NOW())", [$name]);
    echo "Applied: $name\n";
    $count++;
}

echo "Done. $count migration(s) applied.\n";
