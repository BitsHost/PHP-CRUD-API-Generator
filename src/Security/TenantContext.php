<?php
/**
 * Resolved tenant identity for the current request.
 *
 * @package   PHP-CRUD-API-Generator
 * @author    BitsHost
 * @copyright 2026 BitsHost
 * @license   MIT License
 */
declare(strict_types=1);

namespace App\Security;

/**
 * Immutable tenant id for request scope. Null context means multitenancy is off
 * or not applicable — ApiGenerator then behaves as in v2.1.
 */
final class TenantContext
{
    public function __construct(private string $id)
    {
        $id = trim($id);
        if ($id === '') {
            throw new \InvalidArgumentException('Tenant id cannot be empty');
        }
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }
}
