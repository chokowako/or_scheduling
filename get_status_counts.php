<?php
// Enable error logging for debugging, but keep output clean
error_reporting(E_ALL);
ini_set('display_errors', 0); // Keep 0 so it doesn't break JSON output

date_default_timezone_set('Asia/Manila');
header('Content-Type: application/json');
header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: 0"); 

// TEMPORARILY turn errors ON so we can see why it's crashing
error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set('Asia/Manila');
header('Content-Type: application/json');

try {
    // Include your database connection file
   require_once 'config/database.php';

    // Ensure $pdo exists
    if (!isset($pdo)) {
        throw new Exception("Database connection variable \$pdo is not defined in db.php");
    }

    $status_scheduled_count = 0;
    $status_inprogress_count = 0;
    $status_completed_count = 0;
    $status_cancelled_count = 0;

    $today_date = date('Y-m-d');

    // 1. Get Completed, Cancelled, and Delayed counts directly from DB
    $ccStmt = $pdo->prepare("
        SELECT status, COUNT(*) as cnt 
        FROM or_schedules 
        WHERE surgery_date = ? 
        AND status IN ('Completed', 'Cancelled', 'Delayed')
        GROUP BY status
    ");
    $ccStmt->execute([$today_date]);
    while ($row = $ccStmt->fetch(PDO::FETCH_ASSOC)) {
        $st = strtolower(trim($row['status']));
        if (strpos($st, 'complet') !== false) {
            $status_completed_count = (int)$row['cnt'];
        } elseif (strpos($st, 'cancel') !== false || strpos($st, 'delay') !== false) {
            $status_cancelled_count = (int)$row['cnt'];
        }
    }

    // 2. Fetch active/scheduled cases for today
    $schStmt = $pdo->prepare("
        SELECT start_time, end_time, status 
        FROM or_schedules 
        WHERE surgery_date = ? 
        AND status NOT IN ('Completed', 'Cancelled', 'Delayed')
    ");
    $schStmt->execute([$today_date]);
    $today_schedules = $schStmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Get current time as an integer (e.g., 1406)
    $current_time_num = (int)date('Hi');

    foreach ($today_schedules as $schedule) {
        $raw_start = trim($schedule['start_time'] ?? '');
        $raw_end = trim($schedule['end_time'] ?? '');

        if (!empty($raw_start)) {
            $start_num = (int)date('Hi', strtotime($raw_start));
            $end_num = !empty($raw_end) ? (int)date('Hi', strtotime($raw_end)) : ($start_num + 100);

            if ($current_time_num >= $start_num && $current_time_num <= $end_num) {
                $status_inprogress_count++;
            } elseif ($current_time_num < $start_num) {
                $status_scheduled_count++;
            } else {
                $status_inprogress_count++;
            }
        } else {
            $status_scheduled_count++;
        }
    }

    // Output valid JSON
    echo json_encode([
        'success' => true,
        'scheduled' => $status_scheduled_count,
        'inprogress' => $status_inprogress_count,
        'completed' => $status_completed_count,
        'cancelled' => $status_cancelled_count
    ]);

} catch (Exception $e) {
    // If anything fails, return the error safely as JSON instead of a 500 crash
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}