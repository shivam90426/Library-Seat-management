<?php
require_once "../../includes/security.php";
library_system_bootstrap();
require "../../config/db.php";

header("Content-Type: application/json");
require_api_login('admin');

$result=$mysqli->query("SELECT id, name, section_code, pos_x, pos_y, width, height FROM seat_sections ORDER BY id");

$data=[];

while($row=$result->fetch_assoc()){
$data[]=$row;
}

echo json_encode($data);
