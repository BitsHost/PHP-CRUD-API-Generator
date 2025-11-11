<?php
namespace App\Http\Controllers;

use App\SchemaInspector;
use App\OpenApiGenerator;

class DocsController
{
    public function __construct(private SchemaInspector $inspector) {}

    /**
     * GET /openapi
     * @return array{0:mixed,1:int}
     */
    public function openapi(): array
    {
        $result = OpenApiGenerator::generate(
            $this->inspector->getTables(),
            $this->inspector
        );
        return [$result, 200];
    }
}
