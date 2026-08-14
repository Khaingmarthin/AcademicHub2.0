<?php
// MySQL database connection file
// Example only, credentials should be configured appropriately

$db_host = 'localhost';
$db_name = 'ucsmtla_academic_hub';
$db_user = 'root';
$db_pass = ''; // Leave blank for typical WAMP default, or configure from config.php

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
