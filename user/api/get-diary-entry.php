<?php
require_once "../../includes/security.php";
library_system_bootstrap();
require_once "../../config/db.php";
require_once "../../includes/diary_helpers.php";

header("Content-Type: application/json");

require_api_login('user');

ensure_diary_entries_table($mysqli);

$user_id = intval($_SESSION['user_id']);
$entry_date = normalized_date_or_today($_GET['date'] ?? null);

$stmt = $mysqli->prepare("
SELECT content, entry_date, updated_at
FROM diary_entries
WHERE user_id=? AND entry_date=?
LIMIT 1
");
$stmt->bind_param("is", $user_id, $entry_date);
$stmt->execute();
$entry = $stmt->get_result()->fetch_assoc();

echo json_encode([
    "date" => $entry_date,
    "content" => $entry['content'] ?? "",
    "updated_at" => $entry['updated_at'] ?? null
]);
?>
