<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Add this line if admin React is enabled.
// \App\Cors::sendHeaders();

use App\Database;
use App\Router;
use App\Authenticator;

// Load configs
$dbConfig = require __DIR__ . '/../config/db.php';
$apiConfig = require __DIR__ . '/../config/api.php';

// Bootstrap
$db = new Database($dbConfig);
$auth = new Authenticator($apiConfig);
$router = new Router($db, $auth);

// Dispatch
$router->route($_GET);