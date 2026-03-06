<?php
/**
 * Organizer Payment Tracking
 * EventStaff Platform - Organizer Section
 */

require_once '../config/database.php';
require_once '../includes/session.php';

require_role('organizer');

$organizer_id = get_user_id();
$error = '';
$success = '';

// Get filters
$filter_status = trim($_GET['status'] ?? '');
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
        es.shift_date,
        es.start_time,
        es.end_time,
        e.id as event_id,
        e.title,
        e.location,
        u.email,
        ep.full_name
    FROM payments p
    JOIN shift_applications sa ON p.application_id = sa.id
    JOIN event_shifts es ON sa.shift_id = es.id
    JOIN events e ON es.event_id = e.id
    JOIN users u ON sa.employee_id = u.id
    LEFT JOIN employee_profiles ep ON u.id = ep.user_id
    WHERE e.organizer_id = ? AND sa.application_status = 'approved'
";

$params = [$organizer_id];

if (!empty($filter_status)) {
    $query .= " AND p.payment_status = ?";
    $params[] = $filter_status;
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

// Get organizer's events
try {
    $stmt = $conn->prepare("SELECT id, title FROM events WHERE organizer_id = ? ORDER BY event_date DESC");
    $stmt->execute([$organizer_id]);
    $events = $stmt->fetchAll();
} catch (PDOException $e) {
    $events = [];
}

// Calculate totals
$total_amount = 0;
$paid_amount = 0;
$pending_amount = 0;

foreach ($payments as $p) {
    $total_amount += $p['amount'];
    if ($p['payment_status'] === 'paid') $paid_amount += $p['amount'];
    elseif ($p['payment_status'] === 'pending') $pending_amount += $p['amount'];
}

try {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND status = 'unread'");
    $stmt->execute([$organizer_id]);
    $unread_notifications = (int) ($stmt->fetch()['count'] ?? 0);
} catch (PDOException $e) {
    $unread_notifications = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Tracking - EventStaff</title>
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
                            <i class="bi bi-speedometer2 me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="events.php">
                            <i class="bi bi-calendar-check me-1"></i>Events
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="applications.php">
                            <i class="bi bi-file-check me-1"></i>Applications
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
                    <h1><i class="bi bi-wallet2 me-2"></i>Payment Tracking</h1>
                    <p>Track payments to approved employees</p>
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
                    <div class="stat-number total">৳<?php echo number_format($total_amount, 2); ?></div>
                    <div class="stat-label">Total Payable</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card paid">
                    <i class="bi bi-check-circle" style="font-size: 1.5rem; color: #28a745;"></i>
                    <div class="stat-number paid">৳<?php echo number_format($paid_amount, 2); ?></div>
                    <div class="stat-label">Already Paid</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card pending">
                    <i class="bi bi-hourglass-split" style="font-size: 1.5rem; color: #ff9800;"></i>
                    <div class="stat-number pending">৳<?php echo number_format($pending_amount, 2); ?></div>
                    <div class="stat-label">Pending Payment</div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-card">
            <div class="filter-title">
                <i class="bi bi-funnel"></i>Filter Payments
            </div>
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select class="form-control" name="status">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="paid" <?php echo $filter_status === 'paid' ? 'selected' : ''; ?>>Paid</option>
                    </select>
                </div>
                <div class="col-md-6">
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
                <p>Payments will appear here once employees are approved for shifts.</p>
                <a href="applications.php" class="btn" style="background: var(--primary); color: white; margin-top: 20px; text-decoration: none; font-weight: 600;">
                    <i class="bi bi-file-check me-2"></i>Review Applications
                </a>
            </div>
        <?php else: ?>
            <div>
                <?php foreach ($payments as $payment): ?>
                    <div class="payment-card">
                        <div class="payment-header">
                            <div class="employee-info">
                                <h6><?php echo htmlspecialchars($payment['full_name'] ?? $payment['email']); ?></h6>
                                <small><i class="bi bi-envelope"></i><?php echo htmlspecialchars($payment['email']); ?></small>
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
                                    <strong>Status:</strong> Applied & Approved
                                </div>
                                <?php if ($payment['paid_at']): ?>
                                    <div>
                                        <strong>Paid Date:</strong> <?php echo date('M d, Y', strtotime($payment['paid_at'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div style="color: #666; font-size: 0.9rem; margin-top: 10px;">
                            <i class="bi bi-info-circle me-1"></i>
                            Contact admin to update payment status
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
