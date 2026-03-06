<?php
/**
 * Session & Events Debug Page
 */

require_once '../config/database.php';
require_once '../includes/session.php';

require_login();

$user_id = get_user_id();
$email = get_user_email();
$role = get_user_role();

echo "<h2>Session & Events Debug</h2>";
echo "<hr>";

echo "<h3>Your Session Information:</h3>";
echo "<ul>";
echo "<li><strong>User ID:</strong> " . $user_id . "</li>";
echo "<li><strong>Email:</strong> " . $email . "</li>";
echo "<li><strong>Role:</strong> " . $role . "</li>";
echo "</ul>";

echo "<h3>Events in Database for User ID = " . $user_id . ":</h3>";

try {
    $stmt = $conn->prepare("
        SELECT id, title, location, event_date, organizer_id
        FROM events
        WHERE organizer_id = ?
    ");
    $stmt->execute([$user_id]);
    $events = $stmt->fetchAll();
    
    if (empty($events)) {
        echo "<p style='color: red;'><strong>No events found for your organizer ID!</strong></p>";
    } else {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Title</th><th>Location</th><th>Date</th><th>Organizer ID</th></tr>";
        foreach ($events as $event) {
            echo "<tr>";
            echo "<td>" . $event['id'] . "</td>";
            echo "<td>" . $event['title'] . "</td>";
            echo "<td>" . $event['location'] . "</td>";
            echo "<td>" . $event['event_date'] . "</td>";
            echo "<td>" . $event['organizer_id'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>ALL Events in Database:</h3>";

try {
    $stmt = $conn->query("SELECT id, title, organizer_id FROM events");
    $all_events = $stmt->fetchAll();
    
    if (empty($all_events)) {
        echo "<p>No events in database</p>";
    } else {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>Event ID</th><th>Title</th><th>Organizer ID</th></tr>";
        foreach ($all_events as $event) {
            echo "<tr>";
            echo "<td>" . $event['id'] . "</td>";
            echo "<td>" . $event['title'] . "</td>";
            echo "<td>" . $event['organizer_id'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='events.php'>Back to My Events</a></p>";
?>
