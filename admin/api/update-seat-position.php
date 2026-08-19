<?php
require_once "../../includes/security.php";
library_system_bootstrap();
require_request_method('POST');
require_csrf_token();
require "../../config/db.php";

header("Content-Type: application/json");
require_api_login('admin');

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
echo json_encode(["status" => "success"]);
