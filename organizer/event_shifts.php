<?php
/**
 * Event Shifts Management Page
 * EventStaff Platform - Organizer Section
 */

require_once '../config/database.php';
require_once '../includes/session.php';

// Require organizer role
require_role('organizer');

$organizer_id = get_user_id();
$event_id = $_GET['event_id'] ?? null;

if (!$event_id) {
    header('Location: events.php');
    exit();
}

// Verify event belongs to this organizer
try {
    $stmt = $conn->prepare("SELECT * FROM events WHERE id = ? AND organizer_id = ?");
    $stmt->execute([$event_id, $organizer_id]);
    $event = $stmt->fetch();
    
    if (!$event) {
        header('Location: events.php');
        exit();
    }
} catch (PDOException $e) {
    header('Location: events.php');
    exit();
}

$success = '';
$error = '';

// Handle shift creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_shift') {
    $shift_date = trim($_POST['shift_date'] ?? '');
    $shift_time = trim($_POST['shift_time'] ?? '');
    $shift_duration = intval($_POST['shift_duration'] ?? 0);
    $required_staff = intval($_POST['required_staff'] ?? 0);
    $payment = floatval($_POST['payment_per_shift'] ?? 0);
    
    // Validation
    if (empty($shift_date) || empty($shift_time) || $shift_duration <= 0 || $required_staff <= 0 || $payment <= 0) {
        $error = 'All fields are required and must be valid.';
    } else {
        try {
            // Calculate end time from start time + duration
            $start_time_obj = DateTime::createFromFormat('H:i', $shift_time);
            $end_time_obj = clone $start_time_obj;
            $end_time_obj->add(new DateInterval('PT' . $shift_duration . 'H'));
            $end_time = $end_time_obj->format('H:i');
            
            $stmt = $conn->prepare("
                INSERT INTO event_shifts (event_id, shift_date, start_time, end_time, required_staff, payment_per_shift, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$event_id, $shift_date, $shift_time, $end_time, $required_staff, $payment]);
            $success = 'Shift created successfully!';
        } catch (PDOException $e) {
            $error = 'Failed to create shift: ' . $e->getMessage();
        }
    }
}

// Handle shift deletion
if (isset($_GET['delete_shift'])) {
    $shift_id = intval($_GET['delete_shift']);
    try {
        $stmt = $conn->prepare("DELETE FROM event_shifts WHERE id = ? AND event_id = ?");
        $stmt->execute([$shift_id, $event_id]);
        $success = 'Shift deleted successfully!';
        header("Location: event_shifts.php?event_id=$event_id");
        exit();
    } catch (PDOException $e) {
        $error = 'Failed to delete shift.';
    }
}

