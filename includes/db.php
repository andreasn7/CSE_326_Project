<?php
/**
 * CEI326 Web Engineering – database bootstrap.
 *
 * Defines connection constants, opens a single shared PDO handle ($pdo)
 * configured for exceptions and UTF-8, and exposes the API key used by
 * the REST API for authenticated mutations.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'cei326_project');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHAR', 'utf8mb4');

define('API_KEY', 'cei326-demo-admin-key');

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHAR;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    die('A database error occurred. Please try again later.');
}
