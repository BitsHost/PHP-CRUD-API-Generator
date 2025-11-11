<?php
namespace App;

/**
 * Actions constants centralize string literals to reduce typos and enable
 * future refactors (enum / attribute mapping) with minimal diff.
 */
final class Actions
{
    public const LOGIN = 'login';
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
}
