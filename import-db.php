<?php

$host = "127.0.0.1";
$port = "53301";       // use your current Railway tunnel port
$db   = "railway";
$user = "root";
$pass = "jbLTqJDjPIeGCryzVEaalcNTeCMEBMUL";

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => true
        ]
    );

    $sql = file_get_contents(__DIR__ . "/library_db.sql");

    if ($sql === false) {
        die("Could not read library_db.sql");
    }

    echo "Importing database...\n";

    $pdo->exec($sql);

    echo "DATABASE IMPORTED SUCCESSFULLY!\n";

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}