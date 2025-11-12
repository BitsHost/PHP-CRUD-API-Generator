<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\Dialect\MySqlDialect;
use App\Database\SchemaInspector;
use App\Database\Database;

echo "Dialect MySQL Test\n";

// Basic quoteIdent
$dialect = new MySqlDialect();
$quoted = $dialect->quoteIdent('users');
if ($quoted !== '`users`') {
    echo "✗ quoteIdent failed: $quoted\n";
    exit(1);
}

echo "✓ quoteIdent passed\n";

// Optional: if DB configured, ensure inspector delegates
try {
    $dbConfig = require __DIR__ . '/../config/db.php';
    $db = new Database($dbConfig);
    $inspector = new SchemaInspector($db->getPdo(), $dialect);
    $tables = $inspector->getTables();
    echo "✓ inspector.getTables returned array (" . count($tables) . ")\n";
} catch (Throwable $e) {
    echo "(info) Skipping DB-based dialect test: " . $e->getMessage() . "\n";
}

echo "✓ Dialect tests completed\n";
