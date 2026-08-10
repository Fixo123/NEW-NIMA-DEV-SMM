<?php
// NIMA DEV SMM - Database configuration
// Fill these in with the values your hosting provider (InfinityFree / 000webhost) gives you.

define('DB_HOST', 'localhost');      // usually 'localhost' or something like 'sqlXXX.infinityfree.com'
define('DB_NAME', 'your_db_name');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');

// --- SMM API provider settings ---
// Get these from your provider's dashboard (Add Funds -> API, or "API" menu item).
define('SMM_API_URL', 'https://amazingsmm.com/api/v2');
define('SMM_API_KEY', 'your_api_key_here');

// --- Bank account details shown to users on the funds top-up page ---
define('BANK_NAME', 'Your Bank Name');
define('BANK_ACCOUNT_NAME', 'Your Full Name');
define('BANK_ACCOUNT_NUMBER', '0000000000');
define('BANK_BRANCH', 'Your Branch');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

session_start();
