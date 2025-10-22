# API Monitoring System

## 📋 Overview

The API Monitoring System provides comprehensive real-time monitoring, alerting, and analytics for your REST API. It tracks request/response metrics, performance, errors, security events, and system health.

## ✨ Features

### Core Capabilities
- **Real-time Monitoring** - Track all API requests and responses
- **Performance Metrics** - Response times, throughput, error rates
- **Security Monitoring** - Authentication failures, rate limit hits
- **Health Checks** - API health status with scoring system
- **Alerting System** - Configurable thresholds with multiple notification channels
- **Metrics Export** - JSON and Prometheus formats
- **Visual Dashboard** - Real-time HTML dashboard
- **System Metrics** - CPU, memory, disk usage tracking

### What Gets Monitored
- ✅ Request count and rates
- ✅ Response times (avg, min, max)
- ✅ Error rates and types
- ✅ HTTP status code distribution
- ✅ Authentication attempts (success/failure)
- ✅ Rate limit violations
- ✅ System resources (memory, CPU, disk)
- ✅ API health score (0-100)

## 🚀 Quick Start

### 1. Enable Monitoring

In `config/api.php`, add:

```php
'monitoring' => [
    'enabled' => true,
    'metrics_dir' => __DIR__ . '/../storage/metrics',
    'alerts_dir' => __DIR__ . '/../storage/alerts',
    'retention_days' => 30,
    
    'thresholds' => [
        'error_rate' => 5.0,        // Alert if error rate > 5%
        'response_time' => 1000,    // Alert if response > 1000ms
        'auth_failures' => 10,      // Alert if > 10 failures/min
    ],
    
    'alert_handlers' => [
        // Add your alert handlers here
    ],
],
```

### 2. Integrate into Router

Follow the instructions in `MONITOR_INTEGRATION_GUIDE.php` to add monitoring to your Router class.

### 3. Access Monitoring

- **Health Check**: `http://your-api/health.php`
- **Dashboard**: `http://your-api/dashboard.html`
- **Prometheus**: `http://your-api/health.php?format=prometheus`

## 📊 Health Check Endpoint

### GET /health.php

Returns the current health status of the API.

**Response (200 OK - Healthy):**
```json
{
  "status": "healthy",
  "health_score": 100,
  "timestamp": "2025-10-21 14:30:45",
  "uptime": "5 days, 12 hours, 30 minutes",
  "statistics": {
    "total_requests": 15420,
    "total_errors": 12,
    "error_rate": 0.08,
    "avg_response_time": 45.2,
    "min_response_time": 12.5,
    "max_response_time": 350.8,
    "auth_failures": 3,
    "rate_limit_hits": 1,
    "status_code_distribution": {
      "200": 14850,
      "201": 420,
      "400": 50,
      "401": 80,
      "404": 15,
      "500": 5
    }
  },
  "system_metrics": {
    "memory_usage": 45678976,
    "memory_peak": 52428800,
    "memory_limit": "512M",
    "disk_free": 152197468160,
    "disk_total": 244198420480,
    "disk_usage_percent": 37.67,
    "cpu_load": {
      "1min": 0.5,
      "5min": 0.6,
      "15min": 0.7
    }
  },
  "issues": [],
  "recent_alerts": []
}
```

**Response (503 Service Unavailable - Critical):**
```json
{
  "status": "critical",
  "health_score": 35,
  "issues": [
    "High error rate: 15.5%",
    "Slow response time: 1850ms",
    "3 critical alert(s) in last 5 minutes"
  ]
}
```

### Health Status Levels

| Status | Health Score | HTTP Code | Description |
|--------|-------------|-----------|-------------|
| **healthy** | 80-100 | 200 | All systems operational |
| **degraded** | 50-79 | 200 | Minor issues, still functional |
| **critical** | 0-49 | 503 | Significant issues, may be unavailable |

## 🎯 Health Score Calculation

