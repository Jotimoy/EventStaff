<?php
/**
 * Create Event Page
 * EventStaff Platform - Organizer Section
 */

require_once '../config/database.php';
require_once '../includes/session.php';

// Require organizer role
require_role('organizer');

$error = '';
$success = '';
$organizer_id = get_user_id();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $event_date = trim($_POST['event_date'] ?? '');
    $event_time = trim($_POST['event_time'] ?? '');
    $required_staff = !empty($_POST['required_staff']) ? intval($_POST['required_staff']) : 0;
    $payment_per_shift = !empty($_POST['payment_per_shift']) ? floatval($_POST['payment_per_shift']) : 0;
    $shift_duration_hours = !empty($_POST['shift_duration_hours']) ? intval($_POST['shift_duration_hours']) : 0;
    
    // Validation
    if (empty($title)) {
        $error = 'Event title is required.';
    } elseif (empty($description)) {
        $error = 'Description is required.';
    } elseif (empty($location)) {
        $error = 'Location is required.';
    } elseif (empty($event_date)) {
        $error = 'Event date is required.';
    } elseif (empty($event_time)) {
        $error = 'Event time is required.';
    } elseif (empty($_POST['required_staff'])) {
        $error = 'Required staff number is required.';
    } elseif (empty($_POST['payment_per_shift'])) {
        $error = 'Payment amount is required.';
    } elseif (empty($_POST['shift_duration_hours'])) {
        $error = 'Shift duration is required.';
    } elseif (strlen($title) < 3) {
        $error = 'Event title must be at least 3 characters.';
    } elseif (strlen($description) < 10) {
        $error = 'Description must be at least 10 characters.';
    } elseif ($required_staff < 1) {
        $error = 'Required staff must be at least 1.';
    } elseif ($payment_per_shift < 1) {
        $error = 'Payment amount must be greater than 0.';
    } elseif ($shift_duration_hours < 1) {
        $error = 'Shift duration must be at least 1 hour.';
    } else {
        // Check if date is in the future
        $event_datetime = strtotime($event_date . ' ' . $event_time);
        $now = time();
        
        if ($event_datetime <= $now) {
            $error = 'Event date and time must be in the future.';
        } else {
            try {
                // Create event
                $stmt = $conn->prepare("
                    INSERT INTO events (organizer_id, title, description, location, event_date, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$organizer_id, $title, $description, $location, $event_date]);
                
                // Get the event ID that was just created
                $event_id = $conn->lastInsertId();
                
                // Calculate end time from start time + duration hours
                $start_time_obj = DateTime::createFromFormat('H:i', $event_time);
                $end_time_obj = clone $start_time_obj;
                $end_time_obj->add(new DateInterval('PT' . $shift_duration_hours . 'H'));
                $end_time = $end_time_obj->format('H:i');
                
                // Auto-create a shift for this event
                $stmt = $conn->prepare("
                    INSERT INTO event_shifts (event_id, shift_date, start_time, end_time, required_staff, payment_per_shift, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$event_id, $event_date, $event_time, $end_time, $required_staff, $payment_per_shift]);
                
                $success = 'Event created successfully with initial shift!';
                
                // Redirect after 2 seconds
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'events.php';
                    }, 2000);
                </script>";
                
            } catch (PDOException $e) {
                $error = 'Failed to create event: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Event - EventStaff</title>
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
            margin-bottom: 0;
        }

        .form-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.1);
            padding: 40px;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-section {
            margin-bottom: 30px;
        }

        .form-section h5 {
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-control, .form-select {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9ff;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            color: white;
            padding: 14px 40px;
            font-weight: 700;
            border-radius: 12px;
            font-size: 1.05rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-back {
            background: #f0f0f0;
            color: #333;
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back:hover {
            background: #e0e0e0;
            color: var(--primary);
        }

        /* Alert Styling */
        .alert {
            border-radius: 12px;
            border: none;
            animation: slideUp 0.4s ease-out;
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.1) 0%, rgba(220, 53, 69, 0.05) 100%);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.1) 0%, rgba(40, 167, 69, 0.05) 100%);
            color: #155724;
            border-left: 4px solid #28a745;
        }

        /* Info Box */
        .info-box {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            border-left: 4px solid var(--primary);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .info-box strong {
            color: var(--primary);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .form-card {
                padding: 25px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .page-header h1 {
                font-size: 1.8rem;
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
                    <h1><i class="bi bi-plus-circle me-2"></i>Create New Event</h1>
                    <p>Plan your next event and configure shifts</p>
                </div>
                <div class="d-none d-md-block" style="font-size: 4rem; opacity: 0.2;">
                    <i class="bi bi-calendar-event"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container-lg mb-5">
        <div class="row">
            <div class="col-lg-8">
                <div class="form-card">
                    <!-- Back Button -->
                    <div class="mb-4">
                        <a href="events.php" class="btn-back">
                            <i class="bi bi-arrow-left"></i>Back to Events
                        </a>
                    </div>

                    <!-- Success Alert -->
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <strong>Success!</strong> <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Error Alert -->
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-circle-fill me-2"></i>
                            <strong>Error!</strong> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Info Box -->
                    <div class="info-box">
                        <strong><i class="bi bi-info-circle me-1"></i>Event Guidelines:</strong><br>
                        <small>Create detailed event information. You can add shifts and manage applications after event creation.</small>
                    </div>

                    <!-- Form -->
                    <form method="POST" action="" id="eventForm" novalidate>
                        <!-- Event Details Section -->
                        <div class="form-section">
                            <h5><i class="bi bi-info-circle"></i>Event Details</h5>
                            
                            <div class="mb-3">
                                <label class="form-label" for="title">
                                    <i class="bi bi-chat-square-text"></i>Event Title
                                </label>
                                <input type="text" class="form-control form-control-lg" id="title" name="title" 
                                       placeholder="e.g., Tech Summit 2026" required autofocus>
                                <small class="d-block mt-2 text-muted">
                                    <i class="bi bi-lightbulb"></i> Make it clear and descriptive
                                </small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="description">
                                    <i class="bi bi-file-text"></i>Description
                                </label>
                                <textarea class="form-control" id="description" name="description" 
                                          placeholder="Describe your event, purpose, and requirements..." required></textarea>
                                <small class="d-block mt-2 text-muted">
                                    <i class="bi bi-info-circle"></i> Employees will see this information
                                </small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="location">
                                    <i class="bi bi-geo-alt"></i>Location
                                </label>
                                <input type="text" class="form-control form-control-lg" id="location" name="location" 
                                       placeholder="e.g., Dhaka Convention Center" required>
                            </div>
                        </div>

                        <!-- Date & Time Section -->
                        <div class="form-section">
                            <h5><i class="bi bi-calendar"></i>Event Schedule</h5>
                            
                            <div class="form-row">
                                <div>
                                    <label class="form-label" for="event_date">
                                        <i class="bi bi-calendar-week"></i>Event Date
                                    </label>
                                    <input type="date" class="form-control form-control-lg" id="event_date" name="event_date" required>
                                </div>
                                <div>
                                    <label class="form-label" for="event_time">
                                        <i class="bi bi-clock"></i>Event Time
                                    </label>
                                    <input type="time" class="form-control form-control-lg" id="event_time" name="event_time" required>
                                </div>
                            </div>
                        </div>

                        <!-- Shift Details Section -->
                        <div class="form-section">
                            <h5><i class="bi bi-briefcase"></i>Initial Shift Details</h5>
                            
                            <div class="form-row">
                                <div>
                                    <label class="form-label" for="required_staff">
                                        <i class="bi bi-people"></i>Required Staff
                                    </label>
                                    <input type="number" class="form-control form-control-lg" id="required_staff" name="required_staff" 
                                           min="1" placeholder="e.g., 5" required>
                                    <small class="d-block mt-2 text-muted">How many workers do you need?</small>
                                </div>
                                <div>
                                    <label class="form-label" for="shift_duration_hours">
                                        <i class="bi bi-hourglass-split"></i>Shift Duration (Hours)
                                    </label>
                                    <input type="number" class="form-control form-control-lg" id="shift_duration_hours" name="shift_duration_hours" 
                                           min="1" placeholder="e.g., 8" required>
                                    <small class="d-block mt-2 text-muted">How long is the shift?</small>
                                </div>
                            </div>

                            <div class="mb-3 mt-3">
                                <label class="form-label" for="payment_per_shift">
                                    <i class="bi bi-currency-dollar"></i>Payment Per Person (৳)
                                </label>
                                <input type="number" class="form-control form-control-lg" id="payment_per_shift" name="payment_per_shift" 
                                       min="1" step="0.01" placeholder="e.g., 1500" required>
                                <small class="d-block mt-2 text-muted">How much will each worker earn?</small>
                            </div>
                        </div>

                        <!-- Submit Section -->
                        <div class="d-flex gap-3 mt-4">
                            <button type="submit" class="btn btn-lg btn-submit flex-grow-1">
                                <i class="bi bi-check-circle me-2"></i>Create Event
                            </button>
                            <a href="events.php" class="btn btn-lg" style="background: #f0f0f0; color: #333; text-decoration: none;">
                                <i class="bi bi-x-circle me-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="form-card" style="background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);">
                    <h5 style="color: var(--primary); margin-bottom: 20px;">
                        <i class="bi bi-lightbulb"></i>Quick Tips
                    </h5>
                    <div style="font-size: 0.95rem; color: #555; line-height: 1.8;">
                        <p>
                            <strong>✓ Event Title & Description</strong><br>
                            Use clear titles and include event details, dress code, and requirements.
                        </p>
                        <hr style="border-color: rgba(102, 126, 234, 0.2);">
                        <p>
                            <strong>✓ Required Staff</strong><br>
                            How many workers do you need for this event? (minimum 1)
                        </p>
                        <hr style="border-color: rgba(102, 126, 234, 0.2);">
                        <p>
                            <strong>✓ Shift Duration</strong><br>
                            Total hours for the shift (e.g., 8 hours for a full day)
                        </p>
                        <hr style="border-color: rgba(102, 126, 234, 0.2);">
                        <p>
                            <strong>✓ Payment Per Person</strong><br>
                            Amount in Taka each worker will earn (e.g., 1500৳)
                        </p>
                        <hr style="border-color: rgba(102, 126, 234, 0.2);">
                        <p>
                            <strong>✓ Date & Time</strong><br>
                            Must be in the future. An initial shift will be auto-created.
                        </p>
                    </div>
                </div>

                <div class="form-card" style="margin-top: 25px;">
                    <h5 style="color: var(--primary); margin-bottom: 20px;">
                        <i class="bi bi-question-circle"></i>Need Help?
                    </h5>
                    <p style="color: #666; margin-bottom: 0;">
                        Check our documentation for more details on creating events and managing shifts.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set minimum date to today
        const today = new Date();
        const minDate = today.toISOString().split('T')[0];
        document.getElementById('event_date').setAttribute('min', minDate);

        // Form validation
        document.getElementById('eventForm').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const description = document.getElementById('description').value.trim();
            const location = document.getElementById('location').value.trim();
            const date = document.getElementById('event_date').value;
            const time = document.getElementById('event_time').value;
            const requiredStaffValue = document.getElementById('required_staff').value.trim();
            const durationValue = document.getElementById('shift_duration_hours').value.trim();
            const paymentValue = document.getElementById('payment_per_shift').value.trim();

            if (!title || !description || !location || !date || !time) {
                e.preventDefault();
                alert('Please fill all required fields');
                return;
            }

            if (title.length < 3) {
                e.preventDefault();
                alert('Event title must be at least 3 characters');
                return;
            }

            if (description.length < 10) {
                e.preventDefault();
                alert('Description must be at least 10 characters');
                return;
            }

            if (!requiredStaffValue || isNaN(requiredStaffValue) || parseInt(requiredStaffValue) < 1) {
                e.preventDefault();
                alert('Required staff must be at least 1');
                return;
            }

            if (!durationValue || isNaN(durationValue) || parseInt(durationValue) < 1) {
                e.preventDefault();
                alert('Shift duration must be at least 1 hour');
                return;
            }

            if (!paymentValue || isNaN(paymentValue) || parseFloat(paymentValue) < 1) {
                e.preventDefault();
                alert('Payment amount must be greater than 0');
                return;
            }
        });
    </script>
</body>
</html>
