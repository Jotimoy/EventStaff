<?php
/**
 * Browse Available Shifts - Employee Section
 * EventStaff Platform - Employee Feature
 */

require_once '../config/database.php';
require_once '../includes/session.php';

// Require employee role
require_role('employee');

$employee_id = get_user_id();
$error = '';
$success = '';

// Handle shift application
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_shift'])) {
    $shift_id = intval($_POST['shift_id'] ?? 0);
    
    if ($shift_id > 0) {
        try {
            // Check if employee already applied for this shift
            $stmt = $conn->prepare("
                SELECT id FROM shift_applications 
                WHERE shift_id = ? AND employee_id = ?
            ");
            $stmt->execute([$shift_id, $employee_id]);
            
            if ($stmt->fetch()) {
                $error = 'You have already applied for this shift.';
            } else {
                // Create application
                $stmt = $conn->prepare("
                    INSERT INTO shift_applications (shift_id, employee_id, application_status, applied_at)
                    VALUES (?, ?, 'pending', NOW())
                ");
                $stmt->execute([$shift_id, $employee_id]);
                $success = 'Application submitted successfully! Check "My Applications" to track status.';
            }
        } catch (PDOException $e) {
            $error = 'Failed to apply for shift. Please try again.';
        }
    }
}

// Get filters from URL
$filter_location = trim($_GET['location'] ?? '');
$filter_date = trim($_GET['date'] ?? '');
$search = trim($_GET['search'] ?? '');

// Build query
$query = "
    SELECT 
        es.id,
        es.shift_date,
        es.start_time,
        es.end_time,
        es.required_staff,
        es.payment_per_shift,
        e.id as event_id,
        e.title,
        e.description,
        e.location,
        COUNT(DISTINCT sa.id) as total_applications,
        COUNT(DISTINCT CASE WHEN sa.application_status = 'approved' THEN sa.id END) as approved_count,
        (SELECT COUNT(*) FROM shift_applications WHERE shift_id = es.id AND employee_id = ?) as employee_applied
    FROM event_shifts es
    JOIN events e ON es.event_id = e.id
    LEFT JOIN shift_applications sa ON es.id = sa.shift_id
    WHERE es.shift_date >= CURDATE()
";

$params = [$employee_id];

if (!empty($filter_location)) {
    $query .= " AND LOWER(e.location) LIKE LOWER(?)";
    $params[] = '%' . $filter_location . '%';
}

if (!empty($filter_date)) {
    $query .= " AND es.shift_date = ?";
    $params[] = $filter_date;
}

if (!empty($search)) {
    $query .= " AND LOWER(e.title) LIKE LOWER(?)";
    $params[] = '%' . $search . '%';
}

$query .= " GROUP BY es.id ORDER BY es.shift_date ASC, es.start_time ASC";

try {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $shifts = $stmt->fetchAll();
} catch (PDOException $e) {
    $shifts = [];
}

// Get unique locations for filter
try {
    $stmt = $conn->prepare("
        SELECT DISTINCT location FROM events 
        WHERE id IN (
            SELECT DISTINCT event_id FROM event_shifts 
            WHERE shift_date >= CURDATE()
        )
        ORDER BY location ASC
    ");
    $stmt->execute();
    $locations = $stmt->fetchAll();
} catch (PDOException $e) {
    $locations = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Shifts - EventStaff</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        * {
            --primary: #667eea;
            --primary-dark: #764ba2;
            --success: #28a745;
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

        .filter-card {
            background: white;
            padding: 25px;
            margin-bottom: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .filter-title {
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .shift-card {
            border-left: 5px solid var(--primary);
            padding: 20px;
            background: white;
            border-radius: 8px;
            transition: all 0.3s ease;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            position: relative;
        }

        .shift-card:hover {
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.15);
            border-left-color: var(--primary-dark);
        }

        .shift-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .shift-title {
            flex: 1;
            min-width: 250px;
        }

        .shift-title h5 {
            margin: 0;
            font-weight: 700;
            color: #333;
            font-size: 1.2rem;
        }

        .shift-meta {
            font-size: 0.95rem;
            color: #666;
            margin-top: 5px;
        }

        .shift-status {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-available {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }

        .status-applied {
            background: rgba(102, 126, 234, 0.1);
            color: var(--primary);
        }

        .shift-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
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
            font-size: 1.1rem;
        }

        .detail-text strong {
            color: var(--primary);
            display: block;
        }

        .detail-text small {
            color: #888;
        }

        .shift-description {
            background: rgba(102, 126, 234, 0.05);
            padding: 15px;
            border-radius: 8px;
            color: #555;
            margin: 15px 0;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .shift-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .btn-apply {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }

        .btn-apply:hover {
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-apply:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .btn-detail {
            background: #f0f0f0;
            color: #555;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-detail:hover {
            background: #e0e0e0;
            color: #333;
            text-decoration: none;
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

        .footer-area {
            padding: 10px 0;
            text-align: center;
            color: #666;
            font-size: 0.9rem;
            margin-top: 30px;
        }

        .applied-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(102, 126, 234, 0.2);
            border: 2px solid var(--primary);
            color: var(--primary);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .approval-info {
            background: rgba(108, 117, 125, 0.1);
            color: #6c757d;
            padding: 10px 15px;
            border-radius: 6px;
            font-size: 0.9rem;
            margin-top: 10px;
        }

        .approval-info i {
            margin-right: 5px;
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
                        <a class="nav-link active" href="shifts.php">
                            <i class="bi bi-calendar-event me-1"></i>Shifts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="my_applications.php">
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
                    <h1><i class="bi bi-calendar-event me-2"></i>Available Shifts</h1>
                    <p>Browse and apply for shifts that match your availability</p>
                </div>
                <div class="d-none d-md-block" style="font-size: 4rem; opacity: 0.2;">
                    <i class="bi bi-briefcase"></i>
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

        <!-- Filters -->
        <div class="filter-card">
            <div class="filter-title">
                <i class="bi bi-funnel"></i>Filter Shifts
            </div>
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Search by Event Title</label>
                    <input type="text" class="form-control" name="search" 
                           placeholder="e.g., Tech Conference" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Location</label>
                    <select class="form-control" name="location">
                        <option value="">All Locations</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?php echo htmlspecialchars($loc['location']); ?>" 
                                    <?php echo $filter_location === $loc['location'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($loc['location']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-control" name="date" value="<?php echo htmlspecialchars($filter_date); ?>">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn" style="background: var(--primary); color: white; font-weight: 600;">
                        <i class="bi bi-search me-2"></i>Search
                    </button>
                    <a href="shifts.php" class="btn" style="background: #f0f0f0; color: #555; font-weight: 600;">
                        <i class="bi bi-arrow-clockwise me-2"></i>Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Shifts List -->
        <?php if (empty($shifts)): ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h4>No shifts found</h4>
                <p>There are currently no available shifts matching your filters. Check back later!</p>
                <a href="shifts.php" class="btn" style="background: var(--primary); color: white; margin-top: 20px; text-decoration: none;">
                    <i class="bi bi-arrow-clockwise me-2"></i>Clear Filters
                </a>
            </div>
        <?php else: ?>
            <div style="margin-bottom: 40px;">
                <?php foreach ($shifts as $shift): ?>
                    <div class="shift-card">
                        <?php if ($shift['employee_applied']): ?>
                            <div class="applied-badge">
                                <i class="bi bi-check-circle me-1"></i>Applied
                            </div>
                        <?php endif; ?>

                        <div class="shift-header">
                            <div class="shift-title">
                                <h5><?php echo htmlspecialchars($shift['title']); ?></h5>
                                <div class="shift-meta">
                                    <i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($shift['location']); ?>
                                </div>
                            </div>
                            <div>
                                <?php if ($shift['employee_applied']): ?>
                                    <span class="shift-status status-applied">
                                        <i class="bi bi-check me-1"></i>Applied
                                    </span>
                                <?php else: ?>
                                    <span class="shift-status status-available">
                                        <i class="bi bi-star me-1"></i>Available
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Shift Details -->
                        <div class="shift-details">
                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="bi bi-calendar-event"></i>
                                </div>
                                <div class="detail-text">
                                    <strong><?php echo date('M d, Y', strtotime($shift['shift_date'])); ?></strong>
                                    <small>Date</small>
                                </div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="bi bi-clock"></i>
                                </div>
                                <div class="detail-text">
                                    <strong><?php echo date('h:i A', strtotime($shift['start_time'])) . ' - ' . date('h:i A', strtotime($shift['end_time'])); ?></strong>
                                    <small>Time</small>
                                </div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="bi bi-people"></i>
                                </div>
                                <div class="detail-text">
                                    <strong><?php echo $shift['required_staff']; ?></strong>
                                    <small>Staff Needed</small>
                                </div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-icon">
                                    <i class="bi bi-currency-dollar"></i>
                                </div>
                                <div class="detail-text">
                                    <strong>৳<?php echo number_format($shift['payment_per_shift'], 2); ?></strong>
                                    <small>Per Person</small>
                                </div>
                            </div>
                        </div>

                        <!-- Event Description -->
                        <?php if ($shift['description']): ?>
                            <div class="shift-description">
                                <strong style="color: var(--primary);">About this event:</strong><br>
                                <?php echo htmlspecialchars(substr($shift['description'], 0, 200)); ?>
                                <?php if (strlen($shift['description']) > 200): ?>...<?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Application Stats -->
                        <div class="approval-info">
                            <i class="bi bi-info-circle"></i>
                            <strong><?php echo max(0, $shift['required_staff'] - $shift['approved_count']); ?></strong> position(s) still available
                            (<?php echo $shift['approved_count']; ?>/<?php echo $shift['required_staff']; ?> filled)
                        </div>

                        <!-- Action Buttons -->
                        <div class="shift-actions">
                            <?php if ($shift['employee_applied']): ?>
                                <button class="btn-apply" disabled>
                                    <i class="bi bi-check-circle"></i>Already Applied
                                </button>
                            <?php else: ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="shift_id" value="<?php echo $shift['id']; ?>">
                                    <button type="submit" name="apply_shift" class="btn-apply">
                                        <i class="bi bi-hand-thumbs-up"></i>Apply For This Shift
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="footer-area">
                <p><strong><?php echo count($shifts); ?></strong> shift(s) found | 
                <a href="my_applications.php" style="color: var(--primary); text-decoration: none; font-weight: 600;">View My Applications →</a></p>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
