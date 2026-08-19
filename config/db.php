<?php

$host = getenv('MYSQLHOST') ?: 'localhost';
$port = getenv('MYSQLPORT') ?: 3306;
$username = getenv('MYSQLUSER') ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: '';
$database = getenv('MYSQLDATABASE') ?: 'library_db';

$mysqli = new mysqli(
    $host,
    $username,
    $password,
    $database,
    $port
);

if ($mysqli->connect_error) {
    die("Database Connection Failed: " . $mysqli->connect_error);
}

$mysqli->set_charset("utf8mb4");
?>