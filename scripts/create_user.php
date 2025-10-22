<?php
/**
 * Create API User Script
 * 
 * Quick command-line tool to create new API users.
 * 
 * Usage:
 *   php scripts/create_user.php <username> <email> <password> [role]
 * 
 * Example:
 *   php scripts/create_user.php john john@example.com SecurePass123! readonly
 *   php scripts/create_user.php admin admin@example.com AdminPass456! admin
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database;

// Check arguments
if ($argc < 4) {
    echo "❌ Usage: php create_user.php <username> <email> <password> [role]\n";
    echo "\nExamples:\n";
    echo "  php create_user.php john john@example.com SecurePass123!\n";
    echo "  php create_user.php admin admin@example.com AdminPass456! admin\n";
    echo "\nAvailable roles: readonly, editor, admin\n";
    exit(1);
}

$username = $argv[1];
$email = $argv[2];
$password = $argv[3];
$role = $argv[4] ?? 'readonly';

// Validate
if (strlen($username) < 3) {
    echo "❌ Username must be at least 3 characters\n";
    exit(1);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "❌ Invalid email address\n";
    exit(1);
}

if (strlen($password) < 8) {
    echo "❌ Password must be at least 8 characters\n";
    exit(1);
}

$validRoles = ['readonly', 'editor', 'admin'];
if (!in_array($role, $validRoles)) {
    echo "❌ Invalid role. Must be: readonly, editor, or admin\n";
    exit(1);
}

// Connect to database
try {
    $dbConfig = require __DIR__ . '/../config/db.php';
    $db = new Database($dbConfig);
    $pdo = $db->getPdo();
    
    // Check if user already exists
    $stmt = $pdo->prepare(
        "SELECT id FROM api_users WHERE username = :username OR email = :email"
    );
    $stmt->execute(['username' => $username, 'email' => $email]);
    
    if ($stmt->fetch()) {
        echo "❌ User with this username or email already exists\n";
        exit(1);
    }
    
    // Hash password
    $passwordHash = password_hash($password, PASSWORD_ARGON2ID);
    
    // Generate API key (64 character hex string)
    $apiKey = bin2hex(random_bytes(32));
    
    // Insert user
    $stmt = $pdo->prepare(
        "INSERT INTO api_users (username, email, password_hash, role, api_key, active)
         VALUES (:username, :email, :password_hash, :role, :api_key, 1)"
    );
    
    $stmt->execute([
        'username' => $username,
        'email' => $email,
        'password_hash' => $passwordHash,
        'role' => $role,
        'api_key' => $apiKey
    ]);
    
    $userId = $pdo->lastInsertId();
    
    echo "\n";
    echo "✅ User created successfully!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "User ID:   $userId\n";
    echo "Username:  $username\n";
    echo "Email:     $email\n";
    echo "Role:      $role\n";
    echo "API Key:   $apiKey\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "\n";
    echo "🔑 Authentication Methods:\n\n";
    echo "1️⃣  API Key Header:\n";
    echo "   curl -H \"X-API-Key: $apiKey\" \\\n";
    echo "        http://your-api/api.php?action=tables\n\n";
    echo "2️⃣  API Key Query Parameter:\n";
    echo "   curl \"http://your-api/api.php?action=tables&api_key=$apiKey\"\n\n";
    echo "3️⃣  Basic Authentication:\n";
    echo "   curl -u $username:$password \\\n";
    echo "        http://your-api/api.php?action=tables\n\n";
    echo "⚠️  IMPORTANT: Save the API key - it cannot be retrieved later!\n";
    echo "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
