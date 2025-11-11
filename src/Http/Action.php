<?php
namespace App\Http;

/**
 * Action constants to avoid magic strings in routing/authorization.
 */
final class Action
{
    public const TABLES = 'tables';
    public const COLUMNS = 'columns';
    public const LIST = 'list';
    public const COUNT = 'count';
    public const READ = 'read';
    public const CREATE = 'create';
    public const UPDATE = 'update';
    public const DELETE = 'delete';
    public const BULK_CREATE = 'bulk_create';
    public const BULK_DELETE = 'bulk_delete';
    public const OPENAPI = 'openapi';
    public const LOGIN = 'login';
}
