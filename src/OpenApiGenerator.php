<?php
namespace App;

class OpenApiGenerator
{
    public static function generate(array $tables, SchemaInspector $inspector): array
    {
        $paths = [];
        foreach ($tables as $table) {
            $paths["/index.php?action=list&table=$table"] = [
                'get' => [
                    'summary' => "List rows in $table",
                    'responses' => [
                        '200' => [
                            'description' => "List of $table",
                            'content' => ['application/json' => []],
                        ]
                    ]
                ]
            ];
            $paths["/index.php?action=read&table=$table&id={id}"] = [
                'get' => [
                    'summary' => "Read row from $table",
                    'parameters' => [
                        [
                            'name' => 'id',
                            'in' => 'query',
                            'required' => true,
                            'schema' => ['type' => 'string']
                        ]
                    ],
                    'responses' => [
                        '200' => [
                            'description' => "Single $table row",
                            'content' => ['application/json' => []],
                        ]
                    ]
                ]
            ];
            $paths["/index.php?action=create&table=$table"] = [
                'post' => [
                    'summary' => "Create row in $table",
                    'requestBody' => [
                        'required' => true,
                        'content' => ['application/x-www-form-urlencoded' => []],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => "Created",
                            'content' => ['application/json' => []],
                        ]
                    ]
                ]
            ];
            $paths["/index.php?action=update&table=$table&id={id}"] = [
                'post' => [
                    'summary' => "Update row in $table",
                    'parameters' => [
                        [
                            'name' => 'id',
                            'in' => 'query',
                            'required' => true,
                            'schema' => ['type' => 'string']
                        ]
                    ],
                    'requestBody' => [
                        'required' => true,
                        'content' => ['application/x-www-form-urlencoded' => []],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => "Updated",
                            'content' => ['application/json' => []],
                        ]
                    ]
                ]
            ];
            $paths["/index.php?action=delete&table=$table&id={id}"] = [
                'post' => [
                    'summary' => "Delete row in $table",
                    'parameters' => [
                        [
                            'name' => 'id',
                            'in' => 'query',
                            'required' => true,
                            'schema' => ['type' => 'string']
                        ]
                    ],
                    'responses' => [
                        '200' => [
                            'description' => "Deleted",
                            'content' => ['application/json' => []],
                        ]
                    ]
                ]
            ];
        }

        return [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'PHP CRUD API Generator',
                'version' => '1.0.0'
            ],
            'paths' => $paths
        ];
    }
}