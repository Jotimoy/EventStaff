<?php
/**
 * Organizer Notification Center
 * EventStaff Platform - Organizer Section
 */

require_once '../config/database.php';
require_once '../config/NotificationService.php';
require_once '../includes/session.php';

require_role('organizer');

$organizer_id = get_user_id();
$notifier = new NotificationService($conn);
$action = $_GET['action'] ?? '';

// Handle mark as read
if ($action === 'read' && isset($_GET['id'])) {
    $notification_id = intval($_GET['id']);
    $notifier->markAsRead($notification_id);
    header('Location: notifications.php');
    exit();
}

// Handle mark all as read
if ($action === 'read_all') {
    $notifier->markAllAsRead($organizer_id);
    header('Location: notifications.php');
    exit();
}

// Get all notifications
try {
    $stmt = $conn->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$organizer_id]);
    $notifications = $stmt->fetchAll();
} catch (PDOException $e) {
    $notifications = [];
}

$unread_count = count(array_filter($notifications, fn($n) => $n['status'] === 'unread'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - EventStaff</title>
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

        .notification-item {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 15px;
            border-left: 5px solid;
            transition: all 0.3s ease;
        }

        .notification-item.unread {
            background: rgba(102, 126, 234, 0.03);
            border-left-color: var(--primary);
        }

        .notification-item.read {
            border-left-color: #ccc;
        }

        .notification-item.approved {
            border-left-color: #28a745;
        }

        .notification-item.rejected {
            border-left-color: #dc3545;
        }

        .notification-item.payment {
            border-left-color: #ff9800;
        }

        .notification-item:hover {
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
        }

        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }

        .notification-title {
            font-weight: 700;
            color: #333;
            font-size: 1.05rem;
            margin-bottom: 5px;
        }

        .notification-message {
            color: #666;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .notification-meta {
            display: flex;
            gap: 15px;
            font-size: 0.85rem;
            color: #999;
        }

        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-right: 15px;
        }

        .icon-approved { background: rgba(40, 167, 69, 0.1); color: #28a745; }
        .icon-rejected { background: rgba(220, 53, 69, 0.1); color: #dc3545; }
        .icon-payment { background: rgba(255, 193, 7, 0.1); color: #ff9800; }
        .icon-event { background: rgba(102, 126, 234, 0.1); color: var(--primary); }

        .badge-unread {
            background: var(--primary);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
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

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .btn-mark-read {
            padding: 8px 14px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
        }

        .btn-mark-read:hover {
            background: var(--primary-dark);
            color: white;
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
                        <a class="nav-link" href="events.php">
                            <i class="bi bi-calendar-event me-1"></i>My Events
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="applications.php">
                            <i class="bi bi-file-earmark-check me-1"></i>Applications
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="payments.php">
                            <i class="bi bi-wallet2 me-1"></i>Payments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="notifications.php">
                            <i class="bi bi-bell me-1"></i>Notifications
                            <span class="badge bg-danger ms-1 js-notification-badge" data-endpoint="../api/notification_count.php" style="<?php echo $unread_count > 0 ? '' : 'display:none;'; ?>"><?php echo $unread_count > 0 ? $unread_count : ''; ?></span>
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
                    <h1><i class="bi bi-bell-fill me-2"></i>Notifications</h1>
                    <p>Monitor all platform activities and updates</p>
                </div>
                <div class="d-none d-md-block" style="font-size: 4rem; opacity: 0.2;">
                    <i class="bi bi-bell"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container-lg mb-5" style="max-width: 800px;">
        <!-- Action Buttons -->
        <?php if ($unread_count > 0): ?>
            <div class="action-buttons">
                <a href="notifications.php?action=read_all" class="btn-mark-read">
                    <i class="bi bi-check-all me-1"></i>Mark All as Read
                </a>
            </div>
        <?php endif; ?>

        <!-- Notifications List -->
        <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h4>No notifications yet</h4>
                <p>All your event updates, applications, and system messages will appear here</p>
                <a href="events.php" class="btn-mark-read" style="display: inline-block; margin-top: 20px;">
                    <i class="bi bi-calendar-event me-1"></i>Create Your First Event
                </a>
            </div>
        <?php else: ?>
            <div>
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?php echo strtolower($notification['status']); ?> <?php 
                        if (strpos($notification['type'], 'event') !== false) echo 'event';
                        elseif (strpos($notification['type'], 'payment') !== false) echo 'payment';
                    ?>">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div style="display: flex; align-items: start; flex: 1;">
                                <div class="notification-icon <?php
                                    if (strpos($notification['type'], 'event') !== false) echo 'icon-event';
                                    elseif (strpos($notification['type'], 'payment') !== false) echo 'icon-payment';
                                    else echo 'icon-event';
                                ?>">
                                    <?php if (strpos($notification['type'], 'event') !== false): ?>
                                        <i class="bi bi-calendar2-event"></i>
                                    <?php elseif (strpos($notification['type'], 'payment') !== false): ?>
                                        <i class="bi bi-wallet2"></i>
                                    <?php else: ?>
                                        <i class="bi bi-info-circle-fill"></i>
                                    <?php endif; ?>
                                </div>
                                <div style="flex: 1;">
                                    <div class="notification-header" style="gap: 10px;">
                                        <div>
                                            <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                                            <?php if ($notification['status'] === 'unread'): ?>
                                                <span class="badge-unread">New</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></div>
                                    <div class="notification-meta">
                                        <span><i class="bi bi-clock me-1"></i><?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php if ($notification['status'] === 'unread'): ?>
                                <a href="notifications.php?action=read&id=<?php echo $notification['id']; ?>" class="btn-mark-read" style="margin-left: 10px; padding: 6px 10px; font-size: 0.8rem; white-space: nowrap;">
                                    Mark Read
                                </a>
                            <?php endif; ?>
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
