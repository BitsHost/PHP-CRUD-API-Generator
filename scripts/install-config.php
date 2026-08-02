<?php
/**
 * Copy example configs into a target project config/ directory.
 *
 * Usage:
 *   php scripts/install-config.php
 *   php scripts/install-config.php /path/to/project
 */
declare(strict_types=1);

$targetRoot = $argv[1] ?? getcwd();
if (!is_string($targetRoot) || $targetRoot === '' || !is_dir($targetRoot)) {
    fwrite(STDERR, "Target directory not found: {$targetRoot}\n");
    exit(1);
}

$packageRoot = dirname(__DIR__);
$targetConfig = rtrim($targetRoot, "/\\") . DIRECTORY_SEPARATOR . 'config';
if (!is_dir($targetConfig) && !mkdir($targetConfig, 0775, true) && !is_dir($targetConfig)) {
    fwrite(STDERR, "Unable to create {$targetConfig}\n");
    exit(1);
}

$map = [
    'apiexample.php' => 'api.php',
    'dbexample.php' => 'db.php',
    'cache.php' => 'cache.php',
];

$copied = 0;
$skipped = 0;
foreach ($map as $sourceName => $destName) {
    $src = $packageRoot . '/config/' . $sourceName;
    $dest = $targetConfig . DIRECTORY_SEPARATOR . $destName;
    if (!is_file($src)) {
        fwrite(STDERR, "Missing example: {$src}\n");
        continue;
    }
    if (is_file($dest)) {
        echo "skip  {$dest} (already exists)\n";
        $skipped++;
        continue;
    }
    if (!copy($src, $dest)) {
        fwrite(STDERR, "Failed to copy {$src} → {$dest}\n");
        exit(1);
    }
    echo "ok    {$dest}\n";
    $copied++;
}

echo "\nDone. Copied {$copied}, skipped {$skipped}.\n";
echo "Edit config/db.php and config/api.php, then run: php scripts/doctor.php\n";
