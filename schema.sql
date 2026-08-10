-- NIMA DEV SMM - Database Schema
-- Import this file in phpMyAdmin (InfinityFree/000webhost control panel) before using the site.

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    is_admin TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_service_id INT DEFAULT NULL, -- the "service" ID from your API provider's service list
    name VARCHAR(150) NOT NULL,
    category VARCHAR(100) NOT NULL,
    rate_per_1000 DECIMAL(10,2) NOT NULL,
    min_order INT NOT NULL DEFAULT 100,
    max_order INT NOT NULL DEFAULT 10000,
    description VARCHAR(255) DEFAULT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    service_id INT NOT NULL,
    api_order_id VARCHAR(50) DEFAULT NULL, -- order ID returned by the API provider
    link VARCHAR(500) NOT NULL,
    quantity INT NOT NULL,
    charge DECIMAL(10,2) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample services so the order form has something to show.
-- IMPORTANT: set provider_service_id and rate_per_1000 to match the real
-- service IDs/rates from your API provider's service list (see api-services.php).
INSERT INTO services (provider_service_id, name, category, rate_per_1000, min_order, max_order, description) VALUES
(1, 'Instagram Followers', 'Instagram', 1.50, 100, 10000, 'High quality followers'),
(2, 'Instagram Likes', 'Instagram', 0.80, 50, 5000, 'Fast delivery likes'),
(3, 'TikTok Followers', 'TikTok', 1.20, 100, 10000, 'Real looking followers'),
(4, 'YouTube Views', 'YouTube', 2.00, 500, 50000, 'Retention views'),
(5, 'Facebook Page Likes', 'Facebook', 1.00, 100, 10000, 'Page likes');

CREATE TABLE IF NOT EXISTS fund_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    bank_reference VARCHAR(150) DEFAULT NULL, -- e.g. transaction/slip reference number the user typed in
    receipt_path VARCHAR(255) DEFAULT NULL,   -- uploaded receipt image/pdf, stored under uploads/receipts/
    status VARCHAR(20) NOT NULL DEFAULT 'pending', -- pending, approved, rejected
    admin_note VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Make yourself an admin after registering a normal account, e.g.:
-- UPDATE users SET is_admin = 1 WHERE username = 'your_username';
