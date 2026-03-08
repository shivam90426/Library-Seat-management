<?php
session_start();
require_once "../config/db.php";

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit;
}

$user_id = intval($_SESSION['user_id']);

/* ================= SUBSCRIPTION CHECK ================= */

$subStmt = $mysqli->prepare("
SELECT seat_type, end_date 
FROM subscriptions
WHERE user_id=? 
AND status='active'
ORDER BY id DESC 
LIMIT 1
");
$subStmt->bind_param("i",$user_id);
$subStmt->execute();
$sub = $subStmt->get_result()->fetch_assoc();

$today = date("Y-m-d");

$hasActivePlan = false;
$bookingAllowed = false;
$entryAllowed = false;
$graphLimit = 6; 
// default graph limit

if($sub){
    if($sub['end_date'] >= $today){
        $hasActivePlan = true;
        $bookingAllowed = true;
        $entryAllowed = true;
        $graphLimit = intval(str_replace("h","",$sub['seat_type']));
    }
}

/* CHECK ACTIVE SESSION */
$active = $mysqli->prepare("
SELECT entry_time 
FROM timings 
WHERE user_id=? AND exit_time IS NULL
LIMIT 1
");
$active->bind_param("i",$user_id);
$active->execute();
$activeRow = $active->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Dashboard</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Poppins;}

body{
height:100vh;
overflow:hidden;
background:linear-gradient(135deg,#1e293b,#0f172a);
color:white;
}

.dashboard{
display:flex;
height:100%;
}
#calendar{
flex:1;
overflow:hidden;
}
#weeklyChart{
height:100% !important;
}

/* SIDEBAR */
.sidebar{
width:240px;
background:rgba(255,255,255,0.05);
backdrop-filter:blur(10px);
border-radius:30px 0 0 30px;
padding:20px;
display:flex;
flex-direction:column;
justify-content:space-between;
}

.sidebar h2{
font-weight:600;
margin-bottom:25px;
}

.sidebar a{
display:block;
padding:10px 15px;
margin-bottom:8px;
border-radius:12px;
color:white;
text-decoration:none;
transition:0.3s;
}

.sidebar a:hover{
background:rgba(255,255,255,0.1);
transform:translateX(5px);
}

/* MAIN */
.main{
flex:1;
padding:20px;
display:grid;
grid-template-rows:70px 110px 330px 1fr;
gap:15px;
height:100%;
min-height:0;
}

/* TOPBAR */
.topbar{
display:flex;
justify-content:space-between;
align-items:center;
background:rgba(255,255,255,0.05);
backdrop-filter:blur(10px);
border-radius:20px;
padding:15px;
}

/* CARDS */
.stats{
display:grid;
grid-template-columns:repeat(3,1fr);
gap:15px;
}

.card{
background:rgba(255,255,255,0.06);
backdrop-filter:blur(12px);
border-radius:20px;
padding:15px;
min-height:0;
overflow:hidden;
display:flex;
flex-direction:column;
}

/* ROW 2 */
.middle{
display:grid;
grid-template-columns:2fr 1fr;
gap:15px;
height:100%;
min-height:0;
}
/* ROW 3 */
.bottom{
display:grid;
grid-template-columns:2fr 1fr 1fr;
gap:15px;
height:100%;
min-height:0;
}

/* BUTTONS */
.btn-green{
background:rgba(34,197,94,0.2);
border:1px solid #22c55e;
color:#22c55e;
padding:8px 14px;
border-radius:12px;
cursor:pointer;
}

.btn-red{
background:rgba(239,68,68,0.2);
border:1px solid #ef4444;
color:#ef4444;
padding:8px 14px;
border-radius:12px;
cursor:pointer;
}

.btn-red:disabled,
.btn-green:disabled{
opacity:0.4;
cursor:not-allowed;
}

.timer{
font-size:22px;
font-weight:600;
}

/* SMALL TEXTAREA */
textarea{
width:100%;
height:100px;
background:rgba(255,255,255,0.05);
border:none;
border-radius:15px;
color:white;
padding:10px;
resize:none;
}
#calendar{
display:grid;
grid-template-columns:repeat(7,1fr);
gap:6px;
}

