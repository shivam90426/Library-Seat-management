<?php
require "../../config/db.php";

$result=$mysqli->query("
SELECT id,seat_no
FROM seats
ORDER BY position_order
");

$data=[];

while($row=$result->fetch_assoc()){
$data[]=$row;
}

echo json_encode($data);