# Quick Start - bitshost/php-crud-api-generator

**Get started in 5 minutes!**

---

## Step 1: Install

```bash
composer require bitshost/php-crud-api-generator
```

---

## Step 2: Copy 3 files to project root

```bash
copy vendor\bitshost\php-crud-api-generator\public\index.php index.php
copy vendor\bitshost\php-crud-api-generator\dashboard.html dashboard.html
copy vendor\bitshost\php-crud-api-generator\health.php health.php
```

---

## Step 3: Edit index.php (2 lines)

Change config paths to point to vendor:

```php
// Change this:
$dbConfig = require __DIR__ . '/config/db.php';
$apiConfig = require __DIR__ . '/config/api.php';

// To this:
$dbConfig = require __DIR__ . '/vendor/bitshost/php-crud-api-generator/config/db.php';
$apiConfig = require __DIR__ . '/vendor/bitshost/php-crud-api-generator/config/api.php';
```

---

## Step 4: Configure (in vendor directory)

```bash
notepad vendor\bitshost\php-crud-api-generator\config\db.php
notepad vendor\bitshost\php-crud-api-generator\config\api.php
```

**db.php:**
```php
return [
    'host' => 'localhost',
    'dbname' => 'your_database',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4'
];
```

**api.php - Generate JWT secret:**
```bash
php -r "echo bin2hex(random_bytes(32));"
```

Paste result into api.php:
```php
'jwt_secret' => 'YOUR_64_CHAR_SECRET_HERE',
```

---

## Step 5: Run!

```bash
php -S localhost:8000
```

---

## Test

```bash
# Login
curl -X POST -d "username=admin&password=password123" http://localhost:8000/?action=login

# View dashboard
http://localhost:8000/dashboard.html
```

---

## Summary

**3 files copied:**
- index.php (2 lines modified)
- dashboard.html (0 modifications)
- health.php (0 modifications)

**2 files edited:**
- vendor/.../config/db.php
- vendor/.../config/api.php

**Total code changes: 2 lines!** 🎉

That's it! Your API is ready. All configs stay in vendor directory - clean and simple!
