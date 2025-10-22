-- API Users Table
-- Run this SQL to create the users table

CREATE TABLE IF NOT EXISTS api_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'readonly',
    api_key VARCHAR(64) UNIQUE NOT NULL,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_api_key (api_key),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: API key usage tracking
CREATE TABLE IF NOT EXISTS api_key_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    api_key VARCHAR(64) NOT NULL,
    endpoint VARCHAR(255),
    ip_address VARCHAR(45),
    request_count INT DEFAULT 0,
    last_used TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES api_users(id) ON DELETE CASCADE,
    INDEX idx_api_key (api_key),
    INDEX idx_last_used (last_used),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create first admin user
-- Username: admin
-- Password: changeme123
-- API Key: generated below
INSERT INTO api_users (username, email, password_hash, role, api_key, active)
VALUES (
    'admin',
    'admin@example.com',
    '$argon2id$v=19$m=65536,t=4,p=1$SXdYVUozWnE3RmtTWm9VRA$9xL8Ql0DLDqHHgPL9Bs5GqMwJZz+qLzqVHlV/+5vWtk',
    'admin',
    CONCAT(
        LPAD(HEX(FLOOR(RAND() * 4294967296)), 8, '0'),
        LPAD(HEX(FLOOR(RAND() * 4294967296)), 8, '0'),
        LPAD(HEX(FLOOR(RAND() * 4294967296)), 8, '0'),
        LPAD(HEX(FLOOR(RAND() * 4294967296)), 8, '0')
    ),
    1
);

-- View the created admin user and API key
SELECT 
    id,
    username,
    email,
    role,
    api_key,
    active,
    created_at
FROM api_users
WHERE username = 'admin';

-- Note: Default password is 'changeme123' - CHANGE THIS IMMEDIATELY IN PRODUCTION!
