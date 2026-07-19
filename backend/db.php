<?php
// Centralized Database Connection Helper using PDO

$host = 'localhost';
$db   = 'featherflow';
$user = 'root';
$pass = ''; // Default XAMPP/WAMP MySQL password is empty
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // Return JSON error response if this is an API call
     if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false || 
         strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
         http_response_code(500);
         echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
         exit;
     }
     
     // HTML fallback error
     die("Database connection failed. Please ensure MySQL is running and the database is imported. Error: " . $e->getMessage());
}