.day{
padding:8px;
border-radius:10px;
text-align:center;
cursor:pointer;
background:rgba(255,255,255,0.05);
transition:0.2s;
font-size:13px;
}

.day:hover{
background:rgba(255,255,255,0.1);
}

.day.disabled{
opacity:0.3;
cursor:not-allowed;
}

.day.expiry{
background:rgba(239,68,68,0.25);
border:1px solid #ef4444;
}
/* ===== SEAT CONTAINER ===== */

#seatContainer{
display:flex;
flex-direction:column;
gap:20px;
flex:1;
overflow-y:auto;
padding-right:5px;
}
/* ===== SECTION CARD ===== */

.seat-section{
background:rgba(255,255,255,0.07);
backdrop-filter:blur(18px);
border-radius:22px;
padding:15px 18px;
box-shadow:
0 8px 30px rgba(0,0,0,0.25),
inset 0 0 0 1px rgba(255,255,255,0.05);
transition:0.35s ease;
}

.seat-section:hover{
transform:translateY(-4px);
box-shadow:
0 12px 35px rgba(0,0,0,0.35),
inset 0 0 0 1px rgba(255,255,255,0.08);
}

.seat-section h4{
font-weight:600;
font-size:15px;
margin-bottom:12px;
letter-spacing:0.5px;
opacity:0.85;
}
.seat-section h4::after{
content:'';
display:block;
height:2px;
width:40px;
margin-top:6px;
background:linear-gradient(90deg,#3b82f6,#8b5cf6);
border-radius:10px;
}

/* ===== SEAT GRID ===== */

.seat-grid{
display:grid;
grid-template-columns:repeat(auto-fill, 48px);
gap:12px;
}
@keyframes fadeInSeat{
from{
opacity:0;
transform:translateY(10px);
}
to{
opacity:1;
transform:translateY(0);
}
}

.seat{
animation:fadeInSeat .8s ease forwards;
}
/* ===== INDIVIDUAL SEAT ===== */

.seat{
width:48px;
height:48px;
border-radius:14px;
cursor:pointer;
position:relative;
transition:0.25s ease;
display:flex;
align-items:center;
justify-content:center;
}

.seat img{
width:38px;
transition:0.25s ease;
}

/* ===== GLASS HOVER ===== */

.seat:hover{
transform:translateY(-5px) scale(1.05);
box-shadow:
0 8px 20px rgba(0,0,0,0.35),
0 0 20px rgba(59,130,246,0.25);
background:rgba(255,255,255,0.08);
backdrop-filter:blur(8px);
}

/* ===== DISABLED ===== */

.seat.disabled{
opacity:0.4;
cursor:not-allowed;
}

.seat.disabled:hover{
transform:none;
box-shadow:none;
}
#seatPopup{
position:fixed;
top:0;
left:0;
width:100%;
height:100%;
background:rgba(0,0,0,0.7);
display:none;
align-items:center;
justify-content:center;
z-index:999;
}

.popupCard{
width:1100px;
background:#0f172a;
border-radius:20px;
padding:20px;
color:white;
}

.popupHeader{
display:flex;
justify-content:space-between;
align-items:center;
margin-bottom:15px;
}

.seatMap{
display:flex;
flex-direction:column;
gap:20px;
}

.topRow{
display:grid;
grid-template-columns:3fr 1fr;
gap:20px;
}

.bottomRow{
display:grid;
grid-template-columns:2fr 1fr 2fr;
gap:20px;
}

/* office */
.office{
background:rgba(255,255,255,0.05);
border-radius:10px;
display:flex;
align-items:center;
justify-content:center;
font-size:18px;
}

/* seat image */
.seat{
width:22px;
height:22px;
cursor:pointer;
}

.seat img{
width:100%;
}

/* grids */

.grid6{
display:grid;
grid-template-columns:repeat(9,22px);
gap:6px;
}

.grid12{
display:grid;
grid-template-columns:repeat(5,22px);
gap:6px;
}

.gridMid{
display:grid;
grid-template-columns:repeat(2,22px);
gap:6px;
}

