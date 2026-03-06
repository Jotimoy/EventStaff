<?php
/**
 * Organizer Events List Page
 * EventStaff Platform - Organizer Section
 */

require_once '../config/database.php';
require_once '../includes/session.php';

// Require organizer role
require_role('organizer');

$organizer_id = get_user_id();

// Get all events for this organizer
try {
    $stmt = $conn->prepare("
        SELECT 
            e.id, 
            e.title, 
            e.description, 
            e.location, 
            e.event_date, 
            e.created_at,
            COUNT(DISTINCT es.id) as shift_count,
            COUNT(DISTINCT sa.id) as application_count
        FROM events e
        LEFT JOIN event_shifts es ON e.id = es.event_id
        LEFT JOIN shift_applications sa ON es.id = sa.shift_id
        WHERE e.organizer_id = ?
        GROUP BY e.id, e.title, e.description, e.location, e.event_date, e.created_at
        ORDER BY e.event_date DESC
    ");
    $stmt->execute([$organizer_id]);
    $events = $stmt->fetchAll();
} catch (PDOException $e) {
    $events = [];
}

// Helper function to format date
function format_event_date($date) {
    $datetime = strtotime($date);
    return date('M d, Y', $datetime);
}

// Helper function to check if event is upcoming or past
function get_event_status($date) {
    $datetime = strtotime($date);
    // Consider event upcoming if date is today or in the future
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
    <title>My Events - EventStaff</title>
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
            border-bottom: 5px solid rgba(255,255,255,0.1);
        }

        .page-header h1 {
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .page-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .btn-create {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            padding: 12px 28px;
            font-weight: 700;
            border-radius: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-create:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(40, 167, 69, 0.3);
            color: white;
        }

        .event-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            margin-bottom: 25px;
            animation: slideUp 0.5s ease-out;
        }

        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
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

        .event-card-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: start;
        }

        .event-card-header h5 {
            margin: 0;
            font-weight: 700;
            font-size: 1.3rem;
        }

        .event-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .event-card-body {
            padding: 25px;
        }

        .event-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .event-meta-item {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #555;
        }

        .event-meta-item strong {
            color: var(--primary);
        }

        .event-meta-icon {
            font-size: 1.5rem;
            color: var(--primary);
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 8px;
        }

        .event-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }

        .event-stats {
            display: flex;
            gap: 15px;
            padding: 15px 0;
            border-top: 2px solid #f0f0f0;
            border-bottom: 2px solid #f0f0f0;
            margin: 20px 0;
        }

        .event-stat {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
        }

        .event-stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        .event-stat-label {
            font-size: 0.85rem;
            color: #666;
        }

        .event-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-action {
            padding: 10px 16px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }

        .btn-view {
            background: var(--primary);
            color: white;
        }

        .btn-view:hover {
            background: var(--primary-dark);
            color: white;
        }

        .btn-shifts {
            background: #17a2b8;
            color: white;
        }

        .btn-shifts:hover {
            background: #138496;
            color: white;
        }

        .btn-applications {
            background: #ffc107;
            color: #333;
        }

        .btn-applications:hover {
            background: #e0a800;
            color: #333;
        }

        .btn-edit {
            background: #6c757d;
            color: white;
        }

        .btn-edit:hover {
            background: #5a6268;
            color: white;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state-icon {
            font-size: 4rem;
            color: var(--primary);
            opacity: 0.5;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #333;
            font-weight: 700;
            margin-bottom: 10px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 1.8rem;
            }

            .event-card-header {
                flex-direction: column;
                gap: 15px;
            }

            .event-meta {
                grid-template-columns: 1fr;
            }

            .event-actions {
                justify-content: space-between;
            }

            .btn-action {
                font-size: 0.8rem;
                padding: 8px 12px;
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
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="bi bi-calendar-check me-2"></i>My Events</h1>
                    <p>Manage your events and shifts</p>
                </div>
                <div class="d-none d-md-block" style="font-size: 4rem; opacity: 0.2;">
                    <i class="bi bi-calendar-event"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container-lg mb-5">
        <!-- Top Action Bar -->
        <div class="d-flex justify-content-between align-items-center mb-4" style="flex-wrap: wrap; gap: 15px;">
            <div>
                <h5 style="color: #333; margin: 0;">
                    <i class="bi bi-info-circle me-2"></i>
                    Total Events: <strong style="color: var(--primary);"><?php echo count($events); ?></strong>
                </h5>
            </div>
            <a href="create_event.php" class="btn-create">
                <i class="bi bi-plus-circle"></i>Create New Event
            </a>
        </div>

        <!-- Events List -->
        <?php if (empty($events)): ?>
            <!-- Empty State -->
            <div style="background: white; border-radius: 20px; padding: 20px;">
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="bi bi-calendar-x"></i>
                    </div>
                    <h3>No Events Yet</h3>
                    <p style="margin-bottom: 25px;">You haven't created any events. Start by creating your first event!</p>
                    <a href="create_event.php" class="btn-create">
                        <i class="bi bi-plus-circle"></i>Create Your First Event
                    </a>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($events as $event): ?>
                <?php $status = get_event_status($event['event_date']); ?>
                <div class="event-card">
                    <div class="event-card-header">
                        <div>
                            <h5><?php echo htmlspecialchars($event['title']); ?></h5>
                            <small style="opacity: 0.9;">
                                <i class="bi bi-calendar-event"></i>
                                <?php echo format_event_date($event['event_date']); ?>
                            </small>
                        </div>
                        <span class="event-badge badge bg-<?php echo $status['badge']; ?>">
                            <i class="bi bi-circle-fill me-1"></i><?php echo $status['text']; ?>
                        </span>
                    </div>

                    <div class="event-card-body">
                        <!-- Event Meta -->
                        <div class="event-meta">
                            <div class="event-meta-item">
                                <div class="event-meta-icon">
                                    <i class="bi bi-geo-alt-fill"></i>
                                </div>
                                <div>
                                    <strong>Location</strong><br>
                                    <small><?php echo htmlspecialchars($event['location']); ?></small>
                                </div>
                            </div>
                            <div class="event-meta-item">
                                <div class="event-meta-icon">
                                    <i class="bi bi-calendar"></i>
                                </div>
                                <div>
                                    <strong>Created</strong><br>
                                    <small><?php echo date('M d, Y', strtotime($event['created_at'])); ?></small>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="event-description">
                            <?php echo htmlspecialchars(substr($event['description'], 0, 150)); ?>
                            <?php if (strlen($event['description']) > 150): ?>
                                <strong>...</strong>
                            <?php endif; ?>
                        </div>

                        <!-- Stats -->
                        <div class="event-stats">
                            <div class="event-stat">
                                <div class="event-stat-number"><?php echo $event['shift_count']; ?></div>
                                <div class="event-stat-label">Shifts Created</div>
                            </div>
                            <div class="event-stat">
                                <div class="event-stat-number"><?php echo $event['application_count']; ?></div>
                                <div class="event-stat-label">Applications</div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="event-actions">
                            <a href="event_detail.php?id=<?php echo $event['id']; ?>" class="btn-action btn-view">
                                <i class="bi bi-eye"></i>View Details
                            </a>
                            <a href="event_shifts.php?event_id=<?php echo $event['id']; ?>" class="btn-action btn-shifts">
                                <i class="bi bi-clock"></i>Manage Shifts
                            </a>
                            <a href="event_applications.php?event_id=<?php echo $event['id']; ?>" class="btn-action btn-applications">
                                <i class="bi bi-person-check"></i>Applications
                            </a>
                            <button type="button" class="btn-action btn-edit" onclick="alert('Edit functionality coming soon')">
                                <i class="bi bi-pencil"></i>Edit
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
