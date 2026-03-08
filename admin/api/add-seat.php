<?php
require "../../config/db.php";

$seat_no="S".rand(100,999);

$stmt=$mysqli->prepare("
INSERT INTO seats
(seat_no,seat_type,section_name,position_order)
VALUES (?, '6h', 'six', 0)
");

$stmt->bind_param("s",$seat_no);

$stmt->execute();