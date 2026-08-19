<?php

function ensure_diary_entries_table(mysqli $mysqli): void
{
    static $initialized = false;

    if ($initialized) {
        return;
    }

    $sql = "
    CREATE TABLE IF NOT EXISTS diary_entries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        entry_date DATE NOT NULL,
        content TEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_user_entry_date (user_id, entry_date),
        INDEX idx_diary_user_date (user_id, entry_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    $mysqli->query($sql);
    $initialized = true;
}
?>
