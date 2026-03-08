<?php
session_start();
require_once "../config/db.php";
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Seat Layout Builder</title>

<style>

body{
font-family:Poppins;
background:#0f172a;
color:white;
margin:0;
}

.container{
padding:20px;
}

h1{
margin-bottom:20px;
}

.layout{
display:grid;
grid-template-columns:repeat(20,40px);
gap:8px;
background:rgba(255,255,255,0.05);
padding:20px;
border-radius:15px;
}

.seat{
width:40px;
height:40px;
display:flex;
align-items:center;
justify-content:center;
border-radius:10px;
cursor:grab;
}

.seat img{
width:28px;
pointer-events:none;
}

.controls{
margin-top:20px;
display:flex;
gap:10px;
}

button{
padding:10px 15px;
border:none;
border-radius:8px;
cursor:pointer;
}

.addBtn{background:#22c55e;}
.saveBtn{background:#3b82f6;}
.deleteBtn{background:#ef4444;}

</style>
</head>

<body>

<div class="container">

<h1>Seat Layout Builder</h1>

<div id="layout" class="layout"></div>

<div class="controls">

<button class="addBtn" onclick="addSeat()">Add Seat</button>

<button class="saveBtn" onclick="saveLayout()">Save Layout</button>

</div>

</div>

<script>

let seats=[];

function loadLayout(){

fetch("api/get-layout.php")
.then(res=>res.json())
.then(data=>{

seats=data;

renderLayout();

})

}

function renderLayout(){

const layout=document.getElementById("layout");

layout.innerHTML="";

seats.forEach(seat=>{

let div=document.createElement("div");

div.className="seat";

div.draggable=true;

div.dataset.id=seat.id;

let img=document.createElement("img");

img.src="../assets/seats/green.png";

div.appendChild(img);

div.addEventListener("dragstart",dragStart);

div.addEventListener("dragover",dragOver);

div.addEventListener("drop",dropSeat);

layout.appendChild(div);

});

}

let dragged=null;

function dragStart(e){

dragged=e.target;

}

function dragOver(e){

e.preventDefault();

}

function dropSeat(e){

e.preventDefault();

if(e.target.classList.contains("seat")){

let temp=e.target.innerHTML;

e.target.innerHTML=dragged.innerHTML;

dragged.innerHTML=temp;

}

}

function addSeat(){

fetch("api/add-seat.php")
.then(()=>loadLayout());

}

function saveLayout(){

let order=[];

document.querySelectorAll(".seat").forEach((seat,index)=>{

order.push({
id:seat.dataset.id,
position:index
})

})

fetch("api/update-seat-position.php",{
method:"POST",
headers:{
"Content-Type":"application/json"
},
body:JSON.stringify(order)
})

.then(()=>alert("Layout Saved"));

}

loadLayout();

</script>

</body>
</html>