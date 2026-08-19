<?php
require_once "../../includes/security.php";
library_system_bootstrap();
require_request_method('POST');
require_csrf_token();
require_once "../../config/db.php";
require_once "../../includes/diary_helpers.php";

header("Content-Type: application/json");

require_api_login('user');

ensure_diary_entries_table($mysqli);

$user_id = intval($_SESSION['user_id']);
$entry_date = normalized_date_or_today($_POST['date'] ?? null);
$content = trim($_POST['content'] ?? "");

if (strlen($content) > 5000) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Diary entry is too long"]);
    exit;
}

$stmt = $mysqli->prepare("
INSERT INTO diary_entries (user_id, entry_date, content)
VALUES (?, ?, ?)
ON DUPLICATE KEY UPDATE
content = VALUES(content),
updated_at = CURRENT_TIMESTAMP
");
$stmt->bind_param("iss", $user_id, $entry_date, $content);
$stmt->execute();

echo json_encode([
    "status" => "success",
    "date" => $entry_date,
    "updated_at" => date("Y-m-d H:i:s")
]);
?>
