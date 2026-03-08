<?php
require "../config/db.php";

$subs=$mysqli->query("
SELECT s.*,u.name
FROM subscriptions s
JOIN users u ON u.id=s.user_id
");
?>

<!DOCTYPE html>
<html>
<body>

<?php include "includes/admin_sidebar.php"; ?>

<div class="main">

<?php include "includes/admin_header.php"; ?>

<h2>Subscriptions</h2>

<table border="1" cellpadding="10">

<tr>
<th>User</th>
<th>Plan</th>
<th>Start</th>
<th>End</th>
</tr>

<?php while($s=$subs->fetch_assoc()) { ?>

<tr>

<td><?= $s['name'] ?></td>

<td><?= $s['seat_type'] ?></td>

<td><?= $s['start_date'] ?></td>

<td><?= $s['end_date'] ?></td>

</tr>

<?php } ?>

</table>

</div>

</body>
</html>