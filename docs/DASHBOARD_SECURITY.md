# Dashboard & Health Endpoint Security Guide

**⚠️ CRITICAL SECURITY WARNING**

The admin dashboard (`dashboard.html`) and health endpoint (`health.php`) expose sensitive information about your API and **MUST NOT** be publicly accessible when running a production API.

---

## 🔴 What Information is Exposed?

If left unprotected, these files reveal:

### Dashboard (`dashboard.html`)
- ✅ Total API requests processed
- ✅ Error rates and patterns
- ✅ Authentication failure attempts (shows attackers if their attacks are working!)
- ✅ Rate limiting hits (reveals abuse attempts)
- ✅ Average response times (performance data)
- ✅ System metrics (memory usage, CPU load, disk space)
- ✅ HTTP status code distribution
- ✅ Recent alerts and active issues

### Health Endpoint (`health.php`)
- ✅ Database connection status
- ✅ API health score
- ✅ System uptime
- ✅ Detailed statistics (same as dashboard, but JSON format)
- ✅ Prometheus metrics (for monitoring tools)

### Why This is Dangerous

**Attackers can use this information to:**
1. See if their attacks are working (auth failures, rate limits)
2. Identify system weaknesses (high error rates, slow response times)
3. Plan better attacks (know when system is under load)
4. Map your infrastructure (system metrics reveal server capacity)
5. Monitor their impact in real-time (dashboard auto-refreshes)

**Example Attack Scenario:**
```
Attacker visits: https://api.example.com/dashboard.html

Dashboard shows:
- Auth Failures: 450 (their brute force attack!)
- Rate Limit Hits: 890 (they know they need to slow down)
- Avg Response Time: 450ms (system is under load, good time to attack)

Attacker now knows:
✓ Their attack is working
✓ They're being rate limited (need to use more IPs)
✓ System is struggling (double down on attack)
```

---

## 🛡️ Security Solutions

### Solution 1: IP Whitelist (Recommended for Most Users)

**Best for:** Small teams, single office location, personal projects

#### Apache (.htaccess)

Create or edit `.htaccess` in your project root:

```apache
# .htaccess

# Protect Admin Dashboard
<Files "dashboard.html">
    Order Deny,Allow
    Deny from all
    
    # Allow from your office/home IP
    Allow from 203.0.113.42
    
    # Allow from IP range (e.g., office network)
    Allow from 198.51.100.0/24
    
    # Allow from localhost (development)
    Allow from 127.0.0.1
    Allow from ::1
</Files>

# Protect Health Endpoint
<Files "health.php">
    Order Deny,Allow
    Deny from all
    
    # Allow from monitoring server
    Allow from 203.0.113.42
    
    # Allow from load balancer
    Allow from 198.51.100.10
    
    # Allow from localhost
    Allow from 127.0.0.1
    Allow from ::1
</Files>
```

#### Nginx

Add to your Nginx configuration:

```nginx
server {
    listen 80;
    server_name api.example.com;
    root /var/www/api;
    
    # Public API endpoint - accessible to all
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # Protect dashboard
    location = /dashboard.html {
        # Allow only specific IPs
        allow 203.0.113.42;      # Office IP
        allow 198.51.100.0/24;   # Office network
        allow 127.0.0.1;         # Localhost
        deny all;
    }
    
    # Protect health endpoint
    location = /health.php {
        # Allow monitoring servers
        allow 203.0.113.42;      # Monitoring server
        allow 198.51.100.10;     # Load balancer
        allow 127.0.0.1;         # Localhost
        deny all;
        
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        include fastcgi_params;
    }
}
```

**Testing:**
```bash
# From allowed IP - should work
curl https://api.example.com/dashboard.html

# From other IP - should get 403 Forbidden
curl https://api.example.com/dashboard.html
# Response: 403 Forbidden
```

**Find Your IP Address:**
```bash
# Visit this URL in your browser:
https://whatismyipaddress.com/

# Or use command line:
curl https://api.ipify.org
```

---

### Solution 2: HTTP Basic Authentication

**Best for:** Teams with remote workers, multiple locations, VPN users

#### Step 1: Create Password File

