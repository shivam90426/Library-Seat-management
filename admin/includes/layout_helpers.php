<?php

function get_section_type_map(): array
{
    return [
        'six' => '6h',
        'twelve_left' => '12h',
        'twelve_mid' => '12h',
        'twentyfour' => '24h'
    ];
}

function get_section_seat_type(string $sectionCode, ?string $fallback = null): string
{
    $map = get_section_type_map();
    return $map[$sectionCode] ?? ($fallback ?: '6h');
}

function get_seat_prefix(string $seatType): string
{
    if ($seatType === '12h') {
        return '12H-';
    }
    if ($seatType === '24h') {
        return '24H-';
    }
    return '6H-';
}

function generate_next_seat_number(mysqli $mysqli, string $seatType): string
{
    $prefix = get_seat_prefix($seatType);
    $counter = 1;

    $stmt = $mysqli->prepare("SELECT 1 FROM seats WHERE seat_no=? LIMIT 1");

    while (true) {
        $seatNo = $prefix . $counter;
        $stmt->bind_param("s", $seatNo);
        $stmt->execute();

        if ($stmt->get_result()->num_rows === 0) {
            return $seatNo;
        }

        $counter++;
    }
}
?>
