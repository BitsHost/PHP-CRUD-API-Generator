<?php
require __DIR__ . '/../src/Support/QueryValidator.php';
use App\Support\QueryValidator as QV;

$cases = [
    ['fn' => 'table', 'in' => 'users_123', 'exp' => true],
    ['fn' => 'table', 'in' => 'users-123', 'exp' => false],
    ['fn' => 'id',    'in' => '42',       'exp' => true],
    ['fn' => 'id',    'in' => -1,         'exp' => false],
    ['fn' => 'page',  'in' => 0,          'exp' => 1],
    ['fn' => 'pageSize','in'=> 1000,      'exp' => 100],
    ['fn' => 'sort',  'in' => 'name:asc,created_at:desc', 'exp' => true],
    ['fn' => 'sort',  'in' => 'name:up',  'exp' => false],
];

$ok = true;
foreach ($cases as $i => $c) {
    $fn = $c['fn'];
    $in = $c['in'];
    $got = QV::$fn($in);
    if ($got !== $c['exp']) {
        fwrite(STDERR, "Case #$i failed: $fn($in) => ".var_export($got,true)." expected ".var_export($c['exp'],true)."\n");
        $ok = false;
    }
}

echo $ok ? "validator: PASS\n" : "validator: FAIL\n";
exit($ok ? 0 : 1);
