<?php
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize notification toggles or general preferences
    $email_alerts = isset($_POST['enable_email_alerts']) ? 1 : 0;

    $settings = [
        'enable_email_alerts' => $email_alerts
    ];

    try {
        foreach ($settings as $key => $value) {
            $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        }

        header("Location: system-settings.php?success=1");
        exit;
    } catch (PDOException $e) {
        header("Location: system-settings.php?error=" . urlencode($e->getMessage()));
        exit;
    }
}
?>