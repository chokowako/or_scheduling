<?php
/**
 * Reusable Activity Logging Function
 */
function logActivity($pdo, $action_type, $description) {
    try {
        // Check if activity logging is enabled in system settings
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'enable_activity_logging'");
        $stmt->execute();
        $enabled = $stmt->fetchColumn();

        if ($enabled !== '1') {
            return false; // Logging is disabled by admin
        }

        $user_id   = $_SESSION['user_id'] ?? null;
        $user_name = $_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'System User');
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;

        $log_stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, user_name, action_type, description, ip_address) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        return $log_stmt->execute([$user_id, $user_name, $action_type, $description, $ip_address]);
    } catch (Exception $e) {
        // Silently catch to avoid breaking the main app flow if logging fails
        return false;
    }
}