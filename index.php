<?php
/**
 * Application entrypoint (bootstrap) for the PHP-CRUD-API-Generator.
 *
 * Works as:
 * - Standalone: public/index.php (package root = parent dir)
 * - Library: copy this file to your project root next to vendor/
 *
 * @package   PHP-CRUD-API-Generator
 * @author    BitsHost
 * @copyright 2025 BitsHost
 * @license   MIT License
 * @link      https://bitshost.biz/
 */

declare(strict_types=1);

// Project root = folder that owns vendor/ (library copy) or package root (standalone public/)
$projectRoot = is_file(__DIR__ . '/vendor/autoload.php')
    ? __DIR__
    : dirname(__DIR__);

$autoload = $projectRoot . '/vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Composer autoload not found. Run composer install.']);
    exit;
}
require_once $autoload;

use App\Config\ConfigPaths;
use App\Config\Env;
use App\Database\Database as Database;
use App\Application\Router;
use App\Auth\Authenticator as Authenticator;

// PHPCRUD_CONFIG_DIR → ./config (cwd/project) → package config/
$configDir = ConfigPaths::resolveDir(
    is_dir($projectRoot . '/config') ? $projectRoot . '/config' : null
);

Env::load($projectRoot . '/.env');

$dbConfig = require ConfigPaths::db($configDir);
$apiConfig = require ConfigPaths::api($configDir);

$db = new Database($dbConfig);
$auth = new Authenticator($apiConfig, $db->getPdo());
$router = new Router($db, $auth, $configDir);

$router->route($_GET);
