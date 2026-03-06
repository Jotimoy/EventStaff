<?php
/**
 * Debug Page - Create Event Form Testing
 */

require_once '../config/database.php';
require_once '../includes/session.php';

require_role('organizer');

$organizer_id = get_user_id();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug - Create Event</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; padding: 20px; }
        .debug-box { background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        pre { background: #f1f1f1; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
    </style>
</head>
<body>
    <div class="container-lg">
        <h1 class="mb-4"><i class="bi bi-bug me-2"></i>Debug - Create Event Form</h1>

        <!-- Test Form Data -->
        <div class="debug-box">
            <h5><i class="bi bi-info-circle me-2"></i>Form Data Test</h5>
            <p>If you submit the test form below, we can see exactly what data is being sent and if there are any validation errors.</p>
            
            <form method="POST" id="testForm">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" value="Test Event" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control" value="Dhaka Convention Center" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" required>This is a test event for debugging purposes. We need to fill this properly.</textarea>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="event_date" class="form-control" id="dateInput" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Time</label>
                        <input type="time" name="event_time" class="form-control" value="10:00" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Required Staff</label>
                        <input type="number" name="required_staff" class="form-control" value="5" min="1" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Duration (hours)</label>
                        <input type="number" name="shift_duration_hours" class="form-control" value="8" min="1" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Payment (৳)</label>
                    <input type="number" name="payment_per_shift" class="form-control" value="1500" min="1" step="0.01" required>
                </div>

                <button type="submit" name="test_submit" class="btn btn-primary">Test Form Submission</button>
            </form>
        </div>

        <!-- Database Check -->
        <div class="debug-box">
            <h5><i class="bi bi-database me-2"></i>Database Tables Check</h5>
            <?php
            try {
                // Check events table
                $stmt = $conn->prepare("DESCRIBE events");
                $stmt->execute();
                $events_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<h6>✓ events table structure:</h6>";
                echo "<pre>";
                foreach ($events_columns as $col) {
                    echo "{$col['Field']}: {$col['Type']}\n";
                }
                echo "</pre>";

                // Check event_shifts table
                $stmt = $conn->prepare("DESCRIBE event_shifts");
                $stmt->execute();
                $shifts_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<h6>✓ event_shifts table structure:</h6>";
                echo "<pre>";
                foreach ($shifts_columns as $col) {
                    echo "{$col['Field']}: {$col['Type']}\n";
                }
                echo "</pre>";

            } catch (Exception $e) {
                echo "<p class='error'><i class='bi bi-exclamation-triangle me-2'></i>Database Error: {$e->getMessage()}</p>";
            }
            ?>
        </div>

        <!-- User Info -->
        <div class="debug-box">
            <h5><i class="bi bi-person-circle me-2"></i>Your Information</h5>
            <p><strong>User ID:</strong> <code><?php echo $organizer_id; ?></code></p>
            <p><strong>User Email:</strong> <code><?php echo $_SESSION['user_email'] ?? 'Unknown'; ?></code></p>
            <p><strong>Role:</strong> <code><?php echo $_SESSION['user_role'] ?? 'Unknown'; ?></code></p>
        </div>

        <!-- Check Recent Events -->
        <div class="debug-box">
            <h5><i class="bi bi-calendar me-2"></i>Your Recent Events</h5>
            <?php
            try {
                $stmt = $conn->prepare("SELECT * FROM events WHERE organizer_id = ? ORDER BY created_at DESC LIMIT 5");
                $stmt->execute([$organizer_id]);
                $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if ($events) {
                    echo "<table class='table table-sm'>";
                    echo "<thead><tr><th>ID</th><th>Title</th><th>Date</th><th>Location</th><th>Created</th></tr></thead>";
                    echo "<tbody>";
                    foreach ($events as $e) {
                        echo "<tr>";
                        echo "<td><code>{$e['id']}</code></td>";
                        echo "<td>{$e['title']}</td>";
                        echo "<td>{$e['event_date']}</td>";
                        echo "<td>{$e['location']}</td>";
                        echo "<td>{$e['created_at']}</td>";
                        echo "</tr>";
                    }
                    echo "</tbody></table>";
                } else {
                    echo "<p class='text-muted'>No events found for this organizer.</p>";
                }
            } catch (Exception $e) {
                echo "<p class='error'>Error: {$e->getMessage()}</p>";
            }
            ?>
        </div>

        <!-- Form Submission Result -->
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_submit'])): ?>
        <div class="debug-box border-success">
            <h5><i class="bi bi-check-circle me-2 success"></i>Form Submission Test Results</h5>
            
            <h6>Received POST Data:</h6>
            <pre>
<?php
foreach ($_POST as $key => $value) {
    if ($key !== 'test_submit') {
        echo htmlspecialchars($key) . " = " . htmlspecialchars($value) . "\n";
    }
}
?>
            </pre>

            <h6>Validation Check:</h6>
            <pre>
<?php
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$location = trim($_POST['location'] ?? '');
$event_date = trim($_POST['event_date'] ?? '');
$event_time = trim($_POST['event_time'] ?? '');
$required_staff = !empty($_POST['required_staff']) ? intval($_POST['required_staff']) : 0;
$payment_per_shift = !empty($_POST['payment_per_shift']) ? floatval($_POST['payment_per_shift']) : 0;
$shift_duration_hours = !empty($_POST['shift_duration_hours']) ? intval($_POST['shift_duration_hours']) : 0;

echo "✓ Title: " . (strlen($title) >= 3 ? "OK ({$title})" : "FAIL (too short)") . "\n";
echo "✓ Description: " . (strlen($description) >= 10 ? "OK (length: " . strlen($description) . ")" : "FAIL (too short)") . "\n";
echo "✓ Location: " . (!empty($location) ? "OK ({$location})" : "FAIL (empty)") . "\n";
echo "✓ Date: " . (!empty($event_date) ? "OK ({$event_date})" : "FAIL (empty)") . "\n";
echo "✓ Time: " . (!empty($event_time) ? "OK ({$event_time})" : "FAIL (empty)") . "\n";
echo "✓ Required Staff: " . ($required_staff >= 1 ? "OK ({$required_staff})" : "FAIL (must be >= 1)") . "\n";
echo "✓ Payment: " . ($payment_per_shift >= 1 ? "OK ({$payment_per_shift})" : "FAIL (must be >= 1)") . "\n";
echo "✓ Duration: " . ($shift_duration_hours >= 1 ? "OK ({$shift_duration_hours})" : "FAIL (must be >= 1)") . "\n";

// Check future date
$event_datetime = strtotime($event_date . ' ' . $event_time);
$now = time();
echo "✓ Future Date: " . ($event_datetime > $now ? "OK" : "FAIL (past date)") . "\n";
echo "  Current time: " . date('Y-m-d H:i:s') . "\n";
echo "  Event time: " . date('Y-m-d H:i:s', $event_datetime) . "\n";
?>
            </pre>

            <p><a href="create_event.php" class="btn btn-primary btn-sm">Go to Create Event Form</a></p>
        </div>
        <?php endif; ?>

        <div class="mt-4">
            <a href="events.php" class="btn btn-secondary"><i class="bi bi-arrow-left me-2"></i>Back to Events</a>
            <a href="create_event.php" class="btn btn-primary"><i class="bi bi-plus-circle me-2"></i>Go to Create Event</a>
        </div>
    </div>

    <script>
        // Set date to tomorrow for testing
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const dateStr = tomorrow.toISOString().split('T')[0];
        document.getElementById('dateInput').value = dateStr;
        document.getElementById('dateInput').setAttribute('min', dateStr);
    </script>
</body>
</html>
