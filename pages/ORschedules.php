<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once "../config/database.php";

$conn = $pdo;

$today = date('Y-m-d');
$displayDate = date('l, F d, Y');

/* AJAX Fetch Endpoint */
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json');
    
    $sql = "
        SELECT
            s.schedule_id,
            s.patient_id,
            s.surgery_date,
            s.date_end,
            s.start_time,
            s.end_time,
            s.room_id,
            s.is_stat,
            s.priority,
            s.status,
            r.room_name,
            pr.procedure_name,
            CONCAT_WS(' ', surgeon.first_name, NULLIF(surgeon.middle_name, ''), surgeon.last_name, NULLIF(surgeon.suffix_name, '')) AS surgeon_name
        FROM or_schedules s
        LEFT JOIN operating_rooms r ON r.room_id = s.room_id
        LEFT JOIN procedures pr ON pr.procedure_id = s.procedure_id
        LEFT JOIN DOCTORS surgeon ON surgeon.doctor_id = s.surgeon_doctor_id
        WHERE (
            s.surgery_date = :today
            OR (s.surgery_date < :today_range AND s.date_end IS NOT NULL AND s.date_end >= :today_end)
        )
        AND (s.status IS NULL OR s.status <> 'Cancelled')
        ORDER BY r.room_name ASC, s.surgery_date ASC, s.start_time ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':today'       => $today,
        ':today_range' => $today,
        ':today_end'   => $today
    ]);

    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $schedulesByRoom = [];
    foreach ($schedules as $schedule) {
        $roomId = $schedule['room_id'];
        if (!isset($schedulesByRoom[$roomId])) {
            $schedulesByRoom[$roomId] = [
                'room_name' => $schedule['room_name'] ?? 'Unassigned Room',
                'schedules' => []
            ];
        }
        $schedulesByRoom[$roomId]['schedules'][] = $schedule;
    }

    echo json_encode([
        'rooms' => array_values($schedulesByRoom),
        'totalRooms' => count($schedulesByRoom),
        'totalOperations' => count($schedules),
        'totalStat' => array_reduce($schedules, fn($acc, $item) => $acc + ((int)$item['is_stat'] === 1 ? 1 : 0), 0),
        'serverTime' => date('h:i:s A')
    ]);
    exit;
}

