<?php
/**
 * Admin Payment Tracking & Management
 * EventStaff Platform - Admin Section
 */

require_once '../config/database.php';
require_once '../config/NotificationService.php';
require_once '../includes/session.php';

require_role('admin');

$admin_id = get_user_id();
$notifier = new NotificationService($conn);
$error = '';
$success = '';

// Handle payment status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_id = intval($_POST['payment_id'] ?? 0);
    $new_status = trim($_POST['payment_status'] ?? '');
    
    if ($payment_id > 0 && in_array($new_status, ['pending', 'paid', 'failed'])) {
        try {
            $detail_stmt = $conn->prepare("
                SELECT
                    p.id,
                    p.payment_status,
                    p.amount,
                    sa.employee_id,
                    e.title,
                    es.shift_date,
                    es.start_time
                FROM payments p
                JOIN shift_applications sa ON p.application_id = sa.id
                JOIN event_shifts es ON sa.shift_id = es.id
                JOIN events e ON es.event_id = e.id
                WHERE p.id = ?
                LIMIT 1
            ");
            $detail_stmt->execute([$payment_id]);
            $payment_detail = $detail_stmt->fetch();

            if (!$payment_detail) {
                $error = 'Payment record not found.';
            } else {
                $old_status = $payment_detail['payment_status'];
            $paid_at = ($new_status === 'paid') ? date('Y-m-d H:i:s') : NULL;
            
            $stmt = $conn->prepare("
                UPDATE payments 
                SET payment_status = ?, paid_at = ?
                WHERE id = ?
            ");
            $stmt->execute([$new_status, $paid_at, $payment_id]);

                if ($old_status !== $new_status) {
                    $status_type_map = [
                        'pending' => 'payment_pending',
                        'paid' => 'payment_paid',
                        'failed' => 'payment_failed'
                    ];

                    $shift_date = date('M d, Y', strtotime($payment_detail['shift_date']));
                    $shift_time = date('h:i A', strtotime($payment_detail['start_time']));
                    $amount = number_format((float) $payment_detail['amount'], 2);
                    $status_label = ucfirst($new_status);

                    $notifier->notify(
                        (int) $payment_detail['employee_id'],
                        $status_type_map[$new_status],
                        'Payment Status Updated',
                        "Your payment for {$payment_detail['title']} on {$shift_date} at {$shift_time} (Amount: BDT {$amount}) is now marked as {$status_label}.",
                        $payment_id,
                        true
                    );
                }
            
            $success = 'Payment status updated successfully!';
            }
        } catch (PDOException $e) {
            $error = 'Failed to update payment status.';
        }
    }
}

try {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND status = 'unread'");
    $stmt->execute([$admin_id]);
    $unread_notifications = (int) ($stmt->fetch()['count'] ?? 0);
} catch (PDOException $e) {
    $unread_notifications = 0;
}

// Get filters
$filter_status = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');
$event_filter = trim($_GET['event'] ?? '');

// Build query
$query = "
    SELECT 
        p.id,
        p.amount,
        p.payment_status,
        p.paid_at,
        p.created_at,
        sa.employee_id,
        sa.application_status,
        sa.applied_at,
        es.shift_date,
        es.start_time,
        es.end_time,
        e.id as event_id,
        e.title,
        e.location,
        e.organizer_id,
        u_emp.email as employee_email,
        ep.full_name,
        u_org.email as organizer_email,
        op.company_name
    FROM payments p
    JOIN shift_applications sa ON p.application_id = sa.id
    JOIN event_shifts es ON sa.shift_id = es.id
    JOIN events e ON es.event_id = e.id
    JOIN users u_emp ON sa.employee_id = u_emp.id
    LEFT JOIN employee_profiles ep ON u_emp.id = ep.user_id
    JOIN users u_org ON e.organizer_id = u_org.id
    LEFT JOIN organizer_profiles op ON u_org.id = op.user_id
    WHERE 1=1
";

$params = [];

if (!empty($filter_status)) {
    $query .= " AND p.payment_status = ?";
    $params[] = $filter_status;
}

if (!empty($search)) {
    $query .= " AND (LOWER(ep.full_name) LIKE LOWER(?) OR LOWER(u_emp.email) LIKE LOWER(?) OR LOWER(e.title) LIKE LOWER(?))";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

if (!empty($event_filter)) {
    $query .= " AND e.id = ?";
    $params[] = $event_filter;
}

$query .= " ORDER BY p.created_at DESC";

try {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $payments = $stmt->fetchAll();
} catch (PDOException $e) {
    $payments = [];
}

// Get all events for filter
try {
    $stmt = $conn->prepare("SELECT id, title FROM events ORDER BY event_date DESC");
    $stmt->execute();
    $events = $stmt->fetchAll();
} catch (PDOException $e) {
    $events = [];
}

// Calculate totals
$total_amount = 0;
$paid_amount = 0;
$pending_amount = 0;
$failed_amount = 0;

foreach ($payments as $p) {
    $total_amount += $p['amount'];
    if ($p['payment_status'] === 'paid') $paid_amount += $p['amount'];
    elseif ($p['payment_status'] === 'pending') $pending_amount += $p['amount'];
    elseif ($p['payment_status'] === 'failed') $failed_amount += $p['amount'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Tracking - EventStaff Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        * {
            --primary: #667eea;
            --primary-dark: #764ba2;
            --admin-color: #dc3545;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding-top: 80px;
        }

        .page-header {
            background: linear-gradient(135deg, var(--admin-color) 0%, #c82333 100%);
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
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
            border-top: 5px solid;
        }

        .stat-card.paid {
            border-top-color: #28a745;
        }

        .stat-card.pending {
            border-top-color: #ff9800;
        }

        .stat-card.failed {
            border-top-color: #dc3545;
        }

        .stat-card.total {
            border-top-color: var(--primary);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin: 10px 0;
        }

        .stat-number.paid { color: #28a745; }
        .stat-number.pending { color: #ff9800; }
        .stat-number.failed { color: #dc3545; }
        .stat-number.total { color: var(--primary); }

        .stat-label {
            color: #666;
            font-size: 0.95rem;
        }

        .filter-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .payment-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
            border-left: 5px solid var(--primary);
        }

        .payment-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .employee-info h6 {
            margin: 0;
            font-weight: 700;
            color: #333;
            font-size: 1.05rem;
        }

        .employee-info small {
            color: #666;
            display: flex;
            align-items: center;
            gap: 4px;
            margin-top: 4px;
        }

        .payment-amount {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
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

        .status-paid {
            background: rgba(40, 167, 69, 0.15);
            color: #28a745;
        }

        .status-failed {
            background: rgba(220, 53, 69, 0.15);
            color: #dc3545;
        }

        .payment-details {
            background: rgba(102, 126, 234, 0.05);
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            font-size: 0.95rem;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }

        .detail-item strong {
            color: var(--primary);
        }

        .action-form {
            display: flex;
            gap: 10px;
            align-items: end;
            margin-top: 15px;
        }

        .status-select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.9rem;
            color: #333;
            background: white;
        }

        .btn-update {
            padding: 8px 14px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-update:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
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

        .filter-title {
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
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
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-bar-chart me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="payments.php">
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
                    <h1><i class="bi bi-wallet2 me-2"></i>Payment Tracking</h1>
                    <p>Manage and monitor all platform payments</p>
                </div>
                <div class="d-none d-md-block" style="font-size: 4rem; opacity: 0.2;">
                    <i class="bi bi-cash-coin"></i>
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
                <div class="stat-card total">
                    <i class="bi bi-cash-coin" style="font-size: 1.5rem; color: var(--primary);"></i>
                    <div class="stat-number total">৳<?php echo number_format($total_amount, 2); ?></div>
                    <div class="stat-label">Total Amount</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card paid">
                    <i class="bi bi-check-circle" style="font-size: 1.5rem; color: #28a745;"></i>
                    <div class="stat-number paid">৳<?php echo number_format($paid_amount, 2); ?></div>
                    <div class="stat-label">Paid</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card pending">
                    <i class="bi bi-hourglass-split" style="font-size: 1.5rem; color: #ff9800;"></i>
                    <div class="stat-number pending">৳<?php echo number_format($pending_amount, 2); ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card failed">
                    <i class="bi bi-x-circle" style="font-size: 1.5rem; color: #dc3545;"></i>
                    <div class="stat-number failed">৳<?php echo number_format($failed_amount, 2); ?></div>
                    <div class="stat-label">Failed</div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-card">
            <div class="filter-title">
                <i class="bi bi-funnel"></i>Filter Payments
            </div>
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" name="search" 
                           placeholder="Employee, event, email..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select class="form-control" name="status">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="paid" <?php echo $filter_status === 'paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="failed" <?php echo $filter_status === 'failed' ? 'selected' : ''; ?>>Failed</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Event</label>
                    <select class="form-control" name="event">
                        <option value="">All Events</option>
                        <?php foreach ($events as $evt): ?>
                            <option value="<?php echo $evt['id']; ?>" 
                                    <?php echo $event_filter === (string)$evt['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($evt['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn" style="background: var(--primary); color: white; font-weight: 600;">
                        <i class="bi bi-search me-2"></i>Search
                    </button>
                    <a href="payments.php" class="btn" style="background: #f0f0f0; color: #555; font-weight: 600;">
                        <i class="bi bi-arrow-clockwise me-2"></i>Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Payments List -->
        <?php if (empty($payments)): ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h4>No payments found</h4>
                <p>There are no payments matching your filters.</p>
            </div>
        <?php else: ?>
            <div>
                <?php foreach ($payments as $payment): ?>
                    <div class="payment-card">
                        <div class="payment-header">
                            <div class="employee-info">
                                <h6><?php echo htmlspecialchars($payment['full_name'] ?? $payment['employee_email']); ?></h6>
                                <small><i class="bi bi-envelope"></i><?php echo htmlspecialchars($payment['employee_email']); ?></small>
                                <small><i class="bi bi-briefcase"></i><?php echo htmlspecialchars($payment['company_name'] ?? 'Unknown Organizer'); ?></small>
                            </div>
                            <div style="text-align: right;">
                                <div class="payment-amount">৳<?php echo number_format($payment['amount'], 2); ?></div>
                                <span class="status-badge status-<?php echo $payment['payment_status']; ?>">
                                    <?php echo ucfirst($payment['payment_status']); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Event & Shift Info -->
                        <div class="payment-details">
                            <div class="details-grid">
                                <div>
                                    <strong>Event:</strong> <?php echo htmlspecialchars($payment['title']); ?>
                                </div>
                                <div>
                                    <strong>Shift Date:</strong> <?php echo date('M d, Y', strtotime($payment['shift_date'])); ?>
                                </div>
                                <div>
                                    <strong>Shift Time:</strong> <?php echo date('h:i A', strtotime($payment['start_time'])) . ' - ' . date('h:i A', strtotime($payment['end_time'])); ?>
                                </div>
                                <div>
                                    <strong>Location:</strong> <?php echo htmlspecialchars($payment['location']); ?>
                                </div>
                                <div>
                                    <strong>Applied:</strong> <?php echo date('M d, Y', strtotime($payment['applied_at'])); ?>
                                </div>
                                <?php if ($payment['paid_at']): ?>
                                    <div>
                                        <strong>Paid:</strong> <?php echo date('M d, Y H:i', strtotime($payment['paid_at'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Status Update Form -->
                        <div class="action-form">
                            <form method="POST" style="display: flex; gap: 10px; width: 100%;">
                                <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                <select name="payment_status" class="status-select" style="flex: 1; max-width: 200px;">
                                    <option value="pending" <?php echo $payment['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="paid" <?php echo $payment['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                    <option value="failed" <?php echo $payment['payment_status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                </select>
                                <button type="submit" class="btn-update">
                                    <i class="bi bi-check me-1"></i>Update Status
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Footer -->
        <div style="text-align: center; margin-top: 50px; padding: 20px; color: #666; font-size: 0.9rem;">
            <p><a href="dashboard.php" style="color: var(--primary); text-decoration: none; font-weight: 600;">← Back to Dashboard</a></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/notification-polling.js"></script>
</body>
</html>
