<?php
/**
 * Employee Payment Tracking
 * EventStaff Platform - Employee Section
 */

require_once '../config/database.php';
require_once '../includes/session.php';

require_role('employee');

$employee_id = get_user_id();

try {
    // Get employee profile
    $stmt = $conn->prepare("SELECT full_name FROM employee_profiles WHERE user_id = ?");
    $stmt->execute([$employee_id]);
    $profile = $stmt->fetch();
    $employee_name = $profile['full_name'] ?? 'Employee';
    
    // Get payment statistics
    $stmt = $conn->prepare("
        SELECT 
            SUM(CAST(p.amount AS DECIMAL(10,2))) as total_earned,
            SUM(CASE WHEN p.payment_status = 'paid' THEN CAST(p.amount AS DECIMAL(10,2)) ELSE 0 END) as paid_amount,
            SUM(CASE WHEN p.payment_status = 'pending' THEN CAST(p.amount AS DECIMAL(10,2)) ELSE 0 END) as pending_amount,
            COUNT(CASE WHEN p.payment_status = 'paid' THEN 1 END) as paid_count,
            COUNT(CASE WHEN p.payment_status = 'pending' THEN 1 END) as pending_count
        FROM payments p
        JOIN shift_applications sa ON p.application_id = sa.id
        WHERE sa.employee_id = ?
    ");
    $stmt->execute([$employee_id]);
    $stats = $stmt->fetch();
    
    // Get all payments for this employee
    $stmt = $conn->prepare("
        SELECT 
            p.id,
            p.amount,
            p.payment_status,
            p.paid_at,
            p.created_at,
            es.shift_date,
            es.start_time,
            es.end_time,
            e.title,
            e.location,
            op.company_name
        FROM payments p
        JOIN shift_applications sa ON p.application_id = sa.id
        JOIN event_shifts es ON sa.shift_id = es.id
        JOIN events e ON es.event_id = e.id
        LEFT JOIN organizer_profiles op ON e.organizer_id = op.user_id
        WHERE sa.employee_id = ?
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$employee_id]);
    $payments = $stmt->fetchAll();

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND status = 'unread'");
    $stmt->execute([$employee_id]);
    $unread_notifications = (int) ($stmt->fetch()['count'] ?? 0);
} catch (PDOException $e) {
    $stats = null;
    $payments = [];
    $unread_notifications = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Earnings - EventStaff</title>
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
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
            border-top: 5px solid;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.15);
        }

        .stat-card.total {
            border-top-color: var(--primary);
        }

        .stat-card.paid {
            border-top-color: #28a745;
        }

        .stat-card.pending {
            border-top-color: #ff9800;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin: 10px 0;
        }

        .stat-number.total { color: var(--primary); }
        .stat-number.paid { color: #28a745; }
        .stat-number.pending { color: #ff9800; }

        .stat-label {
            color: #666;
            font-size: 0.95rem;
        }

        .stat-detail {
            color: #999;
            font-size: 0.85rem;
            margin-top: 5px;
        }

        .payment-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
            border-left: 5px solid var(--primary);
            transition: all 0.3s ease;
        }

        .payment-card:hover {
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.15);
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

        .event-info h6 {
            margin: 0;
            font-weight: 700;
            color: #333;
            font-size: 1.05rem;
        }

        .event-info small {
            color: #666;
            display: flex;
            align-items: center;
            gap: 4px;
            margin-top: 4px;
        }

        .payment-amount {
            font-size: 1.8rem;
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

        .section-title {
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.3rem;
        }

        .earnings-summary {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); box-shadow: 0 2px 8px rgba(0,0,0,0.1); position: fixed; top: 0; width: 100%; z-index: 1000;">
        <div class="container-lg">
            <a class="navbar-brand fw-bold" href="dashboard.php">
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
                        <a class="nav-link" href="shifts.php">
                            <i class="bi bi-calendar-check me-1"></i>Browse Shifts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="my_applications.php">
                            <i class="bi bi-file-check me-1"></i>Applications
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="payments.php">
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
                    <h1><i class="bi bi-wallet2 me-2"></i>My Earnings</h1>
                    <p>Track your approved shifts and payments</p>
                </div>
                <div class="d-none d-md-block" style="font-size: 4rem; opacity: 0.2;">
                    <i class="bi bi-cash-coin"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container-lg mb-5">
        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stat-card total">
                    <i class="bi bi-cash-coin" style="font-size: 1.5rem; color: var(--primary);"></i>
                    <div class="stat-number total">৳<?php echo number_format($stats['total_earned'] ?? 0, 2); ?></div>
                    <div class="stat-label">Total Earned</div>
                    <div class="stat-detail">From approved shifts</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card paid">
                    <i class="bi bi-check-circle" style="font-size: 1.5rem; color: #28a745;"></i>
                    <div class="stat-number paid">৳<?php echo number_format($stats['paid_amount'] ?? 0, 2); ?></div>
                    <div class="stat-label">Already Paid</div>
                    <div class="stat-detail"><?php echo ($stats['paid_count'] ?? 0) . ' payments'; ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card pending">
                    <i class="bi bi-hourglass-split" style="font-size: 1.5rem; color: #ff9800;"></i>
                    <div class="stat-number pending">৳<?php echo number_format($stats['pending_amount'] ?? 0, 2); ?></div>
                    <div class="stat-label">Pending Payment</div>
                    <div class="stat-detail"><?php echo ($stats['pending_count'] ?? 0) . ' payments'; ?></div>
                </div>
            </div>
        </div>

        <!-- Earnings Summary -->
        <div class="earnings-summary">
            <h5 class="section-title" style="margin-top: 0;">
                <i class="bi bi-graph-up"></i>Earnings Overview
            </h5>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Total Shifts Approved:</strong> <?php echo count($payments); ?></p>
                    <p><strong>Average Payment Per Shift:</strong> ৳<?php echo (count($payments) > 0) ? number_format(($stats['total_earned'] ?? 0) / count($payments), 2) : '0.00'; ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Status:</strong> 
                        <?php if (($stats['pending_amount'] ?? 0) > 0): ?>
                            <span class="badge bg-warning">Awaiting Payment</span>
                        <?php else: ?>
                            <span class="badge bg-success">All Paid</span>
                        <?php endif; ?>
                    </p>
                    <p><strong>Last Updated:</strong> <?php echo date('M d, Y'); ?></p>
                </div>
            </div>
        </div>

        <!-- Payments List -->
        <h5 class="section-title">
            <i class="bi bi-receipt"></i>Payment History
            <span style="background: var(--primary); color: white; padding: 4px 10px; border-radius: 20px; font-size: 0.85rem; margin-left: auto; font-weight: 600;">
                <?php echo count($payments); ?> Shifts
            </span>
        </h5>

        <?php if (empty($payments)): ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h4>No payments yet</h4>
                <p>Your approved shifts will appear here with payment details.</p>
                <a href="my_applications.php" class="btn" style="background: var(--primary); color: white; margin-top: 20px; text-decoration: none; font-weight: 600;">
                    <i class="bi bi-file-check me-2"></i>Check Application Status
                </a>
            </div>
        <?php else: ?>
            <div>
                <?php foreach ($payments as $payment): ?>
                    <div class="payment-card">
                        <div class="payment-header">
                            <div class="event-info">
                                <h6><?php echo htmlspecialchars($payment['title']); ?></h6>
                                <small><i class="bi bi-geo-alt"></i><?php echo htmlspecialchars($payment['location']); ?></small>
                                <small><i class="bi bi-building"></i><?php echo htmlspecialchars($payment['company_name'] ?? 'Event Organizer'); ?></small>
                            </div>
                            <div style="text-align: right;">
                                <div class="payment-amount">৳<?php echo number_format($payment['amount'], 2); ?></div>
                                <span class="status-badge status-<?php echo $payment['payment_status']; ?>">
                                    <?php echo ucfirst($payment['payment_status']); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Shift Details -->
                        <div class="payment-details">
                            <div class="details-grid">
                                <div>
                                    <strong>Shift Date:</strong> <?php echo date('M d, Y', strtotime($payment['shift_date'])); ?>
                                </div>
                                <div>
                                    <strong>Shift Time:</strong> <?php echo date('h:i A', strtotime($payment['start_time'])) . ' - ' . date('h:i A', strtotime($payment['end_time'])); ?>
                                </div>
                                <div>
                                    <strong>Status:</strong> Approved & Scheduled
                                </div>
                                <div>
                                    <strong>Created:</strong> <?php echo date('M d, Y', strtotime($payment['created_at'])); ?>
                                </div>
                                <?php if ($payment['paid_at']): ?>
                                    <div>
                                        <strong>Paid Date:</strong> <?php echo date('M d, Y', strtotime($payment['paid_at'])); ?>
                                    </div>
                                <?php else: ?>
                                    <div style="color: #ff9800;">
                                        <strong><i class="bi bi-hourglass-split me-1"></i>Awaiting Payment</strong>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Status Message -->
                        <?php if ($payment['payment_status'] === 'paid'): ?>
                            <div style="background: rgba(40, 167, 69, 0.05); padding: 10px; border-radius: 6px; color: #28a745; font-size: 0.9rem;">
                                <i class="bi bi-check-circle me-1"></i>
                                <strong>Payment Complete</strong> - Amount transferred to your account
                            </div>
                        <?php else: ?>
                            <div style="background: rgba(255, 193, 7, 0.05); padding: 10px; border-radius: 6px; color: #ff9800; font-size: 0.9rem;">
                                <i class="bi bi-hourglass-split me-1"></i>
                                <strong>Payment Pending</strong> - Waiting for organizer/admin to process
                            </div>
                        <?php endif; ?>
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
