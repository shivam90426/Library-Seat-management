<?php
session_start();
require_once "../config/db.php";

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit;
}

if(isset($required_role)){
    if($_SESSION['role'] !== $required_role){
        header("Location: ../login.php");
        exit;
    }
}
?>