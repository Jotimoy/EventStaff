<?php
/**
 * Event Detail Page
 * EventStaff Platform - Organizer Section
 */

require_once '../config/database.php';
require_once '../includes/session.php';

// Require organizer role
require_role('organizer');

$organizer_id = get_user_id();
$event_id = $_GET['id'] ?? null;

if (!$event_id) {
    header('Location: events.php');
    exit();
}

// Get event details
try {
    $stmt = $conn->prepare("
        SELECT id, organizer_id, title, description, location, event_date, created_at FROM events 
        WHERE id = ? AND organizer_id = ?
    ");
    $stmt->execute([$event_id, $organizer_id]);
    $event = $stmt->fetch();
    
    if (!$event) {
        header('Location: events.php');
        exit();
    }
    
    // Get shifts for this event
    $stmt = $conn->prepare("
        SELECT 
            id, 
            shift_date, 
            start_time,
            end_time,
            required_staff, 
            payment_per_shift,
            (SELECT COUNT(*) FROM shift_applications WHERE shift_id = event_shifts.id) as applications_count,
            (SELECT COUNT(*) FROM shift_applications WHERE shift_id = event_shifts.id AND application_status = 'approved') as approved_count
        FROM event_shifts 
        WHERE event_id = ?
        ORDER BY shift_date ASC, start_time ASC
    ");
    $stmt->execute([$event_id]);
    $shifts = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $event = null;
}

// Helper functions
function format_datetime($date) {
    $datetime = strtotime($date);
    return date('M d, Y', $datetime);
}

function get_event_status($date) {
    $datetime = strtotime($date);
    // Consider upcoming if date is today or in the future
    if (date('Y-m-d', $datetime) >= date('Y-m-d')) {
        return ['status' => 'upcoming', 'badge' => 'success', 'text' => 'Upcoming'];
    } else {
        return ['status' => 'past', 'badge' => 'secondary', 'text' => 'Past'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($event['title']); ?> - EventStaff</title>
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

        .page-header h1 {
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .detail-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 25px;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .detail-section-title {
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

        .detail-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 25px;
        }

        .detail-item {
            padding: 20px;
            background: #f8f9ff;
            border-radius: 12px;
            border-left: 4px solid var(--primary);
        }

        .detail-label {
            color: #666;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .detail-value {
            color: #333;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .description-box {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            padding: 25px;
            border-radius: 12px;
            border-left: 4px solid var(--primary);
            line-height: 1.8;
            color: #555;
            margin-bottom: 25px;
        }

        .btn-back {
            background: #f0f0f0;
            color: #333;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-back:hover {
            background: #e0e0e0;
            color: var(--primary);
        }

        /* Shifts Section */
        .shifts-container {
            display: grid;
            gap: 20px;
        }

        .shift-card {
            background: white;
            border: 2px solid #f0f0f0;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s ease;
        }

        .shift-card:hover {
            border-color: var(--primary);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.2);
        }

        .shift-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .shift-header h5 {
            color: #333;
            font-weight: 700;
            margin: 0;
            flex: 1;
        }

        .shift-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .shift-stat {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .shift-stat-icon {
            color: var(--primary);
            font-size: 1.3rem;
        }

        .shift-stat-text {
            font-size: 0.9rem;
            color: #666;
        }

        .shift-stat-text strong {
            color: var(--primary);
            font-weight: 700;
        }

        .shift-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-action {
            padding: 8px 14px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-edit {
            background: #17a2b8;
            color: white;
        }

        .btn-edit:hover {
            background: #138496;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .btn-applications {
            background: #ffc107;
            color: #333;
        }

        .btn-applications:hover {
            background: #e0a800;
        }

        .empty-shifts {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .btn-add-shift {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-add-shift:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(40, 167, 69, 0.3);
            color: white;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 1.8rem;
            }

            .detail-row {
                grid-template-columns: 1fr;
            }

            .detail-card {
                padding: 20px;
            }
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
                        <a class="nav-link active" href="events.php">
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
            <a href="events.php" class="btn-back" style="margin-bottom: 20px;">
                <i class="bi bi-arrow-left"></i>Back to Events
            </a>
            <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 20px;">
                <div>
                    <h1><?php echo htmlspecialchars($event['title']); ?></h1>
                    <p style="font-size: 1.1rem; opacity: 0.9; margin-bottom: 0;">
                        <i class="bi bi-calendar-event"></i> <?php echo format_datetime($event['event_date']); ?>
                    </p>
                </div>
                <?php $status = get_event_status($event['event_date']); ?>
                <span style="background: rgba(255,255,255,0.2); padding: 10px 20px; border-radius: 20px; font-weight: 600;">
                    <i class="bi bi-circle-fill me-1"></i><?php echo $status['text']; ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container-lg mb-5">
        <!-- Event Details -->
        <div class="detail-card">
            <div class="detail-section-title">
                <i class="bi bi-info-circle"></i>Event Information
            </div>

            <div class="detail-row">
                <div class="detail-item">
                    <div class="detail-label"><i class="bi bi-geo-alt"></i>Location</div>
                    <div class="detail-value"><?php echo htmlspecialchars($event['location']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label"><i class="bi bi-calendar"></i>Event Date</div>
                    <div class="detail-value"><?php echo date('M d, Y', strtotime($event['event_date'])); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label"><i class="bi bi-clock-history"></i>Created</div>
                    <div class="detail-value"><?php echo date('M d, Y', strtotime($event['created_at'])); ?></div>
                </div>
            </div>

            <div class="description-box">
                <strong style="color: var(--primary); display: block; margin-bottom: 10px;">
                    <i class="bi bi-file-text"></i> Description
                </strong>
                <?php echo htmlspecialchars($event['description']); ?>
            </div>
        </div>

        <!-- Shifts Section -->
        <div class="detail-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
                <div class="detail-section-title" style="margin: 0; padding: 0; border: none;">
                    <i class="bi bi-clock"></i>Shifts (<?php echo count($shifts); ?>)
                </div>
                <a href="event_shifts.php?event_id=<?php echo $event['id']; ?>" class="btn-add-shift">
                    <i class="bi bi-plus-circle"></i>Add Shift
                </a>
            </div>

            <?php if (empty($shifts)): ?>
                <div class="empty-shifts">
                    <i class="bi bi-inbox" style="font-size: 3rem; color: #ddd;"></i>
                    <p style="margin-top: 15px;">No shifts created yet for this event.</p>
                </div>
            <?php else: ?>
                <div class="shifts-container">
                    <?php foreach ($shifts as $shift): ?>
                        <div class="shift-card">
                            <div class="shift-header">
                                <h5>
                                    <i class="bi bi-calendar-day" style="color: var(--primary); margin-right: 8px;"></i>
                                    <?php echo date('M d, Y', strtotime($shift['shift_date'])) . ' ' . date('h:i A', strtotime($shift['start_time'])) . ' - ' . date('h:i A', strtotime($shift['end_time'])); ?>
                                </h5>
                            </div>

                            <div class="shift-stats">
                                <div class="shift-stat">
                                    <i class="bi bi-people shift-stat-icon"></i>
                                    <div class="shift-stat-text">
                                        <strong><?php echo $shift['required_staff']; ?></strong> staff needed
                                    </div>
                                </div>
                                <div class="shift-stat">
                                    <i class="bi bi-currency-dollar shift-stat-icon"></i>
                                    <div class="shift-stat-text">
                                        <strong>৳<?php echo $shift['payment_per_shift']; ?></strong> per shift
                                    </div>
                                </div>
                                <div class="shift-stat">
                                    <i class="bi bi-person-check shift-stat-icon"></i>
                                    <div class="shift-stat-text">
                                        <strong><?php echo $shift['applications_count']; ?></strong> applications
                                    </div>
                                </div>
                                <div class="shift-stat">
                                    <i class="bi bi-check-circle shift-stat-icon"></i>
                                    <div class="shift-stat-text">
                                        <strong><?php echo $shift['approved_count']; ?></strong> approved
                                    </div>
                                </div>
                            </div>

                            <div class="shift-actions">
                                <button class="btn-action btn-edit" onclick="alert('Edit shift functionality coming soon')">
                                    <i class="bi bi-pencil"></i>Edit
                                </button>
                                <button class="btn-action btn-applications" onclick="alert('View applications functionality coming soon')">
                                    <i class="bi bi-person-check"></i>View Applications
                                </button>
                                <button class="btn-action btn-delete" onclick="if(confirm('Delete this shift?')) { alert('Delete functionality coming soon'); }">
                                    <i class="bi bi-trash"></i>Delete
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
