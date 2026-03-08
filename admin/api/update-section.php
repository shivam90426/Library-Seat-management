<?php

require "../../config/db.php";

$data=json_decode(file_get_contents("php://input"),true);

$stmt=$mysqli->prepare("
UPDATE seat_sections
SET pos_x=?,pos_y=?
WHERE id=?
");

$stmt->bind_param("iii",$data['x'],$data['y'],$data['id']);

$stmt->execute();