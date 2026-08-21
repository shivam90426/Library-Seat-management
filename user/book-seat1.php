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
cursor:default;
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
.shift-modal{
position:fixed;
inset:0;
display:none;
align-items:center;
justify-content:center;
background:rgba(2,6,23,.78);
padding:20px;
z-index:1000;
}
.shift-card{
width:min(520px,100%);
background:#0f172a;
border:1px solid rgba(255,255,255,.12);
border-radius:24px;
padding:24px;
box-shadow:0 25px 60px rgba(0,0,0,.35);
}
.shift-options{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:20px;}
.shift-option{border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.06);color:#fff;border-radius:18px;padding:18px;text-align:left;cursor:pointer;transition:.2s;}
.shift-option:hover:not(:disabled){transform:translateY(-2px);border-color:rgba(96,165,250,.6);}
.shift-option:disabled{opacity:.45;cursor:not-allowed;}
.shift-option strong{display:block;font-size:16px;margin-bottom:5px;}
.shift-option span{display:block;color:#cbd5e1;font-size:13px;}
.shift-option .shift-state{margin-top:10px;font-size:12px;color:#86efac;}
.shift-option:disabled .shift-state{color:#fca5a5;}
.shift-close{margin-top:16px;width:100%;padding:11px;border-radius:999px;border:1px solid rgba(255,255,255,.12);background:transparent;color:#fff;cursor:pointer;}
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

/* =========================================================
   GLASS UI — linked user pages
   Visual-only overrides: existing PHP/JS functionality kept.
   ========================================================= */
:root{
  --g-bg:#06111f;
  --g-bg2:#0a1b2d;
  --g-card:rgba(13,31,50,.68);
  --g-card2:rgba(18,42,66,.56);
  --g-border:rgba(165,205,245,.20);
  --g-border-hi:rgba(130,170,255,.34);
  --g-text:#f7fbff;
  --g-muted:#b9c9dc;
  --g-purple:#8b5cf6;
  --g-blue:#3b82f6;
  --g-green:#22c55e;
  --g-red:#ef4444;
  --g-orange:#f59e0b;
}
html{background:var(--g-bg)!important;}
body{
  background:
    radial-gradient(circle at 8% 0%,rgba(56,189,248,.14),transparent 28%),
    radial-gradient(circle at 92% 4%,rgba(139,92,246,.14),transparent 28%),
    linear-gradient(135deg,var(--g-bg),var(--g-bg2))!important;
  color:var(--g-text)!important;
  min-height:100vh;
}
.page{position:relative;}
a{color:var(--g-text);}
.back-link,.back,.logout,.back-btn,.action-link{
  background:rgba(255,255,255,.07)!important;
  border:1px solid var(--g-border)!important;
  color:var(--g-text)!important;
  box-shadow:0 10px 30px rgba(0,0,0,.16),inset 0 1px rgba(255,255,255,.08)!important;
  backdrop-filter:blur(14px);
}
.back-link:hover,.back:hover,.logout:hover,.back-btn:hover,.action-link:hover{
  border-color:var(--g-border-hi)!important;
  background:rgba(255,255,255,.11)!important;
}
.card,.panel,.hero,.chart-card,.empty{
  background:linear-gradient(135deg,rgba(15,36,58,.76),rgba(7,22,38,.64))!important;
  border:1px solid var(--g-border)!important;
  box-shadow:0 22px 55px rgba(0,0,0,.24),inset 0 1px rgba(255,255,255,.055)!important;
  backdrop-filter:blur(18px)!important;
}
h1,h2,h3,h4,strong{color:var(--g-text);}
p,.meta,.help,#statusText,.features,.note,.legend-item,.detail span,.card span{color:var(--g-muted)!important;}
.status{
  background:rgba(59,130,246,.13)!important;
  border-color:rgba(96,165,250,.35)!important;
}
.status.expired{
  background:rgba(239,68,68,.13)!important;
  border-color:rgba(248,113,113,.38)!important;
}
input,textarea,select{
  background:rgba(3,15,28,.62)!important;
  color:var(--g-text)!important;
  border:1px solid var(--g-border)!important;
}
input::placeholder,textarea::placeholder{color:#7f94ab!important;}
input:focus,textarea:focus,select:focus{
  border-color:rgba(139,92,246,.65)!important;
  box-shadow:0 0 0 3px rgba(139,92,246,.13)!important;
}
button,.button,.primary-link{
  background:linear-gradient(135deg,#4f46e5,#7c3aed)!important;
  color:#fff!important;
  border:1px solid rgba(167,139,250,.34)!important;
  box-shadow:0 10px 24px rgba(79,70,229,.22)!important;
}
button:hover,.button:hover,.primary-link:hover{filter:brightness(1.08);}
.seat{
  background:rgba(255,255,255,.045)!important;
  border:1px solid rgba(255,255,255,.08);
}
.seat:hover{
  box-shadow:0 10px 24px rgba(59,130,246,.25)!important;
}
.note{
  background:rgba(3,15,28,.48)!important;
  border:1px solid rgba(255,255,255,.08);
}
.shift-modal{background:rgba(1,8,18,.78)!important;backdrop-filter:blur(7px);}
.shift-card,.shift-option{
  background:rgba(10,29,48,.88)!important;
  border-color:var(--g-border)!important;
  color:var(--g-text)!important;
  backdrop-filter:blur(18px);
}
.shift-option span{color:var(--g-muted)!important;}
.shift-close{
  background:rgba(255,255,255,.06)!important;
  border-color:var(--g-border)!important;
  color:var(--g-text)!important;
}

/* My Seat */
.seat-banner{
  background:linear-gradient(135deg,rgba(59,130,246,.24),rgba(99,102,241,.16))!important;
  border:1px solid rgba(96,165,250,.25);
  box-shadow:inset 0 1px rgba(255,255,255,.08);
}
.detail{
  background:rgba(255,255,255,.045)!important;
  border-color:var(--g-border)!important;
}

/* Subscription */
.grid{gap:20px!important;}
.card .price,.price,.plan{color:#a78bfa!important;}
.warning{
  background:rgba(239,68,68,.12)!important;
  border:1px solid rgba(248,113,113,.28)!important;
  color:#fca5a5!important;
}

/* Profile */
.avatar{
  border:1px solid rgba(139,92,246,.55)!important;
  box-shadow:0 12px 35px rgba(79,70,229,.25)!important;
}
.header{border-bottom-color:var(--g-border)!important;}
.message.success{
  background:rgba(34,197,94,.11)!important;
  border-color:rgba(34,197,94,.28)!important;
  color:#86efac!important;
}
.message.error{
  background:rgba(239,68,68,.11)!important;
  border-color:rgba(239,68,68,.28)!important;
  color:#fca5a5!important;
}
.note{color:var(--g-muted)!important;}

/* Payment */
.qr-box img{
  border:1px solid var(--g-border)!important;
  box-shadow:0 12px 30px rgba(0,0,0,.25)!important;
}
.upi-box{
  background:rgba(255,255,255,.055)!important;
  border:1px solid var(--g-border)!important;
}
.success{
  background:rgba(34,197,94,.11)!important;
  border:1px solid rgba(34,197,94,.25)!important;
  color:#86efac!important;
}

/* Tables / history */
table{
  background:rgba(11,29,47,.72)!important;
  color:var(--g-text)!important;
  border:1px solid var(--g-border)!important;
  box-shadow:0 22px 50px rgba(0,0,0,.25)!important;
}
th{
  background:linear-gradient(135deg,#3730a3,#4f46e5)!important;
}
tr:nth-child(even){background:rgba(255,255,255,.035)!important;}
tr:hover{background:rgba(255,255,255,.06)!important;}
td{border-bottom:1px solid rgba(255,255,255,.06);}
.status-approved{color:#4ade80!important;}
.status-pending{color:#fbbf24!important;}
.status-rejected{color:#f87171!important;}

/* Analytics */
.stats .card strong{color:var(--g-text)!important;}
canvas{filter:drop-shadow(0 8px 18px rgba(59,130,246,.08));}

/* Small screens */
@media(max-width:700px){
  body{padding:20px 12px!important;}
  .card,.panel,.hero{border-radius:20px!important;}
}


/* Original seat-image presentation: no artificial tile styling. */
.seat{
    width:38px!important;
    height:38px!important;
    padding:1px!important;
    background:transparent!important;
    border:0!important;
    box-shadow:none!important;
    transform:none!important;
}
.seat:hover{
    transform:none!important;
    background:transparent!important;
    box-shadow:0 0 12px rgba(59,130,246,.18)!important;
}
.seat img{
    width:34px!important;
    height:34px!important;
    object-fit:contain!important;
    display:block!important;
    pointer-events:none!important;
}
.seat.disabled{
    opacity:1!important;
    cursor:not-allowed!important;
}
.seat.disabled img{filter:none!important;}

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

<div class="shift-modal" id="shiftModal">
    <div class="shift-card">
        <h2>Choose Your Shift</h2>
        <p id="shiftSeatLabel" style="margin-top:6px;color:#cbd5e1;"></p>
        <div class="shift-options">
            <button type="button" class="shift-option" id="morningShiftBtn">
                <strong>🌅 Morning</strong>
                <span>6:00 AM – 12:00 PM</span>
                <span class="shift-state" id="morningShiftState">Available</span>
            </button>
            <button type="button" class="shift-option" id="eveningShiftBtn">
                <strong>🌆 Evening</strong>
                <span>12:00 PM – 6:00 PM</span>
                <span class="shift-state" id="eveningShiftState">Available</span>
            </button>
        </div>
        <button type="button" class="shift-close" onclick="closeShiftModal()">Cancel</button>
    </div>
</div>

<script>
const csrfToken = "<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>";
const bookingAllowed = <?= $bookingAllowed ? 'true' : 'false' ?>;
const seatSections = document.getElementById("seatSections");

function seatImageFor(status) {
    if (status === "mine") return "../assets/seats/blue.png";
    if (status === "booked") return "../assets/seats/red.png";
    if (status === "blocked" || status === "unavailable_plan") return "../assets/seats/gray.svg";
    return "../assets/seats/green.png";
}

let selectedSeat = null;

const shiftModal = document.getElementById("shiftModal");
const shiftSeatLabel = document.getElementById("shiftSeatLabel");
const morningShiftBtn = document.getElementById("morningShiftBtn");
const eveningShiftBtn = document.getElementById("eveningShiftBtn");
const morningShiftState = document.getElementById("morningShiftState");
const eveningShiftState = document.getElementById("eveningShiftState");

function closeShiftModal() {
    shiftModal.style.display = "none";
    selectedSeat = null;
}

function openShiftSelector(seat) {
    selectedSeat = seat;
    shiftSeatLabel.textContent = seat.seat_no + " • 6H plan";

    const morningAvailable = seat.morning === "available";
    const eveningAvailable = seat.evening === "available";

    morningShiftBtn.disabled = !morningAvailable;
    eveningShiftBtn.disabled = !eveningAvailable;
    morningShiftState.textContent = morningAvailable ? "Available" : "Already booked";
    eveningShiftState.textContent = eveningAvailable ? "Available" : "Already booked";

    shiftModal.style.display = "flex";
}

morningShiftBtn.onclick = function () {
    if (selectedSeat) bookSeat(selectedSeat.id, "morning");
};

eveningShiftBtn.onclick = function () {
    if (selectedSeat) bookSeat(selectedSeat.id, "evening");
};

function loadSeatMap() {
    fetch("api/get-seats-map.php")
        .then(res => res.json())
        .then(data => {
            const grouped = {};
            seatSections.innerHTML = "";

            data.forEach(seat => {
                const section = seat.section || "General";
                if (!grouped[section]) grouped[section] = [];
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

                    const disabled = ["booked", "blocked", "unavailable_plan", "mine"].includes(seat.status);
                    if (disabled) seatNode.classList.add("disabled");

                    const image = document.createElement("img");
                    image.src = seatImageFor(seat.status);
                    image.alt = seat.seat_no;
                    image.title = seat.seat_no + " (" + seat.seat_type + ")";
                    seatNode.appendChild(image);

                    if (bookingAllowed && seat.status === "available") {
                        seatNode.classList.remove("disabled");
                        seatNode.onclick = function () {
                            if (seat.seat_type === "6h") {
                                openShiftSelector(seat);
                            } else {
                                bookSeat(seat.id, null);
                            }
                        };
                    }

                    grid.appendChild(seatNode);
                });

                wrapper.appendChild(grid);
                seatSections.appendChild(wrapper);
            });
        })
        .catch(() => {
            alert("Unable to load seats right now.");
        });
}

function bookSeat(seatId, shift = null) {
    const body = "seat_id=" + encodeURIComponent(seatId) +
        (shift ? "&shift=" + encodeURIComponent(shift) : "");

    fetch("api/book-seat.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded",
            "X-CSRF-Token": csrfToken
        },
        body: body
    })
    .then(res => res.text())
    .then(response => {
        if (response === "expired") {
            alert("Your subscription has expired.");
            return;
        }
        if (response === "wrong_category") {
            alert("You can book only seats from your subscription category.");
            return;
        }
        if (response === "shift_required") {
            alert("Please select a shift for a 6H seat.");
            return;
        }
        if (response === "already") {
            alert("You already have an active seat booking.");
            closeShiftModal();
            loadSeatMap();
            return;
        }
        if (response === "shift_taken" || response === "taken") {
            alert(response === "shift_taken" ? "That shift was booked by someone else." : "That seat is already booked.");
            closeShiftModal();
            loadSeatMap();
            return;
        }
        if (response === "booked") {
            alert("Seat booked successfully.");
            closeShiftModal();
            loadSeatMap();
            return;
        }

        alert("Unable to book this seat right now.");
    })
    .catch(() => alert("Unable to book this seat right now."));
}

loadSeatMap();
</script>
</body>
</html>
