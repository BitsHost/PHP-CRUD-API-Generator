<?php
/**
 * JWT Secret Generator
 * 
 * Generates a cryptographically secure random secret for JWT token signing.
 * Use this to create a unique secret for your production environment.
 * 
 * Usage:
 *   php scripts/generate_jwt_secret.php
 * 
 * @package PHP-CRUD-API-Generator
 * @version 1.0.0
 */

echo "\n";
echo "========================================\n";
echo "   JWT SECRET GENERATOR\n";
echo "========================================\n";
echo "\n";

// Generate 64-character hexadecimal secret (32 bytes = 256 bits)
$secret = bin2hex(random_bytes(32));

echo "✅ Generated secure JWT secret:\n";
echo "\n";
echo "   " . $secret . "\n";
echo "\n";
echo "========================================\n";
echo "\n";

echo "📋 How to use:\n";
echo "\n";
echo "1. Copy the secret above\n";
echo "\n";
echo "2. Open: config/api.php\n";
echo "\n";
echo "3. Replace this line:\n";
echo "   'jwt_secret' => 'YourSuperSecretKeyChangeMe',\n";
echo "\n";
echo "4. With:\n";
echo "   'jwt_secret' => '" . $secret . "',\n";
echo "\n";
echo "========================================\n";
echo "\n";

echo "⚠️  SECURITY NOTES:\n";
echo "\n";
echo "• Keep this secret PRIVATE (never commit to Git)\n";
echo "• Use different secrets for dev/staging/production\n";
echo "• Generate a new secret if compromised\n";
echo "• Changing the secret invalidates all existing tokens\n";
echo "\n";

echo "💡 TIP: For environment variables (.env file):\n";
echo "   JWT_SECRET=" . $secret . "\n";
echo "\n";

// Option: Save to file
echo "📁 Save to file? (y/n): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));

if (strtolower($line) === 'y') {
    $filename = 'jwt_secret_' . date('Y-m-d_His') . '.txt';
    file_put_contents($filename, $secret);
    echo "✅ Saved to: " . $filename . "\n";
    echo "⚠️  Remember to delete this file after updating your config!\n";
    echo "\n";
}

echo "Done! 🎉\n";
echo "\n";
