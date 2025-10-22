<?php
/**
 * Security Secrets Generator
 * 
 * Generates all security secrets needed for production deployment:
 * - JWT secret (for token signing)
 * - API keys (for API key authentication)
 * - Database encryption key (optional)
 * 
 * Usage:
 *   php scripts/generate_secrets.php
 * 
 * @package PHP-CRUD-API-Generator
 * @version 1.0.0
 */

echo "\n";
echo "╔════════════════════════════════════════╗\n";
echo "║   SECURITY SECRETS GENERATOR          ║\n";
echo "║   PHP-CRUD-API-Generator v1.4.0       ║\n";
echo "╚════════════════════════════════════════╝\n";
echo "\n";

echo "Generating cryptographically secure secrets...\n";
echo "\n";

// Generate secrets
$jwtSecret = bin2hex(random_bytes(32));      // 64-char hex (256-bit)
$apiKey1 = bin2hex(random_bytes(32));        // 64-char hex
$apiKey2 = bin2hex(random_bytes(32));        // 64-char hex
$encryptionKey = bin2hex(random_bytes(32));  // For future use

echo "========================================\n";
echo "  GENERATED SECRETS\n";
echo "========================================\n";
echo "\n";

// JWT Secret
echo "1️⃣  JWT SECRET (for token signing):\n";
echo "\n";
echo "   " . $jwtSecret . "\n";
echo "\n";
echo "   Update in config/api.php:\n";
echo "   'jwt_secret' => '" . $jwtSecret . "',\n";
echo "\n";

// API Keys
echo "========================================\n";
echo "\n";
echo "2️⃣  API KEYS (for API key authentication):\n";
echo "\n";
echo "   Key #1: " . $apiKey1 . "\n";
echo "   Key #2: " . $apiKey2 . "\n";
echo "\n";
echo "   Update in config/api.php:\n";
echo "   'api_keys' => [\n";
echo "       '" . $apiKey1 . "',\n";
echo "       '" . $apiKey2 . "',\n";
echo "   ],\n";
echo "\n";

// Database Encryption Key (optional)
echo "========================================\n";
echo "\n";
echo "3️⃣  DATABASE ENCRYPTION KEY (optional):\n";
echo "\n";
echo "   " . $encryptionKey . "\n";
echo "\n";
echo "   Use for encrypting sensitive data in database\n";
echo "\n";

// Environment Variables Format
echo "========================================\n";
echo "  FOR .env FILE\n";
echo "========================================\n";
echo "\n";
echo "JWT_SECRET=" . $jwtSecret . "\n";
echo "API_KEY_1=" . $apiKey1 . "\n";
echo "API_KEY_2=" . $apiKey2 . "\n";
echo "ENCRYPTION_KEY=" . $encryptionKey . "\n";
echo "\n";

// Security warnings
echo "========================================\n";
echo "  ⚠️  SECURITY WARNINGS\n";
echo "========================================\n";
echo "\n";
echo "✓ Keep these secrets PRIVATE and SECURE\n";
echo "✓ Never commit secrets to Git\n";
echo "✓ Use different secrets for dev/staging/production\n";
echo "✓ Store in environment variables or secure vault\n";
echo "✓ Rotate secrets regularly (every 90 days)\n";
echo "✓ Changing JWT secret invalidates all tokens\n";
echo "\n";

// Save option
echo "========================================\n";
echo "\n";
echo "💾 Save secrets to file? (y/n): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));

if (strtolower($line) === 'y') {
    $timestamp = date('Y-m-d_His');
    $filename = 'secrets_' . $timestamp . '.txt';
    
    $content = "# Generated Security Secrets\n";
    $content .= "# Date: " . date('Y-m-d H:i:s') . "\n";
    $content .= "# ⚠️  DELETE THIS FILE AFTER COPYING SECRETS!\n";
    $content .= "\n";
    $content .= "========================================\n";
    $content .= "JWT SECRET:\n";
    $content .= "========================================\n";
    $content .= $jwtSecret . "\n";
    $content .= "\n";
    $content .= "========================================\n";
    $content .= "API KEYS:\n";
    $content .= "========================================\n";
    $content .= "Key #1: " . $apiKey1 . "\n";
    $content .= "Key #2: " . $apiKey2 . "\n";
    $content .= "\n";
    $content .= "========================================\n";
    $content .= "ENCRYPTION KEY:\n";
    $content .= "========================================\n";
    $content .= $encryptionKey . "\n";
    $content .= "\n";
    $content .= "========================================\n";
    $content .= ".env FORMAT:\n";
    $content .= "========================================\n";
    $content .= "JWT_SECRET=" . $jwtSecret . "\n";
    $content .= "API_KEY_1=" . $apiKey1 . "\n";
    $content .= "API_KEY_2=" . $apiKey2 . "\n";
    $content .= "ENCRYPTION_KEY=" . $encryptionKey . "\n";
    $content .= "\n";
    $content .= "========================================\n";
    $content .= "config/api.php FORMAT:\n";
    $content .= "========================================\n";
    $content .= "'jwt_secret' => '" . $jwtSecret . "',\n";
    $content .= "'api_keys' => ['" . $apiKey1 . "', '" . $apiKey2 . "'],\n";
    $content .= "\n";
    
    file_put_contents($filename, $content);
    
    echo "\n";
    echo "✅ Secrets saved to: " . $filename . "\n";
    echo "\n";
    echo "⚠️  IMPORTANT:\n";
    echo "   1. Copy secrets to your config/api.php or .env\n";
    echo "   2. DELETE THIS FILE: " . $filename . "\n";
    echo "   3. Never commit this file to Git!\n";
    echo "\n";
    
    // Add to .gitignore automatically
    $gitignorePath = __DIR__ . '/../.gitignore';
    if (file_exists($gitignorePath)) {
        $gitignoreContent = file_get_contents($gitignorePath);
        if (strpos($gitignoreContent, 'secrets_*.txt') === false) {
            file_put_contents($gitignorePath, "\n# Generated secrets files\nsecrets_*.txt\n", FILE_APPEND);
            echo "✅ Added 'secrets_*.txt' to .gitignore\n";
        }
    }
} else {
    echo "\n";
    echo "⚠️  Make sure to copy the secrets above before closing!\n";
}

echo "\n";
echo "========================================\n";
echo "  📚 NEXT STEPS\n";
echo "========================================\n";
echo "\n";
echo "1. Update config/api.php with new secrets\n";
echo "2. Or create .env file with environment variables\n";
echo "3. Test authentication with new secrets\n";
echo "4. Deploy to production\n";
echo "\n";
echo "📖 Documentation:\n";
echo "   - docs/AUTHENTICATION.md\n";
echo "   - docs/AUTH_QUICK_REFERENCE.md\n";
echo "\n";

echo "Done! 🎉\n";
echo "\n";
