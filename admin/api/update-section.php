<?php
require_once "../../includes/security.php";
library_system_bootstrap();
require_request_method('POST');
require_csrf_token();
require "../../config/db.php";

header("Content-Type: application/json");
require_api_login('admin');

$data=json_decode(file_get_contents("php://input"),true);

$stmt=$mysqli->prepare("
UPDATE seat_sections
SET pos_x=?,pos_y=?
WHERE id=?
");

$stmt->bind_param("iii",$data['x'],$data['y'],$data['id']);

$stmt->execute();
echo json_encode(["status" => "success"]);
