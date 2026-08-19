<?php
require_once "../../includes/security.php";
library_system_bootstrap();
require "../../config/db.php";
require_once "../includes/layout_helpers.php";

header("Content-Type: application/json");

require_api_login('admin');

$sectionsResult = $mysqli->query("
SELECT id, name, section_code, pos_x, pos_y, width, height
FROM seat_sections
ORDER BY pos_y, pos_x, id
");

$sections = [];
while ($row = $sectionsResult->fetch_assoc()) {
    $row['seat_type'] = get_section_seat_type($row['section_code']);
    $row['seats'] = [];
    $sections[$row['section_code']] = $row;
}

$seatsResult = $mysqli->query("
SELECT id, seat_no, seat_type, section_name, is_active, is_maintenance, position_order
FROM seats
ORDER BY section_name, position_order, id
");

while ($seat = $seatsResult->fetch_assoc()) {
    $sectionCode = $seat['section_name'];
    if (!isset($sections[$sectionCode])) {
        continue;
    }

    $sections[$sectionCode]['seats'][] = [
        "id" => intval($seat['id']),
        "seat_no" => $seat['seat_no'],
        "seat_type" => $seat['seat_type'],
        "section_name" => $seat['section_name'],
        "is_active" => intval($seat['is_active']),
        "is_maintenance" => intval($seat['is_maintenance']),
        "position_order" => intval($seat['position_order'])
    ];
}

echo json_encode([
    "sections" => array_values($sections)
]);
?>
