<?php
/**
 * NexaWork - One-time Indian localization database updater
 * Visit once: http://localhost/PHP/freelancehub/database/run_indian_update.php
 * Delete this file after running in production.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: text/plain; charset=utf-8');

$sqlFile = __DIR__ . '/update_indian_full.sql';
if (!file_exists($sqlFile)) {
    exit("SQL file not found.\n");
}

$sql = file_get_contents($sqlFile);
// Remove USE statement and split on semicolons
$sql = preg_replace('/^USE freelancehub;\s*/i', '', $sql);
$statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));

$pdo = db();
$ok = 0;
$fail = 0;

foreach ($statements as $statement) {
    if ($statement === '' || str_starts_with($statement, '--')) {
        continue;
    }
    // Skip comment-only blocks
    $lines = array_filter(array_map('trim', explode("\n", $statement)), fn($l) => $l !== '' && !str_starts_with($l, '--'));
    if (empty($lines)) {
        continue;
    }
    try {
        $pdo->exec($statement);
        $ok++;
    } catch (PDOException $e) {
        $fail++;
        echo "WARN: " . substr($statement, 0, 80) . "...\n";
        echo "      " . $e->getMessage() . "\n\n";
    }
}

echo "NexaWork Indian localization complete.\n";
echo "Executed: {$ok} statements, warnings: {$fail}\n\n";
echo "Freelancers should now show Indian names, cities, and ₹ rates.\n";
echo "Refresh your homepage to see the changes.\n";
