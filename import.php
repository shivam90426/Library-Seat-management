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
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => true
        ]
    );

    echo "Connected to Railway MySQL successfully!\n";

    $sql = file_get_contents(__DIR__ . "/library_db.sql");

    if ($sql === false) {
        die("Could not read library_db.sql\n");
    }

    $pdo->exec($sql);

    echo "Database imported successfully!\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}