.grid24{
display:grid;
grid-template-columns:repeat(4,22px);
gap:6px;
}
#seatPreview{
height:70px;
border-radius:12px;
background:rgba(255,255,255,0.05);
display:flex;
flex-direction:column;
align-items:center;
justify-content:center;
cursor:pointer;
transition:0.2s;
}

#seatPreview:hover{
background:rgba(255,255,255,0.12);
}

.miniSeats{
display:flex;
gap:6px;
margin-bottom:5px;
}

.miniSeats img{
width:18px;
}
</style>
</head>

<body>
<div class="dashboard">

<!-- SIDEBAR -->
<div class="sidebar">
<div>
<h2>📚 Library</h2>
<a href="dashboard.php">Dashboard</a>
<a href="<?= $bookingAllowed ? 'book-seat.php' : '#' ?>" 
<?= !$bookingAllowed ? 'onclick="alert(\'Subscription expired\')"' : '' ?>>
Book Seat
</a>
<a href="my-seat.php">My Seat</a>
<a href="subscription.php">Subscription</a>
<a href="diary.php">Diary</a>
<a href="../logout.php">Logout</a>
</div>
</div>

<!-- MAIN -->
<div class="main">

<!-- TOPBAR -->
<div class="topbar">
<div>Welcome 👋</div>
<div>
<span class="timer" id="liveTimer">00:00</span>
<button 
class="btn-green" 
id="entryBtn"
<?= !$entryAllowed ? 'disabled' : '' ?>>
Entry
</button>
<button class="btn-red" id="exitBtn" disabled>Exit</button>
</div>
</div>

<!-- STATS -->
<div class="stats">
<div class="card">Total Hours</div>
<div class="card">Subscription</div>
<div class="card">Weekly Plan</div>
</div>

<!-- ROW 2 -->
<div class="middle">
<div class="card">
    <h3 style="margin-bottom:10px;">Seats</h3>
  <div id="seatPreview" onclick="openSeatPopup()">
    <div class="miniSeats">
        <img src="../assets/seats/green.png">
        <img src="../assets/seats/green.png">
        <img src="../assets/seats/green.png">
        <img src="../assets/seats/green.png">
        <img src="../assets/seats/green.png">
        <img src="../assets/seats/green.png">
    </div>

    <p>Click to open seat map</p>
</div>
</div>
<div class="card">
    <h3 style="margin-bottom:10px;">Calendar</h3>
    <div id="calendar"></div>
</div>
</div>

<!-- ROW 3 -->
<div class="bottom">
<div class="card">
<canvas id="weeklyChart"></canvas>
</div>
<div class="card">
Diary
<textarea id="diaryBox"></textarea>
</div>
<div class="card">
Entry / Exit Controls
</div>
</div>

</div>
</div>
<script>

let timerInterval = null;
let entryTime = <?php echo $activeRow ? '"'.$activeRow['entry_time'].'"' : 'null'; ?>;

const entryBtn = document.getElementById("entryBtn");
const exitBtn = document.getElementById("exitBtn");
const liveTimer = document.getElementById("liveTimer");

function startTimer(startTime){
    function update(){
        let now = new Date().getTime();
        let start = new Date(startTime).getTime();
        let diff = now - start;

        let minutes = Math.floor(diff / (1000 * 60));
        let hours = Math.floor(minutes / 60);
        minutes = minutes % 60;

        liveTimer.innerHTML =
            String(hours).padStart(2,'0') + ":" +
            String(minutes).padStart(2,'0');
    }

    update();
    timerInterval = setInterval(update, 60000);
}

/* Resume if active */
if(entryTime){
    startTimer(entryTime);
    entryBtn.disabled = true;
    exitBtn.disabled = false;
}

/* ENTRY */
entryBtn.addEventListener("click", function(){

    fetch("api/timer-entry.php")
    .then(res=>res.text())
    .then(data=>{
        if(data==="started"){
            location.reload();
        }
        if(data==="expired"){
            alert("Subscription expired");
        }
    });

});

/* EXIT */
exitBtn.addEventListener("click", function(){

    fetch("api/timer-exit.php")
    .then(res=>res.text())
    .then(data=>{
        if(data==="stopped"){
            location.reload();
        }
    });

});

