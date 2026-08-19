<?php
require_once "../../includes/security.php";
library_system_bootstrap();
require_request_method('POST');
require_csrf_token();
require "../../config/db.php";

header("Content-Type: application/json");

require_api_login('admin');

$payload = json_decode(file_get_contents("php://input"), true);
$id = intval($payload['id'] ?? ($_POST['id'] ?? 0));

if ($id <= 0) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Invalid seat"]);
    exit;
}

$stmt = $mysqli->prepare("
DELETE FROM seats WHERE id=?
");

$stmt->bind_param("i", $id);

$stmt->execute();

echo json_encode(["status" => $stmt->affected_rows > 0 ? "success" : "error"]);