The health score starts at 100 and deducts points for:

- **High error rate** (>5%) → -30 points
- **Slow responses** (>1000ms) → -20 points
- **Recent critical alerts** → -25 points

## 📈 Metrics Collection

### Request Metrics

```php
$monitor->recordRequest([
    'method' => 'GET',
    'action' => 'list',
    'table' => 'users',
    'ip' => '192.168.1.100',
    'user' => 'john',
]);
```

### Response Metrics

```php
$monitor->recordResponse(
    200,      // HTTP status code
    45.5,     // Response time (ms)
    1024      // Response size (bytes)
);
```

### Error Metrics

```php
$monitor->recordError('Database connection failed', [
    'host' => 'localhost',
    'database' => 'my_db',
]);
```

### Security Events

```php
// Authentication failure
$monitor->recordSecurityEvent('auth_failure', [
    'method' => 'basic',
    'ip' => '192.168.1.100',
    'reason' => 'Invalid credentials',
]);

// Rate limit hit
$monitor->recordSecurityEvent('rate_limit_hit', [
    'identifier' => 'user:123',
    'requests' => 100,
    'limit' => 100,
]);
```

## 🔔 Alert System

### Configurable Thresholds

```php
'thresholds' => [
    'error_rate' => 5.0,        // %
    'response_time' => 1000,    // milliseconds
    'rate_limit' => 90,         // % of limit
    'auth_failures' => 10,      // per minute
],
```

### Alert Levels

- **INFO** - Informational messages
- **WARNING** - Potential issues (slow response, rate limit hit)
- **CRITICAL** - Serious issues (errors, auth failures)

### Alert Handlers

Configure custom alert handlers to send notifications:

```php
'alert_handlers' => [
    // Log to error log
    function($alert) {
        error_log("[{$alert['level']}] {$alert['message']}");
    },
    
    // Send email for critical alerts
    function($alert) {
        if ($alert['level'] === 'critical') {
            mail('admin@example.com', 'API Alert', $alert['message']);
        }
    },
    
    // Send to Slack
    'slackHandler',
    
    // Send to Discord
    'discordHandler',
],
```

See `examples/alert_handlers.php` for complete implementations of:
- Email
- Slack
- Discord
- Telegram
- PagerDuty
- Custom file logging

## 📊 Dashboard

Open `dashboard.html` in your browser for a real-time monitoring dashboard.

**Features:**
- Real-time health status
- Request/response metrics
- Performance graphs
- Security event tracking
- System metrics
- Active issues
- Recent alerts
- Status code distribution
- Auto-refresh every 30 seconds

**Screenshot:**
```
┌─────────────────────────────────────────────────┐
│           API Monitoring Dashboard              │
├─────────────────────────────────────────────────┤
│  Health: ●  HEALTHY  |  Score: 95/100          │
│  Uptime: 5 days, 12 hours                      │
├─────────────────────────────────────────────────┤
│  📊 Requests: 15,420  │  ⚡ Avg Time: 45ms     │
│  ❌ Errors: 12 (0.08%)│  🔒 Auth Fails: 3      │
└─────────────────────────────────────────────────┘
```

## 📈 Prometheus Integration

Export metrics in Prometheus format for scraping:

### GET /health.php?format=prometheus

**Response:**
```
# HELP api_health_score API health score (0-100)
# TYPE api_health_score gauge
api_health_score 95

# HELP api_requests_total Total number of API requests
# TYPE api_requests_total counter
api_requests_total 15420

# HELP api_errors_total Total number of errors
# TYPE api_errors_total counter
api_errors_total 12

# HELP api_error_rate Error rate percentage
# TYPE api_error_rate gauge
api_error_rate 0.08

# HELP api_response_time_ms Response time in milliseconds
# TYPE api_response_time_ms gauge
api_response_time_ms{type="avg"} 45.2
api_response_time_ms{type="min"} 12.5
api_response_time_ms{type="max"} 350.8
```

