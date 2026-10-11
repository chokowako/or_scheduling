<?php
session_start();

require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| HANDLE FORM SUBMISSIONS
|--------------------------------------------------------------------------
*/

$message = "";
$message_type = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $posted_settings = [
        'enable_email_alerts'     => isset($_POST['enable_email_alerts']) ? '1' : '0',
        'enable_sms_alerts'       => isset($_POST['enable_sms_alerts']) ? '1' : '0',
        'alert_lead_time'         => $_POST['alert_lead_time'] ?? '30',
        'enable_activity_logging' => isset($_POST['enable_activity_logging']) ? '1' : '0',
    ];

    try {
        $stmt = $pdo->prepare("
            INSERT INTO system_settings (setting_key, setting_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = ?
        ");

        foreach ($posted_settings as $key => $value) {
            $stmt->execute([$key, $value, $value]);
        }

        header("Location: system-settings.php?success=1");
        exit;
    } catch (PDOException $e) {
        $message = "Unable to update settings: " . $e->getMessage();
        $message_type = "danger";
    }
}

if (isset($_GET['success'])) {
    $message = "Settings successfully updated.";
    $message_type = "success";
}

/*
|--------------------------------------------------------------------------
| GET CURRENT SETTINGS
|--------------------------------------------------------------------------
*/

try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
    $settings = [];
}

/*
|--------------------------------------------------------------------------
| PAGE SETUP
|--------------------------------------------------------------------------
*/

$page_title = "System Settings";

require_once "../includes/header.php";
require_once "../includes/sidebar.php";

?>

<!-- Stylesheets -->
<link rel="stylesheet" href="../assets/css/facilities.css?v=20260907">
<link rel="stylesheet" href="../assets/css/system-settings.css?v=20261011">

<main class="main-content">

    <!-- =========================================================
         TOP BAR
         ========================================================= -->

    <div class="page-topbar">

        <div>
            <div class="page-kicker">
                SYSTEM MANAGEMENT
            </div>

            <h1 class="page-title">
                System Settings
            </h1>

            <p class="page-description">
                Configure general application preferences and notification defaults.
            </p>
        </div>

        <div class="page-user">
            <div class="page-user-icon">
                <i class="bi bi-person-fill"></i>
            </div>

            <div>
                <div class="page-user-name">
                    <?= htmlspecialchars($_SESSION['full_name'] ?? 'System Administrator') ?>
                </div>

                <div class="page-user-role">
                    <?= htmlspecialchars($_SESSION['role'] ?? 'Administrator') ?>
                </div>
            </div>
        </div>

    </div>

    <!-- =========================================================
         ALERT
         ========================================================= -->

    <?php if ($message !== ""): ?>
        <div class="alert alert-<?= htmlspecialchars($message_type) ?>" style="margin-bottom: 20px;">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- =========================================================
         SETTINGS PANEL
         ========================================================= -->

    <div class="settings-panel">

        <form method="POST">

            <!-- SECTION 1: NOTIFICATIONS -->
            <div class="settings-section">

                <h3 class="settings-section-title">
                    Expanded Notification Options
                </h3>

                <!-- EMAIL ALERTS TOGGLE -->
                <div class="settings-checkbox-wrapper">
                    <label class="settings-checkbox-label">
                        <input
                            type="checkbox"
                            name="enable_email_alerts"
                            value="1"
                            <?= (!empty($settings['enable_email_alerts']) && $settings['enable_email_alerts'] == '1') ? 'checked' : ''; ?>
                        >
                        Enable automated email alerts for status changes
                    </label>
                </div>

                <!-- SMS ALERTS TOGGLE -->
                <div class="settings-checkbox-wrapper" style="margin-top: 15px;">
                    <label class="settings-checkbox-label">
                        <input
                            type="checkbox"
                            name="enable_sms_alerts"
                            value="1"
                            <?= (!empty($settings['enable_sms_alerts']) && $settings['enable_sms_alerts'] == '1') ? 'checked' : ''; ?>
                        >
                        Enable SMS / Phone alerts for emergency or urgent schedule updates
                    </label>
                </div>

                <!-- LEAD TIME -->
                <div class="row" style="margin-top: 20px;">
                    <div class="col-md-6">
                        <label class="form-label font-weight-semibold" style="font-size: 14px; color: #334155; margin-bottom: 8px; display: block;">
                            Lead Time for Alerts
                        </label>
                        <select name="alert_lead_time" class="form-select">
                            <option value="15" <?= (($settings['alert_lead_time'] ?? '') == '15') ? 'selected' : ''; ?>>15 minutes before procedure</option>
                            <option value="30" <?= (($settings['alert_lead_time'] ?? '') == '30') ? 'selected' : ''; ?>>30 minutes before procedure</option>
                            <option value="60" <?= (($settings['alert_lead_time'] ?? '') == '60') ? 'selected' : ''; ?>>1 hour before procedure</option>
                            <option value="120" <?= (($settings['alert_lead_time'] ?? '') == '120') ? 'selected' : ''; ?>>2 hours before procedure</option>
                        </select>
                    </div>
                </div>

            </div>

            <!-- SECTION 2: AUDIT & LOGS -->
            <div class="settings-section" style="margin-top: 30px;">

                <h3 class="settings-section-title">
                    Audit & Activity Logs
                </h3>

                <div class="settings-checkbox-wrapper">
                    <label class="settings-checkbox-label">
                        <input
                            type="checkbox"
                            name="enable_activity_logging"
                            value="1"
                            <?= (!empty($settings['enable_activity_logging']) && $settings['enable_activity_logging'] == '1') ? 'checked' : ''; ?>
                        >
                        Enable Activity Logging (Tracks which user creates, edits, or cancels surgery schedules for accountability)
                    </label>
                </div>

            </div>

            <!-- ACTIONS -->
            <div class="settings-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-2"></i> Save Configuration
                </button>
            </div>

        </form>

    </div>

</main>