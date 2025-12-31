# Security Policy

## 🔒 Reporting Security Vulnerabilities

If you discover a security vulnerability in PHP CRUD API Generator, please report it by emailing **security@bitshost.com** or opening a private security advisory on GitHub.

**Please do NOT open public issues for security vulnerabilities.**

We will respond within 48 hours and work with you to address the issue promptly.

---

## ⚠️ CRITICAL: Dashboard Security

### Default Installation is NOT Production-Ready

The admin dashboard (`dashboard.html`) and health endpoint (`health.php`) expose sensitive information including:

- API request statistics and error rates
- Authentication failure attempts
- Rate limiting data (shows blocked attacks)
- System metrics (memory, CPU, disk usage)
- Performance data

**If left unprotected, attackers can:**
- Monitor their attacks in real-time
- Identify system weaknesses
- Plan more effective attacks
- Map your infrastructure

### Required Actions Before Production

**🛡️ YOU MUST protect these files before deploying to production!**

**Quick Fix (5 minutes):**

1. Copy the example `.htaccess`:
   ```bash
   cp .htaccess.example .htaccess
   ```

2. Edit `.htaccess` and replace `YOUR.IP.ADDRESS.HERE` with your actual IP address

3. Test that dashboard is blocked from other IPs

**Complete Security Guide:**

📖 **[Full Dashboard Security Documentation →](docs/DASHBOARD_SECURITY.md)**

This guide includes:
- IP whitelisting (Apache & Nginx)
- HTTP Basic Authentication
- Separate admin subdomain setup
- VPN-only access
- Combined security layers
- Testing and verification

---

## 🔐 Security Best Practices

### 1. Authentication

**Enable authentication in production:**

```php
// config/api.php
'authentication' => [
    'enabled' => true,           // ALWAYS true in production
    'type' => 'api_key',        // or 'basic', 'jwt'
    'apiKeys' => [
        'strong-random-key-here',  // Generate secure keys
    ]
]
```

**Generate secure API keys:**
```bash
# Linux/Mac
openssl rand -base64 32

# Windows PowerShell
[Convert]::ToBase64String((1..32 | ForEach-Object { Get-Random -Maximum 256 }))
```

### 2. Rate Limiting

**Enable rate limiting to prevent abuse:**

```php
// config/api.php
'rateLimiting' => [
    'enabled' => true,
    'maxRequests' => 100,        // Requests per time window
    'timeWindow' => 60,          // Seconds
]
```

### 3. Request Logging

**Enable logging for security monitoring:**

```php
// config/api.php
'logging' => [
    'enabled' => true,
    'logRequests' => true,       // Log all requests
    'logErrors' => true,         // Log errors
    'logAuth' => true,           // Log auth attempts
]
```

### 4. Database Security

**Use least-privilege database user:**

```sql
-- Create API-only user with limited permissions
CREATE USER 'api_user'@'localhost' IDENTIFIED BY 'strong-password';

-- Grant only necessary permissions
GRANT SELECT, INSERT, UPDATE, DELETE ON your_database.* TO 'api_user'@'localhost';

-- Do NOT grant:
-- DROP, CREATE, ALTER, INDEX, GRANT, SUPER, FILE, etc.
```

### 5. HTTPS Only

**Always use HTTPS in production:**

```apache
# Force HTTPS redirect in .htaccess
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]
```

### 6. Input Validation

**Built-in protection is enabled by default:**

```php
// config/api.php
'validation' => [
    'enabled' => true,           // Always keep enabled
    'sanitizeInput' => true,     // Prevent XSS
    'validateTypes' => true,     // Type checking
]
```

### 7. CORS Configuration

**Restrict CORS in production:**

```php
// config/api.php
'cors' => [
    'enabled' => true,
    'allowOrigin' => 'https://yourdomain.com',  // NOT '*' in production!
    'allowMethods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'allowHeaders' => ['Content-Type', 'X-API-Key'],
]
```

### 8. Error Messages

**Hide detailed errors in production:**

```php
// config/api.php
'debug' => false,  // NEVER true in production
```

With `debug = false`:
- Generic error messages to clients
- Detailed errors only in logs
- No stack traces exposed

---

## 🎯 Pre-Production Checklist

Before deploying to production, verify:

- [ ] Dashboard and health endpoint are protected (IP whitelist or Basic Auth)
- [ ] Sensitive folders (`config`, `src`, `storage`, `logs`, `vendor`, `private-vault`, `sql`, `tests`) are not web-accessible (web root points to `public/` **or** per-folder `.htaccess` uses `Require all denied`)
- [ ] Authentication is enabled (`authentication.enabled = true`)
- [ ] Strong API keys generated (not defaults or examples)
- [ ] Secrets (DB credentials, JWT secret, API keys, Basic passwords) are not committed in Git and are configured via environment variables / `.env` or secure config files
- [ ] Rate limiting is enabled
- [ ] Request logging is enabled
- [ ] Debug mode is disabled (`debug = false`)
- [ ] HTTPS is configured and enforced
- [ ] Database user has minimal permissions
- [ ] CORS is properly configured (not `*`)
- [ ] Error messages don't leak sensitive info
- [ ] `.htaccess` or nginx config is in place
- [ ] Backup and monitoring are configured
- [ ] Security logs are being monitored

---

## 🚨 Emergency Response

If you suspect a security breach:

### Immediate Actions

1. **Block all access immediately:**
   ```apache
   # Emergency .htaccess
   Order Deny,Allow
   Deny from all
   Allow from YOUR.SAFE.IP.ONLY
   ```

2. **Check logs for suspicious activity:**
   ```bash
   grep "401\|403\|429\|500" /var/log/apache2/access.log
   tail -1000 logs/api.log | grep "ERROR\|CRITICAL"
   ```

3. **Rotate API keys:**
   ```php
   // config/api.php - generate new keys
   'apiKeys' => [
       'new-secure-key-here',  // Old keys will stop working
   ]
   ```

4. **Review recent database changes**
5. **Contact security@bitshost.com**

---

## 📚 Security Resources

- **[Dashboard Security Guide](docs/DASHBOARD_SECURITY.md)** - Protect admin files
- **[Rate Limiting Docs](docs/RATE_LIMITING.md)** - Prevent API abuse
- **[Request Logging Docs](docs/REQUEST_LOGGING.md)** - Monitor and audit
- **[Comparison with PHP-CRUD-API v2](docs/COMPARISON.md)** - Security differences

---

## 🔄 Security Updates

We take security seriously. Subscribe to security updates:

- Watch this repository for security advisories
- Follow releases for security patches
- Check CHANGELOG.md for security fixes

**Current Version:** 1.0.0  
**Last Security Audit:** 2025-11-10

---

## 📞 Contact

**Security Issues:** security@bitshost.com  
**General Support:** GitHub Issues  
**Documentation:** [docs/](docs/)

---

**Remember: Security is a process, not a product. Stay vigilant!** 🛡️
