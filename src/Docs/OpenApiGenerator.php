<?php
declare(strict_types=1);

namespace App\Docs;

use App\Database\SchemaInspector;

/**
 * OpenApiGenerator (Canonical)
 *
 * Minimal generator to produce a basic OpenAPI 3.0 spec from tables/columns.
 * This avoids depending on the removed legacy class.
 */
class OpenApiGenerator
{
	/**
	 * Generate a minimal OpenAPI spec from schema metadata.
	 *
	 * Contract: Accepts table list and SchemaInspector, returns array spec
	 * suitable for json_encode.
	 */
	/**
	 * @param array<int,string> $tables
	 * @return array<string,mixed>
	 */
	public static function generate(array $tables, SchemaInspector $inspector): array
	{
		$schemas = [];
		foreach ($tables as $table) {
			$columns = $inspector->getColumns($table);
			$properties = [];
			foreach ($columns as $col) {
				$name = $col['Field'] ?? $col['COLUMN_NAME'] ?? null;
				if (!$name) { continue; }
				$type = strtolower((string)($col['Type'] ?? $col['DATA_TYPE'] ?? 'string'));
				$schemaType = 'string';
				if (str_contains($type, 'int')) $schemaType = 'integer';
				elseif (str_contains($type, 'bool') || $type === 'tinyint(1)') $schemaType = 'boolean';
				elseif (str_contains($type, 'float') || str_contains($type, 'double') || str_contains($type, 'decimal')) $schemaType = 'number';
				$properties[$name] = ['type' => $schemaType];
			}
			$schemas[ucfirst($table)] = [
				'type' => 'object',
				'properties' => $properties,
			];
		}

		$paths = [];
		foreach ($tables as $table) {
			$resource = '/' . $table;
			$resourceWithId = '/' . $table . '/{id}';
			$schemaRef = '#/components/schemas/' . ucfirst($table);
			// List
			$paths[$resource]['get'] = [
				'summary' => 'List ' . $table,
				'parameters' => [],
				'responses' => [
					'200' => [
						'description' => 'OK',
						'content' => [
							'application/json' => [
								'schema' => [
									'type' => 'object',
									'properties' => [
										'data' => [
											'type' => 'array',
											'items' => ['$ref' => $schemaRef]
										],
										'meta' => ['type' => 'object']
									]
								]
							]
						]
					]
				]
			];
			// Create
			$paths[$resource]['post'] = [
				'summary' => 'Create ' . $table,
				'requestBody' => [
					'required' => true,
					'content' => [
						'application/json' => [
							'schema' => ['$ref' => $schemaRef]
						]
					]
				],
				'responses' => [
					'201' => [
						'description' => 'Created',
						'content' => [
							'application/json' => [
								'schema' => ['$ref' => $schemaRef]
							]
						]
					]
				]
			];
			// Read
			$paths[$resourceWithId]['get'] = [
				'summary' => 'Read ' . $table,
				'parameters' => [[
					'name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']
				]],
				'responses' => [
					'200' => [
						'description' => 'OK',
						'content' => [
							'application/json' => [
								'schema' => ['$ref' => $schemaRef]
							]
						]
					],
					'404' => ['description' => 'Not Found']
				]
			];
			// Update
			$paths[$resourceWithId]['put'] = [
				'summary' => 'Update ' . $table,
				'parameters' => [[
					'name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']
				]],
				'requestBody' => [
					'required' => true,
					'content' => [
						'application/json' => [
							'schema' => [' $ref' => $schemaRef]
						]
					]
				],
				'responses' => [
					'200' => [
						'description' => 'OK',
						'content' => [
							'application/json' => [
								'schema' => [' $ref' => $schemaRef]
							]
						]
					]
				]
			];
			// Delete
			$paths[$resourceWithId]['delete'] = [
				'summary' => 'Delete ' . $table,
				'parameters' => [[
					'name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']
				]],
				'responses' => [
					'200' => ['description' => 'OK']
				]
			];
		}

		return [
			'openapi' => '3.0.0',
			'info' => [
				'title' => 'Generated API',
				'version' => '1.0.0'
			],
			'paths' => $paths,
			'components' => [
				'schemas' => $schemas
			]
		];
	}
}
