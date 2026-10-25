<?php
/**
 * Reusable Activity Logging Function (Debug Version)
 */
function logActivity($pdo, $action_type, $description) {
    try {
        // 1. Check setting
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'enable_activity_logging'");
        $stmt->execute();
        $enabled = $stmt->fetchColumn();

        // If not set in DB yet, default to '1'
        if ($enabled === false) {
            $enabled = '1';
        }

        if ($enabled !== '1') {
            //echo "<div style='background: red; color: white; padding: 10px;'>Debug: Activity logging is disabled in settings!</div>";
           // exit;
			return; // Fails silently without showing text on screen
        }

        $user_id   = $_SESSION['user_id'] ?? null;
        $user_name = $_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'System User');
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;

        // 2. Try inserting
        $log_stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, user_name, action_type, description, ip_address) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $result = $log_stmt->execute([$user_id, $user_name, $action_type, $description, $ip_address]);
        
        if (!$result) {
            echo "<div style='background: red; color: white; padding: 10px;'>Debug: SQL Insert Failed!</div>";
            exit;
        }

        return true;

    } catch (Exception $e) {
        // This will print the exact database or code error on your screen
        echo "<div style='background: darkred; color: white; padding: 15px; font-family: sans-serif; z-index: 99999; position: relative;'>";
        echo "<strong>Logger Crash Error:</strong> " . htmlspecialchars($e->getMessage());
        echo "</div>";
        exit;
    }
}