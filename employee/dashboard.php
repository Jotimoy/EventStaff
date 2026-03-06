<?php
/**
 * Employee Dashboard
 * EventStaff Platform
 */

require_once '../config/database.php';
require_once '../includes/session.php';

require_role('employee');

$user_id = get_user_id();
$email = get_user_email();

// Check if profile is complete
$stmt = $conn->prepare("SELECT * FROM employee_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

// Get available shifts count
$stmt = $conn->prepare("
    SELECT COUNT(*) as count FROM event_shifts 
    WHERE shift_date >= CURDATE()
");
$stmt->execute();
$shifts_count = $stmt->fetch()['count'];

// Get pending applications
$stmt = $conn->prepare("
    SELECT COUNT(*) as count FROM shift_applications 
    WHERE employee_id = ? AND application_status = 'pending'
");
$stmt->execute([$user_id]);
$pending_apps = $stmt->fetch()['count'];

// Get approved applications
$stmt = $conn->prepare("
    SELECT COUNT(*) as count FROM shift_applications 
    WHERE employee_id = ? AND application_status = 'approved'
");
$stmt->execute([$user_id]);
$approved_apps = $stmt->fetch()['count'];

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
    <title>Employee Dashboard - EventStaff</title>
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

        .navbar {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%) !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 40px;
            border-bottom: 5px solid rgba(255,255,255,0.1);
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
            transition: all 0.3s ease;
            text-align: center;
        }

        .stat-card:hover {
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.15);
            transform: translateY(-3px);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin: 10px 0;
        }

        .stat-label {
            color: #666;
            font-size: 0.95rem;
        }

        .action-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: #333;
            display: block;
            margin-bottom: 20px;
        }

        .action-card:hover {
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.15);
            transform: translateY(-3px);
            color: var(--primary);
        }

        .action-card i {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .action-card h5 {
            font-weight: 700;
            margin-bottom: 8px;
        }

        .action-card p {
            font-size: 0.9rem;
            color: #666;
            margin: 0;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary-custom:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            text-decoration: none;
        }

        .welcome-alert {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border: 2px solid var(--primary);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            color: #333;
        }

        .welcome-alert h4 {
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-lg">
            <a class="navbar-brand" href="dashboard.php" style="font-weight: 700; font-size: 1.3rem;">
                <i class="bi bi-briefcase me-2"></i>EventStaff
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="bi bi-house me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="shifts.php">
                            <i class="bi bi-calendar-event me-1"></i>Shifts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="my_applications.php">
                            <i class="bi bi-file-check me-1"></i>My Applications
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="payments.php">
                            <i class="bi bi-wallet2 me-1"></i>Earnings
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
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="bi bi-person-badge me-2"></i>Welcome, <?php echo htmlspecialchars($profile['full_name']); ?>!</h1>
                    <p>Browse shifts, apply for jobs, and track your earnings</p>
                </div>
                <div class="d-none d-md-block" style="font-size: 4rem; opacity: 0.2;">
                    <i class="bi bi-briefcase"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container-lg mb-5">
        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="bi bi-calendar-event" style="font-size: 2rem; color: var(--primary);"></i>
                    <div class="stat-number"><?php echo $shifts_count; ?></div>
                    <div class="stat-label">Available Shifts</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="bi bi-hourglass-split" style="font-size: 2rem; color: #ff9800;"></i>
                    <div class="stat-number" style="color: #ff9800;"><?php echo $pending_apps; ?></div>
                    <div class="stat-label">Pending Applications</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="bi bi-check-circle" style="font-size: 2rem; color: #28a745;"></i>
                    <div class="stat-number" style="color: #28a745;"><?php echo $approved_apps; ?></div>
                    <div class="stat-label">Approved Jobs</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="bi bi-envelope" style="font-size: 2rem; color: var(--primary);"></i>
                    <div class="stat-number"><?php echo htmlspecialchars($email); ?></div>
                    <div class="stat-label" style="font-size: 0.8rem;">Account Email</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row">
            <div class="col-md-6">
                <a href="shifts.php" class="action-card">
                    <i class="bi bi-calendar-event"></i>
                    <h5>Browse Available Shifts</h5>
                    <p>Find and apply for shifts that match your availability</p>
                </a>
            </div>
            <div class="col-md-6">
                <a href="my_applications.php" class="action-card">
                    <i class="bi bi-file-check"></i>
                    <h5>Track My Applications</h5>
                    <p>View status of all your shift applications</p>
                </a>
            </div>
            <div class="col-md-6">
                <a href="payments.php" class="action-card">
                    <i class="bi bi-wallet2"></i>
                    <h5>View My Earnings</h5>
                    <p>Track your approved shifts and payment status</p>
                </a>
            </div>
            <div class="col-md-6">
                <a href="profile.php" class="action-card">
                    <i class="bi bi-person-circle"></i>
                    <h5>Edit Your Profile</h5>
                    <p>Update your personal info, skills, and preferences</p>
                </a>
            </div>
            <div class="col-md-6">
                <a href="notifications.php" class="action-card">
                    <i class="bi bi-bell"></i>
                    <h5>View Notifications</h5>
                    <p><?php echo $unread_notifications > 0 ? "You have $unread_notifications new messages" : "Stay updated with your account activity"; ?></p>
                </a>
            </div>
            <div class="col-md-6">
                <a href="../auth/logout.php" class="action-card" style="border: 2px solid #f0f0f0;">
                    <i class="bi bi-box-arrow-right" style="color: #ccc;"></i>
                    <h5>Logout</h5>
                    <p>Sign out of your EventStaff account</p>
                </a>
            </div>
        </div>

        <!-- Info Box -->
        <div class="welcome-alert" style="margin-top: 40px;">
            <h4><i class="bi bi-info-circle me-2"></i>Getting Started</h4>
            <p style="margin-bottom: 0;">
                🎯 <strong>Start earning today:</strong> Browse available shifts to find jobs that match your schedule. 
                Apply for shifts you're interested in, and wait for event organizers to review your application. 
                Once approved, your earnings will be tracked in the system.
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/notification-polling.js"></script>
</body>
</html>
