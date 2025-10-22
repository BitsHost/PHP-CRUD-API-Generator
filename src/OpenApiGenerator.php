<?php
namespace App;

/**
 * OpenAPI Specification Generator
 * 
 * Generates OpenAPI 3.0 specification (formerly Swagger) for the entire API
 * by introspecting database tables and creating path definitions for all
 * CRUD operations. Enables automatic API documentation, client SDK generation,
 * and interactive API testing tools.
 * 
 * Features:
 * - OpenAPI 3.0 compliant output
 * - Automatic path generation for all tables
 * - CRUD operation documentation (list, read, create, update, delete)
 * - Query parameter definitions
 * - Request body schemas
 * - Response definitions
 * - JSON output format
 * 
 * Generated Operations Per Table:
 * - GET /index.php?action=list&table={table} - List records
 * - GET /index.php?action=read&table={table}&id={id} - Read single record
 * - POST /index.php?action=create&table={table} - Create record
 * - POST /index.php?action=update&table={table}&id={id} - Update record
 * - POST /index.php?action=delete&table={table}&id={id} - Delete record
 * 
 * Integration Tools:
 * - Swagger UI: Interactive API documentation
 * - Postman: Import as collection
 * - OpenAPI Generator: Generate client SDKs
 * - ReDoc: Beautiful API documentation
 * 
 * @package App
 * @author Adrian D
 * @copyright 2025 BitHost
 * @license MIT
 * @version 1.4.0
 * @link https://upmvc.com
 * 
 * @example
 * // Generate OpenAPI spec
 * $inspector = new SchemaInspector($pdo);
 * $tables = $inspector->getTables();
 * $spec = OpenApiGenerator::generate($tables, $inspector);
 * 
 * // Output as JSON
 * header('Content-Type: application/json');
 * echo json_encode($spec, JSON_PRETTY_PRINT);
 * 
 * @example
 * // Use in Swagger UI
 * // Save to openapi.json, then:
 * // https://petstore.swagger.io/?url=https://yourapi.com/openapi.json
 * 
 * @example
 * // Access via route
 * // GET /api.php?action=openapi
 * // Returns complete OpenAPI specification
 * 
 * @example
 * // Sample output structure:
 * {
 *   "openapi": "3.0.0",
 *   "info": {
 *     "title": "PHP CRUD API Generator",
 *     "version": "1.0.0"
 *   },
 *   "paths": {
 *     "/index.php?action=list&table=users": {
 *       "get": {
 *         "summary": "List rows in users",
 *         "responses": {...}
 *       }
 *     }
 *   }
 * }
 */
class OpenApiGenerator
{
    /**
     * Generate OpenAPI 3.0 specification
     * 
     * Creates complete OpenAPI specification document by introspecting all
     * database tables and generating path definitions for CRUD operations.
     * Returns associative array ready for JSON encoding.
     * 
     * @param array $tables List of table names from SchemaInspector::getTables()
     * @param SchemaInspector $inspector SchemaInspector instance for potential 
     *   future column introspection (not currently used but available for enhancement)
     * @return array OpenAPI 3.0 specification as associative array with keys:
     *   - openapi: string Version ("3.0.0")
     *   - info: array API metadata (title, version)
     *   - paths: array Path definitions for all operations
     * 
     * @example
     * // Basic usage
     * $pdo = new PDO(...);
     * $inspector = new SchemaInspector($pdo);
     * $tables = $inspector->getTables();
     * 
     * $spec = OpenApiGenerator::generate($tables, $inspector);
     * 
     * // Save to file
     * file_put_contents('openapi.json', json_encode($spec, JSON_PRETTY_PRINT));
     * 
     * @example
     * // Output directly
     * header('Content-Type: application/json');
     * echo json_encode(
     *     OpenApiGenerator::generate($tables, $inspector),
     *     JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
     * );
     * 
     * @example
     * // Integration with Swagger UI HTML:
     * <!DOCTYPE html>
     * <html>
     * <head>
     *   <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist/swagger-ui.css">
     * </head>
     * <body>
     *   <div id="swagger-ui"></div>
     *   <script src="https://unpkg.com/swagger-ui-dist/swagger-ui-bundle.js"></script>
     *   <script>
     *     SwaggerUIBundle({
     *       url: '/api.php?action=openapi',
     *       dom_id: '#swagger-ui'
     *     })
     *   </script>
     * </body>
     * </html>
     */
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