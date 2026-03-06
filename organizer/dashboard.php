<?php
/**
 * Organizer Dashboard
 * EventStaff Platform
 */

require_once '../config/database.php';
require_once '../includes/session.php';

require_role('organizer');

$user_id = get_user_id();
$email = get_user_email();

// Check if profile is complete
$stmt = $conn->prepare("SELECT * FROM organizer_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

if (!$profile) {
    header('Location: profile.php?first_time=1');
    exit();
}

// Get organizer statistics
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as total_events FROM events WHERE organizer_id = ?");
    $stmt->execute([$user_id]);
    $stats = $stmt->fetch();
    $total_events = $stats['total_events'];
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total_applications FROM shift_applications sa
        JOIN event_shifts es ON sa.shift_id = es.id
        JOIN events e ON es.event_id = e.id
        WHERE e.organizer_id = ?
    ");
    $stmt->execute([$user_id]);
    $apps = $stmt->fetch();
    $total_applications = $apps['total_applications'];
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as pending_applications FROM shift_applications sa
        JOIN event_shifts es ON sa.shift_id = es.id
        JOIN events e ON es.event_id = e.id
        WHERE e.organizer_id = ? AND sa.application_status = 'pending'
    ");
    $stmt->execute([$user_id]);
    $pending = $stmt->fetch();
    $pending_applications = $pending['pending_applications'];
} catch (PDOException $e) {
    $total_events = 0;
    $total_applications = 0;
    $pending_applications = 0;
}

// Get unread notifications count
$stmt = $conn->prepare("
    SELECT COUNT(*) as count FROM notifications 
    WHERE user_id = ? AND status = 'unread'
");
$stmt->execute([$user_id]);
$unread_notifications = $stmt->fetch()['count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organizer Dashboard - EventStaff</title>
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
        }

        .dashboard-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            animation: slideUp 0.5s ease-out;
        }

        .stat-card:nth-child(2) { animation-delay: 0.1s; }
        .stat-card:nth-child(3) { animation-delay: 0.2s; }

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

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            display: block;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 0.95rem;
            opacity: 0.9;
        }

        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }

        .action-card {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.08) 0%, rgba(118, 75, 162, 0.08) 100%);
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #333;
        }

        .action-card:hover {
            border-color: var(--primary);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
            transform: translateY(-5px);
            color: #333;
        }

        .action-card i {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 15px;
            display: block;
        }

        .action-card h5 {
            color: #333;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .action-card p {
            font-size: 0.9rem;
            color: #666;
            margin: 0;
        }

        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 1.8rem;
            }

            .dashboard-card {
                padding: 20px;
            }

            .action-grid {
                grid-template-columns: 1fr;
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
                        <a class="nav-link active" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="events.php">
                            <i class="bi bi-calendar-check me-1"></i>My Events
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="applications.php">
                            <i class="bi bi-file-check me-1"></i>Applications
                            <?php if ($pending_applications > 0): ?>
                                <span class="badge bg-danger ms-2"><?php echo $pending_applications; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="payments.php">
                            <i class="bi bi-wallet2 me-1"></i>Payments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="notifications.php">
                            <i class="bi bi-bell me-1"></i>Notifications
                            <span class="badge bg-danger ms-1 js-notification-badge" data-endpoint="../api/notification_count.php" style="<?php echo $unread_notifications > 0 ? '' : 'display:none;'; ?>"><?php echo $unread_notifications > 0 ? $unread_notifications : ''; ?></span>
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
            <h1><i class="bi bi-speedometer2 me-2"></i>Organizer Dashboard</h1>
            <p style="font-size: 1.1rem; opacity: 0.9; margin-bottom: 0;">Welcome, <?php echo htmlspecialchars($profile['company_name']); ?>!</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container-lg mb-5">
        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <span class="stat-number"><?php echo $total_events; ?></span>
                    <div class="stat-label">Total Events Created</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <span class="stat-number"><?php echo $total_applications; ?></span>
                    <div class="stat-label">Total Applications</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <span class="stat-number"><?php echo $pending_applications; ?></span>
                    <div class="stat-label">Pending Approvals</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="dashboard-card">
            <h5 style="color: var(--primary); font-weight: 700; margin-bottom: 20px;">
                <i class="bi bi-lightning"></i> Quick Actions
            </h5>
            <div class="action-grid">
                <a href="create_event.php" class="action-card">
                    <i class="bi bi-plus-circle"></i>
                    <h5>Create Event</h5>
                    <p>Start planning a new event</p>
                </a>
                <a href="events.php" class="action-card">
                    <i class="bi bi-calendar-check"></i>
                    <h5>My Events</h5>
                    <p>Manage your events and shifts</p>
                </a>
                <a href="applications.php" class="action-card">
                    <i class="bi bi-file-check"></i>
                    <h5>Applications</h5>
                    <p>Review shift applications
                    <?php if ($pending_applications > 0): ?>
                        <br><strong style="color: var(--primary);"><?php echo $pending_applications; ?> Pending</strong>
                    <?php else: ?>
                        <br><span style="color: #28a745; font-weight: 600;">All Reviewed</span>
                    <?php endif; ?>
                    </p>
                </a>
                <a href="payments.php" class="action-card">
                    <i class="bi bi-wallet2"></i>
                    <h5>Payment Tracking</h5>
                    <p>Monitor employee payments</p>
                </a>
                <a href="notifications.php" class="action-card">
                    <i class="bi bi-bell"></i>
                    <h5>Notifications</h5>
                    <p><?php echo $unread_notifications > 0 ? "You have $unread_notifications new messages" : "Stay updated with events"; ?></p>
                </a>
                <a href="profile.php" class="action-card">
                    <i class="bi bi-building"></i>
                    <h5>Edit Profile</h5>
                    <p>Update company information</p>
                </a>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="dashboard-card">
            <h5 style="color: var(--primary); font-weight: 700; margin-bottom: 20px;">
                <i class="bi bi-clock-history"></i> Getting Started
            </h5>
            <div style="color: #666; line-height: 1.8;">
                <p><strong>✓ Step 1:</strong> Create an event with basic information (title, description, location, date)</p>
                <p><strong>✓ Step 2:</strong> Add shifts to your event (specify date, time, staff required, and payment)</p>
                <p><strong>✓ Step 3:</strong> Employees will see your shifts and apply for positions</p>
                <p><strong>✓ Step 4:</strong> Review and approve/reject applications</p>
                <p><strong>✓ Step 5:</strong> Track payments after the event</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/notification-polling.js"></script>
</body>
</html>