```bash
# Create password file (run on your server)
htpasswd -c /etc/apache2/.htpasswd admin

# You'll be prompted to enter password
New password: ********
Re-type new password: ********

# Add more users (without -c flag)
htpasswd /etc/apache2/.htpasswd developer1
htpasswd /etc/apache2/.htpasswd manager
```

#### Step 2: Configure Apache

```apache
# .htaccess

<Files "dashboard.html">
    AuthType Basic
    AuthName "Admin Dashboard - Authorized Personnel Only"
    AuthUserFile /etc/apache2/.htpasswd
    Require valid-user
</Files>

<Files "health.php">
    AuthType Basic
    AuthName "Health Monitoring"
    AuthUserFile /etc/apache2/.htpasswd
    Require valid-user
</Files>
```

#### Step 3: Configure Nginx

```nginx
location = /dashboard.html {
    auth_basic "Admin Dashboard";
    auth_basic_user_file /etc/nginx/.htpasswd;
}

location = /health.php {
    auth_basic "Health Monitoring";
    auth_basic_user_file /etc/nginx/.htpasswd;
    
    fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    include fastcgi_params;
}
```

**Accessing the Dashboard:**
- Browser will prompt for username and password
- Enter credentials created with `htpasswd`
- Credentials are stored in browser session

**For Monitoring Tools (Prometheus, etc.):**
```yaml
# prometheus.yml
scrape_configs:
  - job_name: 'php-crud-api'
    metrics_path: '/health.php?format=prometheus'
    basic_auth:
      username: 'monitoring'
      password: 'secret-monitoring-password'
    static_configs:
      - targets: ['api.example.com']
```

---

### Solution 3: Separate Admin Subdomain (Best Practice)

**Best for:** Production SaaS, enterprise applications, high-security requirements

#### Directory Structure

```
/var/www/
├── api/                    # Public API
│   ├── public/
│   │   └── index.php      # Public endpoint (world accessible)
│   ├── config/
│   └── vendor/
│
└── admin/                  # Admin panel (restricted)
    ├── dashboard.html     # Protected dashboard
    └── health.php         # Protected health check
```

#### Apache Virtual Hosts

```apache
# Public API - Open to the world
<VirtualHost *:443>
    ServerName api.example.com
    DocumentRoot /var/www/api/public
    
    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/api.crt
    SSLCertificateKeyFile /etc/ssl/private/api.key
    
    <Directory /var/www/api/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # Block direct access to admin files if they exist here
    <FilesMatch "^(dashboard\.html|health\.php)$">
        Require all denied
    </FilesMatch>
</VirtualHost>

# Admin Panel - Restricted access
<VirtualHost *:443>
    ServerName admin-api.example.com
    DocumentRoot /var/www/admin
    
    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/admin-api.crt
    SSLCertificateKeyFile /etc/ssl/private/admin-api.key
    
    <Directory /var/www/admin>
        Options -Indexes
        AllowOverride All
        
        # IP Whitelist
        Require ip 203.0.113.42        # Office
        Require ip 198.51.100.0/24     # VPN range
        
        # AND Basic Auth (double protection)
        AuthType Basic
        AuthName "Admin Area"
        AuthUserFile /etc/apache2/.htpasswd
        Require valid-user
    </Directory>
</VirtualHost>
```

#### Nginx Configuration

```nginx
# Public API
server {
    listen 443 ssl http2;
    server_name api.example.com;
    root /var/www/api/public;
    
    ssl_certificate /etc/ssl/certs/api.crt;
    ssl_certificate_key /etc/ssl/private/api.key;
    
    location / {
        try_files $uri /index.php?$query_string;
    }
    
    # Block admin files
    location ~ ^/(dashboard\.html|health\.php)$ {
        deny all;
    }
}

# Admin Panel
server {
    listen 443 ssl http2;
    server_name admin-api.example.com;
    root /var/www/admin;
    
    ssl_certificate /etc/ssl/certs/admin-api.crt;
    ssl_certificate_key /etc/ssl/private/admin-api.key;
    
    # IP Whitelist
    allow 203.0.113.42;        # Office
    allow 198.51.100.0/24;     # VPN
    deny all;
    
    # Additional Basic Auth
    auth_basic "Admin Dashboard";
    auth_basic_user_file /etc/nginx/.htpasswd;
    
    location / {
        try_files $uri $uri/ =404;
    }
}
```