### Prometheus Configuration

In `prometheus.yml`:

```yaml
scrape_configs:
  - job_name: 'api-monitor'
    scrape_interval: 30s
    static_configs:
      - targets: ['your-api:80']
    metrics_path: '/health.php'
    params:
      format: ['prometheus']
```

## 📊 Statistics API

### Get Statistics

```php
$stats = $monitor->getStats(60); // Last 60 minutes

// Returns:
[
    'total_requests' => 1500,
    'total_errors' => 5,
    'error_rate' => 0.33,
    'avg_response_time' => 52.5,
    'min_response_time' => 10.2,
    'max_response_time' => 450.8,
    'auth_failures' => 3,
    'rate_limit_hits' => 2,
    'status_code_distribution' => [
        200 => 1450,
        201 => 35,
        400 => 5,
        401 => 3,
        500 => 2,
    ],
    'time_window' => 60,
]
```

### Get Recent Alerts

```php
$alerts = $monitor->getRecentAlerts(60); // Last 60 minutes

// Returns:
[
    [
        'level' => 'critical',
        'message' => 'Database connection failed',
        'context' => ['host' => 'localhost'],
        'timestamp' => 1729540845.123,
        'datetime' => '2025-10-21 14:30:45',
    ],
    // ...
]
```

### Export Metrics

```php
// JSON format
$json = $monitor->exportMetrics('json');

// Prometheus format
$prometheus = $monitor->exportMetrics('prometheus');
```

## 🛠️ Configuration

### Full Configuration Example

```php
'monitoring' => [
    // Enable/disable monitoring
    'enabled' => true,
    
    // Storage directories
    'metrics_dir' => __DIR__ . '/../storage/metrics',
    'alerts_dir' => __DIR__ . '/../storage/alerts',
    
    // Retention policy
    'retention_days' => 30,  // Keep metrics for 30 days
    
    // Health check interval
    'check_interval' => 60,  // Check every 60 seconds
    
    // Alert thresholds
    'thresholds' => [
        'error_rate' => 5.0,        // Alert if error rate > 5%
        'response_time' => 1000,    // Alert if response > 1000ms
        'rate_limit' => 90,         // Alert if > 90% of limit used
        'auth_failures' => 10,      // Alert if > 10 failures per minute
    ],
    
    // Alert handlers (callables)
    'alert_handlers' => [
        'errorLogHandler',    // Log to PHP error log
        'emailHandler',       // Send emails
        'slackHandler',       // Send to Slack
    ],
    
    // System metrics collection
    'collect_system_metrics' => true,
],
```

### Environment-Specific Configuration

**Development:**
```php
'monitoring' => [
    'enabled' => true,
    'log_level' => 'debug',
    'thresholds' => [
        'error_rate' => 20.0,  // More lenient
    ],
],
```

**Production:**
```php
'monitoring' => [
    'enabled' => true,
    'log_level' => 'warning',
    'thresholds' => [
        'error_rate' => 1.0,   // Strict
        'response_time' => 500,
    ],
    'alert_handlers' => [
        'pagerDutyHandler',    // Critical alerts
        'slackHandler',
    ],
],
```

## 🔧 Integration Examples

### Basic Integration

```php
use App\Monitor;

$monitor = new Monitor($config['monitoring']);

// Record request
$monitor->recordRequest([
    'method' => $_SERVER['REQUEST_METHOD'],
    'action' => $action,
    'table' => $table,
    'ip' => $_SERVER['REMOTE_ADDR'],
    'user' => $currentUser,
]);

// Record response
$executionTime = (microtime(true) - $startTime) * 1000;
$monitor->recordResponse($statusCode, $executionTime, $responseSize);
```

### Error Handling

```php
try {
    // API logic
} catch (\Exception $e) {
    $monitor->recordError($e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);
    throw $e;
}
```

