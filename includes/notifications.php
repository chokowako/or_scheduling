<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Check if a system setting is enabled
 */
function isSettingEnabled($key, $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        return $value === '1';
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Send Email Notification on Schedule Creation or Status Update
 */
function sendScheduleEmail($pdo, $recipient_email, $recipient_name, $patient_name, $or_room, $status, $procedure_date, $action_type = 'created') {
    // 1. Check if Email Alerts are enabled in System Settings
    if (!isSettingEnabled('enable_email_alerts', $pdo)) {
        return false; // Email alerts disabled
    }

    // 2. Email Subject & Title
    $title = ($action_type === 'created') ? "New OR Schedule Created" : "OR Schedule Updated";
    $subject = "{$title}: {$patient_name} - {$or_room}";

    $headers  = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: OR Scheduling System <no-reply@hospital.com>" . "\r\n";

    // 3. Email Body Template
    $body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
            .container { padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px; max-width: 600px; }
            .header { background: #0d9488; color: #fff; padding: 14px 20px; border-radius: 6px 6px 0 0; }
            .badge { display: inline-block; padding: 4px 10px; background: #e0f2fe; color: #0369a1; font-weight: bold; border-radius: 4px; font-size: 13px; }
            .footer { margin-top: 20px; font-size: 12px; color: #64748b; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h3 style='margin:0;'>{$title}</h3>
            </div>
            <div style='padding: 20px 0;'>
                <p>Hello <strong>" . htmlspecialchars($recipient_name) . "</strong>,</p>
                <p>A surgical procedure has been scheduled with the following details:</p>
                
                <table style='width: 100%; border-collapse: collapse; margin: 15px 0;'>
                    <tr><td style='padding: 6px 0;'><strong>Patient:</strong></td><td>" . htmlspecialchars($patient_name) . "</td></tr>
                    <tr><td style='padding: 6px 0;'><strong>Operating Room:</strong></td><td>" . htmlspecialchars($or_room) . "</td></tr>
                    <tr><td style='padding: 6px 0;'><strong>Scheduled Date:</strong></td><td>" . htmlspecialchars($procedure_date) . "</td></tr>
                    <tr><td style='padding: 6px 0;'><strong>Status:</strong></td><td><span class='badge'>" . htmlspecialchars($status) . "</span></td></tr>
                </table>

                <p>Please log in to your dashboard for full details.</p>
            </div>
            <div class='footer'>
                This is an automated system notification. Please do not reply directly to this email.
            </div>
        </div>
    </body>
    </html>
    ";

    return mail($recipient_email, $subject, $body, $headers);
}