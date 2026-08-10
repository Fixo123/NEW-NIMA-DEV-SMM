<?php
// NIMA DEV SMM - Database configuration
//
// On Heroku: these are read automatically from the JAWSDB_URL config var
// (set when you add the JawsDB MySQL add-on) — you don't need to edit
// anything below for Heroku.
//
// On InfinityFree / 000webhost: JAWSDB_URL won't exist, so it falls back
// to the manual values below — fill those in with what your host gives you.

$jawsdbUrl = getenv('JAWSDB_URL');

if ($jawsdbUrl) {
    $dbParts = parse_url($jawsdbUrl);
    define('DB_HOST', $dbParts['host']);
    define('DB_PORT', $dbParts['port'] ?? 3306);
    define('DB_NAME', ltrim($dbParts['path'], '/'));
    define('DB_USER', $dbParts['user']);
    define('DB_PASS', $dbParts['pass']);
} else {
    // --- Manual fallback (InfinityFree / 000webhost) ---
    define('DB_HOST', 'https://nima-smm-a3cef4bd11c3.herokuapp.com/');      // usually 'localhost' or something like 'sqlXXX.infinityfree.com'
    define('DB_PORT', 3306);
    define('DB_NAME', 'your_db_name');
    define('DB_USER', 'your_db_user');
    define('DB_PASS', 'your_db_password');
}

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
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
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
