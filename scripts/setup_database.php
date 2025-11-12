<?php
/**
 * Setup Database Tables
 * 
 * Creates api_users and api_key_usage tables + first admin user
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\Database as Database;

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  Setting up API User Management Tables\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    $dbConfig = require __DIR__ . '/../config/db.php';
    $db = new Database($dbConfig);
    $pdo = $db->getPdo();
    
    // Read SQL file
    $sql = file_get_contents(__DIR__ . '/../sql/create_api_users.sql');
    
    // Remove comments and split into individual statements
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql); // Remove /* */ comments
    $sql = preg_replace('/--.*$/m', '', $sql);        // Remove -- comments
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    echo "📋 Executing SQL statements...\n\n";
    
    $successCount = 0;
    foreach ($statements as $index => $statement) {
        if (empty($statement)) continue;
        
        // Detect statement type for better output
        if (stripos($statement, 'CREATE TABLE') !== false) {
            preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches);
            $tableName = $matches[1] ?? 'unknown';
            echo "  ✓ Creating table: $tableName\n";
        } elseif (stripos($statement, 'INSERT INTO') !== false) {
            echo "  ✓ Creating default admin user\n";
        } elseif (stripos($statement, 'SELECT') !== false) {
            echo "  ✓ Retrieving admin credentials...\n";
        }
        
        try {
            $result = $pdo->exec($statement);
            $successCount++;
        } catch (\PDOException $e) {
            // Check if it's a "table already exists" error
            if (strpos($e->getMessage(), 'already exists') !== false) {
                echo "    ⚠️  (Table already exists, skipping)\n";
            } elseif (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo "    ⚠️  (Admin user already exists, skipping)\n";
            } else {
                throw $e;
            }
        }
    }
    
    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ Database setup complete!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    // Close previous statements before querying
    $pdo = null;
    $pdo = (new Database($dbConfig))->getPdo();
    
    // Get admin user details
    $stmt = $pdo->query("SELECT username, email, role, api_key FROM api_users WHERE username = 'admin'");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "🔑 Default Admin User Created:\n\n";
        echo "  Username:  {$admin['username']}\n";
        echo "  Email:     {$admin['email']}\n";
        echo "  Password:  changeme123\n";
        echo "  Role:      {$admin['role']}\n";
        echo "  API Key:   {$admin['api_key']}\n\n";
        echo "⚠️  IMPORTANT: Change the password immediately!\n\n";
        echo "Test it:\n";
        echo "  curl -u admin:changeme123 \\\n";
        echo "       http://localhost/PHP-CRUD-API-Generator/public/index.php?action=tables\n\n";
    }
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📝 Next Steps:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    echo "1. Create new users:\n";
    echo "   php scripts/create_user.php john john@example.com SecurePass123! readonly\n\n";
    echo "2. List all users:\n";
    echo "   php scripts/list_users.php\n\n";
    echo "3. Update config/api.php:\n";
    echo "   Change 'auth_method' to 'apikey' or 'basic'\n\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
