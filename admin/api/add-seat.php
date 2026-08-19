<?php
require_once "../../includes/security.php";
library_system_bootstrap();
require_request_method('POST');
require_csrf_token();
require "../../config/db.php";
require_once "../includes/layout_helpers.php";

header("Content-Type: application/json");

require_api_login('admin');

$payload = json_decode(file_get_contents("php://input"), true);
$sectionCode = trim($payload['section_code'] ?? ($_POST['section_code'] ?? 'six'));
$allowedSections = array_keys(get_section_type_map());
if (!in_array($sectionCode, $allowedSections, true)) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Invalid section"]);
    exit;
}
$seatType = get_section_seat_type($sectionCode);
$seatNo = generate_next_seat_number($mysqli, $seatType);

$positionStmt = $mysqli->prepare("
SELECT COALESCE(MAX(position_order), -1) + 1 AS next_position
FROM seats
WHERE section_name=?
");
$positionStmt->bind_param("s", $sectionCode);
$positionStmt->execute();
$nextPosition = intval($positionStmt->get_result()->fetch_assoc()['next_position'] ?? 0);

$stmt = $mysqli->prepare("
INSERT INTO seats
(seat_no, seat_type, section_name, position_order, is_active, is_maintenance)
VALUES (?, ?, ?, ?, 1, 0)
");
$stmt->bind_param("sssi", $seatNo, $seatType, $sectionCode, $nextPosition);
$stmt->execute();

echo json_encode([
    "status" => "success",
    "seat_no" => $seatNo
]);
?>
