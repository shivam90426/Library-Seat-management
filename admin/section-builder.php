<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">
<title>Section Builder</title>

<link rel="stylesheet" href="style.css">

<style>

.builder{
position:relative;
width:1000px;
height:600px;
background:#e2e8f0;
border-radius:15px;
margin-top:20px;
}

.section{
position:absolute;
background:#2563eb;
color:white;
padding:15px;
border-radius:10px;
cursor:move;
text-align:center;
font-weight:600;
}

</style>

</head>

<body>

<?php include "includes/admin_sidebar.php"; ?>

<div class="main">

<?php include "includes/admin_header.php"; ?>

<h2>Section Layout Builder</h2>

<div id="builder" class="builder"></div>

</div>

<script>

const builder=document.getElementById("builder")

fetch("api/get-sections.php")
.then(res=>res.json())
.then(data=>{

data.forEach(section=>{

let div=document.createElement("div")

div.className="section"

div.innerText=section.name

div.style.left=section.pos_x+"px"
div.style.top=section.pos_y+"px"

div.style.width=(section.width*80)+"px"
div.style.height=(section.height*60)+"px"

div.dataset.id=section.id

div.draggable=true

div.addEventListener("dragend",savePosition)

builder.appendChild(div)

})

})

function savePosition(e){

let id=e.target.dataset.id

let x=e.target.offsetLeft
let y=e.target.offsetTop

fetch("api/update-section.php",{

method:"POST",

headers:{
"Content-Type":"application/json"
},

body:JSON.stringify({
id:id,
x:x,
y:y
})

})

}

</script>

</body>
</html>