function formatTime($time) {
    return empty($time) ? '' : date('g:i A', strtotime($time));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OR Schedule Board</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/ORschedules.css?v=2026-v9">
</head>
<body>

<div class="or-dashboard">
    <!-- Header -->
    <header class="dashboard-header">
        <div class="header-left">
            <div class="brand-badge">
                <i class="bi bi-hospital"></i>
            </div>
            <div>
                <h1 class="header-title">Ciudad Medical Zamboanga</h1>
                <p class="header-subtitle">Operating Room Schedule Board</p>
            </div>
        </div>

        <div class="header-center">
            <div class="date-chip">
                <i class="bi bi-calendar-event"></i>
                <span><?= htmlspecialchars($displayDate) ?></span>
            </div>
        </div>

        <div class="header-right">
            <div class="sync-status" id="syncStatus">
                <i class="bi bi-arrow-repeat spin-icon"></i>
                <span id="lastSyncText">Syncing...</span>
            </div>
            <div class="live-pill">
                <span class="pulse-dot"></span>
                <span>LIVE SYSTEM</span>
            </div>
            <div id="currentTime" class="clock-display"><?= date('h:i:s A') ?></div>
        </div>
    </header>

    <!-- High-Priority Alert Banner (STAT Alert) -->
    <div id="statBanner" class="stat-alert-banner" style="display: none;">
        <div class="stat-alert-content">
            <i class="bi bi-exclamation-triangle-fill flashing-icon"></i>
            <span class="stat-alert-text">CRITICAL EMERGENCY / STAT CASE IN PROGRESS OR QUEUED</span>
        </div>
    </div>

    <!-- Metrics Bar -->
    <section class="metrics-bar">
        <div class="metric-card">
            <span class="metric-value" id="metricRooms">0</span>
            <span class="metric-label">Active Operating Rooms</span>
        </div>
        <div class="metric-card">
            <span class="metric-value" id="metricOperations">0</span>
            <span class="metric-label">Total Scheduled Cases</span>
        </div>
        <div class="metric-card" id="metricStatCard">
            <span class="metric-value" id="metricStat">0</span>
            <span class="metric-label">Emergency STAT Cases</span>
        </div>
    </section>

    <!-- Board Content -->
    <main class="board-container">
        <div class="schedule-table-wrapper" id="tableWrapper">
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th class="col-room">Operating Room</th>
                        <th class="col-details">Scheduled Operations & Details</th>
                    </tr>
                </thead>
                <tbody id="boardBody">
                    <!-- Dynamic rendering via JavaScript -->
                </tbody>
            </table>
        </div>
        <div id="emptyState" class="empty-state" style="display: none;">
            <i class="bi bi-calendar2-check"></i>
            <h3>No Operations Scheduled</h3>
            <p>There are no operating room cases logged for today.</p>
        </div>
    </main>

    <!-- Footer -->
    <footer class="dashboard-footer">
        <div><i class="bi bi-shield-check"></i> Ciudad Medical Zamboanga Operating Room System</div>
        <div id="footerSync">Real-Time AJAX Polling Active (Every 15s)</div>
    </footer>
</div>

<script>
let lastSyncTime = new Date();

function updateClock() {
    const now = new Date();
    let hours = now.getHours();
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12 || 12;

    const clock = document.getElementById('currentTime');
    if (clock) clock.textContent = `${String(hours).padStart(2, '0')}:${minutes}:${seconds} ${ampm}`;
    
    // Update relative sync time
    const elapsedSeconds = Math.floor((now - lastSyncTime) / 1000);
    const syncText = document.getElementById('lastSyncText');
    if (syncText) {
        if (elapsedSeconds < 5) {
            syncText.textContent = 'Updated just now';
        } else {
            syncText.textContent = `Synced ${elapsedSeconds}s ago`;
        }
    }
}

function formatTimeString(timeStr) {
    if (!timeStr) return '';
    const parts = timeStr.split(':');
    if (parts.length < 2) return timeStr;
    let hours = parseInt(parts[0], 10);
    const minutes = parts[1];
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12 || 12;
    return `${hours}:${minutes} ${ampm}`;
}

function calculateProgress(startTime, endTime) {
    if (!startTime || !endTime) return null;

    const now = new Date();
    const [startH, startM] = startTime.split(':').map(Number);
    const [endH, endM] = endTime.split(':').map(Number);

    const start = new Date(now.getFullYear(), now.getMonth(), now.getDate(), startH, startM, 0);
    const end = new Date(now.getFullYear(), now.getMonth(), now.getDate(), endH, endM, 0);

    if (now < start || now > end) return null;

    const total = end - start;
    const elapsed = now - start;
    const percentage = Math.min(100, Math.max(0, Math.round((elapsed / total) * 100)));

    return { percentage, elapsedMinutes: Math.floor(elapsed / 60000) };
}

function renderDashboard(data) {
    const tbody = document.getElementById('boardBody');
    const tableWrapper = document.getElementById('tableWrapper');
    const emptyState = document.getElementById('emptyState');
    const statBanner = document.getElementById('statBanner');
    const metricStatCard = document.getElementById('metricStatCard');

    document.getElementById('metricRooms').textContent = data.totalRooms;
    document.getElementById('metricOperations').textContent = data.totalOperations;
    document.getElementById('metricStat').textContent = data.totalStat;

    // STAT High-Priority Banner & Card Highlight
    if (data.totalStat > 0) {
        statBanner.style.display = 'block';
        metricStatCard.classList.add('highlight-stat');
    } else {
        statBanner.style.display = 'none';
        metricStatCard.classList.remove('highlight-stat');
    }

    if (data.totalRooms === 0) {
        tableWrapper.style.display = 'none';
        emptyState.style.display = 'block';
        return;
    }

    tableWrapper.style.display = 'block';
    emptyState.style.display = 'none';

    let html = '';
    data.rooms.forEach(room => {
        html += `<tr>
            <td class="room-cell">
                <div class="room-card">
                    <div class="room-icon-wrapper">
                        <i class="bi bi-door-open-fill"></i>
                    </div>
                    <div class="room-info">
                        <h2 class="room-title">${room.room_name}</h2>
                        <span class="case-badge">
                            <span class="badge-dot"></span>
                            ${room.schedules.length} ${room.schedules.length === 1 ? 'Scheduled Case' : 'Scheduled Cases'}
                        </span>
                    </div>
                </div>
            </td>
            <td class="details-cell">
                <div class="cards-list">`;

        room.schedules.forEach(schedule => {
            const isStat = parseInt(schedule.is_stat, 10) === 1;
            const priority = (schedule.priority || 'elective').toLowerCase();
            const status = (schedule.status || 'scheduled').toLowerCase().replace(/\s+/g, '-');
            const progress = calculateProgress(schedule.start_time, schedule.end_time);

            html += `
                <div class="case-card priority-${priority} status-${status} ${isStat ? 'is-stat' : ''}">
                    <div class="card-main-content">
                        <div class="card-left-info">
                            <span class="card-time">
                                <i class="bi bi-clock"></i>
                                ${formatTimeString(schedule.start_time)} - ${formatTimeString(schedule.end_time)}
                            </span>
                            <h3 class="card-procedure">${schedule.procedure_name || 'Procedure Unspecified'}</h3>
                            <div class="card-meta">
                                <span class="surgeon-info">
                                    <i class="bi bi-person-badge-fill"></i> 
                                    <strong>Surgeon:</strong> ${schedule.surgeon_name || 'Unassigned'}
                                </span>
                            </div>
                        </div>
                        <div class="card-right-badge">
                            <span class="badge-priority">
                                ${isStat ? 'STAT' : (schedule.priority || 'Elective')}
                            </span>
                        </div>
                    </div>`;

            // Visual Time-Progress Line
            if (progress !== null) {
                html += `
                    <div class="progress-container">
                        <div class="progress-bar-header">
                            <span>In Progress (${progress.elapsedMinutes} mins elapsed)</span>
                            <span>${progress.percentage}%</span>
                        </div>
                        <div class="progress-track">
                            <div class="progress-fill" style="width: ${progress.percentage}%;"></div>
                        </div>
                    </div>`;
            }

            html += `</div>`;
        });

        html += `</div></td></tr>`;
    });

    tbody.innerHTML = html;
}

// AJAX Polling
function fetchScheduleData() {
    fetch('?ajax=1')
        .then(res => res.json())
        .then(data => {
            lastSyncTime = new Date();
            renderDashboard(data);
        })
        .catch(err => console.error('Error refreshing schedule data:', err));
}

// Auto-Scroll Logic
function initAutoScroll() {
    const scrollContainer = document.querySelector('.board-container');
    let scrollSpeed = 1; 
    let scrollInterval;

    function startScroll() {
        scrollInterval = setInterval(() => {
            if (!scrollContainer) return;
            
            if (scrollContainer.scrollTop + scrollContainer.clientHeight >= scrollContainer.scrollHeight - 5) {
                setTimeout(() => { scrollContainer.scrollTop = 0; }, 2000);
            } else {
                scrollContainer.scrollTop += scrollSpeed;
            }
        }, 50);
    }

    startScroll();
}

document.addEventListener('DOMContentLoaded', () => {
    updateClock();
    setInterval(updateClock, 1000);
    fetchScheduleData();
    setInterval(fetchScheduleData, 15000); // Refresh every 15s
    initAutoScroll();
});
</script>

</body>
</html>