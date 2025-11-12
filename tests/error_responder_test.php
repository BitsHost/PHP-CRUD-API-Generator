<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Http\ErrorResponder;
use App\Observability\RequestLogger;
use App\Observability\Monitor;

$responder = new ErrorResponder(new RequestLogger(), new Monitor(), true);
try {
    throw new \RuntimeException('Boom');
} catch (\Throwable $e) {
    [$payload,$status] = $responder->fromException($e, ['action'=>'test'], 500);
    if ($status !== 500 || ($payload['error'] ?? null) !== 'Boom') {
        fwrite(STDERR, "unexpected payload/status" . PHP_EOL);
        exit(1);
    }
}
echo "error_responder: PASS\n";
