<?php

require "../../config/db.php";

$result=$mysqli->query("SELECT * FROM seat_sections");

$data=[];

while($row=$result->fetch_assoc()){
$data[]=$row;
}

echo json_encode($data);