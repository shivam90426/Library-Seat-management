<?php
require "../../config/db.php";

$id=intval($_POST['id']);

$stmt=$mysqli->prepare("
DELETE FROM seats WHERE id=?
");

$stmt->bind_param("i",$id);

$stmt->execute();