<?php

// Database Configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "library_db";

// Create connection
$mysqli = new mysqli($host, $username, $password, $database);

// Check connection
if ($mysqli->connect_errno) {
    die("Database Connection Failed: " . $mysqli->connect_error);
}

// Set charset (important for production)
$mysqli->set_charset("utf8mb4");

?>