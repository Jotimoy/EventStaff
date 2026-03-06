<?php
/**
 * Review Shift Applications - Organizer Section
 * EventStaff Platform - Organizer Feature
 */

require_once '../config/database.php';
require_once '../config/NotificationService.php';
require_once '../includes/session.php';

// Require organizer role
require_role('organizer');

// Initialize notification service
$notifier = new NotificationService($conn);

$organizer_id = get_user_id();
$error = '';
$success = '';

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $application_id = intval($_POST['application_id'] ?? 0);
    $action = trim($_POST['action'] ?? '');
    
    if ($application_id > 0 && in_array($action, ['approve', 'reject'])) {
        try {
            // Verify this application belongs to one of the organizer's shifts
            $stmt = $conn->prepare("
                SELECT sa.id, sa.shift_id FROM shift_applications sa
                JOIN event_shifts es ON sa.shift_id = es.id
                JOIN events e ON es.event_id = e.id
                WHERE sa.id = ? AND e.organizer_id = ?
            ");
            $stmt->execute([$application_id, $organizer_id]);
            $app = $stmt->fetch();
            
            if ($app) {
                $new_status = ($action === 'approve') ? 'approved' : 'rejected';
                
                $stmt = $conn->prepare("
                    UPDATE shift_applications 
                    SET application_status = ?, reviewed_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$new_status, $application_id]);
                
                // Get applicant details for notification
                $stmt = $conn->prepare("
                    SELECT sa.employee_id, e.title as event_title, es.shift_date, es.start_time, es.payment_per_shift
                    FROM shift_applications sa
                    JOIN event_shifts es ON sa.shift_id = es.id
                    JOIN events e ON es.event_id = e.id
                    WHERE sa.id = ?
                ");
                $stmt->execute([$application_id]);
                $app_details = $stmt->fetch();
                
                // If approving, create payment record and notify
                if ($action === 'approve') {
                    if ($app_details) {
                        // Create payment record
                        $stmt = $conn->prepare("
                            INSERT INTO payments (application_id, amount, payment_status, created_at)
                            VALUES (?, ?, 'pending', NOW())
                            ON DUPLICATE KEY UPDATE id=id
                        ");
                        $stmt->execute([$application_id, $app_details['payment_per_shift']]);
                    }
                    
                    // Send approval notification to employee
                    if ($app_details) {
                        $shift_date = date('M d, Y', strtotime($app_details['shift_date']));
                        $shift_time = date('h:i A', strtotime($app_details['start_time']));
                        $notifier->notify(
                            $app_details['employee_id'],
                            'application_approved',
                            'Application Approved!',
                            "Your application for {$app_details['event_title']} on $shift_date at $shift_time has been approved! Payment: ৳{$app_details['payment_per_shift']}"
                        );
                    }
                } else {
                    // Send rejection notification to employee
                    if ($app_details) {
                        $shift_date = date('M d, Y', strtotime($app_details['shift_date']));
                        $notifier->notify(
                            $app_details['employee_id'],
                            'application_rejected',
                            'Application Decision',
                            "Your application for {$app_details['event_title']} on $shift_date has been reviewed. Unfortunately, you were not selected for this shift."
                        );
                    }
                }
                
                $success = 'Application ' . ucfirst($new_status) . ' successfully! Notification sent to employee.';
            } else {
                $error = 'Application not found or access denied.';
            }
        } catch (PDOException $e) {
            $error = 'Failed to update application. Please try again.';
        }
    }
}

// Get filters
$filter_status = trim($_GET['status'] ?? '');
$filter_event = trim($_GET['event'] ?? '');
$search = trim($_GET['search'] ?? '');

// Build query
$query = "
    SELECT 
        sa.id as application_id,
        sa.employee_id,
        sa.application_status,
        sa.applied_at,
        sa.reviewed_at,
        es.id as shift_id,
        es.shift_date,
        es.start_time,
        es.end_time,
        es.required_staff,
        es.payment_per_shift,
        e.id as event_id,
        e.title,
        e.location,
        u.email,
        ep.full_name,
        ep.phone,
        ep.skills
    FROM shift_applications sa
    JOIN event_shifts es ON sa.shift_id = es.id
    JOIN events e ON es.event_id = e.id
    JOIN users u ON sa.employee_id = u.id
    LEFT JOIN employee_profiles ep ON u.id = ep.user_id
    WHERE e.organizer_id = ?
";

$params = [$organizer_id];

if (!empty($filter_status)) {
    $query .= " AND sa.application_status = ?";
    $params[] = $filter_status;
}

if (!empty($filter_event)) {
    $query .= " AND e.id = ?";
    $params[] = $filter_event;
}

if (!empty($search)) {
    $query .= " AND (LOWER(ep.full_name) LIKE LOWER(?) OR LOWER(u.email) LIKE LOWER(?))";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$query .= " ORDER BY sa.applied_at DESC";

try {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $applications = $stmt->fetchAll();
} catch (PDOException $e) {
    $applications = [];
}

// Get organizer's events for filter
try {
    $stmt = $conn->prepare("SELECT id, title FROM events WHERE organizer_id = ? ORDER BY event_date DESC");
    $stmt->execute([$organizer_id]);
    $events = $stmt->fetchAll();
} catch (PDOException $e) {
    $events = [];
}

// Get stats
$pending_count = 0;
$approved_count = 0;
$rejected_count = 0;

foreach ($applications as $app) {
    if ($app['application_status'] === 'pending') $pending_count++;
    elseif ($app['application_status'] === 'approved') $approved_count++;
    elseif ($app['application_status'] === 'rejected') $rejected_count++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Applications - EventStaff</title>
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
            margin-bottom: 10px;
        }

        .page-header p {
            font-size: 1.05rem;
            opacity: 0.9;
            margin: 0;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            text-align: center;
            margin-bottom: 25px;
            border-top: 4px solid var(--primary);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin: 10px 0;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        .filter-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .filter-title {
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .application-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
            border-left: 5px solid var(--primary);
            transition: all 0.3s ease;
        }

        .application-card:hover {
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.15);
        }

        .app-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .applicant-info h5 {
            margin: 0;
            font-weight: 700;
            color: #333;
            font-size: 1.2rem;
        }

        .applicant-meta {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .applicant-meta span {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-pending {
            background: rgba(255, 193, 7, 0.15);
            color: #ff9800;
        }

        .status-approved {
            background: rgba(40, 167, 69, 0.15);
            color: #28a745;
        }

        .status-rejected {
            background: rgba(220, 53, 69, 0.15);
            color: #dc3545;
        }

        .shift-info {
            background: rgba(102, 126, 234, 0.05);
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            font-size: 0.95rem;
        }

        .shift-info strong {
            color: var(--primary);
        }

        .employee-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #555;
        }

        .detail-icon {
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 8px;
            color: var(--primary);
        }

        .detail-text strong {
            color: var(--primary);
            display: block;
            font-size: 0.95rem;
        }

        .detail-text small {
            color: #888;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .btn-approve, .btn-reject {
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.9rem;
        }

        .btn-approve {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }

        .btn-approve:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
            color: white;
            text-decoration: none;
        }

        .btn-reject {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }

        .btn-reject:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
            color: white;
            text-decoration: none;
        }

        .btn-approve:disabled, .btn-reject:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-state h4 {
            color: #666;
            margin-bottom: 10px;
        }

        .skills-badge {
            display: inline-block;
            background: rgba(102, 126, 234, 0.1);
            color: var(--primary);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            margin-right: 4px;
            margin-bottom: 4px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); box-shadow: 0 2px 8px rgba(0,0,0,0.1); position: fixed; top: 0; width: 100%; z-index: 1000;">
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
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-house me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="events.php">
                            <i class="bi bi-calendar-event me-1"></i>Events
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="applications.php">
                            <i class="bi bi-file-check me-1"></i>Applications
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
                    <h1><i class="bi bi-file-check me-2"></i>Review Applications</h1>
                    <p>Manage employee applications for your event shifts</p>
                </div>
                <div class="d-none d-md-block" style="font-size: 4rem; opacity: 0.2;">
                    <i class="bi bi-list-check"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container-lg mb-5">
        <!-- Success/Error Messages -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <strong>Success!</strong> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle-fill me-2"></i>
                <strong>Error!</strong> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card" style="border-top-color: #ff9800;">
                    <i class="bi bi-hourglass-split" style="font-size: 1.5rem; color: #ff9800;"></i>
                    <div class="stat-number" style="color: #ff9800;"><?php echo $pending_count; ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="border-top-color: #28a745;">
                    <i class="bi bi-check-circle" style="font-size: 1.5rem; color: #28a745;"></i>
                    <div class="stat-number" style="color: #28a745;"><?php echo $approved_count; ?></div>
                    <div class="stat-label">Approved</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="border-top-color: #dc3545;">
                    <i class="bi bi-x-circle" style="font-size: 1.5rem; color: #dc3545;"></i>
                    <div class="stat-number" style="color: #dc3545;"><?php echo $rejected_count; ?></div>
                    <div class="stat-label">Rejected</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="bi bi-file-earmark-text" style="font-size: 1.5rem; color: var(--primary);"></i>
                    <div class="stat-number"><?php echo count($applications); ?></div>
                    <div class="stat-label">Total</div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-card">
            <div class="filter-title">
                <i class="bi bi-funnel"></i>Filter Applications
            </div>
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Search by Name or Email</label>
                    <input type="text" class="form-control" name="search" 
                           placeholder="Employee name or email" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Event</label>
                    <select class="form-control" name="event">
                        <option value="">All Events</option>
                        <?php foreach ($events as $evt): ?>
                            <option value="<?php echo $evt['id']; ?>" 
                                    <?php echo $filter_event === (string)$evt['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($evt['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select class="form-control" name="status">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $filter_status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $filter_status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn" style="background: var(--primary); color: white; font-weight: 600;">
                        <i class="bi bi-search me-2"></i>Search
                    </button>
                    <a href="applications.php" class="btn" style="background: #f0f0f0; color: #555; font-weight: 600;">
                        <i class="bi bi-arrow-clockwise me-2"></i>Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Applications List -->
        <?php if (empty($applications)): ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h4>No applications found</h4>
                <p>There are no applications matching your filters. Create events and shifts to receive employee applications!</p>
                <a href="events.php" class="btn" style="background: var(--primary); color: white; margin-top: 20px; text-decoration: none; font-weight: 600;">
                    <i class="bi bi-calendar-event me-2"></i>View My Events
                </a>
            </div>
        <?php else: ?>
            <div>
                <?php foreach ($applications as $app): ?>
                    <div class="application-card">
                        <div class="app-header">
                            <div class="applicant-info">
                                <h5>
                                    <a href="employee_profile.php?id=<?php echo $app['employee_id']; ?>" style="color: #333; text-decoration: none;">
                                        <?php echo htmlspecialchars($app['full_name'] ?? $app['email']); ?>
                                    </a>
                                </h5>
                                <div class="applicant-meta">
                                    <span><i class="bi bi-envelope"></i><?php echo htmlspecialchars($app['email']); ?></span>
                                    <?php if ($app['phone']): ?>
                                        <span><i class="bi bi-telephone"></i><?php echo htmlspecialchars($app['phone']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <a href="employee_profile.php?id=<?php echo $app['employee_id']; ?>" style="color: var(--primary); text-decoration: none; font-size: 0.9rem; margin-top: 8px; display: inline-block;">
                                    <i class="bi bi-person-circle me-1"></i>View Full Profile
                                </a>
                            </div>
                            <div>
                                <span class="status-badge status-<?php echo $app['application_status']; ?>">
                                    <i class="bi bi-circle-fill me-1"></i>
                                    <?php 
                                    echo match($app['application_status']) {
                                        'pending' => 'Pending',
                                        'approved' => 'Approved',
                                        'rejected' => 'Rejected',
                                        default => 'Unknown'
                                    };
                                    ?>
                                </span>
                            </div>
                        </div>

                        <!-- Event & Shift Info -->
                        <div class="shift-info">
                            <div style="margin-bottom: 10px;">
                                <strong style="font-size: 1.05rem;"><?php echo htmlspecialchars($app['title']); ?></strong><br>
                                <small style="color: #666;">
                                    <i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($app['location']); ?>
                                </small>
                            </div>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px;">
                                <div>
                                    <strong>Shift Date:</strong> <?php echo date('M d, Y', strtotime($app['shift_date'])); ?>
                                </div>
                                <div>
                                    <strong>Time:</strong> <?php echo date('h:i A', strtotime($app['start_time'])) . ' - ' . date('h:i A', strtotime($app['end_time'])); ?>
                                </div>
                                <div>
                                    <strong>Payment:</strong> ৳<?php echo number_format($app['payment_per_shift'], 2); ?>
                                </div>
                                <div>
                                    <strong>Applied:</strong> <?php echo date('M d, Y', strtotime($app['applied_at'])); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Employee Skills -->
                        <?php if ($app['skills']): ?>
                            <div style="margin: 15px 0;">
                                <strong style="color: var(--primary);">Skills:</strong><br>
                                <?php 
                                $skills = array_filter(explode(',', $app['skills']));
                                foreach ($skills as $skill): 
                                ?>
                                    <span class="skills-badge"><?php echo htmlspecialchars(trim($skill)); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Review Info -->
                        <?php if ($app['reviewed_at']): ?>
                            <div style="background: rgba(102, 126, 234, 0.05); padding: 10px; border-radius: 6px; margin: 15px 0; font-size: 0.9rem; color: #555;">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Reviewed:</strong> <?php echo date('M d, Y \a\t h:i A', strtotime($app['reviewed_at'])); ?>
                            </div>
                        <?php else: ?>
                            <div style="background: rgba(255, 193, 7, 0.05); padding: 10px; border-radius: 6px; margin: 15px 0; font-size: 0.9rem; color: #ff9800;">
                                <i class="bi bi-hourglass-split me-2"></i>
                                <strong>Pending your review...</strong>
                            </div>
                        <?php endif; ?>

                        <!-- Action Buttons -->
                        <?php if ($app['application_status'] === 'pending'): ?>
                            <div class="action-buttons">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="application_id" value="<?php echo $app['application_id']; ?>">
                                    <button type="submit" name="action" value="approve" class="btn-approve">
                                        <i class="bi bi-check-circle"></i>Approve
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="application_id" value="<?php echo $app['application_id']; ?>">
                                    <button type="submit" name="action" value="reject" class="btn-reject">
                                        <i class="bi bi-x-circle"></i>Reject
                                    </button>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="action-buttons">
                                <button class="btn-approve" disabled>
                                    <i class="bi bi-check-circle"></i>Already <?php echo ucfirst($app['application_status']); ?>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Footer -->
        <div style="text-align: center; margin-top: 50px; padding: 20px; color: #666; font-size: 0.9rem;">
            <p><a href="events.php" style="color: var(--primary); text-decoration: none; font-weight: 600;">← Back to Events</a></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
