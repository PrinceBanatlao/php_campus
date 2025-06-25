<?php
$host = 'localhost';
$dbname = 'campusfinder';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); 
} catch (PDOException $e) {
    
    error_log("Database connection failed: " . $e->getMessage(), 3, __DIR__ . '/error.log');
    
    http_response_code(500);
    die("Unable to connect to the database. Please try again later.");
}
?>