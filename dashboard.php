<?php

$page_title = "Dashboard";

/* =========================================================
   START SESSION
   ========================================================= */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "config/database.php";
require_once "includes/logger.php";


   /* =========================================================
   SCHEDULE ACTIONS
   ========================================================= */

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			
    $action = $_POST['schedule_action'] ?? '';
    $schedule_id = (int)($_POST['schedule_id'] ?? 0);

    $user_id = $_SESSION['user_id'] ?? null;
    
    // Capture the new fields
    $pre_op_diagnosis        = $_POST['pre_op_diagnosis'] ?? '';
    $post_op_diagnosis       = $_POST['post_op_diagnosis'] ?? '';
    $drains                  = $_POST['drains'] ?? '';
    $specimen_lab_exam       = $_POST['specimen_lab_exam'] ?? '';
    $sponge_count_verified   = $_POST['sponge_count_verified'] ?? '';

    if ($schedule_id > 0 && $user_id) {

        try {
            /* Start transaction */
            $pdo->beginTransaction();

            /* -------------------------------------------------
               GET ROOM AND PATIENT ID ASSIGNED TO THIS SCHEDULE
               ------------------------------------------------- */
            $stmt = $pdo->prepare("
                SELECT room_id, patient_id
                FROM or_schedules
                WHERE schedule_id = ?
            ");

            $stmt->execute([
                $schedule_id
            ]);

            $schedule = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$schedule) {
                throw new Exception('Schedule not found.');
            }

            $room_id = $schedule['room_id'];
            $patient_id = $schedule['patient_id'];
    


            /* -------------------------------------------------
               COMPLETE SCHEDULE
               ------------------------------------------------- */
            if ($action === 'complete') {

                $stmt = $pdo->prepare("
                    UPDATE or_schedules
                    SET
                        status = 'Completed',
                        completed_by = ?,
                        completed_at = NOW(),
                        pre_op_diagnosis = ?,
                        post_op_diagnosis = ?,
                        drains = ?,
                        specimen_lab_exam = ?,
                        sponge_count_verified = ?
                    WHERE schedule_id = ?
                ");

                $stmt->execute([
                    $user_id,
                    $pre_op_diagnosis,
                    $post_op_diagnosis,
                    $drains,
                    $specimen_lab_exam,
                    $sponge_count_verified,
                    $schedule_id
                ]);


                /* Make operating room available */
                $stmt = $pdo->prepare("
                    UPDATE operating_rooms SET
                    status = 'Available'
                    WHERE room_id = ?");
                $stmt->execute([
                    $room_id
                ]);
            
              /* Update Patient Record to Complete */
                if ($patient_id) {
                    $stmt = $pdo->prepare("
                        UPDATE patients SET status = 'Completed'
                        WHERE patient_id = ?");
                    $stmt->execute([
                        $patient_id
                    ]);
                }

                // >>> ADD IT HERE (Logs the completion) <<<
                logActivity(
                    $pdo, 
                    'COMPLETE_SCHEDULE', 
                    "Completed surgery schedule ID: {$schedule_id} for Patient ID: {$patient_id}"
                );
            }


            /* -------------------------------------------------
               CANCEL SCHEDULE
               ------------------------------------------------- */
            elseif ($action === 'cancel') {

                $cancel_reason = trim($_POST['cancel_reason'] ?? '');

                if ($cancel_reason === '') {
                    throw new Exception('Cancellation reason is required.');
                }

                $stmt = $pdo->prepare("
                    UPDATE or_schedules
                    SET
                        status = 'Cancelled',
                        cancel_reason = ?,
                        cancelled_by = ?,
                        cancelled_at = NOW()
                    WHERE schedule_id = ?
                ");

                $stmt->execute([
                    $cancel_reason,
                    $user_id,
                    $schedule_id
                ]);


             /* Make operating room available */
                $stmt = $pdo->prepare("
                    UPDATE operating_rooms SET
                    status = 'Available'
                    WHERE room_id = ?
                ");

                $stmt->execute([
                    $room_id
                ]);

                // >>> ADD IT HERE (Logs the cancellation) <<<
                logActivity(
                    $pdo, 
                    'CANCEL_SCHEDULE', 
                    "Cancelled surgery schedule ID: {$schedule_id}. Reason: {$cancel_reason}"
                );
            }
			
			
                    
            /* -------------------------------------------------
               RESCHEDULE SCHEDULE
               ------------------------------------------------- */
            elseif ($action === 'reschedule') {

                $new_date  = $_POST['new_surgery_date'] ?? '';
                $new_room  = !empty($_POST['new_operating_room']) ? (int)$_POST['new_operating_room'] : null; 
                $new_start = $_POST['new_start_time'] ?? '';
                $new_end   = $_POST['new_end_time'] ?? '';

                if (empty($new_date) || empty($new_start) || empty($new_end) || empty($new_room)) {
                    throw new Exception('New surgery date, start time, end time, and operating room are required.');
                }

                // 1. Update the schedule with new date, time, room, and status
                $stmt = $pdo->prepare("
                    UPDATE or_schedules
                    SET
                        surgery_date = ?,
                        start_time = ?,
                        end_time = ?,
                        room_id = ?,
                        status = 'Rescheduled'
                    WHERE schedule_id = ?
                ");

                $stmt->execute([
                    $new_date,
                    $new_start,
                    $new_end,
                    $new_room,
                    $schedule_id
                ]);

               // 2. If the room has changed, update room statuses accordingly
                if ($room_id !== $new_room) {
                    // Free up the old room
                    $stmt = $pdo->prepare("
                        UPDATE operating_rooms 
                        SET status = 'Available' 
                        WHERE room_id = ?
                    ");
                    $stmt->execute([$room_id]);

                    // Set the newly selected room to Occupied
                    $stmt = $pdo->prepare("
                        UPDATE operating_rooms 
                        SET status = 'Occupied' 
                        WHERE room_id = ?
                    ");
                    $stmt->execute([$new_room]);
                }

                // >>> ADD IT HERE (Logs the reschedule action) <<<
                logActivity(
                    $pdo, 
                    'RESCHEDULE_SCHEDULE', 
                    "Rescheduled surgery schedule ID: {$schedule_id} to Date: {$new_date} in Room ID: {$new_room}"
                );
            }

			

           /* -------------------------------------------------
               EDIT SCHEDULE DETAILS
               ------------------------------------------------- */
            elseif ($action === 'edit') {
                $new_procedure_id        =$_POST['edit_procedure_id'] ?? null;
                $new_surgeon_id          =$_POST['edit_surgeon_id'] ?? null;
                $new_anesthesiologist_id =$_POST['edit_anesthesiologist_id'] ?? null;
                $new_room_id             = !empty($_POST['edit_room_id']) ? (int)$_POST['edit_room_id'] : null;
                $new_priority            =$_POST['edit_priority'] ?? 'Elective';
                $is_stat                 = isset($_POST['is_stat']) ? 1 : 0;

                // Timeline, anesthetic, and clinical notes:
                $surgery_date            =$_POST['surgery_date'] ?? null;
                $date_end                = !empty($_POST['date_end']) ?$_POST['date_end'] : null;
                $start_time              =$_POST['start_time'] ?? null;
                $end_time                = !empty($_POST['end_time']) ?$_POST['end_time'] : null;
                $anesthetic              =$_POST['anesthetic'] ?? null;
                $surgical_type           =$_POST['surgical_type'] ?? null;
                $cardiologist_id         = !empty($_POST['cardiologist_id']) ?$_POST['cardiologist_id'] : null;
                $circulating_nurse       =$_POST['circulating_nurse'] ?? null;
                $instrument_nurse        =$_POST['instrument_nurse'] ?? null;
                $remarks                 =$_POST['remarks'] ?? null;
                $infections              =$_POST['infections'] ?? null;

                // Flexible check for assistant IDs (handles arrays from dynamic rows)
                $raw_assistants          = $_POST['assistant_doctor_ids'] ?? $_POST['edit_assistant_surgeon_id'] ?? [];
                $assistant_doctor_ids    = is_array($raw_assistants) ? $raw_assistants : [$raw_assistants];

                if (empty($new_procedure_id) or empty($new_surgeon_id) or empty($new_room_id) or empty($surgery_date) or empty($start_time)) {
                    throw new Exception('Procedure, Primary Surgeon, Operating Room, Surgery Date, and Start Time are required fields.');
                }

                // 1. Update the main schedule details in the database
                $stmt =$pdo->prepare("
                    UPDATE or_schedules
                    SET
                        procedure_id = ?,
                        surgeon_doctor_id = ?,
                        anesthesiologist_doctor_id = ?,
                        room_id = ?,
                        priority = ?,
                        is_stat = ?,
                        surgery_date = ?,
                        date_end = ?,
                        start_time = ?,
                        end_time = ?,
                        anesthetic = ?,
                        surgical_type = ?,
                        cardiologist_id = ?,
                        circulating_nurse = ?,
                        instrument_nurse = ?,
                        remarks = ?,
                        infections = ?
                    WHERE schedule_id = ?
                ");

                $stmt->execute([
                    $new_procedure_id,$new_surgeon_id,
                    !empty($new_anesthesiologist_id) ? $new_anesthesiologist_id : null,$new_room_id,
                    $new_priority,$is_stat,
                    $surgery_date,$date_end,
                    $start_time,$end_time,
                    $anesthetic,$surgical_type,
                    $cardiologist_id,$circulating_nurse,
                    $instrument_nurse,$remarks,
                    $infections,$schedule_id
                ]);

               // 2. Manage Assistant Surgeons in `or_schedule_assistants` with order tracking
                // Catch the correct form input name coming from JavaScript (`assistant_surgeons[]`)
                $raw_assistants = $_POST['assistant_surgeons'] ?? $_POST['assistant_doctor_ids'] ?? [];

                // Normalize into an array and filter out any blank values
                $assistant_doctor_ids = array_filter(
                    is_array($raw_assistants) ? $raw_assistants : [$raw_assistants], 
                    function($val) { return !empty(trim($val)); }
                );

                // Always delete existing assistants first so we can sync cleanly
                $delAssistants =$pdo->prepare("DELETE FROM or_schedule_assistants WHERE schedule_id = ?");
                $delAssistants->execute([$schedule_id]);

                // If valid assistants were submitted, insert them with sequential order
                if (!empty($assistant_doctor_ids)) {
                    $insAssistant =$pdo->prepare("INSERT INTO or_schedule_assistants (schedule_id, doctor_id, assistant_order) VALUES (?, ?, ?)");
                    
                    $order = 1;
                    foreach ($assistant_doctor_ids as $doc_id) {$insAssistant->execute([$schedule_id, (int)$doc_id, $order]);$order++;
                    }
                }
				
				

               // 3. If the operating room changed, update room statuses safely
                if ($room_id !== $new_room_id) {
                    if ($room_id) {
                        $freeStmt = $pdo->prepare("UPDATE operating_rooms SET status = 'Available' WHERE room_id = ?");
                        $freeStmt->execute([$room_id]);
                    }
                    if ($new_room_id) {
                        $occupyStmt = $pdo->prepare("UPDATE operating_rooms SET status = 'Occupied' WHERE room_id = ?");
                        $occupyStmt->execute([$new_room_id]);
                    }
                }

                // >>> ADD IT HERE (Logs the edit before committing transaction) <<<
                logActivity(
                    $pdo, 
                    'EDIT_SCHEDULE', 
                    "Updated surgery schedule ID: {$schedule_id} in Room ID: {$new_room_id} on {$surgery_date}"
                );
            }
            
            /* Save and commit all changes successfully */
            $pdo->commit();

        } catch (Exception $e) {
            /* Undo changes if something failed */
            if ($pdo->inTransaction()) {$pdo->rollBack();
            }
            // Optional debugging: Uncomment the line below if you ever need to see an error on screen
            // die("Error: " . $e->getMessage());
        }
    }
	
	
	

    /* Prevent form resubmission */
    header("Location: dashboard.php");
    exit;
}
		
		
		
/* =========================================================
   TODAY'S DATE
   ========================================================= */

	$today_display = date("l, F j, Y");

/* =========================================================
   OPERATING ROOMS
   ========================================================= */

	$roomsStmt = $pdo->query("
    SELECT
        room_id,
        room_name,
        room_description,
        status
    FROM operating_rooms
    ORDER BY room_name ASC");

$rooms = $roomsStmt->fetchAll(PDO::FETCH_ASSOC);


	/* =========================================================
   TODAY'S SCHEDULES
   ========================================================= */

	$scheduleStmt = $pdo->query("
    SELECT 
        os.schedule_id,
        os.surgery_date,
        os.date_end,                 -- Added for End Date field
        os.start_time,
        os.end_time,
        os.status,
        os.priority,
        os.procedure_id,
        os.room_id,
        os.surgeon_doctor_id,
        os.anesthesiologist_doctor_id,
        os.cardiologist_id,   -- Added for Cardiologist field
        os.is_stat,

        -- Added Procedure & Anesthesia form fields
        os.anesthetic,
        os.surgical_type,
        os.circulating_nurse,
        os.instrument_nurse,

        -- Added Additional Information form fields 
        os.remarks,                  -- (Change to your actual column name if it's 'notes' or similar)
        os.infections,   -- (Change to your actual column name if it's 'precautions')

        p.first_name AS patient_first_name,
        p.middle_name AS patient_middle_name,
        p.last_name AS patient_last_name,

        s.first_name AS surgeon_first_name,
        s.last_name AS surgeon_last_name,

        a.first_name AS anesthesiologist_first_name,
        a.last_name AS anesthesiologist_last_name,

        r.room_name,

        pr.procedure_name,

        -- PULLS ASSISTANT DOCTOR IDs AS A COMMA-SEPARATED LIST
        (
            SELECT GROUP_CONCAT(osa.doctor_id ORDER BY osa.assistant_order ASC)
            FROM or_schedule_assistants osa
            WHERE osa.schedule_id = os.schedule_id
        ) AS assistant_ids

    FROM or_schedules os

    LEFT JOIN patients p
        ON os.patient_id = p.patient_id

    LEFT JOIN doctors s
        ON os.surgeon_doctor_id = s.doctor_id

    LEFT JOIN doctors a
        ON os.anesthesiologist_doctor_id = a.doctor_id

    LEFT JOIN operating_rooms r
        ON os.room_id = r.room_id

    LEFT JOIN procedures pr
        ON os.procedure_id = pr.procedure_id

    WHERE os.status NOT IN ('Completed', 'Cancelled')

    ORDER BY os.start_time ASC;");

	$today_schedules = $scheduleStmt->fetchAll(PDO::FETCH_ASSOC);
	$total_today_surgeries = count($today_schedules);



/* =========================================================
   ROOM COUNTS
   ========================================================= */

	$total_rooms = count($rooms);
	$available_rooms = 0;
	$occupied_rooms = 0;
	$maintenance_rooms = 0;
	$inactive_rooms = 0;

	foreach ($rooms as $room) {
		$room_status = strtolower(
			trim($room['status'] ?? '')
		);

		switch ($room_status) {

			case 'available':
				$available_rooms++;
				break;

			case 'occupied':
				$occupied_rooms++;
				break;

			case 'maintenance':
				$maintenance_rooms++;
				break;

			case 'inactive':
				$inactive_rooms++;
				break;
		}
	}



	

/* =========================================================
   SCHEDULE HELPERS
   ========================================================= */
	function dashboard_format_time($time)
	{
		if (empty($time)) {
			return '--:--';
		}
		$timestamp = strtotime($time);

		if ($timestamp === false) {
			return '--:--';
		}
		return date('g:i', $timestamp);
	}


	function dashboard_format_period($time)
	{
		if (empty($time)) {
			return '';
		}

		$timestamp = strtotime($time);

		if ($timestamp === false) {
			return '';
		}

		return strtoupper(
			date('A', $timestamp)
		);
	}


	function dashboard_duration_minutes($start, $end)
	{
		if (empty($start) || empty($end)) {
			return 0;
		}

		$start_timestamp = strtotime($start);
		$end_timestamp = strtotime($end);

		if (
			$start_timestamp === false
			|| $end_timestamp === false
		) {
			return 0;
		}

		$minutes = round(
			($end_timestamp - $start_timestamp) / 60
		);

		return max(0, $minutes);
	}


	function dashboard_patient_name($schedule)
	{
		$name = trim(
			($schedule['patient_first_name'] ?? '') . ' ' .
			($schedule['patient_middle_name'] ?? '') . ' ' .
			($schedule['patient_last_name'] ?? '')
		);

		return $name !== ''
			? $name
			: 'Unnamed Patient';
	}


	function dashboard_doctor_name($first_name, $last_name)
	{
		$name = trim(
			($first_name ?? '') . ' ' .
			($last_name ?? '')
		);

		if ($name === '') {
			return 'Not assigned';
		}

		return 'Dr. ' . $name;
	}


	function dashboard_status_class($status)
	{
		switch (strtolower(trim($status))) {

			case 'scheduled':
				return 'scheduled';

			case 'confirmed':
				return 'confirmed';

			case 'in progress':
				return 'in-progress';

			case 'completed':
				return 'completed';

			case 'cancelled':
				return 'cancelled';

			default:
				return 'default';
		}
	}


	function dashboard_priority_class($priority)
	{
		switch (strtolower(trim($priority))) {

			case 'emergency':
				return 'emergency';

			case 'urgent':
				return 'urgent';

			case 'elective':
				return 'elective';

			default:
				return 'default';
		}
	}


	function dashboard_priority_icon($priority)
	{
		switch (strtolower(trim($priority))) {

			case 'emergency':
				return 'bi-lightning-charge-fill';

			case 'urgent':
				return 'bi-exclamation-circle-fill';

			case 'elective':
				return 'bi-calendar-check';

			default:
				return 'bi-circle';
		}
	}


	function dashboard_room_status_class($status)
	{
		$status = strtolower(trim($status));

		switch ($status) {

			case 'available':
				return 'available-room';

			case 'occupied':
				return 'occupied-room';

			case 'maintenance':
				return 'maintenance-room';

			case 'inactive':
				return 'inactive-room';

			default:
				return 'available-room';
		}
	}


	function dashboard_room_icon($status)
	{
		$status = strtolower(trim($status));

		switch ($status) {

			case 'occupied':
				return 'bi-door-closed';

			case 'maintenance':
				return 'bi-tools';

			case 'inactive':
				return 'bi-slash-circle';

			default:
				return 'bi-door-open';
		}
	}


	function dashboard_room_status_icon($status)
	{
		$status = strtolower(trim($status));

		switch ($status) {

			case 'available':
				return 'bi-check-circle';

			case 'occupied':
				return 'bi-activity';

			case 'maintenance':
				return 'bi-tools';

			case 'inactive':
				return 'bi-dash-circle';

			default:
				return 'bi-check-circle';
		}
	}
	
/* =========================================================
   TODAY'S CASE STATUS BREAKDOWN (Integer Hour/Minute Match)
   ========================================================= */
	date_default_timezone_set('Asia/Manila'); 
	$status_scheduled_count = 0;
	$status_inprogress_count = 0;
	$status_completed_count = 0;
	$status_cancelled_count = 0;

	$today_date = date('Y-m-d');

	// 1. Get Completed and Cancelled counts from DB directly
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
		if (str_contains($st, 'complet')) {
			$status_completed_count = (int)$row['cnt'];
		} elseif (str_contains($st, 'cancel') || str_contains($st, 'delay')) {
			$status_cancelled_count = (int)$row['cnt'];
		}
	}

	// 2. Get current time as an integer (e.g., 2:01 PM becomes 1401)
	$current_time_num = (int)date('Hi');
	
	// 3. Loop through $today_schedules for Scheduled vs In Progress
	foreach ($today_schedules as $schedule) {
		$raw_start = trim($schedule['start_time'] ?? '');
		$raw_end = trim($schedule['end_time'] ?? '');

		if (!empty($raw_start)) {
			// Convert start and end times to numeric format (HHMM)
			$start_num = (int)date('Hi', strtotime($raw_start));
			$end_num = !empty($raw_end) ? (int)date('Hi', strtotime($raw_end)) : ($start_num + 100);

			// Compare numbers directly
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

	
	?>
	
	
	
	

	
	<?php require_once "includes/header.php"; ?>
	<?php require_once "includes/sidebar.php"; ?>
	<div class="main-wrapper">
	

		<!-- =====================================================
		 TOPBAR
		 ===================================================== -->

	<header class="topbar">

		<div class="topbar-left">
			<button
				type="button"
				class="mobile-menu-btn"
				onclick="document.querySelector('.sidebar')?.classList.toggle('show')"
				aria-label="Open menu"
			>
				<i class="bi bi-list"></i>
			</button>

			<div>
				<div class="page-kicker">
					OPERATING ROOM MANAGEMENT
				</div>

				<h1 class="page-title">
					Dashboard
				</h1>
			</div>
		</div>


		<div class="topbar-right">
			<div class="topbar-date">
				<i class="bi bi-calendar3"></i>
				<span>
					<?= htmlspecialchars($today_display) ?>
				</span>
			</div>

			<button
				type="button"
				class="notification-btn"
				aria-label="Notifications"
			>

				<i class="bi bi-bell"></i>
				<?php if ($total_today_surgeries > 0): ?>
					<span class="notification-dot"></span>
				<?php endif; ?>
			</button>

			<div class="user-profile">
				<div class="user-avatar">
					<?= htmlspecialchars(
						strtoupper(
							substr(
								$full_name ?? 'U',0,1))
					) ?>
				</div>

				<div class="user-info">
					<strong>
						<?= htmlspecialchars($full_name ?? 'User') ?>
					</strong>
					<span>
						<?= htmlspecialchars($role ?? 'User') ?>
					</span>
				</div>
				<i class="bi bi-chevron-down user-chevron"></i>
			</div>
		</div>
	</header>


	<!-- =====================================================
		 DASHBOARD CONTENT
		 ===================================================== -->

		<main class="dashboard-content">


		<!-- =================================================
			 HERO
			 ================================================= -->

			<section class="dashboard-hero">
				<div class="hero-content">
					<div class="hero-label">
						<span class="hero-status-dot"></span>
						OPERATING ROOM COMMAND CENTER
					</div>

					<h2>
						Good day, <?= htmlspecialchars($full_name ?? 'User') ?>.
					</h2>


					<p>
						Monitor today's operating room activity,
						surgical schedules, and room availability
						from one centralized workspace.
					</p>

					<div class="hero-actions">
						<a
							href="pages/schedules.php"
							class="hero-primary-btn"
						>
							<i class="bi bi-calendar-plus"></i>
							<span>Add Surgery Schedule</span>
						</a>

						<a
								href="/or_scheduling/pages/ORschedules.php"
							target="_blank"
							rel="noopener noreferrer"
							class="hero-secondary-btn"
						>
							<i class="bi bi-calendar3"></i>
							<span>View Schedule</span>
						</a>
					
					</div>
				</div>

				<div class="hero-visual">
					<div class="hero-circle circle-one"></div>
					<div class="hero-circle circle-two"></div>
					<div class="hero-medical-icon">
						<i class="bi bi-hospital"></i>
					</div>

					<div class="hero-floating-card">
						<div class="floating-icon">
							<i class="bi bi-heart-pulse"></i>
						</div>

						<div>
							<strong>
								<?= $total_today_surgeries ?>
							</strong>
							<small>
								Today's procedures
							</small>
						</div>
					</div>
				</div>
			</section>

			<!-- =================================================
			 KPI CARDS
			 ================================================= -->

			<section class="stats-grid">
				<div class="stat-card">
					<div class="stat-top">
						<div class="stat-icon green">
							<i class="bi bi-heart-pulse-fill"></i>
						</div>

						<span class="stat-badge positive">
							Today
						</span>
					</div>

					<div class="stat-value">
						<?= $total_today_surgeries ?>
					</div>

					<div class="stat-label">
						Scheduled Operations
					</div>

					<div class="stat-footer">
						<i class="bi bi-arrow-up-right"></i>
						Today's operating schedule
					</div>
				</div>

				<div class="stat-card">
					<div class="stat-top">
						<div class="stat-icon blue">
							<i class="bi bi-door-open-fill"></i>
						</div>

						<span class="stat-badge available">
							Available
						</span>
					</div>


					<div class="stat-value">
						<?= $available_rooms ?>
						<span class="stat-total">
							/ <?= $total_rooms ?>
						</span>
					</div>

					<div class="stat-label">
						Available OR Rooms
					</div>

					<div class="stat-footer">
						<i class="bi bi-check-circle"></i>
						Ready for scheduling
					</div>
				</div>


				<div class="stat-card">
					<div class="stat-top">
						<div class="stat-icon orange">
							<i class="bi bi-door-closed-fill"></i>
						</div>
						<span class="stat-badge pending">
							Active
						</span>
					</div>

					<div class="stat-value">
						<?= $occupied_rooms ?>
					</div>
					<div class="stat-label">
						Occupied OR Rooms
					</div>
					<div class="stat-footer">
						<i class="bi bi-clock-history"></i>
						Currently occupied
					</div>
				</div>

				<div class="stat-card">
					<div class="stat-top">
						<div class="stat-icon purple">
							<i class="bi bi-hospital-fill"></i>
						</div>
						<span class="stat-badge registered">
							Registered
						</span>
					</div>

					<div class="stat-value">
						<?= $total_rooms ?>
					</div>
					<div class="stat-label">
						Operating Rooms
					</div>
					<div class="stat-footer">
						<i class="bi bi-grid-3x3-gap"></i>
						Total configured rooms
					</div>
				</div>
			</section>




		<!-- =================================================
			 MAIN DASHBOARD GRID
			 ================================================= -->

				<section class="dashboard-main-grid">


				<!-- =============================================
					 TODAY'S SCHEDULE
					 ============================================= -->
				<section class="schedule-panel">
				   <div class="panel-header">
						<div>
							<span class="panel-kicker">
								TODAY
							</span>
							<h3>
								Today's Schedule
							</h3>
						</div>

						<span class="period-label">
							<?= $total_today_surgeries ?>
							<?= $total_today_surgeries === 1
								? 'operation'
								: 'operations'
							?>
						</span>
					</div>

					<div class="schedule-list">
						<?php if (!empty($today_schedules)): ?>
							<?php foreach ($today_schedules as $schedule): ?>
								<?php
									$patient_name =
										dashboard_patient_name($schedule);
									$surgeon_name =dashboard_doctor_name($schedule['surgeon_first_name'],$schedule['surgeon_last_name']);
									$anesthesiologist_name =dashboard_doctor_name($schedule['anesthesiologist_first_name'],$schedule['anesthesiologist_last_name']);
									$display_time =dashboard_format_time($schedule['start_time']);
									$display_period =dashboard_format_period($schedule['start_time']);
									$duration_minutes =dashboard_duration_minutes($schedule['start_time'],$schedule['end_time']);
									$status =trim($schedule['status'] ?? '');
									
									if ($status === '') {$status = 'Scheduled';}
									$priority =trim($schedule['priority'] ?? '');
									
									if ($priority === '') {$priority = 'Elective';}							
									$status_class =dashboard_status_class($status);
									$priority_class =dashboard_priority_class($priority);
									$priority_icon =dashboard_priority_icon($priority);
									$procedure_name =trim($schedule['procedure_name'] ?? '');
									
									if ($procedure_name === '') {$procedure_name ='Procedure not specified';}
									$room_name =trim($schedule['room_name'] ?? '');
									if ($room_name === '') {$room_name ='Room not assigned';}
								?>
								
								<article class="schedule-item <?= htmlspecialchars($priority_class) ?>">

									<!-- TIME / ROOM / STATUS -->
									<div class="schedule-card-top">
										<div class="schedule-time-block">
											<strong>
												<?= htmlspecialchars($display_time) ?>
											</strong>
											<span>
												<?= htmlspecialchars($display_period) ?>
											</span>
										</div>

										<div class="schedule-top-right">
											<span class="schedule-room">
												<i class="bi bi-door-open"></i>
												<?= htmlspecialchars($room_name) ?>
											</span>
											<span
												class="schedule-status <?= htmlspecialchars($status_class) ?>"
											>
												<span class="status-dot"></span>
												<?= htmlspecialchars($status) ?>
											</span>
										</div>
									</div>


									<!-- PATIENT -->
									<div class="schedule-patient">
										<span class="schedule-section-label">
											PATIENT
										</span>
										<strong>
											<?= htmlspecialchars($patient_name) ?>
										</strong>
									</div>


									<!-- PROCEDURE -->
									<div class="schedule-procedure">
										<div class="procedure-icon">
											<i class="bi bi-heart-pulse"></i>
										</div>

										<div class="procedure-content">
											<span>
												PROCEDURE
											</span>
											<strong>
												<?= htmlspecialchars($procedure_name) ?>
											</strong>
										</div>
									</div>


									<!-- TEAM -->
									<div class="schedule-team-grid">
										<div class="schedule-team-member">
											<div class="team-icon">
												<i class="bi bi-person-badge"></i>
											</div>
											<div>
												<span>
													SURGEON
												</span>
												<strong>
													<?= htmlspecialchars($surgeon_name) ?>
												</strong>
											</div>
										</div>

										<div class="schedule-team-member">
											<div class="team-icon anesthesiologist-icon">
												<i class="bi bi-person-vcard"></i>
											</div>

											<div>
												<span>
													ANESTHESIOLOGIST
												</span>
												<strong>
													<?= htmlspecialchars($anesthesiologist_name) ?>
												</strong>
											</div>
										</div>
									</div>

									<!-- PRIORITY / DURATION -->
											<div class="schedule-card-footer">
												<span
													class="schedule-priority <?= htmlspecialchars($priority_class) ?>"
												>
													<i class="bi <?= htmlspecialchars($priority_icon) ?>"></i>
													<?= htmlspecialchars($priority) ?>
												</span>

												<span class="schedule-duration">
													<i class="bi bi-clock"></i>
													<?php if ($duration_minutes > 0): ?>
														<?= htmlspecialchars($duration_minutes) ?>
														min
													<?php else: ?>
														Duration not set
													<?php endif; ?>
												</span>
											</div>

											<!-- MANAGE SCHEDULE -->
											<div class="schedule-manage-row">
												<button
											type="button"
											class="schedule-manage-btn"
											data-bs-toggle="modal"
											data-bs-target="#manageScheduleModal"
											data-schedule-id="<?= (int)$schedule['schedule_id'] ?>"
											data-patient="<?= htmlspecialchars($patient_name ?? '', ENT_QUOTES) ?>"
											data-procedure="<?= htmlspecialchars($procedure_name ?? '', ENT_QUOTES) ?>"
											data-procedure-id="<?= (int)($schedule['procedure_id'] ?? 0) ?>"
											data-room="<?= htmlspecialchars($room_name ?? '', ENT_QUOTES) ?>"
											data-room-id="<?= (int)($schedule['room_id'] ?? 0) ?>"
											data-date="<?= htmlspecialchars($schedule['surgery_date'] ?? '', ENT_QUOTES) ?>"
											data-date-end="<?= htmlspecialchars($schedule['date_end'] ?? '', ENT_QUOTES) ?>"
											data-start-time="<?= htmlspecialchars($schedule['start_time'] ?? '', ENT_QUOTES) ?>"
											data-end-time="<?= htmlspecialchars($schedule['end_time'] ?? '', ENT_QUOTES) ?>"
											data-surgeon="<?= htmlspecialchars($surgeon_name ?? '', ENT_QUOTES) ?>"
											data-surgeon-id="<?= (int)($schedule['surgeon_doctor_id'] ?? $schedule['surgeon_id'] ?? 0) ?>"
											data-anesthesiologist="<?= htmlspecialchars($anesthesiologist_name ?? '', ENT_QUOTES) ?>"
											data-anesthesiologist-id="<?= (int)($schedule['anesthesiologist_doctor_id'] ?? $schedule['anesthesiologist_id'] ?? 0) ?>"
											data-cardiologist-id="<?= (int)($schedule['cardiologist_id'] ?? 0) ?>"
											data-anesthetic="<?= htmlspecialchars($schedule['anesthetic'] ?? '', ENT_QUOTES) ?>"
											data-surgical-type="<?= htmlspecialchars($schedule['surgical_type'] ?? '', ENT_QUOTES) ?>"
											data-circulating-nurse="<?= htmlspecialchars($schedule['circulating_nurse'] ?? '', ENT_QUOTES) ?>"
											data-instrument-nurse="<?= htmlspecialchars($schedule['instrument_nurse'] ?? '', ENT_QUOTES) ?>"
											data-remarks="<?= htmlspecialchars($schedule['remarks'] ?? '', ENT_QUOTES) ?>"
											data-infections="<?= htmlspecialchars($schedule['infections'] ?? '', ENT_QUOTES) ?>"
											data-status="<?= htmlspecialchars($status ?? '', ENT_QUOTES) ?>"
											data-priority="<?= htmlspecialchars($priority ?? 'Elective', ENT_QUOTES) ?>"
											data-is-stat="<?= (int)($schedule['is_stat'] ?? 0) ?>"
											data-assistants="<?= htmlspecialchars($schedule['assistant_ids'] ?? '', ENT_QUOTES) ?>">
											<i class="bi bi-sliders"></i>
											<span>
												Manage Schedule
											</span>
										</button>
											</div>
								</article>
								
							<?php endforeach; ?>

						<?php else: ?>

							<div class="schedule-empty-state">
								<div class="schedule-empty-icon">
									<i class="bi bi-calendar-x"></i>
								</div>
								<strong>
									No operations scheduled today
								</strong>
								<span>
									There are currently no operating room
									procedures scheduled for today.
								</span>
							</div>
						<?php endif; ?>
					</div>


					<!-- FIXED ACTION -->
					<div class="schedule-footer">
						<div class="schedule-action">
							<a
								href="pages/schedules.php"
								class="schedule-action-link"
							>
								<i class="bi bi-plus-circle"></i>
								<span>
									Add Surgery Schedule
								</span>
								<i class="bi bi-arrow-right"></i>
							</a>
						</div>
					</div>				
				</section>



				<!-- =============================================
					 OR ROOMS
					 ============================================= -->

				<section class="rooms-panel">
					<div class="panel-header">
						<div>
							<span class="panel-kicker">
								OPERATING ROOMS
							</span>
							<h3>
								OR Rooms
							</h3>
						</div>


						<span class="rooms-count">
							<?= $total_rooms ?>

							<?= $total_rooms === 1
								? 'Room'
								: 'Rooms'
							?>
						</span>
					</div>

					<div class="room-list">

						<?php if (!empty($rooms)): ?>
							<?php foreach ($rooms as $room): ?>
								<?php
									$room_status =trim($room['status'] ?? '');

									if ($room_status === '') {$room_status = 'Available';}

									$room_status_class =dashboard_room_status_class($room_status);
									$room_icon =dashboard_room_icon($room_status);
									$room_status_icon =dashboard_room_status_icon($room_status);
									$room_description =trim($room['room_description'] ?? '');

									if ($room_description === '') {$room_description ='Operating room';}
								?>

								<div
									class="room-card <?= htmlspecialchars($room_status_class) ?>"
								>
									<div class="room-icon">
										<i class="bi <?= htmlspecialchars($room_icon) ?>"></i>
									</div>

									<div class="room-info">
										<strong>
											<?= htmlspecialchars($room['room_name']) ?>
										</strong>
										<span>
											<?= htmlspecialchars($room_description) ?>
										</span>
									</div>

									<div class="room-status">
										<i class="bi <?= htmlspecialchars($room_status_icon) ?>"></i>
										<?= htmlspecialchars($room_status) ?>
									</div>
								</div>

							<?php endforeach; ?>

						<?php else: ?>

							<div class="room-empty-state">
								<div class="room-empty-icon">
									<i class="bi bi-door-closed"></i>
								</div>
								<strong>
									No operating rooms
								</strong>
								<span>
									Add operating rooms to manage
									availability here.
								</span>
							</div>
						<?php endif; ?>

					</div>


					<div class="rooms-footer">
						<a href="pages/rooms.php">
							<i class="bi bi-plus-circle"></i>
							<span>
								Manage Operating Rooms
							</span>
							<i class="bi bi-arrow-right"></i>
						</a>
					</div>
				</section>
			</section>


			<!-- =================================================
			 LOWER GRID
			 ================================================= -->
		
			
			<section class="dashboard-lower-grid">

					<!-- =============================================
					 SURGERY OVERVIEW
					 ============================================= -->
		
					<div class="report-card widget-card">
							<!-- Card Header -->
							<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
								<div>
									<h3 class="widget-title">Today’s Case Status</h3>
									<span class="widget-subtitle">Real-time operational breakdown</span>
								</div>
								<span class="live-badge">Live</span>
							</div>
							
							<!-- Status Breakdown List -->
							<div class="status-list">
								
								<!-- 1. Scheduled / Pending -->
								<div class="status-row row-scheduled">
								<span class="status-label ecg-wrapper">
									<span class="status-icon-box">
										<svg id="js-clock-icon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#3b82f6" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
											<circle cx="12" cy="12" r="9"></circle>
											<polyline points="12 6 12 12 16 14"></polyline>
										</svg>
									</span>
									Scheduled / Pending
								</span>
								<strong class="status-count"><?php echo $status_scheduled_count; ?></strong>
							</div>
								
								<!-- 2. In Progress (Intraop) -->
								<div class="status-row row-inprogress pulse-inprogress">
									<span class="status-label ecg-wrapper">
										<span class="status-icon-box">
											<svg id="js-ecg-monitor" viewBox="0 0 100 24" width="65" height="22" style="overflow: visible;">
												<path id="ecg-dynamic-path" d="M0,12 L20,12 L26,4 L32,20 L38,8 L44,16 L50,12 L100,12" fill="none" stroke="#f59e0b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
											</svg>
										</span>
										In Progress (Intraop)
									</span>
									<strong id="inprogress-count" class="status-count"><?php echo $status_inprogress_count; ?></strong>
								</div>
								
								<!-- 3. Completed / PACU -->
								<div class="status-row row-completed">
									<span class="status-label ecg-wrapper">
										<span class="status-icon-box">
											<svg id="js-completed-icon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
												<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
												<polyline points="22 4 12 14.01 9 11.01"></polyline>
											</svg>
										</span>
										Completed / PACU
									</span>
									<strong class="status-count"><?php echo $status_completed_count; ?></strong>
								</div>

								<!-- 4. Cancelled / Delayed -->
								<div class="status-row row-cancelled">
									<span class="status-label ecg-wrapper">
										<span class="status-icon-box">
											<svg id="js-cancelled-icon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#ef4444" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
												<circle cx="12" cy="12" r="10"></circle>
												<line x1="15" y1="9" x2="9" y2="15"></line>
												<line x1="9" y1="9" x2="15" y2="15"></line>
											</svg>
										</span>
										Cancelled / Delayed
									</span>
									<strong class="status-count"><?php echo $status_cancelled_count; ?></strong>
								</div>
							</div>
						</div>
			


					<!-- =============================================
					 QUICK ACTIONS
					 ============================================= -->
					<section class="panel">
						<div class="panel-header">
							<div>
								<span class="panel-kicker">
									SHORTCUTS
								</span>
								<h3>
									Quick Actions
								</h3>
							</div>
						</div>

						<div class="quick-actions">
							<a
								href="pages/patients.php"
								class="quick-action"
							>
								<div class="quick-icon green">
									<i class="bi bi-person-plus"></i>
								</div>
								<div class="quick-text">
									<strong>
										Register Patient
									</strong>
									<small>
										Add a new patient record
									</small>
								</div>
								<i class="bi bi-chevron-right"></i>
							</a>

							<a
								href="pages/surgeons.php"
								class="quick-action"
							>
								<div class="quick-icon blue">
									<i class="bi bi-person-badge"></i>
								</div>

								<div class="quick-text">
									<strong>
										Manage Surgeons
									</strong>
									<small>
										View and manage surgical staff
									</small>
								</div>
								<i class="bi bi-chevron-right"></i>
							</a>

							<a
								href="pages/anesthesiologists.php"
								class="quick-action"
							>
								<div class="quick-icon purple">
									<i class="bi bi-person-vcard"></i>
								</div>

								<div class="quick-text">
									<strong>
										Manage Anesthesiologists
									</strong>
									<small>
										Manage anesthesia specialists
									</small>
								</div>
								<i class="bi bi-chevron-right"></i>
							</a>

							<a
								href="pages/reports.php"
								class="quick-action"
							>
								<div class="quick-icon orange">
									<i class="bi bi-bar-chart"></i>
								</div>
								<div class="quick-text">
									<strong>
										View Reports
									</strong>
									<small>
										Review operating room activity
									</small>
								</div>
								<i class="bi bi-chevron-right"></i>
							</a>
						</div>
					</section>
			</section>
		</main>
	</div>


	<!-- =========================================================
		 MANAGE SCHEDULE MODAL (Wrapped with the Form)
		 ========================================================= -->
	<div
		class="modal fade"
		id="manageScheduleModal"
		tabindex="-1"
		aria-labelledby="manageScheduleModalLabel"
		aria-hidden="true"
	>

		<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
			<!-- THE FORM NOW WRAPS THE ENTIRE MODAL CONTENT -->
			<form id="scheduleActionForm" method="POST" action="dashboard.php" class="modal-content schedule-modal">

				<!-- HIDDEN SYSTEM INPUTS -->
				<input type="hidden" name="schedule_action" id="scheduleAction">
				<input type="hidden" name="schedule_id" id="scheduleId">
				<input type="hidden" name="cancel_reason" id="hiddenCancelReason">

				<!-- HEADER -->
				<div class="modal-header schedule-modal-header">
					<div>
						<span class="schedule-modal-kicker">
							OR SCHEDULE
						</span>
						<h5
							class="modal-title"
							id="manageScheduleModalLabel"
						>
							Manage Schedule & Clinical Logs
						</h5>
					</div>

					<button
						type="button"
						class="btn-close"
						data-bs-dismiss="modal"
						aria-label="Close"
					></button>
				</div>

				<!-- BODY -->
				<div class="modal-body">

					<!-- SCHEDULE INFORMATION (Read-only details) -->
					<div
						id="scheduleDetailsSection"
						class="schedule-details-section"
					>

						<div class="schedule-modal-patient">
							<span class="schedule-modal-label">PATIENT</span>
							<strong id="modalPatient">—</strong>
						</div>

						<div class="schedule-modal-grid mb-4">
							<div class="schedule-modal-info">
								<span>PROCEDURE</span>
								<strong id="modalProcedure">—</strong>
							</div>

							<div class="schedule-modal-info">
								<span>OPERATING ROOM</span>
								<strong id="modalRoom">—</strong>
							</div>

							<div class="schedule-modal-info">
								<span>SURGERY DATE</span>
								<strong id="modalDate">—</strong>
							</div>

							<div class="schedule-modal-info">
								<span>TIME</span>
								<strong id="modalTime">—</strong>
							</div>

							<div class="schedule-modal-info">
								<span>SURGEON</span>
								<strong id="modalSurgeon">—</strong>
							</div>

							<div class="schedule-modal-info">
								<span>ANESTHESIOLOGIST</span>
								<strong id="modalAnesthesiologist">—</strong>
							</div>
						</div>


						<!-- CLINICAL INPUT FIELDS -->
						<div class="border-top pt-3">
							<h6 class="text-primary fw-bold mb-3">
								<i class="bi bi-clipboard2-pulse-fill"></i> Clinical & Post-Op Logs
							</h6>
							
							<div class="clinical-logs-grid">
								<div class="clinical-field-group">
									<label class="clinical-field-label" for="inputPreOpDiagnosis">Pre-Operation Diagnosis</label>
									<textarea id="inputPreOpDiagnosis" name="pre_op_diagnosis" class="form-control" rows="2" placeholder="Enter pre-op diagnosis..."></textarea>
								</div>

								<div class="clinical-field-group">
									<label class="clinical-field-label" for="inputPostOpDiagnosis">Post-Operation Diagnosis</label>
									<textarea id="inputPostOpDiagnosis" name="post_op_diagnosis" class="form-control" rows="2" placeholder="Enter post-op diagnosis..."></textarea>
								</div>

								<div class="clinical-field-group">
									<label class="clinical-field-label" for="inputDrains">Drains (Kinds & Numbers)</label>
									<input type="text" id="inputDrains" name="drains" class="form-control" placeholder="e.g., Penrose drain (2)">
								</div>

								<div class="clinical-field-group">
									<label class="clinical-field-label" for="inputSpecimen">Specimen for Lab Exam</label>
									<input type="text" id="inputSpecimen" name="specimen_lab_exam" class="form-control" placeholder="e.g., Tissue biopsy">
								</div>

								<div class="clinical-field-group full-width">
									<label class="clinical-field-label" for="inputSpongeCountVerified">Sponge & Instrument Count Verified</label>
									<input type="text" id="inputSpongeCountVerified" name="sponge_count_verified" class="form-control" placeholder="e.g., Verified correct / counts match">
								</div>
							</div>
						</div>
					</div>
					
					
					
					
					<!-- RESCHEDULE SECTION -->
							<div
								id="rescheduleSection"
								class="reschedule-section"
								style="display: none;"
							>
								<div class="cancel-warning" style="background: #e0f2fe; border-color: #bae6fd; color: #0369a1;">
									<i class="bi bi-calendar-event-fill"></i>
									<div>
										<strong>Reschedule Surgery for <span id="reschedulePatientName" style="text-decoration: underline;">Patient</span></strong>
										<span>Please select the new date, time, and operating room.</span>
									</div>
								</div>

								<div class="schedule-modal-grid mb-3" style="margin-top: 15px;">
									<div class="clinical-field-group">
										<label class="clinical-field-label" for="newSurgeryDate">New Surgery Date</label>
										<input type="date" id="newSurgeryDate" name="new_surgery_date" class="form-control">
									</div>

									<div class="clinical-field-group">
										<label class="clinical-field-label" for="newOperatingRoom">New Operating Room</label>
										<select id="newOperatingRoom" name="new_operating_room" class="form-control" required>
											<option value="">Select available room...</option>
											<?php 
											if (!empty($rooms)) {
												foreach ($rooms as $room) {
													if (strtolower(trim($room['status'])) === 'available') {
														$roomId = htmlspecialchars($room['room_id']);
														$roomName = htmlspecialchars($room['room_name']);
														echo "<option value=\"{$roomId}\">{$roomName}</option>";
													}
												}
											}
											?>
										</select>
									</div>

									<div class="clinical-field-group">
										<label class="clinical-field-label" for="newStartTime">New Start Time</label>
										<input type="time" id="newStartTime" name="new_start_time" class="form-control">
									</div>

									<div class="clinical-field-group">
										<label class="clinical-field-label" for="newEndTime">New End Time</label>
										<input type="time" id="newEndTime" name="new_end_time" class="form-control">
									</div>
								</div>
								
									<!-- RESCHEDULE ACTION (Footer buttons) -->
										<div
											id="rescheduleActionButtons"
											class="schedule-action-buttons"
											style="display: none;"
										>
											<button
												type="button"
												class="btn schedule-back-btn"
												id="backFromRescheduleBtn"
											>
												<i class="bi bi-arrow-left"></i>
												Back
											</button>

											<button
												type="button"
												class="btn"
												id="confirmRescheduleBtn"
												onclick="submitReschedule()"
												style="background: #0284c7; color: white; font-weight: 600;"
											>
												<i class="bi bi-calendar-check-fill"></i>
												Confirm Reschedule
											</button>
										</div>
							</div>
							
						
						
									<!-- EDIT SECTION -->
										<div id="editSection" class="edit-section" style="display: none;">
											<div class="cancel-warning" style="background: #e2f0e8; border-color: #d8e8e0; color: #1c4d39;">
												<i class="bi bi-pencil-fill"></i>
												<div>
													<strong>Edit Schedule for <span id="editPatientName" style="text-decoration: underline;">Patient</span></strong>
													<span>Update schedule timeline, room, team, procedure, and clinical notes.</span>
												</div>
											</div>

											<!-- Container matching your exact reference theme style -->
											<div style="margin-top: 15px; display: flex; flex-direction: column; gap: 20px;">

												<!-- 1. SCHEDULE DETAILS CARD -->
												<div style="background: #f1f7f4; border: 1px solid #d8e8e0; border-radius: 14px; padding: 22px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
													<div style="display: flex; align-items: center; gap: 12px; margin-bottom: 18px;">
														<div style="background: #e2f0e8; color: #1c4d39; font-weight: 700; font-size: 0.85rem; width: 28px; height: 28px; border-radius: 6px; display: flex; align-items: center; justify-content: center;">
															01
														</div>
														<div style="background: #e2f0e8; color: #1c4d39; padding: 6px 10px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
															<i class="bi bi-calendar-event" style="font-size: 1.1rem;"></i>
														</div>
														<div>
															<h1 style="margin: 0; font-weight: 700; color: #1c4d39; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.5px;">Schedule Details</h1>
															<span style="font-size: 0.8rem; color: #52796f;">Surgery dates and operating time window.</span>
														</div>
													</div>

													<div style="display: flex; flex-direction: column; gap: 15px;">
														<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
															<div class="clinical-field-group" style="background: #ffffff; border: 1px solid #d8e8e0; border-radius: 10px; padding: 14px;">
																<label class="clinical-field-label" for="editSurgeryDate" style="font-weight: 700; color: #1c4d39; font-size: 0.8rem; margin-bottom: 6px; display: block;block; text-transform: none !important;">Surgey Date *</label>
																<input type="date" id="editSurgeryDate" name="surgery_date" class="form-control" required style="border-radius: 8px; border-color: #d8e8e0;">
															</div>
															<div class="clinical-field-group" style="background: #ffffff; border: 1px solid #d8e8e0; border-radius: 10px; padding: 14px;">
																<label class="clinical-field-label" for="editDateEnd" style="font-weight: 700; color: #1c4d39; font-size: 0.8rem; margin-bottom: 6px; display: block;block; text-transform: none !important;">End Date</label>
																<input type="date" id="editDateEnd" name="date_end" class="form-control" style="border-radius: 8px; border-color: #d8e8e0;">
															</div>
														</div>

														<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
															<div class="clinical-field-group" style="background: #ffffff; border: 1px solid #d8e8e0; border-radius: 10px; padding: 14px;">
																<label class="clinical-field-label" for="editStartTime" style="font-weight: 700; color: #1c4d39; font-size: 0.8rem; margin-bottom: 6px; display: block;block; text-transform: none !important;">Start Time *</label>
																<input type="time" id="editStartTime" name="start_time" class="form-control" required style="border-radius: 8px; border-color: #d8e8e0;">
															</div>
															<div class="clinical-field-group" style="background: #ffffff; border: 1px solid #d8e8e0; border-radius: 10px; padding: 14px;">
																<label class="clinical-field-label" for="editEndTime" style="font-weight: 700; color: #1c4d39; font-size: 0.8rem; margin-bottom: 6px; display: block;block; text-transform: none !important;">End Time</label>
																<input type="time" id="editEndTime" name="end_time" class="form-control" style="border-radius: 8px; border-color: #d8e8e0;">
															</div>
														</div>
													</div>
												</div>

												<!-- 2. OPERATING ROOM & CLINICAL PRIORITY CARD -->
												<div style="background: #f1f7f4; border: 1px solid #d8e8e0; border-radius: 14px; padding: 22px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
													<div style="display: flex; align-items: center; gap: 12px; margin-bottom: 18px;">
														<div style="background: #e2f0e8; color: #1c4d39; font-weight: 700; font-size: 0.85rem; width: 28px; height: 28px; border-radius: 6px; display: flex; align-items: center; justify-content: center;">
															02
														</div>
														<div style="background: #e2f0e8; color: #1c4d39; padding: 6px 10px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
															<i class="bi bi-hospital" style="font-size: 1.1rem;"></i>
														</div>
														<div>
															<h6 style="margin: 0; font-weight: 700; color: #1c4d39; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.5px;">Operating Room & Priority</h6>
															<span style="font-size: 0.8rem; color: #52796f;">Theater assignment, priority level, and STAT status.</span>
														</div>
													</div>

													<div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 15px; align-items: stretch;">
														<div class="clinical-field-group" style="background: #ffffff; border: 1px solid #d8e8e0; border-radius: 10px; padding: 14px;">
															<label class="clinical-field-label" for="editRoom" style="font-weight: 700; color: #1c4d39; font-size: 0.8rem; margin-bottom: 6px; display: block;block; text-transform: none !important;">Operating Room / Theater Room</label>
															<select id="editRoom" name="edit_room_id" class="form-control" style="border-radius: 8px; border-color: #d8e8e0;">
																<?php foreach ($rooms as $room): ?>
																	<option value="<?= $room['room_id'] ?>"><?= htmlspecialchars($room['room_name']) ?></option>
																<?php endforeach; ?>
															</select>
														</div>

														<div class="clinical-field-group" style="background: #ffffff; border: 1px solid #d8e8e0; border-radius: 10px; padding: 14px;">
															<label class="clinical-field-label" for="editPriority" style="font-weight: 700; color: #1c4d39; font-size: 0.8rem; margin-bottom: 6px; display: block;block; text-transform: none !important;">Clinical Priority</label>
															<select id="editPriority" name="edit_priority" class="form-control" style="border-radius: 8px; border-color: #d8e8e0;">
																<option value="Elective">Elective</option>
																<option value="Urgent">Urgent</option>
																<option value="Emergency">Emergency</option>
															</select>
														</div>

														<div class="clinical-field-group" style="background: #ffffff; border: 1px solid #d8e8e0; border-radius: 10px; padding: 14px; display: flex; flex-direction: column; justify-content: center;">
															<label class="clinical-field-label" style="font-weight: 700; color: #1c4d39; font-size: 0.8rem; margin-bottom: 6px; display: block;block; text-transform: none !important;">Immediate (STAT)</label>
															<div style="display: flex; align-items: center; gap: 10px;">
																<div class="form-check form-switch" style="padding-left: 2.5rem; margin: 0;">
																	<input class="form-check-input" type="checkbox" role="switch" id="statProcedureToggle" name="is_stat" value="1" style="width: 2.5rem; height: 1.3rem; cursor: pointer;">
																</div>
																<span id="statToggleLabel" style="font-weight: 600; color: #1c4d39; font-size: 0.85rem;">No</span>
															</div>
														</div>
													</div>
												</div>

												<!-- 3. SURGICAL TEAM CARD -->
												<div style="background: #f1f7f4; border: 1px solid #d8e8e0; border-radius: 14px; padding: 22px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">	
													<div style="display: flex; align-items: center; gap: 12px; margin-bottom: 18px;">
														<div style="background: #e2f0e8; color: #1c4d39; font-weight: 700; font-size: 0.85rem; width: 28px; height: 28px; border-radius: 6px; display: flex; align-items: center; justify-content: center;">
															03
														</div>
														<div style="background: #e2f0e8; color: #1c4d39; padding: 6px 10px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
															<i class="bi bi-people" style="font-size: 1.1rem;"></i>
														</div>
														<div>
															<h6 style="margin: 0; font-weight: 700; color: #1c4d39; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.5px;">Surgical Team</h6>
															<span style="font-size: 0.8rem; color: #52796f;">Assign the primary surgeon and anesthesiologist.</span>
														</div>
													</div>

													<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
														<div class="clinical-field-group" style="background: #ffffff; border: 1px solid #d8e8e0; border-radius: 10px; padding: 14px;">
															<label class="clinical-field-label" for="editSurgeon" style="font-weight: 700; color: #1c4d39; font-size: 0.8rem; margin-bottom: 6px; display: block;block; text-transform: none !important;">Primary Surgeon *</label>
															<select id="editSurgeon" name="edit_surgeon_id" class="form-control" required style="border-radius: 8px; border-color: #d8e8e0;">
																<option value="">Select primary surgeon</option>
																<?php 
																$docStmt = $pdo->query("SELECT doctor_id, first_name, last_name FROM doctors ORDER BY last_name ASC");
																while ($doc = $docStmt->fetch(PDO::FETCH_ASSOC)) {
																	echo "<option value=\"{$doc['doctor_id']}\">Dr. " . htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']) . "</option>";
																}
																?>
															</select>
														</div>

														<div class="clinical-field-group" style="background: #ffffff; border: 1px solid #d8e8e0; border-radius: 10px; padding: 14px;">
															<label class="clinical-field-label" for="editAnesthesiologist" style="font-weight: 700; color: #1c4d39; font-size: 0.8rem; margin-bottom: 6px; display: block;block; text-transform: none !important;">Anesthesiologist</label>
															<select id="editAnesthesiologist" name="edit_anesthesiologist_id" class="form-control" style="border-radius: 8px; border-color: #d8e8e0;">
																<option value="">Select anesthesiologist</option>
																<?php 
																$anesStmt = $pdo->query("SELECT doctor_id, first_name, last_name FROM doctors ORDER BY last_name ASC");
																while ($doc = $anesStmt->fetch(PDO::FETCH_ASSOC)) {
																	echo "<option value=\"{$doc['doctor_id']}\">Dr. " . htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']) . "</option>";
																}
																?>
															</select>
														</div>
													</div>
												</div>

												<!-- 4. ASSISTANT SURGEONS CARD -->
												<div style="background: #f1f7f4; border: 1px solid #d8e8e0; border-radius: 14px; padding: 22px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
													<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
														<div style="display: flex; align-items: center; gap: 12px;">
															<div style="background: #e2f0e8; color: #1c4d39; font-weight: 700; font-size: 0.85rem; width: 28px; height: 28px; border-radius: 6px; display: flex; align-items: center; justify-content: center;">
																04
															</div>
															<div style="background: #e2f0e8; color: #1c4d39; padding: 6px 10px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
																<i class="bi bi-person-plus" style="font-size: 1.1rem;"></i>
															</div>
															<div>
																<h6 style="margin: 0; font-weight: 700; color: #1c4d39; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.5px;">Assistant Surgeons</h6>
																<span style="font-size: 0.8rem; color: #52796f;">Add additional doctors participating in the procedure.</span>
															</div>
														</div>
														<button type="button" id="addAssistantBtn" class="btn btn-sm" style="background: #ffffff; color: #1c4d39; border: 1px solid #d8e8e0; font-weight: 600; border-radius: 6px;">
															<i class="bi bi-plus-lg"></i> Add Assistant
														</button>
													</div>

													<div style="background: #ffffff; border: 1px solid #d8e8e0; border-radius: 10px; padding: 14px;">
														<!-- Container where JavaScript rows will be injected -->
														<div id="assistantRowsContainer" style="display: flex; flex-direction: column; gap: 10px;">
															<!-- Row 1 will always be generated or pre-populated here -->
														</div>
													</div>
												</div>

												<!-- 5. PROCEDURE & ANESTHESIA CARD -->
												<div style="background: #f1f7f4; border: 1px solid #d8e8e0; border-radius: 14px; padding: 22px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
													<div style="display: flex; align-items: center; gap: 12px; margin-bottom: 18px;">
														<div style="background: #e2f0e8; color: #1c4d39; font-weight: 700; font-size: 0.85rem; width: 28px; height: 28px; border-radius: 6px; display: flex; align-items: center; justify-content: center;">
															05
														</div>
														<div style="background: #e2f0e8; color: #1c4d39; padding: 6px 10px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
															<i class="bi bi-file-earmark-medical" style="font-size: 1.1rem;"></i>
														</div>
														<div>
															<h6 style="margin: 0; font-weight: 700; color: #1c4d39; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.5px;">Procedure & Anesthesia</h6>
															<span style="font-size: 0.8rem; color: #52796f;">Procedure, anesthetic, surgical type, and supporting nursing/cardiac staff.</span>
														</div>
													</div>

													<div style="display: flex; flex-direction: column; gap: 15px;">
														<div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 15px;">
															<div class="clinical-field-group" style="background: #ffffff; border: 1px solid #d8e8e0; border-radius: 10px; padding: 14px;">
																<label class="clinical-field-label" for="editProcedure" style="font-weight: 700; color: #1c4d39; font-size: 0.8rem; margin-bottom: 6px; display: block;block; text-transform: none !important;">Procedure *</label>
																<select id="editProcedure" name="edit_procedure_id" class="form-control" required style="border-radius: 8px; border-color: #d8e8e0;">
																	<option value="">Select Procedure</option>
																	<?php 
																	$procStmt = $pdo->query("SELECT procedure_id, procedure_name FROM procedures ORDER BY procedure_name ASC");
																	while ($proc = $procStmt->fetch(PDO::FETCH_ASSOC)) {
																		echo "<option value=\"{$proc['procedure_id']}\">" . htmlspecialchars($proc['procedure_name']) . "</option>";
																	}
																	?>
																</select>
															</div>

															<div class="clinical-field-group" style="background: #ffffff; border: 1px solid #d8e8e0; border-radius: 10px; padding: 14px;">
																<label class="clinical-field-label" for="editAnesthetic" style="font-weight: 700; color: #1c4d39; font-size: 0.8rem; margin-bottom: 6px; display: block; text-transform: none !important;">Anesthetic</label>
																<select id="editAnesthetic" name="anesthetic" class="form-control" style="border-radius: 8px; border-color: #d8e8e0;">
																	<option value="">Select anesthetic</option>
																	<?php       
																	$anesQuery = $pdo->query("SELECT anesthetic_id, anesthetic_name FROM anesthetics ORDER BY anesthetic_name ASC");
																	while ($anes = $anesQuery->fetch(PDO::FETCH_ASSOC)) {
																		echo "<option value=\"{$anes['anesthetic_id']}\">" . htmlspecialchars($anes['anesthetic_name']) . "</option>";
																	}
																	?>
																</select>
															</div>

															<div class="clinical-field-group" style="background: #ffffff; border: 1px solid #d8e8e0; border-radius: 10px; padding: 14px;">
																<label class="clinical-field-label" for="editSurgicalType" style="font-weight: 700; color: #1c4d39; font-size: 0.8rem; margin-bottom: 6px; display: block;block; text-transform: none !important;">Surgical Type</label>
																<input type="text" id="editSurgicalType" name="surgical_type" class="form-control" placeholder="Surgical type" style="border-radius: 8px; border-color: #d8e8e0;">
															</div>
														</div>

														<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
															<div class="clinical-field-group" style="background: #ffffff; border: 1px solid #d8e8e0; border-radius: 10px; padding: 14px;">
																<label class="clinical-field-label" for="editCardiologist" style="font-weight: 700; color: #1c4d39; font-size: 0.8rem; margin-bottom: 6px; display: block;block; text-transform: none !important;">Cardiologist</label>
																<select id="editCardiologist" name="cardiologist_id" class="form-control" style="border-radius: 8px; border-color: #d8e8e0;">
																	<option value="">Select cardiologist</option>
																	<?php 
																	$cardioStmt = $pdo->query("SELECT doctor_id, first_name, last_name FROM doctors ORDER BY last_name ASC");
																	while ($doc = $cardioStmt->fetch(PDO::FETCH_ASSOC)) {
																		echo "<option value=\"{$doc['doctor_id']}\">Dr. " . htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']) . "</option>";
																	}
																	?>
																</select>
															</div>

															<div class="clinical-field-group" style="background: #ffffff; border: 1px solid #d8e8e0; border-radius: 10px; padding: 14px;">
																<label class="clinical-field-label" for="editCirculatingNurse" style="font-weight: 700; color: #1c4d39; font-size: 0.8rem; margin-bottom: 6px; display: block;block; text-transform: none !important;">Circulating Nurse</label>
																<input type="text" id="editCirculatingNurse" name="circulating_nurse" class="form-control" placeholder="Circulating nurse" style="border-radius: 8px; border-color: #d8e8e0;">
															</div>

															<div class="clinical-field-group" style="background: #ffffff; border: 1px solid #d8e8e0; border-radius: 10px; padding: 14px;">
																<label class="clinical-field-label" for="editInstrumentNurse" style="font-weight: 700; color: #1c4d39; font-size: 0.8rem; margin-bottom: 6px; display: block;block; text-transform: none !important;">Instrument Nurse</label>
																<input type="text" id="editInstrumentNurse" name="instrument_nurse" class="form-control" placeholder="Instrument nurse" style="border-radius: 8px; border-color: #d8e8e0;">
															</div>
														</div>
													</div>
												</div>

												<!-- 6. ADDITIONAL INFORMATION CARD -->
												<div style="background: #f1f7f4; border: 1px solid #d8e8e0; border-radius: 14px; padding: 22px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
													<div style="display: flex; align-items: center; gap: 12px; margin-bottom: 18px;">
														<div style="background: #e2f0e8; color: #1c4d39; font-weight: 700; font-size: 0.85rem; width: 28px; height: 28px; border-radius: 6px; display: flex; align-items: center; justify-content: center;">
															06
														</div>
														<div style="background: #e2f0e8; color: #1c4d39; padding: 6px 10px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
															<i class="bi bi-journal-text" style="font-size: 1.1rem;"></i>
														</div>
														<div>
															<h6 style="margin: 0; font-weight: 700; color: #1c4d39; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.5px;">Additional Information</h6>
															<span style="font-size: 0.8rem; color: #52796f;">Clinical remarks, infection statuses, and special precautions.</span>
														</div>
													</div>

													<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
														<div class="clinical-field-group" style="background: #ffffff; border: 1px solid #d8e8e0; border-radius: 10px; padding: 14px;">
															<label class="clinical-field-label" for="editRemarks" style="font-weight: 700; color: #1c4d39; font-size: 0.8rem; margin-bottom: 6px; display: block;block; text-transform: none !important;">Remarks</label>
															<textarea id="editRemarks" name="remarks" class="form-control" rows="3" placeholder="Enter additional remarks or instructions..." style="border-radius: 8px; border-color: #d8e8e0;"></textarea>
														</div>
														<div class="clinical-field-group" style="background: #ffffff; border: 1px solid #d8e8e0; border-radius: 10px; padding: 14px;">
															<label class="clinical-field-label" for="editInfections" style="font-weight: 700; color: #1c4d39; font-size: 0.8rem; margin-bottom: 6px; display: block;block; text-transform: none !important;">Infections / Precautions</label>
															<textarea id="editInfections" name="infections" class="form-control" rows="3" placeholder="Record infection status or precautions..." style="border-radius: 8px; border-color: #d8e8e0;"></textarea>
														</div>
													</div>
												</div>

											</div>
										</div>

										<!-- EDIT ACTIONS (Footer buttons) -->
										<div id="editActionButtons" class="schedule-action-buttons" style="display: none; margin-top: 24px; gap: 12px;">
											<button type="button" class="btn schedule-back-btn" id="backFromEditBtn" style="border: 1px solid #cbd5e1; color: #475569; font-weight: 600;">
												<i class="bi bi-arrow-left"></i> Back
											</button>
											<button type="button" class="btn" id="confirmEditBtn" style="background: #1c4d39; color: #ffffff; font-weight: 600; border: none; box-shadow: 0 2px 4px rgba(28, 77, 57, 0.2);">
												<i class="bi bi-check-lg"></i> Save Changes
											</button>
										</div>

									
							
							
					

							<!-- CANCELLATION SECTION -->
							<div
								id="cancelSection"
								class="cancel-section"
								style="display: none;"
							>
								<div class="cancel-warning">
									<i class="bi bi-exclamation-triangle-fill"></i>
									<div>
										<strong>Cancel this schedule?</strong>
										<span>Please provide a reason for cancellation.</span>
									</div>
								</div>

								<label for="cancelReason" class="cancel-reason-label">Cancellation Reason</label>
								<textarea
									id="cancelReason"
									class="form-control cancel-reason-input"
									rows="4"
									placeholder="Enter the reason for cancellation..."
								></textarea>

								<div id="cancelReasonError" class="cancel-reason-error" style="display: none;">
									Please enter a cancellation reason.
								</div>
								
								<!-- CANCELLATION ACTIONS (Footer buttons) -->
									<div
										id="cancelActionButtons"
										class="schedule-action-buttons"
										style="display: none;"
									>
										<button
											type="button"
											class="btn schedule-back-btn"
											id="backToScheduleBtn"
										>
											<i class="bi bi-arrow-left"></i>
											Back
										</button>

										<button
											type="button"
											class="btn schedule-confirm-cancel-btn"
											id="confirmCancelBtn"
										>
											<i class="bi bi-x-circle-fill"></i>
											Confirm Cancellation
										</button>
									</div>
							</div>
					

				</div>


	
				<!-- FOOTER -->
				<div class="modal-footer schedule-modal-footer">

					<!-- NORMAL ACTIONS -->
					<div
						id="scheduleActionButtons"
						class="schedule-action-buttons"
					>
						<!-- ADDED RESCHEDULE BUTTON -->
						<button
							type="button"
							class="btn"
							id="showRescheduleBtn"
							style="background: #0284c7; color: white;"
						>
							<i class="bi bi-calendar-event"></i>
							Reschedule
						</button>
						
						
						<!-- ADDED EDIT BUTTON -->
						<button
							type="button"
							class="btn"
							id="showEditBtn"
							style="background: #475569; color: white;"
						>
							<i class="bi bi-pencil-square"></i>
							Edit Details
						</button>
						

						<!-- ADDED COMPLETE BUTTON -->
						<button
							type="button"
							class="btn schedule-complete-btn"
							id="completeScheduleBtn"
						>
							<i class="bi bi-check-circle-fill"></i>
							Complete Schedule
						</button>


						<!-- ADDED CANCEL BUTTON -->
						<button
							type="button"
							class="btn schedule-cancel-btn"
							id="showCancelBtn"
						>
							<i class="bi bi-x-circle-fill"></i>
							Cancel Schedule
						</button>
					</div>
							
				</div>
			</form>
		</div>	
	</div>




	<!-- =========================================================
     MANAGE SCHEDULE & CLINICAL LOGS JAVASCRIPT
     ========================================================= -->
<script>
document.addEventListener('DOMContentLoaded', function () {

    /* 1. DOM REFERENCES */
    const manageModal            = document.getElementById('manageScheduleModal');
    const scheduleActionForm     = document.getElementById('scheduleActionForm');
    const scheduleAction         = document.getElementById('scheduleAction');
    const scheduleId             = document.getElementById('scheduleId');
    const hiddenCancelReason     = document.getElementById('hiddenCancelReason');

    const scheduleDetailsSection = document.getElementById('scheduleDetailsSection');
    const rescheduleSection      = document.getElementById('rescheduleSection');
    const cancelSection          = document.getElementById('cancelSection');
    const editSection            = document.getElementById('editSection');

    const scheduleActionButtons   = document.getElementById('scheduleActionButtons');
    const rescheduleActionButtons = document.getElementById('rescheduleActionButtons');
    const cancelActionButtons     = document.getElementById('cancelActionButtons');
    const editActionButtons       = document.getElementById('editActionButtons');

    const cancelReason      = document.getElementById('cancelReason');
    const cancelReasonError = document.getElementById('cancelReasonError');

    const statToggle        = document.getElementById('statProcedureToggle');
    const statLabel         = document.getElementById('statToggleLabel');

    const addAssistantBtn   = document.getElementById('addAssistantBtn');
    const assistantContainer = document.getElementById('assistantRowsContainer');

    /* 2. VIEW SWITCHER */
    function setModalView(view) {
        if (scheduleDetailsSection) scheduleDetailsSection.style.display = 'none';
        if (rescheduleSection)      rescheduleSection.style.display      = 'none';
        if (cancelSection)          cancelSection.style.display          = 'none';
        if (editSection)            editSection.style.display            = 'none';

        if (scheduleActionButtons)   scheduleActionButtons.style.display   = 'none';
        if (rescheduleActionButtons) rescheduleActionButtons.style.display = 'none';
        if (cancelActionButtons)     cancelActionButtons.style.display     = 'none';
        if (editActionButtons)       editActionButtons.style.display       = 'none';

        if (view === 'reschedule') {
            if (rescheduleSection)      rescheduleSection.style.display      = 'block';
            if (rescheduleActionButtons) rescheduleActionButtons.style.display = 'flex';
            if (scheduleAction)         scheduleAction.value                 = 'reschedule';
        } else if (view === 'cancel') {
            if (scheduleDetailsSection) scheduleDetailsSection.style.display = 'block';
            if (cancelSection)          cancelSection.style.display          = 'block';
            if (cancelActionButtons)     cancelActionButtons.style.display     = 'flex';
            if (scheduleAction)         scheduleAction.value                 = 'cancel';
            if (cancelReason)           cancelReason.focus();
        } else if (view === 'edit') {
            if (editSection)          editSection.style.display          = 'block';
            if (editActionButtons)     editActionButtons.style.display     = 'flex';
            if (scheduleAction)       scheduleAction.value               = 'edit';
        } else {
            if (scheduleDetailsSection) scheduleDetailsSection.style.display = 'block';
            if (scheduleActionButtons)   scheduleActionButtons.style.display   = 'flex';
            if (scheduleAction)         scheduleAction.value                 = '';
        }
    }

    /* 3. ASSISTANT SURGEONS REPEATER */
    const doctorOptionsHtml = `
        <option value="">Select assistant surgeon</option>
        <?php 
        $docJsStmt = $pdo->query("SELECT doctor_id, first_name, last_name FROM doctors ORDER BY last_name ASC");
        while ($docJ = $docJsStmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<option value=\"{$docJ['doctor_id']}\">Dr. " . htmlspecialchars($docJ['first_name'] . ' ' . $docJ['last_name']) . "</option>";
        }
        ?>
    `;

    window.addAssistantRow = function(selectedValue = '') {
        if (!assistantContainer) return;
        const rowCount = assistantContainer.children.length + 1;
        
        const rowDiv = document.createElement('div');
        rowDiv.className = 'assistant-row';
        rowDiv.style.cssText = 'display: flex; align-items: center; gap: 10px; background: #f8fafc; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0;';
        
        rowDiv.innerHTML = `
            <div style="background: #e2e8f0; color: #475569; font-weight: 700; font-size: 0.75rem; padding: 4px 8px; border-radius: 4px; min-width: 32px; text-align: center;">
                ${String(rowCount).padStart(2, '0')}
            </div>
            <div style="flex-grow: 1;">
                <select name="assistant_surgeons[]" class="form-control" style="border-radius: 6px; border-color: #cbd5e1; font-size: 0.85rem;">
                    ${doctorOptionsHtml}
                </select>
            </div>
            <button type="button" class="btn remove-assistant-btn" style="border: 1px solid #fecaca; color: #dc2626; background: #fef2f2; border-radius: 6px; padding: 4px 10px;">
                <i class="bi bi-x-lg"></i>
            </button>
        `;

        if (selectedValue) {
            rowDiv.querySelector('select').value = String(selectedValue);
        }

        rowDiv.querySelector('.remove-assistant-btn').addEventListener('click', function() {
            rowDiv.remove();
            reindexAssistantRows();
        });

        assistantContainer.appendChild(rowDiv);
    };

    function reindexAssistantRows() {
        if (!assistantContainer) return;
        const rows = assistantContainer.querySelectorAll('.assistant-row');
        rows.forEach((row, index) => {
            const badge = row.querySelector('div:first-child');
            if (badge) badge.textContent = String(index + 1).padStart(2, '0');
        });
    }

    window.clearAssistantRows = function() {
        if (assistantContainer) assistantContainer.innerHTML = '';
    };

    if (addAssistantBtn) {
        addAssistantBtn.addEventListener('click', function() {
            window.addAssistantRow();
        });
    }

    /* 4. MODAL POPULATION & PRE-LOADING VALUES */
if (manageModal) {
    manageModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        if (!button) return;
		
		
		// ---> PUT THEM HERE <---
        console.log("Surgeon ID from button:", button.dataset.surgeonId);
        console.log("Anesthesiologist ID from button:", button.dataset.anesthesiologistId);
        console.log("Cardiologist ID from button:", button.dataset.cardiologistId);
        console.log("Surgeon Select Element:", document.getElementById('editSurgeon'));
        console.log("Surgeon Select Options Count:", document.getElementById('editSurgeon')?.options.length);
		

        // Populate Schedule ID
        scheduleId.value = button.dataset.scheduleId || '';
        const patientName = button.dataset.patient || '—';

        // Populate Patient Name headers
        document.getElementById('modalPatient').textContent = patientName;
        const editPatientEl = document.getElementById('editPatientName');
        if (editPatientEl) editPatientEl.textContent = patientName;

        // Populate Read-only Summary View fields
        document.getElementById('modalProcedure').textContent        = button.dataset.procedure || '—';
        document.getElementById('modalRoom').textContent             = button.dataset.room || '—';
        document.getElementById('modalDate').textContent             = button.dataset.date || '—';
        document.getElementById('modalTime').textContent             = formatScheduleTime(button.dataset.startTime, button.dataset.endTime);
        document.getElementById('modalSurgeon').textContent          = button.dataset.surgeon || '—';
        document.getElementById('modalAnesthesiologist').textContent = button.dataset.anesthesiologist || '—';

        // ---------------------------------------------------------
        // PRE-LOAD EDIT FORM FIELDS (WITH TEXT & ID FALLBACK MATCHING)
        // ---------------------------------------------------------
        
        // 1. Procedure
        const procSelect = document.getElementById('editProcedure');
        if (procSelect) {
            const pId = button.dataset.procedureId;
            const pName = button.dataset.procedure;
            procSelect.value = pId;
            if (procSelect.value === "" && pName) {
                for (let opt of procSelect.options) {
                    if (opt.text.trim().toLowerCase() === pName.trim().toLowerCase()) {
                        procSelect.value = opt.value;
                        break;
                    }
                }
            }
        }

        // 2. Room (Handles theater 4 / room name matching securely)
        const roomSelect = document.getElementById('editRoom');
        if (roomSelect) {
            const rId = button.dataset.roomId;
            const rName = button.dataset.room;
            roomSelect.value = rId;
            if (roomSelect.value === "" && rName) {
                for (let opt of roomSelect.options) {
                    if (opt.text.trim().toLowerCase() === rName.trim().toLowerCase()) {
                        roomSelect.value = opt.value;
                        break;
                    }
                }
            }
        }

        // 3. Primary Surgeon (with fallback option check & retry timeout)
        const surgeonSelect = document.getElementById('editSurgeon');
        if (surgeonSelect) {
            const sId = button.dataset.surgeonId;
            const sName = button.dataset.surgeon;
            surgeonSelect.value = sId;
            if (surgeonSelect.value === "" && sName) {
                for (let opt of surgeonSelect.options) {
                    if (opt.text.toLowerCase().includes(sName.trim().toLowerCase())) {
                        surgeonSelect.value = opt.value;
                        break;
                    }
                }
            }
            if (surgeonSelect.value === "" && sId) {
                setTimeout(() => { surgeonSelect.value = sId; }, 100);
            }
        }

        // 4. Anesthesiologist (with fallback option check & retry timeout)
        const anesSelect = document.getElementById('editAnesthesiologist');
        if (anesSelect) {
            const aId = button.dataset.anesthesiologistId;
            const aName = button.dataset.anesthesiologist;
            anesSelect.value = aId;
            if (anesSelect.value === "" && aName && aName !== '—') {
                for (let opt of anesSelect.options) {
                    if (opt.text.toLowerCase().includes(aName.trim().toLowerCase())) {
                        anesSelect.value = opt.value;
                        break;
                    }
                }
            }
            if (anesSelect.value === "" && aId) {
                setTimeout(() => { anesSelect.value = aId; }, 100);
            }
        }

        // 5. Priority
        const priorityVal = button.dataset.priority;
        if (priorityVal) {
            const priorityEl = document.getElementById('editPriority');
            if (priorityEl) priorityEl.value = priorityVal;
        }

        // 6. Schedule Dates & Times
        const surgeryDateEl = document.getElementById('editSurgeryDate');
        if (surgeryDateEl) surgeryDateEl.value = button.dataset.date || '';

        const dateEndEl = document.getElementById('editDateEnd');
        if (dateEndEl) dateEndEl.value = button.dataset.dateEnd || '';

        const startTimeEl = document.getElementById('editStartTime');
        if (startTimeEl) startTimeEl.value = button.dataset.startTime || '';

        const endTimeEl = document.getElementById('editEndTime');
        if (endTimeEl) endTimeEl.value = button.dataset.endTime || '';

        // 7. Procedure & Anesthesia Extended Fields
        const anestheticEl = document.getElementById('editAnesthetic');
        if (anestheticEl) anestheticEl.value = button.dataset.anesthetic || '';

        const surgicalTypeEl = document.getElementById('editSurgicalType');
        if (surgicalTypeEl) surgicalTypeEl.value = button.dataset.surgicalType || '';

        // Cardiologist (with retry timeout)
        const cardiologistSelect = document.getElementById('editCardiologist');
        if (cardiologistSelect) {
            const cId = button.dataset.cardiologistId;
            cardiologistSelect.value = cId || '';
            if (cardiologistSelect.value === "" && cId) {
                setTimeout(() => { cardiologistSelect.value = cId; }, 100);
            }
        }

        const circulatingNurseEl = document.getElementById('editCirculatingNurse');
        if (circulatingNurseEl) circulatingNurseEl.value = button.dataset.circulatingNurse || '';

        const instrumentNurseEl = document.getElementById('editInstrumentNurse');
        if (instrumentNurseEl) instrumentNurseEl.value = button.dataset.instrumentNurse || '';

        // 8. Additional Information (Remarks & Infections)
        const remarksEl = document.getElementById('editRemarks');
        if (remarksEl) remarksEl.value = button.dataset.remarks || '';

        const infectionsEl = document.getElementById('editInfections');
        if (infectionsEl) infectionsEl.value = button.dataset.infections || '';

        // STAT Toggle State
        const isStat = button.dataset.isStat === '1';
        if (statToggle) {
            statToggle.checked = isStat;
            if (statLabel) statLabel.textContent = isStat ? 'Yes' : 'No';
        }

        // Assistant Surgeons Repeater Pre-loading
        if (typeof window.clearAssistantRows === 'function') {
            window.clearAssistantRows();
        }
        const assistantsAttr = button.dataset.assistants;
        const originalAssistants = assistantsAttr ? assistantsAttr.split(',') : [];
        if (originalAssistants.length > 0 && originalAssistants[0].trim() !== '') {
            originalAssistants.forEach(astId => {
                window.addAssistantRow(astId.trim());
            });
        } else {
            window.addAssistantRow();
        }

        // Reset view to normal on open
        setModalView('normal');
    });
}
	
	
	

    /* 5. BUTTON CLICKS & SUBMISSIONS */
    document.addEventListener('click', function (e) {
        if (e.target.closest('#showRescheduleBtn')) {
            e.preventDefault();
            setModalView('reschedule');
        }
        if (e.target.closest('#backFromRescheduleBtn')) {
            e.preventDefault();
            setModalView('normal');
        }
        if (e.target.closest('#confirmRescheduleBtn')) {
            e.preventDefault();
            if (scheduleAction) scheduleAction.value = 'reschedule';
            if (scheduleActionForm) scheduleActionForm.submit();
        }

        if (e.target.closest('#showCancelBtn')) {
            e.preventDefault();
            setModalView('cancel');
        }
        if (e.target.closest('#backToScheduleBtn')) {
            e.preventDefault();
            setModalView('normal');
        }

        if (e.target.closest('#showEditBtn')) {
            e.preventDefault();
            setModalView('edit');
        }
        if (e.target.closest('#backFromEditBtn')) {
            e.preventDefault();
            setModalView('normal');
        }
        if (e.target.closest('#confirmEditBtn')) {
            e.preventDefault();
            if (scheduleAction) scheduleAction.value = 'edit';
            if (scheduleActionForm) scheduleActionForm.submit();
        }
    });

    const completeScheduleBtn = document.getElementById('completeScheduleBtn');
    if (completeScheduleBtn) {
        completeScheduleBtn.addEventListener('click', function () {
            if (!scheduleId.value) return;
            scheduleAction.value = 'complete';
            if (hiddenCancelReason) hiddenCancelReason.value = '';
            if (scheduleActionForm) scheduleActionForm.submit();
        });
    }

    const confirmCancelBtn = document.getElementById('confirmCancelBtn');
    if (confirmCancelBtn) {
        confirmCancelBtn.addEventListener('click', function () {
            const reason = cancelReason.value.trim();
            if (reason === '') {
                if (cancelReasonError) cancelReasonError.style.display = 'block';
                cancelReason.focus();
                return;
            }
            if (!scheduleId.value) return;
            scheduleAction.value = 'cancel';
            if (hiddenCancelReason) hiddenCancelReason.value = reason;
            if (scheduleActionForm) scheduleActionForm.submit();
        });
    }

    if (statToggle && statLabel) {
        statToggle.addEventListener('change', function () {
            statLabel.textContent = this.checked ? 'Yes' : 'No';
        });
    }

    function formatScheduleTime(startTime, endTime) {
        if (!startTime) return '—';
        return formatTime(startTime) + (endTime ? ' - ' + formatTime(endTime) : '');
    }

    function formatTime(time) {
        const parts = time.split(':');
        if (parts.length < 2) return time;
        let hour = parseInt(parts[0], 10);
        const minute = parts[1];
        const period = hour >= 12 ? 'PM' : 'AM';
        hour = hour % 12 || 12;
        return hour + ':' + minute + ' ' + period;
    }
});











/*
 * BACKGROUND FETCH & STATUS COUNTERS
 */
function updateCounter(elementId, newValue) {
    const el = document.getElementById(elementId);
    if (el && el.innerText != newValue) {
        el.innerText = newValue;
        el.classList.add('pop-animation');
        setTimeout(() => {
            el.classList.remove('pop-animation');
        }, 400);
    }
}

setInterval(function() {
    fetch('get_status_counts.php?t=' + Date.now())
        .then(response => response.json())
        .then(data => {
            updateCounter('scheduled-count', data.scheduled);
            updateCounter('inprogress-count', data.inprogress);
            updateCounter('completed-count', data.completed);
            updateCounter('cancelled-count', data.cancelled);
        })
        .catch(error => console.error('Fetch error:', error));
}, 5000);


/*
 * IN-PROGRESS PULSE & GLOBAL ANIMATIONS
 */
document.addEventListener("DOMContentLoaded", function() {
    const inProgressRow = document.querySelector('.row-inprogress');
    if (inProgressRow) {
        let isLight = true;
        setInterval(() => {
            if (isLight) {
                inProgressRow.style.backgroundColor = '#fcd34d';
                inProgressRow.style.boxShadow = '0 6px 16px rgba(245, 158, 11, 0.5)';
            } else {
                inProgressRow.style.backgroundColor = '#fffbeb';
                inProgressRow.style.boxShadow = '0 0 0 rgba(245, 158, 11, 0)';
            }
            isLight = !isLight;
        }, 1000);
    }
});


document.addEventListener("DOMContentLoaded", function () {
    const ecgPath = document.getElementById("ecg-dynamic-path");
    const length = 150;
    if (ecgPath) {
        ecgPath.style.strokeDasharray = length;
        ecgPath.style.strokeDashoffset = length;
    }
    let ecgOffset = length;

    const clockIcon = document.getElementById("js-clock-icon");
    const completedIcon = document.getElementById("js-completed-icon");
    const cancelledIcon = document.getElementById("js-cancelled-icon");

    let angle = 0;

    function runGlobalAnimations() {
        if (ecgPath) {
            ecgOffset -= 3;
            if (ecgOffset <= -length) {
                ecgOffset = length;
            }
            ecgPath.style.strokeDashoffset = ecgOffset;
        }

        angle += 0.04;
        const scaleValue = 1 + Math.sin(angle) * 0.12;

        if (clockIcon) clockIcon.style.transform = `scale(${scaleValue})`;
        if (completedIcon) completedIcon.style.transform = `scale(${scaleValue})`;
        if (cancelledIcon) cancelledIcon.style.transform = `scale(${scaleValue})`;

        requestAnimationFrame(runGlobalAnimations);
    }

    runGlobalAnimations();
});





</script>


<?php require_once "includes/footer.php"; ?>