**DNS Configuration:**
```
api.example.com          A    203.0.113.100  (Public API server)
admin-api.example.com    A    203.0.113.100  (Same server, different subdomain)
```

**Access:**
- Public API: `https://api.example.com/` (world accessible)
- Admin Dashboard: `https://admin-api.example.com/dashboard.html` (restricted)
- Health Check: `https://admin-api.example.com/health.php` (restricted)

---

### Solution 4: VPN-Only Access

**Best for:** Maximum security, internal tools, enterprise environments

#### Setup

1. **Configure VPN** (OpenVPN, WireGuard, etc.)
2. **Dashboard only accessible via VPN IP range**

```apache
# .htaccess
<Files "dashboard.html">
    Order Deny,Allow
    Deny from all
    # Only allow VPN IP range
    Allow from 10.8.0.0/24
</Files>

<Files "health.php">
    Order Deny,Allow
    Deny from all
    Allow from 10.8.0.0/24
</Files>
```

**Workflow:**
1. Team member connects to VPN
2. Gets VPN IP (e.g., 10.8.0.50)
3. Can now access `https://api.example.com/dashboard.html`
4. Disconnect VPN → Access denied

---

### Solution 5: Move to Private Directory

**Best for:** Quick security, simple deployments

#### Structure

```
project-root/
├── public/              # Web-accessible (document root)
│   └── index.php       # Public API only
├── admin/              # NOT web-accessible
│   ├── dashboard.html
│   └── health.php
├── config/
└── vendor/
```

#### Apache Configuration

```apache
<VirtualHost *:80>
    ServerName api.example.com
    # Document root points to public/ only
    DocumentRoot /var/www/project-root/public
    
    <Directory /var/www/project-root/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    # Admin files are outside document root - not accessible via web
</VirtualHost>
```

**Access Admin Files:**
- SSH into server
- Run: `php admin/health.php` (command line)
- Or create secure proxy script

---

## 🔒 Combined Security Layers (Maximum Protection)

For **production SaaS APIs**, use multiple layers:

```apache
# admin-api.example.com virtual host

<Directory /var/www/admin>
    # Layer 1: IP Whitelist
    Require ip 203.0.113.42
    Require ip 198.51.100.0/24
    
    # Layer 2: Basic Authentication
    AuthType Basic
    AuthName "Admin Dashboard"
    AuthUserFile /etc/apache2/.htpasswd
    Require valid-user
    
    # Layer 3: SSL Certificate (client cert optional)
    SSLVerifyClient require
    SSLVerifyDepth 1
    SSLCACertificateFile /etc/ssl/certs/ca.crt
</Directory>
```

**To access, you need:**
1. ✅ Allowed IP address (office/VPN)
2. ✅ Valid username/password
3. ✅ Valid SSL client certificate (optional)

Attacker would need to compromise **all three** layers!

---

## 🎯 Quick Setup Guide by Use Case

### Personal Project / Development
**Solution:** IP Whitelist (5 minutes)
```apache
<Files "dashboard.html">
    Order Deny,Allow
    Deny from all
    Allow from 127.0.0.1
    Allow from YOUR_HOME_IP
</Files>
```

### Small Team / Startup
**Solution:** Basic Auth (10 minutes)
- Create `.htpasswd` with team credentials
- Everyone can access from anywhere
- Simple to manage

### Production SaaS
**Solution:** Separate Subdomain + IP Whitelist + Basic Auth (30 minutes)
- Maximum security
- Professional setup
- Easy to audit

### Enterprise
**Solution:** VPN-Only + Client Certificates
- Maximum security
- Compliance-ready
- Full audit trail

---

## ✅ Verification Checklist

After implementing security, test:

### Test 1: Public Access (Should Fail)
```bash
# From random IP/computer
curl https://api.example.com/dashboard.html
# Expected: 403 Forbidden or 401 Unauthorized

curl https://api.example.com/health.php
# Expected: 403 Forbidden or 401 Unauthorized
```

