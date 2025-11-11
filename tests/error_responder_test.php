<?php
// Stub logger & monitor must be first before other namespace blocks
namespace App {
    if (!class_exists('App\\RequestLogger')) {
        class RequestLogger { public function __construct($cfg=[]){} public function logError($m,$ctx){ } }
    }
    if (!class_exists('App\\Monitor')) {
        class Monitor { public function recordError($m,$ctx){ } }
    }
}

namespace {
    require __DIR__ . '/../src/Http/Response.php';
    require __DIR__ . '/../src/Http/ErrorResponder.php';

    use App\Http\ErrorResponder;
    use App\RequestLogger;
    use App\Monitor;

    $responder = new ErrorResponder(new RequestLogger(), new Monitor(), true);
    try {
        throw new \RuntimeException('Boom');
    } catch (\Throwable $e) {
        [$payload,$status] = $responder->fromException($e, ['action'=>'test'], 500);
        if ($status !== 500 || $payload['error'] !== 'Boom') {
            fwrite(STDERR, "unexpected payload/status" . PHP_EOL);
            exit(1);
        }
    }
    echo "error_responder: PASS\n";
}
