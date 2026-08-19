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
$sections = $payload['sections'] ?? [];

if (!is_array($sections)) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Invalid layout payload"]);
    exit;
}

$mysqli->begin_transaction();

try {
    foreach ($sections as $section) {
        $sectionId = intval($section['id'] ?? 0);
        $sectionCode = $section['section_code'] ?? '';
        $sectionName = trim($section['name'] ?? '');
        $posX = intval($section['pos_x'] ?? 0);
        $posY = intval($section['pos_y'] ?? 0);
        $width = max(1, intval($section['width'] ?? 1));
        $height = max(1, intval($section['height'] ?? 1));

        if ($sectionId <= 0 || $sectionName === '' || !array_key_exists($sectionCode, get_section_type_map())) {
            throw new RuntimeException('Invalid layout payload');
        }

        if ($sectionId > 0) {
            $sectionStmt = $mysqli->prepare("
                UPDATE seat_sections
                SET name=?, pos_x=?, pos_y=?, width=?, height=?
                WHERE id=?
            ");
            $sectionStmt->bind_param("siiiii", $sectionName, $posX, $posY, $width, $height, $sectionId);
            $sectionStmt->execute();
        }

        $seatType = get_section_seat_type($sectionCode);
        $seats = $section['seats'] ?? [];

        foreach ($seats as $index => $seat) {
            $seatId = intval($seat['id'] ?? 0);
            if ($seatId <= 0) {
                continue;
            }

            $seatNo = trim($seat['seat_no'] ?? '');
            if ($seatNo === '') {
                $seatNo = generate_next_seat_number($mysqli, $seatType);
            }
            if (!preg_match('/^[A-Z0-9-]{2,30}$/i', $seatNo)) {
                throw new RuntimeException('Invalid seat number');
            }

            $isActive = !empty($seat['is_active']) ? 1 : 0;
            $isMaintenance = !empty($seat['is_maintenance']) ? 1 : 0;

            $seatStmt = $mysqli->prepare("
                UPDATE seats
                SET seat_no=?, seat_type=?, section_name=?, section_id=?, position_order=?, is_active=?, is_maintenance=?
                WHERE id=?
            ");
            $seatStmt->bind_param(
                "sssiiiii",
                $seatNo,
                $seatType,
                $sectionCode,
                $sectionId,
                $index,
                $isActive,
                $isMaintenance,
                $seatId
            );
            $seatStmt->execute();
        }
    }

    $mysqli->commit();
    echo json_encode(["status" => "success"]);
} catch (Throwable $e) {
    $mysqli->rollback();
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Unable to save layout"]);
}
?>