// Get all shifts for this event
try {
    $stmt = $conn->prepare("
        SELECT 
            es.*,
            COUNT(DISTINCT sa.id) as application_count,
            COUNT(DISTINCT CASE WHEN sa.application_status = 'approved' THEN sa.id END) as approved_count
        FROM event_shifts es
        LEFT JOIN shift_applications sa ON es.id = sa.shift_id
        WHERE es.event_id = ?
        GROUP BY es.id
        ORDER BY es.shift_date ASC, es.start_time ASC
    ");
    $stmt->execute([$event_id]);
    $shifts = $stmt->fetchAll();
} catch (PDOException $e) {
    $shifts = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Shifts - <?php echo htmlspecialchars($event['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        * {
            --primary: #667eea;
            --primary-dark: #764ba2;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding-top: 80px;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 40px;
        }

        .content-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 25px;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .section-title {
            color: var(--primary);
            font-weight: 700;
            font-size: 1.3rem;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 10px 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-submit {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            padding: 12px 28px;
            font-weight: 700;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(40, 167, 69, 0.3);
            color: white;
        }

        .shift-card {
            background: white;
            border: 2px solid #f0f0f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .shift-card:hover {
            border-color: var(--primary);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.15);
        }

        .shift-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .shift-title {
            font-weight: 700;
            font-size: 1.1rem;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .shift-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .stat-item {
            background: #f8f9ff;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid var(--primary);
        }

        .stat-label {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary);
        }

        .btn-action {
            padding: 8px 14px;
            border-radius: 8px;
            border: none;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
            color: white;
            transform: translateY(-2px);
        }

        .btn-back {
            background: #f0f0f0;
            color: #333;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            background: #e0e0e0;
            color: var(--primary);
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }

        @media (max-width: 768px) {
            .content-card { padding: 20px; }
            .shift-stats { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top" style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%) !important;">
        <div class="container-lg">
            <a class="navbar-brand fw-bold" href="../index.php">
                <i class="bi bi-calendar-event me-2"></i>EventStaff
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="events.php">
                            <i class="bi bi-calendar-check me-1"></i>My Events
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="bi bi-person-circle me-1"></i>Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../auth/logout.php">
                            <i class="bi bi-box-arrow-right me-1"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container-lg">
            <a href="events.php" class="btn-back mb-3">
                <i class="bi bi-arrow-left"></i>Back to Events
            </a>
            <h1 style="font-weight: 700; font-size: 2.5rem; margin-bottom: 10px;">
                <i class="bi bi-clock-history me-2"></i>Manage Shifts
            </h1>
            <p style="font-size: 1.1rem; opacity: 0.9; margin: 0;">
                <?php echo htmlspecialchars($event['title']); ?>
            </p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container-lg mb-5">
        <div class="row">
            <!-- Create Shift Form -->
            <div class="col-lg-4 mb-4">
                <div class="content-card">
                    <div class="section-title">
                        <i class="bi bi-plus-circle"></i>Add New Shift
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-circle me-2"></i><?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="action" value="create_shift">

                        <div class="mb-3">
                            <label class="form-label" for="shift_date">
                                <i class="bi bi-calendar"></i>Shift Date
                            </label>
                            <input type="date" class="form-control" id="shift_date" name="shift_date" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="shift_time">
                                <i class="bi bi-clock"></i>Start Time
                            </label>
                            <input type="time" class="form-control" id="shift_time" name="shift_time" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="shift_duration">
                                <i class="bi bi-hourglass-split"></i>Duration (Hours)
                            </label>
                            <input type="number" class="form-control" id="shift_duration" name="shift_duration" 
                                   min="1" placeholder="e.g., 8" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="required_staff">
                                <i class="bi bi-people"></i>Required Staff
                            </label>
                            <input type="number" class="form-control" id="required_staff" name="required_staff" 
                                   min="1" placeholder="e.g., 5" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="payment_per_shift">
                                <i class="bi bi-currency-dollar"></i>Payment Per Person (৳)
                            </label>
                            <input type="number" class="form-control" id="payment_per_shift" name="payment_per_shift" 
                                   min="1" step="0.01" placeholder="e.g., 1500" required>
                        </div>

                        <button type="submit" class="btn btn-submit w-100">
                            <i class="bi bi-plus-circle me-2"></i>Create Shift
                        </button>
                    </form>
                </div>
            </div>

            <!-- Shifts List -->
            <div class="col-lg-8">
                <div class="content-card">
                    <div class="section-title">
                        <i class="bi bi-list-ul"></i>Shifts List (<?php echo count($shifts); ?>)
                    </div>

                    <?php if (empty($shifts)): ?>
                        <div class="empty-state">
                            <i class="bi bi-inbox" style="font-size: 3rem; color: #ddd;"></i>
                            <p style="margin-top: 15px;">No shifts created yet. Add your first shift using the form.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($shifts as $shift): ?>
                            <div class="shift-card">
                                <div class="shift-header">
                                    <div class="shift-title">
                                        <i class="bi bi-calendar-event" style="color: var(--primary);"></i>
                                        <?php echo date('M d, Y', strtotime($shift['shift_date'])) . ' ' . date('h:i A', strtotime($shift['start_time'])) . ' - ' . date('h:i A', strtotime($shift['end_time'])); ?>
                                    </div>
                                    <button class="btn-action btn-delete" 
                                            onclick="if(confirm('Delete this shift? All applications will be removed.')) { window.location.href='?event_id=<?php echo $event_id; ?>&delete_shift=<?php echo $shift['id']; ?>'; }">
                                        <i class="bi bi-trash"></i>Delete
                                    </button>
                                </div>

                                <div class="shift-stats">
                                    <div class="stat-item">
                                        <div class="stat-label">Required Staff</div>
                                        <div class="stat-value"><?php echo $shift['required_staff']; ?></div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-label">Payment</div>
                                        <div class="stat-value">৳<?php echo number_format($shift['payment_per_shift'], 2); ?></div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-label">Applications</div>
                                        <div class="stat-value"><?php echo $shift['application_count']; ?></div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-label">Approved</div>
                                        <div class="stat-value"><?php echo $shift['approved_count']; ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('shift_date').setAttribute('min', today);
    </script>
</body>
</html>
