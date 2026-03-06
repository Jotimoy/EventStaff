<?php
/**
 * Employee Profile View - For Organizers
 * EventStaff Platform - Organizer Section
 */

require_once '../config/database.php';
require_once '../includes/session.php';

// Require organizer role
require_role('organizer');

$organizer_id = get_user_id();
$employee_id = intval($_GET['id'] ?? 0);

if ($employee_id <= 0) {
    header('Location: applications.php');
    exit();
}

try {
    // Get employee profile
    $stmt = $conn->prepare("
        SELECT u.id, u.email, ep.full_name, ep.phone, ep.skills, ep.experience, ep.created_at
        FROM users u
        LEFT JOIN employee_profiles ep ON u.id = ep.user_id
        WHERE u.id = ? AND u.role = 'employee'
    ");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch();
    
    if (!$employee) {
        header('Location: applications.php');
        exit();
    }
    
    // Get employee's applications for this organizer
    $stmt = $conn->prepare("
        SELECT 
            sa.id,
            sa.application_status,
            sa.applied_at,
            sa.reviewed_at,
            es.shift_date,
            es.start_time,
            es.end_time,
            es.required_staff,
            es.payment_per_shift,
            e.id as event_id,
            e.title,
            e.location
        FROM shift_applications sa
        JOIN event_shifts es ON sa.shift_id = es.id
        JOIN events e ON es.event_id = e.id
        WHERE sa.employee_id = ? AND e.organizer_id = ?
        ORDER BY sa.applied_at DESC
    ");
    $stmt->execute([$employee_id, $organizer_id]);
    $applications = $stmt->fetchAll();
    
} catch (PDOException $e) {
    header('Location: applications.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($employee['full_name'] ?? 'Employee'); ?> - EventStaff</title>
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
            padding: 30px 0;
            margin-bottom: 40px;
        }

        .page-header h1 {
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .profile-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            padding: 30px;
            margin-bottom: 30px;
        }

        .profile-header {
            display: flex;
            align-items: start;
            gap: 30px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            flex-shrink: 0;
        }

        .profile-info h2 {
            margin: 0 0 10px 0;
            color: #333;
            font-weight: 700;
        }

        .profile-info .contact-info {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 15px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #555;
        }

        .contact-item i {
            color: var(--primary);
            font-size: 1.2rem;
        }

        .section-title {
            font-weight: 700;
            color: var(--primary);
            margin-top: 25px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .skills-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 20px;
        }

        .skill-badge {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.15) 0%, rgba(118, 75, 162, 0.15) 100%);
            color: var(--primary);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .experience-text {
            background: rgba(102, 126, 234, 0.05);
            padding: 15px;
            border-left: 4px solid var(--primary);
            border-radius: 8px;
            color: #555;
            line-height: 1.6;
        }

        .application-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .application-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid var(--primary);
        }

        .application-item h6 {
            margin: 0 0 8px 0;
            font-weight: 700;
            color: #333;
        }

        .application-item small {
            color: #666;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 4px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
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

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background: rgba(255, 255, 255, 0.25);
            color: white;
        }

        .member-since {
            color: #999;
            font-size: 0.9rem;
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
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container-lg">
            <a href="applications.php" class="back-button">
                <i class="bi bi-arrow-left"></i>Back to Applications
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container-lg mb-5" style="max-width: 900px;">
        <!-- Profile Card -->
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div class="profile-info" style="flex: 1;">
                    <h2><?php echo htmlspecialchars($employee['full_name'] ?? 'Employee'); ?></h2>
                    <p class="member-since">
                        <i class="bi bi-calendar3 me-1"></i>
                        Member since <?php echo date('M d, Y', strtotime($employee['created_at'])); ?>
                    </p>
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="bi bi-envelope"></i>
                            <span><?php echo htmlspecialchars($employee['email']); ?></span>
                        </div>
                        <?php if ($employee['phone']): ?>
                            <div class="contact-item">
                                <i class="bi bi-telephone"></i>
                                <span><?php echo htmlspecialchars($employee['phone']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <hr style="margin: 30px 0; border-color: #e0e0e0;">

            <!-- Skills Section -->
            <?php if ($employee['skills']): ?>
                <div>
                    <h5 class="section-title">
                        <i class="bi bi-star-fill"></i>Skills
                    </h5>
                    <div class="skills-container">
                        <?php 
                        $skills = array_filter(explode(',', $employee['skills']), fn($s) => trim($s) !== '');
                        if (!empty($skills)) {
                            foreach ($skills as $skill): 
                        ?>
                            <span class="skill-badge"><?php echo htmlspecialchars(trim($skill)); ?></span>
                        <?php 
                            endforeach;
                        } else {
                            echo '<p style="color: #999; margin: 0;">No skills listed</p>';
                        }
                        ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Experience Section -->
            <?php if ($employee['experience']): ?>
                <div>
                    <h5 class="section-title">
                        <i class="bi bi-briefcase-fill"></i>Experience
                    </h5>
                    <div class="experience-text">
                        <?php echo nl2br(htmlspecialchars($employee['experience'])); ?>
                    </div>
                </div>
            <?php else: ?>
                <div>
                    <h5 class="section-title">
                        <i class="bi bi-briefcase-fill"></i>Experience
                    </h5>
                    <p style="color: #999; margin: 0;">No experience information provided</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Applications History -->
        <div class="profile-card">
            <h5 class="section-title" style="margin-top: 0;">
                <i class="bi bi-file-check"></i>Applications History
                <span style="background: var(--primary); color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.8rem; margin-left: auto; font-weight: 600;">
                    <?php echo count($applications); ?>
                </span>
            </h5>

            <?php if (empty($applications)): ?>
                <div style="text-align: center; padding: 40px 20px; color: #999;">
                    <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3; display: block; margin-bottom: 15px;"></i>
                    <p>No applications yet</p>
                </div>
            <?php else: ?>
                <ul class="application-list">
                    <?php foreach ($applications as $app): ?>
                        <li class="application-item">
                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                <div>
                                    <h6>
                                        <?php echo htmlspecialchars($app['title']); ?>
                                        <br>
                                        <small style="font-size: 0.8rem; color: #666; font-weight: normal;">
                                            <i class="bi bi-geo-alt"></i><?php echo htmlspecialchars($app['location']); ?>
                                        </small>
                                    </h6>
                                    <small>
                                        <i class="bi bi-calendar3"></i>
                                        <strong><?php echo date('M d, Y', strtotime($app['shift_date'])); ?></strong>
                                        <?php echo date('h:i A', strtotime($app['start_time'])) . ' - ' . date('h:i A', strtotime($app['end_time'])); ?>
                                    </small>
                                    <br>
                                    <small>
                                        <i class="bi bi-cash-coin"></i>
                                        <strong>৳<?php echo number_format($app['payment_per_shift'], 2); ?></strong> per shift
                                    </small>
                                    <br>
                                    <small>
                                        <i class="bi bi-calendar-check"></i>
                                        Applied on <?php echo date('M d, Y', strtotime($app['applied_at'])); ?>
                                    </small>
                                </div>
                                <span class="status-badge status-<?php echo $app['application_status']; ?>">
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
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div style="text-align: center; padding: 20px; color: #666; font-size: 0.9rem;">
            <a href="applications.php" style="color: var(--primary); text-decoration: none; font-weight: 600;">
                ← Back to Applications
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