</script>
<script>
const expiryDate = "<?= $sub['end_date'] ?? '' ?>";
</script>
<script>
const graphLimit = <?= $graphLimit ?>;
</script>
<script>

fetch("api/get-weekly.php")
.then(res => res.json())
.then(data => {

    const colors = [
        "#3b82f6",
        "#8b5cf6",
        "#06b6d4",
        "#22c12e",
        "#f59e0b",
        "#f97316",
        "#ec4899"
    ];

    new Chart(document.getElementById('weeklyChart'), {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.hours,
                backgroundColor: colors,
                borderRadius: 12
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: graphLimit
                }
            },
            plugins: {
                legend: { display: false }
            },
            onClick: (e, elements) => {
                if(elements.length > 0){
                    let index = elements[0].index;
                    alert(data.labels[index] + " : " + data.hours[index] + " hrs");
                }
            }
        }
    });

});

</script>
<script>

const calendar = document.getElementById("calendar");

function generateCalendar(){

    const today = new Date();
    const year = today.getFullYear();
    const month = today.getMonth();

    const firstDay = new Date(year, month, 1);
    let startDay = firstDay.getDay();

    // Monday start adjust
    startDay = startDay === 0 ? 6 : startDay - 1;

    const daysInMonth = new Date(year, month+1, 0).getDate();

    calendar.innerHTML = "";

    // Empty cells before month start
    for(let i=0;i<startDay;i++){
        calendar.innerHTML += "<div></div>";
    }

    for(let day=1; day<=daysInMonth; day++){

        const fullDate = `${year}-${String(month+1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;

        const div = document.createElement("div");
        div.classList.add("day");
        div.innerText = day;

        // Expiry highlight
        if(fullDate === expiryDate){
            div.classList.add("expiry");
        }

        // Disable future
        if(new Date(fullDate) > today){
            div.classList.add("disabled");
        }else{
            div.onclick = function(){
                fetch("api/get-day-hours.php?date="+fullDate)
                .then(res=>res.text())
                .then(hours=>{
                    alert(fullDate+" : "+hours+" hrs");
                });
            };
        }

        calendar.appendChild(div);
    }
}

generateCalendar();

</script>
<script>

const seatContainer = document.getElementById("seatContainer");

function loadSeats(){

    fetch("api/get-seats.php")
    .then(res=>res.json())
    .then(data=>{

        seatContainer.innerHTML = "";

        const grouped = {};

        data.forEach(seat=>{
            if(!grouped[seat.seat_type]){
                grouped[seat.seat_type] = [];
            }
            grouped[seat.seat_type].push(seat);
        });

        for(const type in grouped){

            const sectionDiv = document.createElement("div");
            sectionDiv.classList.add("seat-section");

            const title = document.createElement("h4");
            title.innerText = type + " Section";
            sectionDiv.appendChild(title);

            const grid = document.createElement("div");
            grid.classList.add("seat-grid");

            grouped[type].forEach(seat=>{

                const seatDiv = document.createElement("div");
                seatDiv.classList.add("seat");

                let img = document.createElement("img");

                if(seat.status === "available"){
                    img.src = "../assets/seats/green.png";
                }
                else if(seat.status === "booked"){
                    img.src = "../assets/seats/red.png";
                    seatDiv.classList.add("disabled");
                }
                else if(seat.status === "mine"){
                    img.src = "../assets/seats/blue.png";
                    seatDiv.style.boxShadow="0 0 18px rgba(59,130,246,0.8)";
                }
                else{
                    img.src = "../assets/seats/red.png";
                    seatDiv.classList.add("disabled");
                }

                seatDiv.appendChild(img);

                if(seat.status === "available"){
                    seatDiv.onclick = function(){
                        bookSeat(seat.id);
                    };
                }

                grid.appendChild(seatDiv);

            });

            sectionDiv.appendChild(grid);
            seatContainer.appendChild(sectionDiv);
        }

    });

}

function bookSeat(seatId){

    fetch("api/book-seat.php",{
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:"seat_id="+seatId
    })
    .then(res=>res.text())
    .then(response=>{

        if(response==="expired"){
            alert("Subscription expired");
        }
        else if(response==="taken"){
            alert("Seat already booked");
        }
        else{
            loadSeats();
        }

    });

}

loadSeats();
function openSeatPopup(){
document.getElementById("seatPopup").style.display="flex";
loadSeatMap();
}

function closeSeatPopup(){
document.getElementById("seatPopup").style.display="none";
}
function createSeat(container,status){

let div=document.createElement("div");
div.className="seat";

let img=document.createElement("img");

if(status==="available"){
img.src="../assets/seats/green.png";
}

if(status==="booked"){
img.src="../assets/seats/red.png";
}

if(status==="mine"){
img.src="../assets/seats/blue.png";
}

div.appendChild(img);

container.appendChild(div);
}

function loadSeats(){

let six=document.getElementById("sixSeats");
let twelveL=document.getElementById("twelveLeft");
let twelveM=document.getElementById("twelveMiddle");
let twenty=document.getElementById("twentyfour");

six.innerHTML="";
twelveL.innerHTML="";
twelveM.innerHTML="";
twenty.innerHTML="";

/* 6h seats 7x9 */
for(let i=0;i<63;i++){
createSeat(six,"available");
}

/* 12h left */
for(let i=0;i<20;i++){
createSeat(twelveL,"available");
}

/* middle columns */
for(let i=0;i<10;i++){
createSeat(twelveM,"available");
}

/* 24h */
for(let i=0;i<16;i++){
createSeat(twenty,"available");
}

}

loadSeats();


function loadSeatMap(){

fetch("api/get-seats-map.php")
.then(res=>res.json())
.then(data=>{

const six=document.getElementById("sixSeats");
const twelveL=document.getElementById("twelveLeft");
const twelveM=document.getElementById("twelveMiddle");
const twenty=document.getElementById("twentyfour");

six.innerHTML="";
twelveL.innerHTML="";
twelveM.innerHTML="";
twenty.innerHTML="";

data.forEach(seat=>{

let div=document.createElement("div");
div.className="seat";

let img=document.createElement("img");

if(seat.status==="available"){
img.src="../assets/seats/green.png";
}

if(seat.status==="booked"){
img.src="../assets/seats/red.png";
}

if(seat.status==="mine"){
img.src="../assets/seats/blue.png";
}

if(seat.status==="blocked"){
img.src="../assets/seats/gray.png";
}

div.appendChild(img);

if(seat.status==="available"){
div.onclick=function(){
bookSeat(seat.id);
};
}

if(seat.section==="six"){
six.appendChild(div);
}

if(seat.section==="twelve_left"){
twelveL.appendChild(div);
}

if(seat.section==="twelve_mid"){
twelveM.appendChild(div);
}

if(seat.section==="twentyfour"){
twenty.appendChild(div);
}

});

});
}

function bookSeat(seatId){

fetch("api/book-seat.php",{
method:"POST",
headers:{
"Content-Type":"application/x-www-form-urlencoded"
},
body:"seat_id="+seatId
})
.then(res=>res.text())
.then(response=>{

if(response==="taken"){
alert("Seat already booked");
}
else{
loadSeatMap();
}

});

}

</script>
</body>
</html>
<!-- Seat Popup -->
<div id="seatPopup">

<div class="popupCard">

<div class="popupHeader">
<h2>BOOK SEAT</h2>
<button onclick="closeSeatPopup()">✕</button>
</div>

<div class="seatMap">

<!-- TOP -->
<div class="topRow">

<div class="sixSection">
<h4>6h Section</h4>
<div class="grid6" id="sixSeats"></div>
</div>

<div class="office">
Office
</div>

</div>

<!-- BOTTOM -->
<div class="bottomRow">

<div class="twelveLeft">
<h4>12h</h4>
<div class="grid12" id="twelveLeft"></div>
</div>

<div class="twelveMiddle">
<h4>12h</h4>
<div class="gridMid" id="twelveMiddle"></div>
</div>

<div class="twentyfour">
<h4>24h</h4>
<div class="grid24" id="twentyfour"></div>
</div>

</div>

</div>

</div>
</div>