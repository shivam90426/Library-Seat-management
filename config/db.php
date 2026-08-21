<?php

/*
|--------------------------------------------------------------------------
| Database Configuration
|--------------------------------------------------------------------------
| Railway:
| Uses MYSQLHOST, MYSQLPORT, MYSQLUSER, MYSQLPASSWORD, MYSQLDATABASE
|
| Local XAMPP:
| Falls back to localhost / root / empty password / library_db
|--------------------------------------------------------------------------
*/

$host = getenv('MYSQLHOST');
$port = getenv('MYSQLPORT');
$user = getenv('MYSQLUSER');
$password = getenv('MYSQLPASSWORD');
$database = getenv('MYSQLDATABASE');

/*
|--------------------------------------------------------------------------
| Local XAMPP fallback
|--------------------------------------------------------------------------
*/

if (empty($host)) {
    $host = 'localhost';
}

if (empty($port)) {
    $port = 3306;
}

if (empty($user)) {
    $user = 'root';
}

if ($password === false) {
    $password = '';
}

if (empty($database)) {
    $database = 'library_db';
}

/*
|--------------------------------------------------------------------------
| Database Connection
|--------------------------------------------------------------------------
*/

$mysqli = new mysqli(
    $host,
    $user,
    $password,
    $database,
    (int)$port
);

if ($mysqli->connect_errno) {
    die("Database connection failed: " . $mysqli->connect_error);
}

$mysqli->set_charset("utf8mb4");