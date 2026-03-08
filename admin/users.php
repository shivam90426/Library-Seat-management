<?php
require "../config/db.php";

$users=$mysqli->query("SELECT * FROM users");
?>

<!DOCTYPE html>
<html>
<body>

<?php include "includes/admin_sidebar.php"; ?>

<div class="main">

<?php include "includes/admin_header.php"; ?>

<h2>Users</h2>

<table border="1" cellpadding="10">

<tr>
<th>Name</th>
<th>Email</th>
<th>Role</th>
</tr>

<?php while($u=$users->fetch_assoc()) { ?>

<tr>

<td><?= $u['name'] ?></td>

<td><?= $u['email'] ?></td>

<td><?= $u['role'] ?></td>

</tr>

<?php } ?>

</table>

</div>

</body>
</html>