### Security Events

```php
// Failed authentication
if (!$authenticated) {
    $monitor->recordSecurityEvent('auth_failure', [
        'method' => 'basic',
        'user' => $username,
        'ip' => $_SERVER['REMOTE_ADDR'],
        'reason' => 'Invalid credentials',
    ]);
}

// Rate limit exceeded
if ($rateLimitExceeded) {
    $monitor->recordSecurityEvent('rate_limit_hit', [
        'identifier' => $identifier,
        'requests' => $requestCount,
        'limit' => $limit,
    ]);
}
```

## 📁 File Structure

```
storage/
├── metrics/
│   ├── metrics_2025-10-21.log
│   ├── metrics_2025-10-20.log
│   └── .gitignore
└── alerts/
    ├── alerts_2025-10-21.log
    ├── alerts_2025-10-20.log
    └── .gitignore
```

### Log Format

**Metrics** (JSON Lines):
```json
{"type":"request","timestamp":1729540845.123,"datetime":"2025-10-21 14:30:45","data":{"method":"GET","action":"list","table":"users","ip":"192.168.1.100","user":"john"}}
{"type":"response","timestamp":1729540845.168,"datetime":"2025-10-21 14:30:45","data":{"status_code":200,"response_time":45.5,"response_size":1024,"is_error":false,"is_server_error":false}}
```

**Alerts** (JSON Lines):
```json
{"level":"critical","message":"High error rate detected","context":{"error_rate":8.5,"threshold":5.0},"timestamp":1729540845.123,"datetime":"2025-10-21 14:30:45"}
```

## 🧹 Maintenance

### Cleanup Old Files

```php
$deleted = $monitor->cleanup();
echo "Deleted $deleted old files";
```

### Cron Job

Add to crontab for automatic cleanup:

```cron
# Clean up monitoring files daily at 3 AM
0 3 * * * cd /path/to/api && php -r "require 'vendor/autoload.php'; (new App\Monitor(require 'config/api.php'))->cleanup();"
```

## 🔍 Troubleshooting

### Issue: No metrics being recorded

**Check:**
1. Is `monitoring.enabled` set to `true`?
2. Do storage directories exist with write permissions?
3. Is Monitor properly initialized?

### Issue: Alerts not firing

**Check:**
1. Are thresholds configured correctly?
2. Are alert handlers registered?
3. Check alert log files for errors

### Issue: Dashboard not loading

**Check:**
1. Is `health.php` accessible?
2. Check browser console for JavaScript errors
3. Verify API endpoint URLs in dashboard.html

### Issue: High disk usage

**Solution:**
1. Reduce `retention_days` in config
2. Run cleanup more frequently
3. Set up log rotation

## 🎯 Best Practices

### 1. Set Appropriate Thresholds

- **Development**: Lenient thresholds for testing
- **Staging**: Moderate thresholds
- **Production**: Strict thresholds

### 2. Use Multiple Alert Channels

- **INFO**: Log only
- **WARNING**: Log + Slack
- **CRITICAL**: Log + Slack + Email + PagerDuty

### 3. Monitor the Monitor

Set up external monitoring for the health endpoint itself.

### 4. Regular Reviews

- Review alerts weekly
- Adjust thresholds based on patterns
- Archive old metrics

### 5. Performance

- Keep retention period reasonable (30-90 days)
- Run cleanup regularly
- Consider external log aggregation for high traffic

## 📚 Additional Resources

- **Examples**: `examples/monitoring_demo.php`
- **Alert Handlers**: `examples/alert_handlers.php`
- **Integration Guide**: `MONITOR_INTEGRATION_GUIDE.php`
- **Dashboard**: `dashboard.html`
- **Health Endpoint**: `health.php`

## 🤝 Support

For issues, questions, or contributions, please refer to the main project documentation.

---

**Version:** 1.0.0  
**Last Updated:** October 21, 2025
