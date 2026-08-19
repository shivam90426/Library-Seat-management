<?php

// Railway MySQL configuration
$host = getenv('MYSQLHOST') ?: 'localhost';
$port = getenv('MYSQLPORT') ?: '3306';
$username = getenv('MYSQLUSER') ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: 'jbLTqJDjPIeGCryzVEaalcNTeCMEBMUL';
$database = getenv('MYSQLDATABASE') ?: 'library_db';

// Create connection
$mysqli = new mysqli(
    $host,
    $username,
    $password,
    $database,
    $port
);

// Check connection
if ($mysqli->connect_error) {
    die("Database Connection Failed: " . $mysqli->connect_error);
}

// Set charset
$mysqli->set_charset("utf8mb4");

?>