<?php
/**
 * Application entrypoint (bootstrap) for the PHP-CRUD-API-Generator.
 *
 * @package   PHP-CRUD-API-Generator
 * @author    BitsHost
 * @copyright 2025 BitsHost
 * @license   MIT License
 * @link      https://bitshost.biz/
 * @created   2025-11-12
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Load optional .env file before configs (non-breaking; env vars override PHP defaults)
\App\Config\Env::load(__DIR__ . '/../.env');

// Add this line if admin React is enabled.
// \App\Cors::sendHeaders();

use App\Database\Database as Database;
use App\Application\Router;
use App\Auth\Authenticator as Authenticator;

// Load configs
$dbConfig = require __DIR__ . '/../config/db.php';
$apiConfig = require __DIR__ . '/../config/api.php';

// Bootstrap
$db = new Database($dbConfig);
$auth = new Authenticator($apiConfig, $db->getPdo());
$router = new Router($db, $auth);

// Dispatch
$router->route($_GET);