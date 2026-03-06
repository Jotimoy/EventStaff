<?php
/**
 * Admin Dashboard - System Overview & User Management
 * EventStaff Platform - Admin Section
 */

require_once '../config/database.php';
require_once '../includes/session.php';

// Require admin role
require_role('admin');

$admin_id = get_user_id();
$error = '';
$success = '';

// Handle role change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id'] ?? 0);
    $new_role = trim($_POST['new_role'] ?? '');
    
    if ($user_id > 0 && in_array($new_role, ['admin', 'organizer', 'employee'])) {
        try {
            $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$new_role, $user_id]);
            $success = 'User role updated successfully!';
        } catch (PDOException $e) {
            $error = 'Failed to update user role.';
        }
    }
}

// Get system statistics
try {
    // Total stats
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users");
    $stmt->execute();
    $total_users = $stmt->fetch()['count'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM events");
    $stmt->execute();
    $total_events = $stmt->fetch()['count'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM event_shifts");
    $stmt->execute();
    $total_shifts = $stmt->fetch()['count'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM shift_applications");
    $stmt->execute();
    $total_applications = $stmt->fetch()['count'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM payments");
    $stmt->execute();
    $total_payments = $stmt->fetch()['count'];
    
    // Role breakdown
    $stmt = $conn->prepare("
        SELECT role, COUNT(*) as count FROM users GROUP BY role
    ");
    $stmt->execute();
    $role_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Application status breakdown
    $stmt = $conn->prepare("
        SELECT application_status, COUNT(*) as count FROM shift_applications GROUP BY application_status
    ");
    $stmt->execute();
    $app_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Payment status breakdown
    $stmt = $conn->prepare("
        SELECT payment_status, COUNT(*) as count FROM payments GROUP BY payment_status
    ");
    $stmt->execute();
    $payment_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Total revenue
    $stmt = $conn->prepare("
        SELECT SUM(CAST(amount AS DECIMAL(10,2))) as total FROM payments WHERE payment_status = 'paid'
    ");
    $stmt->execute();
    $revenue = $stmt->fetch()['total'] ?? 0;
    
} catch (PDOException $e) {
    $total_users = 0;
    $total_events = 0;
    $total_shifts = 0;
    $total_applications = 0;
    $total_payments = 0;
}

// Get all users with profiles
try {
    $stmt = $conn->prepare("
        SELECT 
            u.id,
            u.email,
            u.role,
            u.created_at,
            COALESCE(op.company_name, ep.full_name, 'N/A') as name
        FROM users u
        LEFT JOIN organizer_profiles op ON u.id = op.user_id AND u.role = 'organizer'
        LEFT JOIN employee_profiles ep ON u.id = ep.user_id AND u.role = 'employee'
        ORDER BY u.created_at DESC
    ");
    $stmt->execute();
    $all_users = $stmt->fetchAll();
} catch (PDOException $e) {
    $all_users = [];
}

// Get recent activities
try {
    $stmt = $conn->prepare("
        SELECT 'event' as type, e.id, e.title as name, e.created_at FROM events
        UNION ALL
        SELECT 'application', sa.id, CONCAT(u.email, ' - Shift'), sa.applied_at FROM shift_applications sa
        JOIN users u ON sa.employee_id = u.id
        ORDER BY created_at DESC LIMIT 10
    ");
    $stmt->execute();
    $recent_activities = $stmt->fetchAll();
} catch (PDOException $e) {
    $recent_activities = [];
}

// Get unread notifications count
$stmt = $conn->prepare("
    SELECT COUNT(*) as count FROM notifications 
    WHERE user_id = ? AND status = 'unread'
");
$stmt->execute([$admin_id]);
$unread_notifications = $stmt->fetch()['count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - EventStaff</title>
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

        .stat-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            padding: 25px;
            margin-bottom: 25px;
            border-top: 5px solid var(--primary);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.15);
        }

        .stat-number {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--primary);
            margin: 10px 0;
        }

        .stat-label {
            color: #666;
            font-size: 0.95rem;
        }

        .stat-icon {
            font-size: 2rem;
            color: var(--primary);
            opacity: 0.8;
        }

        .card-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            padding: 30px;
            margin-bottom: 30px;
        }

        .section-title {
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.3rem;
        }

        .user-table {
            margin-top: 20px;
        }

        .user-table thead {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.08) 0%, rgba(118, 75, 162, 0.08) 100%);
        }

        .user-table th {
            border: none;
            color: var(--primary);
            font-weight: 700;
            padding: 15px;
        }

        .user-table td {
            padding: 15px;
            border-color: #e0e0e0;
            vertical-align: middle;
        }

        .user-table tbody tr:hover {
            background: rgba(102, 126, 234, 0.03);
        }

        .role-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .role-admin {
            background: rgba(220, 53, 69, 0.15);
            color: #dc3545;
        }

        .role-organizer {
            background: rgba(102, 126, 234, 0.15);
            color: var(--primary);
        }

        .role-employee {
            background: rgba(40, 167, 69, 0.15);
            color: #28a745;
        }

        .role-select {
            padding: 6px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.9rem;
            color: #333;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .role-select:hover {
            border-color: var(--primary);
        }

        .role-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-change {
            padding: 6px 12px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-change:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
        }

        .activity-item {
            padding: 15px;
            border-left: 4px solid var(--primary);
            background: #f8f9fa;
            border-radius: 6px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .activity-type {
            display: inline-block;
            padding: 4px 8px;
            background: var(--primary);
            color: white;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-right: 10px;
        }

        .chart-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .mini-stat {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid var(--primary);
        }

        .mini-stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
            margin: 8px 0;
        }

        .mini-stat-label {
            color: #666;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); box-shadow: 0 2px 8px rgba(0,0,0,0.1); position: fixed; top: 0; width: 100%; z-index: 1000;">
        <div class="container-lg">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="bi bi-shield-check me-2"></i>EventStaff Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="bi bi-bar-chart me-1"></i>Dashboard
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
                    <h1><i class="bi bi-bar-chart-fill me-2"></i>Admin Dashboard</h1>
                    <p>System Overview & User Management</p>
                </div>
                <div class="d-none d-md-block" style="font-size: 4rem; opacity: 0.2;">
                    <i class="bi bi-shield-check"></i>
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

        <!-- Key Statistics -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <i class="bi bi-people stat-icon"></i>
                    <div class="stat-number"><?php echo $total_users; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <i class="bi bi-calendar-event stat-icon"></i>
                    <div class="stat-number"><?php echo $total_events; ?></div>
                    <div class="stat-label">Total Events</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <i class="bi bi-clock stat-icon"></i>
                    <div class="stat-number"><?php echo $total_shifts; ?></div>
                    <div class="stat-label">Total Shifts</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <i class="bi bi-file-check stat-icon"></i>
                    <div class="stat-number"><?php echo $total_applications; ?></div>
                    <div class="stat-label">Total Applications</div>
                </div>
            </div>
        </div>

        <!-- Secondary Statistics -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="stat-card" style="border-top-color: #28a745;">
                    <i class="bi bi-cash-coin stat-icon" style="color: #28a745;"></i>
                    <div class="stat-number" style="color: #28a745;">৳<?php echo number_format($revenue, 2); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card" style="border-top-color: #ff9800;">
                    <i class="bi bi-receipt stat-icon" style="color: #ff9800;"></i>
                    <div class="stat-number" style="color: #ff9800;"><?php echo $total_payments; ?></div>
                    <div class="stat-label">Total Payments</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card" style="border-top-color: #17a2b8;">
                    <i class="bi bi-building stat-icon" style="color: #17a2b8;"></i>
                    <div class="stat-number" style="color: #17a2b8;"><?php echo $role_stats['organizer'] ?? 0; ?></div>
                    <div class="stat-label">Organizers</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card" style="border-top-color: #6f42c1;">
                    <i class="bi bi-person stat-icon" style="color: #6f42c1;"></i>
                    <div class="stat-number" style="color: #6f42c1;"><?php echo $role_stats['employee'] ?? 0; ?></div>
                    <div class="stat-label">Employees</div>
                </div>
            </div>
        </div>

        <!-- Breakdown Charts -->
        <div class="row mb-4">
            <div class="col-lg-6">
                <div class="card-section">
                    <h5 class="section-title">
                        <i class="bi bi-pie-chart"></i>Application Status
                    </h5>
                    <div class="chart-container">
                        <div class="mini-stat">
                            <i class="bi bi-hourglass-split" style="color: #ff9800; font-size: 1.5rem;"></i>
                            <div class="mini-stat-number"><?php echo $app_stats['pending'] ?? 0; ?></div>
                            <div class="mini-stat-label">Pending</div>
                        </div>
                        <div class="mini-stat">
                            <i class="bi bi-check-circle" style="color: #28a745; font-size: 1.5rem;"></i>
                            <div class="mini-stat-number"><?php echo $app_stats['approved'] ?? 0; ?></div>
                            <div class="mini-stat-label">Approved</div>
                        </div>
                        <div class="mini-stat">
                            <i class="bi bi-x-circle" style="color: #dc3545; font-size: 1.5rem;"></i>
                            <div class="mini-stat-number"><?php echo $app_stats['rejected'] ?? 0; ?></div>
                            <div class="mini-stat-label">Rejected</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card-section">
                    <h5 class="section-title">
                        <i class="bi bi-wallet2"></i>Payment Status
                    </h5>
                    <div class="chart-container">
                        <div class="mini-stat">
                            <i class="bi bi-check-circle" style="color: #28a745; font-size: 1.5rem;"></i>
                            <div class="mini-stat-number"><?php echo $payment_stats['paid'] ?? 0; ?></div>
                            <div class="mini-stat-label">Paid</div>
                        </div>
                        <div class="mini-stat">
                            <i class="bi bi-hourglass-split" style="color: #ff9800; font-size: 1.5rem;"></i>
                            <div class="mini-stat-number"><?php echo $payment_stats['pending'] ?? 0; ?></div>
                            <div class="mini-stat-label">Pending</div>
                        </div>
                        <div class="mini-stat">
                            <i class="bi bi-x-circle" style="color: #dc3545; font-size: 1.5rem;"></i>
                            <div class="mini-stat-number"><?php echo $payment_stats['failed'] ?? 0; ?></div>
                            <div class="mini-stat-label">Failed</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Management -->
        <div class="card-section">
            <h5 class="section-title">
                <i class="bi bi-people-fill"></i>User Management
                <span style="background: var(--primary); color: white; padding: 4px 10px; border-radius: 20px; font-size: 0.85rem; margin-left: auto; font-weight: 600;">
                    <?php echo count($all_users); ?> Users
                </span>
            </h5>

            <div class="table-responsive user-table">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Name</th>
                            <th>Current Role</th>
                            <th>Change Role</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_users as $user): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($user['email']); ?></strong>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($user['name']); ?>
                                </td>
                                <td>
                                    <span class="role-badge role-<?php echo $user['role']; ?>">
                                        <i class="bi bi-circle-fill me-1"></i>
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display: flex; gap: 8px;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <select name="new_role" class="role-select">
                                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                            <option value="organizer" <?php echo $user['role'] === 'organizer' ? 'selected' : ''; ?>>Organizer</option>
                                            <option value="employee" <?php echo $user['role'] === 'employee' ? 'selected' : ''; ?>>Employee</option>
                                        </select>
                                        <button type="submit" class="btn-change">Change</button>
                                    </form>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                    </small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card-section">
            <h5 class="section-title">
                <i class="bi bi-clock-history"></i>Recent Activity
            </h5>
            <div>
                <?php if (empty($recent_activities)): ?>
                    <p style="color: #999; text-align: center; padding: 30px;">No recent activity</p>
                <?php else: ?>
                    <?php foreach ($recent_activities as $activity): ?>
                        <div class="activity-item">
                            <div>
                                <span class="activity-type">
                                    <?php echo strtoupper($activity['type']); ?>
                                </span>
                                <strong><?php echo htmlspecialchars(substr($activity['name'], 0, 50)); ?></strong>
                            </div>
                            <small class="text-muted">
                                <?php echo date('M d, Y H:i', strtotime($activity['created_at'] ?? $activity['applied_at'])); ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- System Info -->
        <div class="card-section">
            <h5 class="section-title">
                <i class="bi bi-info-circle"></i>System Information
            </h5>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Platform:</strong> EventStaff</p>
                    <p><strong>Version:</strong> 1.0.0</p>
                    <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Database:</strong> MySQL (eventstaff_db)</p>
                    <p><strong>Current Date:</strong> <?php echo date('M d, Y H:i:s'); ?></p>
                    <p><strong>Admin Email:</strong> <?php echo htmlspecialchars(get_user_email()); ?></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/notification-polling.js"></script>
</body>
</html>
