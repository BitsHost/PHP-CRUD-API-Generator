# Changelog

## 1.3.0 - Request Logging and Monitoring

### New Features
- **📝 Request Logging**: Comprehensive request/response logging system
  - Automatic logging of all API requests and responses
  - Multiple log levels (debug, info, warning, error)
  - Sensitive data redaction (passwords, tokens, API keys)
  - Authentication attempt logging
  - Rate limit hit logging
  - Error logging with stack traces
  - Log rotation and cleanup
  - Configurable log retention
  - Statistics and analytics
  - Zero configuration required (works out of the box)

### Improvements
- Enhanced security with comprehensive audit logging
- Better debugging capabilities with detailed request/response logging
- Performance monitoring with execution time tracking
- Security monitoring with authentication and rate limit logging
- Automatic sensitive data redaction in logs
- Added log statistics for monitoring
- Improved Router integration with automatic logging

### Logging Features
- **Request Details**: Method, action, table, IP, user, query params, headers, body
- **Response Details**: Status code, execution time, response size, body (optional)
- **Authentication Logging**: Success/failure with reasons
- **Rate Limit Logging**: Tracks rate limit violations
- **Error Logging**: Comprehensive error details with context
- **Sensitive Data Redaction**: Automatic redaction of passwords, tokens, API keys
- **Log Rotation**: Automatic rotation when file exceeds size limit
- **Cleanup**: Automatic removal of old log files
- **Statistics**: Daily statistics (requests, errors, warnings, etc.)

### Configuration
- Added logging section to api.example.php:
  - `enabled` - Enable/disable logging
  - `log_dir` - Log directory path
  - `log_level` - Minimum log level (debug, info, warning, error)
  - `log_headers` - Log request headers
  - `log_body` - Log request body
  - `log_query_params` - Log query parameters
  - `log_response_body` - Log response body (optional)
  - `max_body_length` - Maximum body length to log
  - `sensitive_keys` - Keys to redact in logs
  - `rotation_size` - Size threshold for log rotation
  - `max_files` - Maximum log files to retain

### Documentation
- Added `docs/REQUEST_LOGGING.md` (coming soon)
- Updated README with logging information
- Added logging demo script
- Added comprehensive test coverage

### Testing
- Added comprehensive RequestLoggerTest with 11 test cases
- Tests cover: request logging, sensitive data redaction, auth logging, rate limit logging, error logging, statistics, rotation, cleanup

### Migration Notes
- ✅ **100% Backward Compatible** - No breaking changes
- Logging is enabled by default but can be disabled in config
- Logs are stored in `/logs` directory (auto-created)
- Recommended: Review log settings for production use

---

## 1.2.0 - Rate Limiting and Production Security

### New Features
- **🔒 Rate Limiting**: Built-in rate limiting to prevent API abuse
  - Configurable request limits (default: 100 requests per 60 seconds)
  - Smart identification (user, API key, or IP address)
  - Standard HTTP headers (X-RateLimit-Limit, X-RateLimit-Remaining, X-RateLimit-Reset)
  - File-based storage (easily extensible to Redis/Memcached)
  - Automatic cleanup of old rate limit data
  - 429 Too Many Requests response with retry information
  - Per-user/IP rate limiting with sliding window algorithm
  - Zero configuration required (works out of the box)

### Improvements
- Enhanced security with rate limiting layer
- Added comprehensive rate limiting documentation
- Added storage directory structure for rate limit data
- Improved Router class with rate limit integration
- Added rate limit headers to all API responses
- Better protection against DoS and brute force attacks

### Documentation
- Added `docs/RATE_LIMITING.md` with comprehensive guide
- Updated README with rate limiting information
- Added client implementation examples (JavaScript, Python, PHP)
- Added benchmarks and performance considerations
- Added troubleshooting guide

### Testing
- Added comprehensive RateLimiterTest with 11 test cases
- Tests cover: basic limiting, request counting, window expiration, headers, cleanup

### Configuration
- Added rate_limit section to api.example.php:
  - `enabled` - Enable/disable rate limiting
  - `max_requests` - Maximum requests per window
  - `window_seconds` - Time window in seconds
  - `storage_dir` - Storage directory for rate limit data

### Migration Notes
- ✅ **100% Backward Compatible** - No breaking changes
- Rate limiting is enabled by default but can be disabled in config
- Existing APIs will continue to work without modification
- Recommended: Review and adjust rate limits for your use case

---

## 1.1.0 - Enhanced Query Capabilities and Bulk Operations

### New Features
- **Advanced Filter Operators**: Support for comparison operators (eq, neq, gt, gte, lt, lte, like, in, notin, null, notnull)
- **Field Selection**: Select specific fields in list queries using the `fields` parameter
- **Count Endpoint**: New `count` action to get record counts with optional filtering (no pagination overhead)
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
- Count records: `/index.php?action=count&table=users&filter=status:eq:active`
- Bulk create: `POST /index.php?action=bulk_create&table=users` with JSON array
- Bulk delete: `POST /index.php?action=bulk_delete&table=users` with `{"ids":[1,2,3]}`

## 1.0.0

- Initial release: automatic CRUD API generator for MySQL/MariaDB.
- Supports API Key, Basic Auth, JWT, and OAuth-ready authentication.
- Includes OpenAPI docs endpoint.
- Fully PSR-4, Composer, and PHPUnit compatible.