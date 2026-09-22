<?php
session_start();

// Check user authentication
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Include your database connection file (Adjust path if necessary, e.g., "../includes/db.php")
// require_once "../includes/db.php";

// 1. Capture filter parameters from the URL query string
$exportType = $_GET['type'] ?? 'csv';
$search     = trim($_GET['search'] ?? '');
$status     = trim($_GET['status'] ?? '');
$dateFrom   = trim($_GET['date_from'] ?? '');
$dateTo     = trim($_GET['date_to'] ?? '');

/*
  --------------------------------------------------------------------------
  2. BUILD YOUR DATABASE QUERY DYNAMICALLY
  --------------------------------------------------------------------------
  Example SQL logic structure:
  
  $sql = "SELECT mrn, patient_name, procedure_name, surgeon_name, schedule_date, status 
          FROM patients WHERE 1=1";
  $params = [];

  if (!empty($search)) {
      $sql .= " AND (mrn LIKE ? OR patient_name LIKE ? OR procedure_name LIKE ?)";
      $params[] = "%$search%";
      $params[] = "%$search%";
      $params[] = "%$search%";
  }
  if (!empty($status)) {
      $sql .= " AND status = ?";
      $params[] = $status;
  }
  if (!empty($dateFrom)) {
      $sql .= " AND DATE(schedule_date) >= ?";
      $params[] = $dateFrom;
  }
  if (!empty($dateTo)) {
      $sql .= " AND DATE(schedule_date) <= ?";
      $params[] = $dateTo;
  }

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
*/

// --- MOCK DATA FOR DEMO PURPOSES (Remove this once your DB is connected) ---
$patients = [
    ['mrn' => 'MRN-2026-0812', 'patient_name' => 'Eleanor Vance', 'procedure' => 'Laparoscopic Cholecystectomy', 'surgeon' => 'Dr. Sarah Jenkins', 'date_time' => '2026-09-20 08:30:00', 'status' => 'Scheduled'],
    ['mrn' => 'MRN-2026-0813', 'patient_name' => 'Marcus Brody', 'procedure' => 'Coronary Artery Bypass', 'surgeon' => 'Dr. Alan Grant', 'date_time' => '2026-09-20 10:15:00', 'status' => 'Scheduled'],
    ['mrn' => 'MRN-2026-0798', 'patient_name' => 'Clara Oswald', 'procedure' => 'Appendectomy', 'surgeon' => 'Dr. Sarah Jenkins', 'date_time' => '2026-09-19 14:00:00', 'status' => 'Completed'],
    ['mrn' => 'MRN-2026-0754', 'patient_name' => 'Arthur Pendelton', 'procedure' => 'Total Knee Arthroplasty', 'surgeon' => 'Dr. Robert Chen', 'date_time' => '2026-09-18 09:00:00', 'status' => 'Cancelled'],
];
// --------------------------------------------------------------------------

$filename = "Patient_Report_" . date('Y-m-d_Hi');

// Handle Export Formats
if ($exportType === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    $output = fopen('php://output', 'w');
    // Add CSV Headers
    fputcsv($output, ['MRN', 'Patient Name', 'Procedure', 'Surgeon', 'Date & Time', 'Status']);
    
    foreach ($patients as $row) {
        fputcsv($output, [
            $row['mrn'], 
            $row['patient_name'], 
            $row['procedure'] ?? $row['procedure_name'] ?? '', 
            $row['surgeon'] ?? $row['surgeon_name'] ?? '', 
            $row['date_time'] ?? $row['schedule_date'] ?? '', 
            $row['status']
        ]);
    }
    fclose($output);
    exit;

} elseif ($exportType === 'excel') {
    // Generates a clean HTML table format readable by Microsoft Excel (.xls)
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    
    echo "<table border='1'>";
    echo "<tr style='background-color: #0f4c3a; color: #ffffff;'>";
    echo "<th>MRN</th><th>Patient Name</th><th>Procedure</th><th>Surgeon</th><th>Date & Time</th><th>Status</th>";
    echo "</tr>";
    
    foreach ($patients as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['mrn']) . "</td>";
        echo "<td>" . htmlspecialchars($row['patient_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['procedure'] ?? $row['procedure_name'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['surgeon'] ?? $row['surgeon_name'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['date_time'] ?? $row['schedule_date'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    exit;

} elseif ($exportType === 'pdf') {
    // If you use a library like Dompdf or TCPDF, handle PDF generation here.
    // For standard implementation, fallback to printing a clean styled window:
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Patient Report PDF Export</title>
        <style>
            body { font-family: Arial, sans-serif; color: #1e293b; padding: 20px; }
            h2 { color: #0f4c3a; margin-bottom: 5px; }
            p { font-size: 12px; color: #64748b; margin-bottom: 20px; }
            table { width: 100%; border-collapse: collapse; font-size: 12px; }
            th, td { border: 1px solid #cbd5e1; padding: 8px 12px; text-align: left; }
            th { background-color: #f1f5f9; color: #0f4c3a; }
        </style>
    </head>
    <body onload="window.print();">
        <h2>OR Intelligence System - Patient Report</h2>
        <p>Generated on: <?php echo date('F j, Y, g:i a'); ?></p>
        <table>
            <thead>
                <tr>
                    <th>MRN</th>
                    <th>Patient Name</th>
                    <th>Procedure</th>
                    <th>Surgeon</th>
                    <th>Date & Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($patients as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['mrn']); ?></td>
                    <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['procedure'] ?? $row['procedure_name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['surgeon'] ?? $row['surgeon_name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['date_time'] ?? $row['schedule_date'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
    exit;
}