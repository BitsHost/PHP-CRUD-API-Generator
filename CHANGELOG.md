# Changelog

## 1.1.0 - Enhanced Query Capabilities and Bulk Operations

### New Features
- **Advanced Filter Operators**: Support for comparison operators (eq, neq, gt, gte, lt, lte, like, in, notin, null, notnull)
- **Field Selection**: Select specific fields in list queries using the `fields` parameter
- **Bulk Operations**: 
  - `bulk_create` - Create multiple records in a single transaction
  - `bulk_delete` - Delete multiple records by IDs in a single query
- **Input Validation**: Added comprehensive input validation for table names, column names, IDs, and query parameters
- **Response Helper**: Added Response class for standardized API responses (for future use)
- **Backward Compatibility**: Old filter format (`col:value`) still works alongside new format (`col:op:value`)

### Improvements
- Fixed SQL injection vulnerability in filter parameter by using parameterized queries with unique parameter names
- Added Validator class for centralized input validation and sanitization
- Improved error messages with proper HTTP status codes
- Enhanced documentation with detailed examples of new features
- Transaction support for bulk create operations

### Filter Operators
- `eq` - Equals
- `neq`/`ne` - Not equals
- `gt` - Greater than
- `gte`/`ge` - Greater than or equal
- `lt` - Less than
- `lte`/`le` - Less than or equal
- `like` - Pattern matching
- `in` - In list (pipe-separated values)
- `notin`/`nin` - Not in list
- `null` - Is NULL
- `notnull` - Is NOT NULL

### Examples
- Field selection: `/index.php?action=list&table=users&fields=id,name,email`
- Advanced filtering: `/index.php?action=list&table=users&filter=age:gt:18,status:eq:active`
- IN operator: `/index.php?action=list&table=orders&filter=status:in:pending|processing|shipped`
- Bulk create: `POST /index.php?action=bulk_create&table=users` with JSON array
- Bulk delete: `POST /index.php?action=bulk_delete&table=users` with `{"ids":[1,2,3]}`

## 1.0.0

- Initial release: automatic CRUD API generator for MySQL/MariaDB.
- Supports API Key, Basic Auth, JWT, and OAuth-ready authentication.
- Includes OpenAPI docs endpoint.
- Fully PSR-4, Composer, and PHPUnit compatible.