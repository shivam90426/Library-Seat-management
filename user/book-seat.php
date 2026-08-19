<?php
require_once "../includes/security.php";
library_system_bootstrap();
require_once "../config/db.php";

require_login('user');

$user_id = intval($_SESSION['user_id']);
$today = date("Y-m-d");

$subStmt = $mysqli->prepare("
SELECT seat_type, end_date
FROM subscriptions
WHERE user_id=? AND status='active'
ORDER BY id DESC
LIMIT 1
");
$subStmt->bind_param("i", $user_id);
$subStmt->execute();
$subscription = $subStmt->get_result()->fetch_assoc();

$bookingAllowed = $subscription && $subscription['end_date'] >= $today;
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Book Seat</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Poppins,sans-serif;}
body{
min-height:100vh;
background:linear-gradient(135deg,#0f172a,#1e293b);
color:#fff;
padding:32px 20px;
}
.page{
max-width:1200px;
margin:0 auto;
}
.topbar{
display:flex;
justify-content:space-between;
align-items:center;
gap:16px;
margin-bottom:24px;
}
.back-link,.action-link{
text-decoration:none;
color:#fff;
padding:10px 18px;
border-radius:999px;
background:rgba(255,255,255,0.08);
border:1px solid rgba(255,255,255,0.12);
}
.hero{
background:rgba(255,255,255,0.06);
border:1px solid rgba(255,255,255,0.1);
border-radius:24px;
padding:28px;
margin-bottom:24px;
box-shadow:0 20px 40px rgba(0,0,0,0.2);
}
.hero h1{
font-size:32px;
margin-bottom:0;
}
.status{
margin-top:16px;
display:inline-flex;
padding:10px 16px;
border-radius:999px;
background:rgba(37,99,235,0.18);
border:1px solid rgba(96,165,250,0.4);
}
.status.expired{
background:rgba(239,68,68,0.16);
border-color:rgba(248,113,113,0.45);
}
.layout{
display:grid;
grid-template-columns:1.25fr 320px;
gap:24px;
}
.panel{
background:rgba(255,255,255,0.06);
border:1px solid rgba(255,255,255,0.1);
border-radius:24px;
padding:24px;
box-shadow:0 20px 40px rgba(0,0,0,0.18);
}
.section-grid{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
gap:18px;
}
.section-card h3{
margin-bottom:14px;
}
.seat-grid{
display:grid;
grid-template-columns:repeat(auto-fill, minmax(34px, 34px));
gap:10px;
}
.seat{
width:34px;
height:34px;
border-radius:10px;
display:flex;
align-items:center;
justify-content:center;
background:rgba(255,255,255,0.04);
transition:transform .2s ease, box-shadow .2s ease;
cursor:pointer;
}
.seat:hover{
transform:translateY(-2px);
box-shadow:0 10px 20px rgba(37,99,235,0.25);
}
.seat.disabled{
opacity:.45;
cursor:not-allowed;
}
.seat.disabled:hover{
transform:none;
box-shadow:none;
}
.seat img{
width:24px;
}
.legend{
display:grid;
gap:12px;
margin-top:8px;
}
.legend-item{
display:flex;
align-items:center;
gap:12px;
color:#cbd5e1;
}
.legend-item img{
width:24px;
}
.note{
margin-top:20px;
padding:16px;
border-radius:18px;
background:rgba(15,23,42,0.55);
color:#cbd5e1;
line-height:1.6;
}
@media(max-width: 920px){
.layout{
grid-template-columns:1fr;
}
}
</style>
</head>
<body>
<div class="page">
    <div class="topbar">
        <a class="back-link" href="dashboard.php">Back to Dashboard</a>
        <a class="action-link" href="my-seat.php">View My Seat</a>
    </div>

    <div class="hero">
        <h1>Choose Your Seat</h1>
        <?php if ($bookingAllowed): ?>
            <div class="status">Active plan: <?= htmlspecialchars($subscription['seat_type']) ?></div>
        <?php else: ?>
            <div class="status expired">You need an active subscription before you can book a seat.</div>
        <?php endif; ?>
    </div>

    <div class="layout">
        <div class="panel">
            <div class="section-grid" id="seatSections"></div>
        </div>

        <div class="panel">
            <h3 style="margin-bottom:16px;">Seat Legend</h3>
            <div class="legend">
                <div class="legend-item"><img src="../assets/seats/green.png" alt="Available"> Available</div>
                <div class="legend-item"><img src="../assets/seats/blue.png" alt="Your seat"> Your seat</div>
                <div class="legend-item"><img src="../assets/seats/red.png" alt="Booked"> Already booked</div>
                <div class="legend-item"><img src="../assets/seats/gray.svg" alt="Blocked"> Blocked or maintenance</div>
            </div>
            <div class="note">
                Daily plans can book once per day. Longer plans keep a fixed seat for the active subscription period.
            </div>
        </div>
    </div>
</div>

<script>
const csrfToken = "<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>";
const bookingAllowed = <?= $bookingAllowed ? 'true' : 'false' ?>;
const seatSections = document.getElementById("seatSections");

function seatImageFor(status) {
    if (status === "mine") return "../assets/seats/blue.png";
    if (status === "booked") return "../assets/seats/red.png";
    if (status === "blocked") return "../assets/seats/gray.svg";
    return "../assets/seats/green.png";
}

function loadSeatMap() {
    fetch("api/get-seats-map.php")
        .then(res => res.json())
        .then(data => {
            const grouped = {};
            seatSections.innerHTML = "";

            data.forEach(seat => {
                const section = seat.section || "General";
                if (!grouped[section]) {
                    grouped[section] = [];
                }
                grouped[section].push(seat);
            });

            Object.keys(grouped).forEach(sectionName => {
                const wrapper = document.createElement("div");
                wrapper.className = "section-card";

                const title = document.createElement("h3");
                title.textContent = sectionName.replace(/_/g, " ").replace(/\b\w/g, char => char.toUpperCase());
                wrapper.appendChild(title);

                const grid = document.createElement("div");
                grid.className = "seat-grid";

                grouped[sectionName].forEach(seat => {
                    const seatNode = document.createElement("div");
                    seatNode.className = "seat";

                    if (seat.status === "booked" || seat.status === "blocked") {
                        seatNode.classList.add("disabled");
                    }

                    const image = document.createElement("img");
                    image.src = seatImageFor(seat.status);
                    image.alt = seat.seat_no;
                    image.title = seat.seat_no + " (" + seat.seat_type + ")";
                    seatNode.appendChild(image);

                    if (bookingAllowed && seat.status === "available") {
                        seatNode.onclick = function () {
                            bookSeat(seat.id);
                        };
                    } else if (seat.status !== "mine") {
                        seatNode.classList.add("disabled");
                    }

                    grid.appendChild(seatNode);
                });

                wrapper.appendChild(grid);
                seatSections.appendChild(wrapper);
            });
        });
}

function bookSeat(seatId) {
    fetch("api/book-seat.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded",
            "X-CSRF-Token": csrfToken
        },
        body: "seat_id=" + encodeURIComponent(seatId)
    })
    .then(res => res.text())
    .then(response => {
        if (response === "expired") {
            alert("Your subscription has expired.");
            return;
        }
        if (response === "already") {
            alert("You already have an active seat booking.");
            return;
        }
        if (response === "taken") {
            alert("That seat was booked by someone else.");
            return;
        }
        if (response === "booked") {
            alert("Seat booked successfully.");
            loadSeatMap();
            return;
        }

        alert("Unable to book this seat right now.");
    });
}

loadSeatMap();
</script>
</body>
</html>
