<?php

require "../../config/db.php";

$data=json_decode(file_get_contents("php://input"),true);

foreach($data as $seat){

$stmt=$mysqli->prepare("
UPDATE seats
SET position_order=?
WHERE id=?
");

$stmt->bind_param("ii",$seat['position'],$seat['id']);

$stmt->execute();

}