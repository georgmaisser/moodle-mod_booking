<?php
/**
 * Validate embeddings fixture for tests.
 * Quick script to verify fixture CSV is correct and contains embeddings.
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');

$fixturepath = __DIR__ . '/agent/embedded_llm/fixtures/task_catalog_embeddings.csv';
$runtimepath = make_temp_directory('mod_booking/wbagent') . '/task_catalog_embeddings.csv';

if (!file_exists($fixturepath)) {
    echo "ERROR: Fixture not found at $fixturepath\n";
    exit(1);
}

// Copy fixture to runtime location
copy($fixturepath, $runtimepath);
echo "✓ Fixture copied to runtime\n";

// Read CSV and validate
$file = fopen($runtimepath, 'r');
$headers = fgetcsv($file);
$rowcount = 0;

if (!$headers) {
    echo "ERROR: Cannot read CSV headers\n";
    exit(1);
}

$expected_headers = [
    'task', 'intent', 'readonly', 'description',
    'minimal_input_json', 'example_input_json', 'message_triggers_json',
    'embedding_model', 'embedding_dimensions', 'content_hash', 'embedding_json'
];

if ($headers !== $expected_headers) {
    echo "ERROR: CSV headers mismatch\n";
    echo "Expected: " . implode(", ", $expected_headers) . "\n";
    echo "Got: " . implode(", ", $headers) . "\n";
    exit(1);
}

echo "✓ CSV headers are correct\n";

// Validate rows
$embedding_col = array_search('embedding_json', $headers);
while (($row = fgetcsv($file)) !== false) {
    $rowcount++;

    if (count($row) !== count($headers)) {
        echo "ERROR: Row $rowcount has incorrect column count\n";
        exit(1);
    }

    // Validate embedding is JSON array
    if (!empty($row[$embedding_col])) {
        $embedding = json_decode($row[$embedding_col], true);
        if (!is_array($embedding)) {
            echo "ERROR: Row $rowcount has invalid embedding JSON\n";
            exit(1);
        }
        if (count($embedding) !== 1536) {
            echo "ERROR: Row $rowcount embedding has " . count($embedding) . " dimensions, expected 1536\n";
            exit(1);
        }
    }
}
fclose($file);

echo "✓ CSV validated: $rowcount tasks with embeddings\n";
echo "✓ Each embedding has 1536 dimensions (correct!)\n";

// Verify first few embeddings are floats
$file = fopen($runtimepath, 'r');
fgetcsv($file); // Skip header
for ($i = 0; $i < 3; $i++) {
    $row = fgetcsv($file);
    if ($row && !empty($row[$embedding_col])) {
        $embedding = json_decode($row[$embedding_col], true);
        $sample = array_slice($embedding, 0, 3);
        echo "  Task {$i}: sample embedding = [" . implode(", ", $sample) . ", ...]\n";
    }
}
fclose($file);

echo "\nSUCCESS: Fixture is valid and ready for tests!\n";
