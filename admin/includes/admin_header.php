<?php
if(!isset($_SESSION)){
session_start();
}
?>
<head>

<link rel="stylesheet" href="style.css">

</head>

<div class="topbar">

<div class="left">

<button onclick="history.back()" class="backBtn">← Back</button>

<h2>Admin Panel</h2>

</div>

<div class="right">

<span>Welcome Admin</span>

</div>

</div>