### Test 2: Public API (Should Work)
```bash
# Public API should still work
curl https://api.example.com/?action=list&table=users
# Expected: API response (200 OK)
```

### Test 3: Authorized Access (Should Work)
```bash
# From allowed IP or with credentials
curl -u admin:password https://api.example.com/dashboard.html
# Expected: Dashboard HTML

curl -u admin:password https://api.example.com/health.php
# Expected: Health JSON
```

### Test 4: Monitoring Tools
```bash
# Prometheus should still be able to scrape
curl -u monitoring:secret https://api.example.com/health.php?format=prometheus
# Expected: Prometheus metrics
```

---

## 📊 Monitoring & Logging

### Log Failed Access Attempts

Add to Apache config:
```apache
<Files "dashboard.html">
    # ... security config ...
    
    # Log unauthorized attempts
    CustomLog /var/log/apache2/admin-access.log combined
    ErrorLog /var/log/apache2/admin-error.log
</Files>
```

### Monitor Failed Attempts

```bash
# Check failed access attempts
tail -f /var/log/apache2/admin-error.log | grep "dashboard.html"

# Count attempts per IP
awk '{print $1}' /var/log/apache2/admin-error.log | sort | uniq -c | sort -nr
```

### Alert on Suspicious Activity

```bash
# Create monitoring script
#!/bin/bash
# /usr/local/bin/monitor-admin-access.sh

THRESHOLD=10
COUNT=$(grep "dashboard.html" /var/log/apache2/admin-error.log | \
        grep "$(date +%Y-%m-%d)" | wc -l)

if [ $COUNT -gt $THRESHOLD ]; then
    echo "WARNING: $COUNT failed dashboard access attempts today!" | \
    mail -s "Security Alert: Admin Dashboard" admin@example.com
fi
```

---

## 🔐 Additional Hardening

### Rate Limit Admin Endpoints

```nginx
# Nginx rate limiting
http {
    limit_req_zone $binary_remote_addr zone=admin:10m rate=5r/m;
    
    server {
        location = /dashboard.html {
            limit_req zone=admin burst=2 nodelay;
            # ... other security config ...
        }
    }
}
```

### Disable Directory Listing

```apache
<Directory /var/www/admin>
    Options -Indexes
</Directory>
```

### Add Security Headers

```apache
<Directory /var/www/admin>
    Header always set X-Frame-Options "DENY"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "no-referrer"
</Directory>
```

---

## 📚 Best Practices Summary

✅ **DO:**
- Protect dashboard and health endpoint from public access
- Use IP whitelisting for known locations
- Add HTTP Basic Auth for additional security
- Use separate subdomain for admin (production)
- Monitor failed access attempts
- Use HTTPS/SSL for all admin access
- Keep access logs for auditing
- Test security configuration regularly

❌ **DON'T:**
- Leave dashboard publicly accessible
- Use weak passwords for Basic Auth
- Share admin credentials publicly
- Expose health.php without authentication
- Ignore failed access attempts in logs
- Use HTTP (unencrypted) for admin access

---

## 🆘 Emergency: Already Exposed?

If you've already deployed with public dashboard access:

### Immediate Actions (Next 5 Minutes)

1. **Block access immediately:**
```apache
# Emergency .htaccess
<Files "dashboard.html">
    Require all denied
</Files>
<Files "health.php">
    Require all denied
</Files>
```

2. **Check access logs:**
```bash
grep "dashboard.html" /var/log/apache2/access.log
grep "health.php" /var/log/apache2/access.log
```

3. **Look for suspicious IPs:**
```bash
awk '{print $1}' access.log | sort | uniq -c | sort -nr | head -20
```

4. **Change API keys if exposed**
5. **Implement proper security (choose solution above)**
6. **Monitor for next 24-48 hours**

---

## 📞 Support

If you need help securing your installation:
- GitHub Issues: [Report security concerns](https://github.com/BitsHost/php-crud-api-generator/issues)
- Email: security@example.com
- Documentation: [Full security guide](https://github.com/BitsHost/php-crud-api-generator/docs)

---

**Remember: Security is not optional for production APIs!** 🛡️

The dashboard and health endpoint are powerful admin tools - treat them with the same security level as your database credentials.
