<?php

$host = "127.0.0.1";
$port = 53301;
$user = "root";
$password = "jbLTqJDjPIeGCryzVEaalcNTeCMEBMUL";
$database = "railway";

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );

    echo "CONNECTED SUCCESSFULLY\n";

    $result = $pdo->query("SELECT VERSION()");
    echo "MySQL version: " . $result->fetchColumn() . "\n";

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}