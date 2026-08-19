<?php
require_once "../includes/security.php";
library_system_bootstrap();
require_once "../config/db.php";

require_login('admin');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Seat Structure Builder</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Poppins,sans-serif;}
body{
height:100vh;
overflow:hidden;
padding:14px 12px;
color:#f8fafc;
background:
radial-gradient(circle at top left, rgba(14,165,233,0.18), transparent 24%),
radial-gradient(circle at top right, rgba(37,99,235,0.16), transparent 22%),
linear-gradient(145deg,#07111f,#11233b 55%, #0f172a);
}
.page{
max-width:1720px;
height:calc(100vh - 28px);
margin:0 auto;
display:grid;
grid-template-rows:auto auto 1fr;
gap:10px;
}
.topbar{
display:flex;
justify-content:space-between;
align-items:center;
gap:12px;
flex-wrap:wrap;
}
.back-link,.action-link,.ghost-btn,.primary-btn,.danger-btn{
display:inline-flex;
align-items:center;
justify-content:center;
text-decoration:none;
border:none;
cursor:pointer;
transition:.2s ease;
}
.back-link,.action-link,.ghost-btn{
padding:10px 16px;
border-radius:999px;
color:#e2e8f0;
background:rgba(255,255,255,0.07);
border:1px solid rgba(255,255,255,0.09);
}
.primary-btn{
padding:12px 18px;
border-radius:14px;
color:#fff;
background:linear-gradient(90deg,#0ea5e9,#2563eb);
font-weight:600;
box-shadow:0 14px 26px rgba(37,99,235,0.25);
}
.danger-btn{
padding:10px 14px;
border-radius:14px;
color:#fff;
background:linear-gradient(90deg,#dc2626,#b91c1c);
font-weight:600;
}
.hero{
padding:14px 18px;
border-radius:24px;
background:linear-gradient(135deg,rgba(15,23,42,0.78),rgba(30,41,59,0.64));
border:1px solid rgba(255,255,255,0.08);
box-shadow:0 22px 50px rgba(0,0,0,0.24);
}
.hero-row{
display:flex;
justify-content:space-between;
align-items:flex-start;
gap:14px;
flex-wrap:wrap;
}
.hero h1{font-size:30px;margin-bottom:6px;}
.hero p{color:#bfdbfe;line-height:1.6;max-width:820px;font-size:14px;}
.hero-strip{
display:flex;
gap:8px;
flex-wrap:wrap;
margin-top:12px;
}
.hero-chip{
display:inline-flex;
padding:7px 11px;
border-radius:999px;
font-size:12px;
background:rgba(255,255,255,0.07);
border:1px solid rgba(255,255,255,0.08);
color:#dbeafe;
}
.workspace{
display:grid;
grid-template-columns:minmax(0,1.95fr) 300px;
gap:10px;
min-height:0;
}
.panel{
min-height:0;
background:linear-gradient(180deg,rgba(15,23,42,0.72),rgba(15,23,42,0.58));
border:1px solid rgba(255,255,255,0.08);
border-radius:24px;
padding:14px;
box-shadow:0 22px 44px rgba(0,0,0,0.22);
display:flex;
flex-direction:column;
}
.panel-head{
display:flex;
justify-content:space-between;
align-items:flex-end;
gap:10px;
flex-wrap:wrap;
margin-bottom:14px;
}
.panel h2,.panel h3{margin-bottom:6px;}
.muted{color:#a5c5ea;font-size:13px;line-height:1.5;}
.board-wrap{
position:relative;
flex:1;
min-height:0;
border-radius:22px;
overflow:auto;
background:
linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px),
linear-gradient(180deg, rgba(2,6,23,0.45), rgba(15,23,42,0.35));
background-size:28px 28px,28px 28px,100% 100%;
border:1px dashed rgba(125,211,252,0.14);
}
.board{
position:relative;
width:100%;
height:100%;
min-height:760px;
min-width:100%;
}
.section-block{
position:absolute;
display:flex;
flex-direction:column;
gap:12px;
padding:14px;
border-radius:22px;
background:linear-gradient(180deg,rgba(30,41,59,0.95),rgba(15,23,42,0.92));
border:1px solid rgba(255,255,255,0.08);
box-shadow:0 18px 36px rgba(0,0,0,0.22);
cursor:grab;
user-select:none;
overflow:hidden;
}
.section-block.dragging{
opacity:.75;
cursor:grabbing;
box-shadow:0 24px 42px rgba(14,165,233,0.16);
}
.section-block.resizing{
cursor:nwse-resize;
}
.section-block.selected{
border-color:rgba(96,165,250,0.78);
box-shadow:0 0 0 1px rgba(96,165,250,0.46), 0 18px 36px rgba(37,99,235,0.18);
}
.block-head{
display:flex;
justify-content:space-between;
align-items:flex-start;
gap:10px;
}
.block-title{
display:grid;
gap:6px;
}
.block-title h4{
font-size:17px;
}
.section-meta{
display:flex;
gap:6px;
flex-wrap:wrap;
}
.badge{
display:inline-flex;
padding:5px 9px;
border-radius:999px;
font-size:11px;
background:rgba(14,165,233,0.18);
border:1px solid rgba(56,189,248,0.18);
color:#bae6fd;
}
.move-tip{
font-size:11px;
color:#93c5fd;
padding:6px 8px;
border-radius:999px;
background:rgba(255,255,255,0.05);
}
.seat-grid{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(40px,1fr));
gap:6px;
}
.resize-handle{
position:absolute;
right:8px;
bottom:8px;
width:18px;
height:18px;
border-radius:6px;
background:linear-gradient(135deg,#38bdf8,#2563eb);
box-shadow:0 8px 18px rgba(37,99,235,0.32);
cursor:nwse-resize;
z-index:3;
}
.resize-handle::before{
content:"";
position:absolute;
inset:4px;
border-right:2px solid rgba(255,255,255,0.9);
border-bottom:2px solid rgba(255,255,255,0.9);
}
.seat-chip{
height:38px;
border-radius:14px;
display:flex;
align-items:center;
justify-content:center;
background:rgba(255,255,255,0.06);
border:1px solid rgba(255,255,255,0.06);
font-size:10px;
font-weight:600;
cursor:pointer;
transition:.16s ease;
padding:0 4px;
text-align:center;
line-height:1.2;
}
.seat-chip:hover{
transform:translateY(-1px);
border-color:rgba(125,211,252,0.2);
}
.seat-chip.selected{
background:rgba(37,99,235,0.18);
border-color:rgba(96,165,250,0.78);
}
.seat-chip.inactive{
opacity:.55;
}
.seat-chip.maintenance{
background:rgba(245,158,11,0.16);
border-color:rgba(245,158,11,0.32);
}
.board-actions{
display:flex;
justify-content:space-between;
align-items:center;
gap:12px;
flex-wrap:wrap;
margin-top:14px;
}
#statusMessage{
font-size:13px;
color:#c7dfff;
padding:10px 14px;
border-radius:999px;
background:rgba(255,255,255,0.06);
border:1px solid rgba(255,255,255,0.06);
}
.inspector{
display:flex;
flex-direction:column;
gap:14px;
overflow:auto;
min-height:0;
}
.inspector-card{
display:grid;
gap:14px;
padding:16px;
border-radius:20px;
background:rgba(2,6,23,0.34);
border:1px solid rgba(255,255,255,0.06);
}
.inspector-empty{
padding:18px;
border-radius:18px;
background:rgba(2,6,23,0.34);
border:1px dashed rgba(186,230,253,0.14);
color:#cbd5e1;
line-height:1.7;
}
.field-grid{
display:grid;
grid-template-columns:repeat(2,minmax(0,1fr));
gap:10px;
}
.field{
display:grid;
gap:6px;
}
.field label{
font-size:12px;
color:#bfdbfe;
}
.field input,.field select{
width:100%;
padding:10px 12px;
border-radius:14px;
border:1px solid rgba(255,255,255,0.08);
background:rgba(2,6,23,0.52);
color:#fff;
outline:none;
}
.field input:focus,.field select:focus{
border-color:rgba(56,189,248,0.55);
box-shadow:0 0 0 3px rgba(14,165,233,0.12);
}
.preview-row{
display:flex;
justify-content:space-between;
align-items:center;
gap:12px;
padding:14px;
border-radius:18px;
background:linear-gradient(135deg,rgba(14,165,233,0.18),rgba(37,99,235,0.14));
border:1px solid rgba(96,165,250,0.2);
}
.pill-row{
display:flex;
gap:8px;
flex-wrap:wrap;
}
.flag{
display:inline-flex;
padding:4px 8px;
border-radius:999px;
font-size:11px;
}
.flag.active{background:rgba(22,163,74,0.18);color:#86efac;}
.flag.maintenance{background:rgba(245,158,11,0.18);color:#fcd34d;}
.flag.inactive{background:rgba(220,38,38,0.18);color:#fca5a5;}
@media(max-width:1180px){
  body{height:auto;overflow:auto;}
  .page{height:auto;grid-template-rows:auto auto auto;}
  .workspace{grid-template-columns:1fr;}
  .board-wrap{height:70vh;}
}
@media(max-width:720px){
  .field-grid{grid-template-columns:1fr;}
}
</style>
</head>
<body>
<div class="page">
    <div class="topbar">
        <a href="dashboard.php" class="back-link">Back to Dashboard</a>
        <a href="section-builder.php" class="action-link">Open Structure View</a>
    </div>

    <div class="hero">
        <div class="hero-row">
            <div>
                <h1>Seat Structure Builder</h1>
                <p>Scrolling ko kam kiya gaya hai. Ab section blocks board par cursor se hold karke manually place karo, aur seats ko same group ke andar ya dusre group me set karo.</p>
            </div>
            <div class="hero-strip">
                <span class="hero-chip">Less page scrolling</span>
                <span class="hero-chip">Drag sections manually</span>
                <span class="hero-chip">Compact seat layout</span>
            </div>
        </div>
    </div>

    <div class="workspace">
        <div class="panel">
            <div class="panel-head">
                <div>
                    <h2>Manual Structure Board</h2>
                    <p class="muted">Section block ko hold karo aur board par jahan chaho wahan place karo. Save karne par wahi structure store ho jayega.</p>
                </div>
                <span class="move-tip">Drag section cards to reposition</span>
            </div>

            <div class="board-wrap">
                <div id="board" class="board"></div>
            </div>

            <div class="board-actions">
                <button class="primary-btn" type="button" onclick="saveLayout()">Save Structure</button>
                <span id="statusMessage"></span>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">
                <div>
                    <h3>Inspector</h3>
                    <p class="muted">Selected section ya seat ko yahin se compact mode me edit karo.</p>
                </div>
            </div>
            <div id="inspectorContent" class="inspector">
                <div class="inspector-empty">Select any section block or seat chip to edit it.</div>
            </div>
        </div>
    </div>
</div>

<script>
const csrfToken = "<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>";
let layoutData = [];
let selectedSeatId = null;
let selectedSectionCode = null;
let dragSeatId = null;
let draggingSection = null;
const board = document.getElementById("board");
const boardWrap = document.querySelector(".board-wrap");
const inspectorContent = document.getElementById("inspectorContent");
const statusMessage = document.getElementById("statusMessage");
const WIDTH_UNIT = 40;
const HEIGHT_UNIT = 84;
const MIN_SECTION_WIDTH = 74;
const MIN_SECTION_HEIGHT = 170;
const SECTION_HORIZONTAL_PADDING = 26;
const BOARD_PADDING = 48;

function loadLayout() {
    fetch("api/get-layout.php")
        .then(res => res.json())
        .then(data => {
            layoutData = data.sections || [];
            renderBoard();
            renderInspector();
        });
}

function renderBoard() {
    board.innerHTML = "";
    updateBoardSize();

    layoutData.forEach(section => {
        const block = document.createElement("div");
        block.className = "section-block" + (section.section_code === selectedSectionCode ? " selected" : "");
        block.dataset.sectionCode = section.section_code;
        block.style.left = `${Number(section.pos_x || 0)}px`;
        block.style.top = `${Number(section.pos_y || 0)}px`;
        block.style.width = `${getSectionPixelWidth(section)}px`;
        block.style.minHeight = `${getSectionPixelHeight(section)}px`;

        block.innerHTML = `
            <div class="block-head">
                <div class="block-title">
                    <h4>${escapeHtml(section.name)}</h4>
                    <div class="section-meta">
                        <span class="badge">${escapeHtml(section.section_code)}</span>
                        <span class="badge">${escapeHtml(section.seat_type)}</span>
                        <span class="badge">${section.seats.length} seats</span>
                    </div>
                </div>
                <span class="move-tip">Hold & move</span>
            </div>
        `;

        const seatGrid = document.createElement("div");
        seatGrid.className = "seat-grid";
        seatGrid.dataset.sectionCode = section.section_code;
        seatGrid.addEventListener("dragover", event => event.preventDefault());
        seatGrid.addEventListener("drop", () => moveSeatToSection(section.section_code));

        section.seats.forEach(seat => {
            const chip = document.createElement("div");
            let classes = "seat-chip";
            if (Number(seat.id) === Number(selectedSeatId)) classes += " selected";
            if (!Number(seat.is_active)) classes += " inactive";
            if (Number(seat.is_maintenance)) classes += " maintenance";
            chip.className = classes;
            chip.draggable = true;
            chip.textContent = seat.seat_no;
            chip.title = `${seat.seat_no} (${seat.seat_type})`;

            chip.addEventListener("click", event => {
                event.stopPropagation();
                selectedSeatId = seat.id;
                selectedSectionCode = section.section_code;
                renderBoard();
                renderInspector();
            });
            chip.addEventListener("dragstart", event => {
                event.stopPropagation();
                dragSeatId = seat.id;
            });
            chip.addEventListener("dragend", () => {
                dragSeatId = null;
            });

            seatGrid.appendChild(chip);
        });

        block.appendChild(seatGrid);
        const resizeHandle = document.createElement("div");
        resizeHandle.className = "resize-handle";
        block.appendChild(resizeHandle);

        block.addEventListener("click", () => {
            selectedSectionCode = section.section_code;
            selectedSeatId = null;
            renderBoard();
            renderInspector();
        });

        enableSectionDragging(block, section, resizeHandle);
        board.appendChild(block);
    });
}

function updateBoardSize() {
    const wrapWidth = boardWrap ? boardWrap.clientWidth : 0;
    const wrapHeight = boardWrap ? boardWrap.clientHeight : 0;

    let requiredWidth = wrapWidth;
    let requiredHeight = Math.max(760, wrapHeight);

    layoutData.forEach(section => {
        const sectionRight = Number(section.pos_x || 0) + getSectionPixelWidth(section) + BOARD_PADDING;
        const sectionBottom = Number(section.pos_y || 0) + getSectionPixelHeight(section) + BOARD_PADDING;
        requiredWidth = Math.max(requiredWidth, sectionRight);
        requiredHeight = Math.max(requiredHeight, sectionBottom);
    });

    board.style.width = `${Math.max(requiredWidth, 760)}px`;
    board.style.height = `${requiredHeight}px`;
}

function enableSectionDragging(element, section, resizeHandle) {
    let startX = 0;
    let startY = 0;
    let originX = 0;
    let originY = 0;
    let originWidth = 0;
    let originHeight = 0;

    resizeHandle.addEventListener("pointerdown", event => {
        event.stopPropagation();
        selectedSectionCode = section.section_code;
        selectedSeatId = null;
        renderInspector();

        startX = event.clientX;
        startY = event.clientY;
        originWidth = getSectionPixelWidth(section);
        originHeight = getSectionPixelHeight(section);

        element.classList.add("resizing");
        resizeHandle.setPointerCapture(event.pointerId);

        const moveHandler = moveEvent => {
            const boardRect = board.getBoundingClientRect();
            const sectionLeft = Number(section.pos_x || 0);
            const sectionTop = Number(section.pos_y || 0);
            const nextWidthPx = Math.max(MIN_SECTION_WIDTH, originWidth + (moveEvent.clientX - startX));
            const nextHeightPx = Math.max(MIN_SECTION_HEIGHT, originHeight + (moveEvent.clientY - startY));
            const maxWidthPx = Math.max(MIN_SECTION_WIDTH, boardRect.width - sectionLeft - 8);
            const maxHeightPx = Math.max(MIN_SECTION_HEIGHT, boardRect.height - sectionTop - 8);

            section.width = Math.max(1, Math.round((Math.min(nextWidthPx, maxWidthPx) - SECTION_HORIZONTAL_PADDING) / WIDTH_UNIT));
            section.height = Math.max(2, Math.round(Math.min(nextHeightPx, maxHeightPx) / HEIGHT_UNIT));

            element.style.width = `${getSectionPixelWidth(section)}px`;
            element.style.minHeight = `${getSectionPixelHeight(section)}px`;
        };

        const upHandler = upEvent => {
            element.classList.remove("resizing");
            resizeHandle.releasePointerCapture(upEvent.pointerId);
            resizeHandle.removeEventListener("pointermove", moveHandler);
            resizeHandle.removeEventListener("pointerup", upHandler);
            resizeHandle.removeEventListener("pointercancel", upHandler);
            renderBoard();
            renderInspector();
        };

        resizeHandle.addEventListener("pointermove", moveHandler);
        resizeHandle.addEventListener("pointerup", upHandler);
        resizeHandle.addEventListener("pointercancel", upHandler);
    });

    element.addEventListener("pointerdown", event => {
        if (event.target.closest(".seat-chip") || event.target.closest(".resize-handle")) {
            return;
        }

        draggingSection = section.section_code;
        selectedSectionCode = section.section_code;
        selectedSeatId = null;
        renderInspector();

        startX = event.clientX;
        startY = event.clientY;
        originX = Number(section.pos_x || 0);
        originY = Number(section.pos_y || 0);

        element.classList.add("dragging");
        element.setPointerCapture(event.pointerId);

        const moveHandler = moveEvent => {
            if (draggingSection !== section.section_code) {
                return;
            }

            const boardRect = board.getBoundingClientRect();
            const blockWidth = getSectionPixelWidth(section);
            const blockHeight = getSectionPixelHeight(section);
            const nextX = Math.max(0, Math.min(boardRect.width - blockWidth, originX + (moveEvent.clientX - startX)));
            const nextY = Math.max(0, Math.min(boardRect.height - blockHeight, originY + (moveEvent.clientY - startY)));
            section.pos_x = nextX;
            section.pos_y = nextY;
            element.style.left = `${nextX}px`;
            element.style.top = `${nextY}px`;
        };

        const upHandler = upEvent => {
            element.classList.remove("dragging");
            draggingSection = null;
            element.releasePointerCapture(upEvent.pointerId);
            element.removeEventListener("pointermove", moveHandler);
            element.removeEventListener("pointerup", upHandler);
            element.removeEventListener("pointercancel", upHandler);
            renderBoard();
            renderInspector();
        };

        element.addEventListener("pointermove", moveHandler);
        element.addEventListener("pointerup", upHandler);
        element.addEventListener("pointercancel", upHandler);
    });
}

function renderInspector() {
    const seat = findSelectedSeat();
    const section = findSelectedSection();

    if (seat) {
        inspectorContent.innerHTML = `
            <div class="inspector-card">
                <div class="preview-row">
                    <div>
                        <strong style="display:block;font-size:22px;">${escapeHtml(seat.seat_no)}</strong>
                        <span class="muted">${escapeHtml(seat.seat_type)} seat in ${escapeHtml(seat.section_name)}</span>
                    </div>
                    <div class="pill-row">
                        <span class="flag ${seat.is_active ? 'active' : 'inactive'}">${seat.is_active ? 'Active' : 'Inactive'}</span>
                        ${seat.is_maintenance ? '<span class="flag maintenance">Maintenance</span>' : ''}
                    </div>
                </div>
                <div class="field-grid">
                    <div class="field">
                        <label>Seat Number</label>
                        <input type="text" id="seatNoInput" value="${escapeHtml(seat.seat_no)}">
                    </div>
                    <div class="field">
                        <label>Section</label>
                        <select id="seatSectionInput">
                            ${layoutData.map(item => `<option value="${escapeHtml(item.section_code)}" ${item.section_code === seat.section_name ? 'selected' : ''}>${escapeHtml(item.name)}</option>`).join("")}
                        </select>
                    </div>
                    <div class="field">
                        <label>Status</label>
                        <select id="seatActiveInput">
                            <option value="1" ${seat.is_active ? 'selected' : ''}>Active</option>
                            <option value="0" ${!seat.is_active ? 'selected' : ''}>Inactive</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Maintenance</label>
                        <select id="seatMaintenanceInput">
                            <option value="0" ${!seat.is_maintenance ? 'selected' : ''}>Normal</option>
                            <option value="1" ${seat.is_maintenance ? 'selected' : ''}>Under Maintenance</option>
                        </select>
                    </div>
                </div>
                <div class="board-actions">
                    <button class="primary-btn" type="button" onclick="applySeatChanges()">Apply Changes</button>
                    <button class="danger-btn" type="button" onclick="deleteSelectedSeat()">Delete Seat</button>
                </div>
            </div>
        `;
        return;
    }

    if (section) {
        inspectorContent.innerHTML = `
            <div class="inspector-card">
                <div class="preview-row">
                    <div>
                        <strong style="display:block;font-size:22px;">${escapeHtml(section.name)}</strong>
                        <span class="muted">${escapeHtml(section.section_code)} section | ${escapeHtml(section.seat_type)} seats</span>
                    </div>
                    <div class="pill-row">
                        <span class="flag active">${section.seats.length} seats</span>
                    </div>
                </div>
                <div class="field-grid">
                    <div class="field">
                        <label>Section Name</label>
                        <input type="text" id="sectionNameInput" value="${escapeHtml(section.name)}">
                    </div>
                    <div class="field">
                        <label>Section Code</label>
                        <input type="text" value="${escapeHtml(section.section_code)}" disabled>
                    </div>
                    <div class="field">
                        <label>Width</label>
                        <input type="number" id="sectionWidthInput" min="1" value="${Number(section.width || 4)}">
                    </div>
                    <div class="field">
                        <label>Height</label>
                        <input type="number" id="sectionHeightInput" min="1" value="${Number(section.height || 4)}">
                    </div>
                    <div class="field">
                        <label>Position X</label>
                        <input type="number" id="sectionPosXInput" value="${Number(section.pos_x || 0)}">
                    </div>
                    <div class="field">
                        <label>Position Y</label>
                        <input type="number" id="sectionPosYInput" value="${Number(section.pos_y || 0)}">
                    </div>
                </div>
                <div class="board-actions">
                    <button class="ghost-btn" type="button" onclick="addSeat('${escapeJs(section.section_code)}')">Add Seat</button>
                    <button class="primary-btn" type="button" onclick="applySectionChanges()">Apply Section</button>
                </div>
            </div>
        `;
        return;
    }

    inspectorContent.innerHTML = `<div class="inspector-empty">Select any section block or seat chip to edit it.</div>`;
}

function findSelectedSeat() {
    for (const section of layoutData) {
        const seat = section.seats.find(item => Number(item.id) === Number(selectedSeatId));
        if (seat) {
            return seat;
        }
    }
    return null;
}

function findSelectedSection() {
    return layoutData.find(item => item.section_code === selectedSectionCode) || null;
}

function moveSeatToSection(targetSectionCode) {
    if (!dragSeatId) {
        return;
    }

    let movingSeat = null;

    layoutData.forEach(section => {
        const index = section.seats.findIndex(seat => Number(seat.id) === Number(dragSeatId));
        if (index !== -1) {
            movingSeat = section.seats.splice(index, 1)[0];
        }
    });

    if (!movingSeat) {
        return;
    }

    const targetSection = layoutData.find(section => section.section_code === targetSectionCode);
    if (!targetSection) {
        return;
    }

    movingSeat.section_name = targetSectionCode;
    movingSeat.seat_type = targetSection.seat_type;
    targetSection.seats.push(movingSeat);

    selectedSectionCode = targetSectionCode;
    selectedSeatId = movingSeat.id;
    renderBoard();
    renderInspector();
}

function applySeatChanges() {
    const seat = findSelectedSeat();
    if (!seat) {
        return;
    }

    const nextSeatNo = document.getElementById("seatNoInput").value.trim();
    const nextSection = document.getElementById("seatSectionInput").value;
    const nextActive = Number(document.getElementById("seatActiveInput").value);
    const nextMaintenance = Number(document.getElementById("seatMaintenanceInput").value);

    if (nextSeatNo) {
        seat.seat_no = nextSeatNo;
    }
    seat.is_active = nextActive;
    seat.is_maintenance = nextMaintenance;

    if (nextSection !== seat.section_name) {
        selectedSeatId = seat.id;
        dragSeatId = seat.id;
        moveSeatToSection(nextSection);
        dragSeatId = null;
    } else {
        renderBoard();
        renderInspector();
    }
}

function applySectionChanges() {
    const section = findSelectedSection();
    if (!section) {
        return;
    }

    section.name = document.getElementById("sectionNameInput").value.trim() || section.name;
    section.width = Math.max(1, Number(document.getElementById("sectionWidthInput").value || section.width));
    section.height = Math.max(1, Number(document.getElementById("sectionHeightInput").value || section.height));
    section.pos_x = Math.max(0, Number(document.getElementById("sectionPosXInput").value || section.pos_x));
    section.pos_y = Math.max(0, Number(document.getElementById("sectionPosYInput").value || section.pos_y));

    renderBoard();
    renderInspector();
}

function getSectionPixelWidth(section) {
    return Math.max(MIN_SECTION_WIDTH, SECTION_HORIZONTAL_PADDING + (Number(section.width || 4) * WIDTH_UNIT));
}

function getSectionPixelHeight(section) {
    return Math.max(MIN_SECTION_HEIGHT, Number(section.height || 2) * HEIGHT_UNIT);
}

function addSeat(sectionCode) {
    fetch("api/add-seat.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-Token": csrfToken
        },
        body: JSON.stringify({ section_code: sectionCode })
    })
    .then(res => res.json())
    .then(() => {
        selectedSectionCode = sectionCode;
        statusMessage.textContent = "Seat added. Save when you're ready.";
        loadLayout();
    });
}

function deleteSelectedSeat() {
    if (!selectedSeatId) {
        return;
    }

    fetch("api/delete-seat.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-Token": csrfToken
        },
        body: JSON.stringify({ id: selectedSeatId })
    })
    .then(res => res.json())
    .then(() => {
        selectedSeatId = null;
        statusMessage.textContent = "Seat deleted.";
        loadLayout();
    });
}

function saveLayout() {
    statusMessage.textContent = "Saving structure...";

    fetch("api/save-layout.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-Token": csrfToken
        },
        body: JSON.stringify({ sections: layoutData })
    })
    .then(res => res.json())
    .then(data => {
        statusMessage.textContent = data.status === "success" ? "Structure saved successfully." : (data.message || "Unable to save layout.");
        if (data.status === "success") {
            loadLayout();
        }
    })
    .catch(() => {
        statusMessage.textContent = "Unable to save layout.";
    });
}

function escapeHtml(value) {
    return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function escapeJs(value) {
    return String(value ?? "").replace(/\\/g, "\\\\").replace(/'/g, "\\'");
}

loadLayout();
window.addEventListener("resize", updateBoardSize);
</script>
</body>
</html>
