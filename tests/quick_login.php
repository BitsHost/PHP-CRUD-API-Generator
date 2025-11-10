<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Database;
use App\Router;
use App\Authenticator;

// Simulate POST request with form data
$_GET = ['action' => 'login'];
$_POST = ['username' => 'admin', 'password' => 'secret'];
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';

$dbConfig = require __DIR__ . '/../config/db.php';
$apiConfig = require __DIR__ . '/../config/api.php';

$db = new Database($dbConfig);
$auth = new Authenticator($apiConfig, $db->getPdo());
$router = new Router($db, $auth);

ob_start();
$router->route($_GET);
$response = ob_get_clean();

echo $response;
