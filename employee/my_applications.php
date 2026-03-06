<?php
/**
 * My Applications - Employee Section
 * EventStaff Platform - Track shift applications
 */

require_once '../config/database.php';
require_once '../includes/session.php';

// Require employee role
require_role('employee');

$employee_id = get_user_id();

// Get all applications
try {
    $stmt = $conn->prepare("
        SELECT 
            sa.id as application_id,
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
            e.description,
            e.location
        FROM shift_applications sa
        JOIN event_shifts es ON sa.shift_id = es.id
        JOIN events e ON es.event_id = e.id
        WHERE sa.employee_id = ?
        ORDER BY sa.applied_at DESC
    ");
    $stmt->execute([$employee_id]);
    $applications = $stmt->fetchAll();
} catch (PDOException $e) {
    $applications = [];
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
    <title>My Applications - EventStaff</title>
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
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            text-align: center;
            margin-bottom: 30px;
            border-top: 4px solid var(--primary);
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

        .app-title h5 {
            margin: 0;
            font-weight: 700;
            color: #333;
            font-size: 1.2rem;
        }

        .app-meta {
            font-size: 0.95rem;
            color: #666;
            margin-top: 5px;
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

        .app-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #555;
        }

        .detail-icon {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 6px;
            color: var(--primary);
            font-size: 1rem;
        }

        .detail-text small {
            color: #888;
            display: block;
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
                        <a class="nav-link" href="shifts.php">
                            <i class="bi bi-calendar-event me-1"></i>Shifts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="my_applications.php">
                            <i class="bi bi-file-check me-1"></i>My Applications
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
                    <h1><i class="bi bi-file-check me-2"></i>My Applications</h1>
                    <p>Track your shift applications and approval status</p>
                </div>
                <div class="d-none d-md-block" style="font-size: 4rem; opacity: 0.2;">
                    <i class="bi bi-list-check"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container-lg mb-5">
        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card" style="border-top-color: #ff9800;">
                    <i class="bi bi-hourglass-split" style="font-size: 2rem; color: #ff9800;"></i>
                    <div class="stat-number" style="color: #ff9800;"><?php echo $pending_count; ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="border-top-color: #28a745;">
                    <i class="bi bi-check-circle" style="font-size: 2rem; color: #28a745;"></i>
                    <div class="stat-number" style="color: #28a745;"><?php echo $approved_count; ?></div>
                    <div class="stat-label">Approved</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="border-top-color: #dc3545;">
                    <i class="bi bi-x-circle" style="font-size: 2rem; color: #dc3545;"></i>
                    <div class="stat-number" style="color: #dc3545;"><?php echo $rejected_count; ?></div>
                    <div class="stat-label">Rejected</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="border-top-color: var(--primary);">
                    <i class="bi bi-file-earmark-text" style="font-size: 2rem; color: var(--primary);"></i>
                    <div class="stat-number"><?php echo count($applications); ?></div>
                    <div class="stat-label">Total</div>
                </div>
            </div>
        </div>

        <!-- Applications List -->
        <?php if (empty($applications)): ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h4>No applications yet</h4>
                <p>You haven't applied for any shifts yet. Browse available shifts and apply today!</p>
                <a href="shifts.php" class="btn" style="background: var(--primary); color: white; margin-top: 20px; text-decoration: none; font-weight: 600;">
                    <i class="bi bi-search me-2"></i>Browse Shifts
                </a>
            </div>
        <?php else: ?>
            <div>
                <?php foreach ($applications as $app): ?>
                    <div class="application-card">
                        <div class="app-header">
                            <div class="app-title">
                                <h5><?php echo htmlspecialchars($app['title']); ?></h5>
                                <div class="app-meta">
                                    <i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($app['location']); ?>
                                </div>
                            </div>
                            <div>
                                <span class="status-badge status-<?php echo $app['application_status']; ?>">
                                    <i class="bi bi-circle-fill me-1"></i>
                                    <?php 
                                    echo match($app['application_status']) {
                                        'pending' => 'Pending Review',
                                        'approved' => 'Approved',
                                        'rejected' => 'Rejected',
                                        default => 'Unknown'
                                    };
                                    ?>
                                </span>
                            </div>
                        </div>

                        <!-- Shift Details -->
                        <div class="app-details">
                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="bi bi-calendar-event"></i>
                                </div>
                                <div>
                                    <strong><?php echo date('M d, Y', strtotime($app['shift_date'])); ?></strong>
                                    <small>Date</small>
                                </div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="bi bi-clock"></i>
                                </div>
                                <div>
                                    <strong><?php echo date('h:i A', strtotime($app['start_time'])) . ' - ' . date('h:i A', strtotime($app['end_time'])); ?></strong>
                                    <small>Time</small>
                                </div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="bi bi-currency-dollar"></i>
                                </div>
                                <div>
                                    <strong>৳<?php echo number_format($app['payment_per_shift'], 2); ?></strong>
                                    <small>Payment</small>
                                </div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="bi bi-calendar-check"></i>
                                </div>
                                <div>
                                    <strong><?php echo date('M d, Y', strtotime($app['applied_at'])); ?></strong>
                                    <small>Applied</small>
                                </div>
                            </div>
                        </div>

                        <!-- Review Date (if reviewed) -->
                        <?php if ($app['reviewed_at']): ?>
                            <div style="background: rgba(102, 126, 234, 0.05); padding: 12px; border-radius: 8px; margin: 15px 0; font-size: 0.9rem; color: #555;">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Review Date:</strong> <?php echo date('M d, Y \a\t h:i A', strtotime($app['reviewed_at'])); ?>
                            </div>
                        <?php else: ?>
                            <div style="background: rgba(255, 193, 7, 0.05); padding: 12px; border-radius: 8px; margin: 15px 0; font-size: 0.9rem; color: #ff9800;">
                                <i class="bi bi-hourglass-split me-2"></i>
                                <strong>Waiting for organizer review...</strong>
                            </div>
                        <?php endif; ?>

                        <!-- Event Description -->
                        <?php if ($app['description']): ?>
                            <div style="background: rgba(102, 126, 234, 0.05); padding: 12px; border-radius: 8px; margin-top: 15px; font-size: 0.9rem; color: #555;">
                                <strong style="color: var(--primary);">Event Details:</strong><br>
                                <?php echo htmlspecialchars(substr($app['description'], 0, 150)); ?>
                                <?php if (strlen($app['description']) > 150): ?>...<?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Footer -->
        <div style="text-align: center; margin-top: 50px; padding: 20px; color: #666; font-size: 0.9rem;">
            <p><a href="shifts.php" style="color: var(--primary); text-decoration: none; font-weight: 600;">← Browse More Shifts</a></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
