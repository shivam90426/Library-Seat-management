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
    echo json_encode(["status"=>"error","message"=>"Invalid layout payload"]);
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $mysqli->begin_transaction();

    $sectionStmt = $mysqli->prepare("
        UPDATE seat_sections
        SET name=?, pos_x=?, pos_y=?, width=?, height=?
        WHERE id=?
    ");

    // Read the existing section row before validating its type.
    // Some projects contain non-seat sections (for example "office") that
    // are valid layout blocks but are intentionally not part of the seat
    // type map. Those blocks must still be movable/resizable and savable.
    $existingSectionStmt = $mysqli->prepare("
        SELECT section_code,
               (SELECT seat_type FROM seats WHERE section_name = seat_sections.section_code ORDER BY id LIMIT 1) AS existing_seat_type
        FROM seat_sections
        WHERE id=?
        LIMIT 1
    ");

    $seatStmt = $mysqli->prepare("
        UPDATE seats
        SET seat_no=?, seat_type=?, section_name=?, position_order=?, is_active=?, is_maintenance=?
        WHERE id=?
    ");

    foreach ($sections as $section) {
        $sectionId = intval($section['id'] ?? 0);
        $sectionCode = trim((string)($section['section_code'] ?? ''));
        $sectionName = trim((string)($section['name'] ?? ''));

        if ($sectionId <= 0 || $sectionName === '') {
            throw new RuntimeException("Invalid section data.");
        }

        // Validate the section against the database. Known seat sections
        // use the normal 6h/12h/24h mapping; custom/non-seat sections keep
        // their existing seat type and are still allowed to save.
        $existingSectionStmt->bind_param("i", $sectionId);
        $existingSectionStmt->execute();
        $existingResult = $existingSectionStmt->get_result();
        $existingSection = $existingResult->fetch_assoc();
        if (!$existingSection) {
            throw new RuntimeException("Section not found.");
        }

        if (trim((string)$existingSection['section_code']) !== $sectionCode) {
            throw new RuntimeException("Section code mismatch.");
        }

        $posX = max(0, intval($section['pos_x'] ?? 0));
        $posY = max(0, intval($section['pos_y'] ?? 0));
        $width = max(1, intval($section['width'] ?? 1));
        $height = max(1, intval($section['height'] ?? 1));

        $sectionStmt->bind_param("siiiii", $sectionName, $posX, $posY, $width, $height, $sectionId);
        $sectionStmt->execute();

        $existingSeatType = trim((string)($existingSection['existing_seat_type'] ?? ''));
        $seatType = get_section_seat_type($sectionCode, $existingSeatType ?: '6h');
        $seats = is_array($section['seats'] ?? null) ? $section['seats'] : [];

        foreach ($seats as $index => $seat) {
            $seatId = intval($seat['id'] ?? 0);
            if ($seatId <= 0) continue;

            $seatNo = trim((string)($seat['seat_no'] ?? ''));
            if ($seatNo === '') {
                $seatNo = generate_next_seat_number($mysqli, $seatType);
            }
            if (!preg_match('/^[A-Z0-9-]{2,30}$/i', $seatNo)) {
                throw new RuntimeException("Invalid seat number.");
            }

            $isActive = !empty($seat['is_active']) ? 1 : 0;
            $isMaintenance = !empty($seat['is_maintenance']) ? 1 : 0;
            $positionOrder = intval($index);

            $seatStmt->bind_param(
                "sssiiii",
                $seatNo, $seatType, $sectionCode, $positionOrder,
                $isActive, $isMaintenance, $seatId
            );
            $seatStmt->execute();
        }
    }

    $mysqli->commit();
    echo json_encode(["status"=>"success","message"=>"Structure saved successfully."]);
} catch (Throwable $e) {
    try { $mysqli->rollback(); } catch (Throwable $ignore) {}
    http_response_code(500);
    echo json_encode([
        "status"=>"error",
        "message"=>"Unable to save layout.",
        "detail"=>$e->getMessage()
    ]);
}
?>
