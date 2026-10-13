<?php
require_once __DIR__ . '/../config/database.php';

// Include PHPMailer library files
require_once __DIR__ . '/phpmailer/src/Exception.php';
require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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
 * Send Email Notification using PHPMailer & Gmail SMTP
 */
function sendScheduleEmail($pdo, $recipient_email, $recipient_name, $patient_name, $or_room, $status, $procedure_date, $procedure_time = '', $procedure_name = '', $clinical_priority = '', $is_stat = 0, $action_type = 'created') {
    
    if (!isSettingEnabled('enable_email_alerts', $pdo)) {
        return false;
    }

    $title = ($action_type === 'created') ? "New OR Schedule Created" : "OR Schedule Updated";
    $subject = "{$title}: {$patient_name} - {$or_room}";

    $time_display = !empty($procedure_time) ? date('h:i A', strtotime($procedure_time)) : '';
    $datetime_str = trim($procedure_date . ' ' . $time_display);

    // Format 1 or 0 cleanly for the email body
    $stat_badge = ($is_stat == 1) ? "<span style='color: #dc2626; font-weight: bold;'>STAT / EMERGENCY</span>" : "Routine";

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
                    <tr><td style='padding: 6px 0;'><strong>Procedure:</strong></td><td>" . htmlspecialchars($procedure_name) . "</td></tr>
                    <tr><td style='padding: 6px 0;'><strong>Operating Room:</strong></td><td>" . htmlspecialchars($or_room) . "</td></tr>
                    <tr><td style='padding: 6px 0;'><strong>Scheduled Date & Time:</strong></td><td>" . htmlspecialchars($datetime_str) . "</td></tr>
                    <tr><td style='padding: 6px 0;'><strong>Clinical Priority:</strong></td><td>" . htmlspecialchars($clinical_priority) . "</td></tr>
                    <tr><td style='padding: 6px 0;'><strong>Stat Status:</strong></td><td>" . $stat_badge . "</td></tr>
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

    // 4. Send via PHPMailer SMTP
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'boomzdummy@gmail.com';
        $mail->Password   = 'drflkxbklevikmvf';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('boomzdummy@gmail.com', 'OR Scheduling System');
        $mail->addAddress($recipient_email, $recipient_name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        return $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}