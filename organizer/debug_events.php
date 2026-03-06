<?php
/**
 * Event Creation Debug Page
 */

require_once '../config/database.php';

echo "<h2>EventStaff Database Debug</h2>";
echo "<hr>";

// Check if events table exists
try {
    $stmt = $conn->query("SHOW TABLES LIKE 'events'");
    $table_exists = $stmt->rowCount() > 0;
    
    echo "<p><strong>Events Table Exists:</strong> " . ($table_exists ? "✅ YES" : "❌ NO") . "</p>";
    
    if ($table_exists) {
        // Show table structure
        echo "<p><strong>Table Structure:</strong></p>";
        $stmt = $conn->query("DESCRIBE events");
        echo "<pre>";
        while ($row = $stmt->fetch()) {
            echo $row['Field'] . " (" . $row['Type'] . ")\n";
        }
        echo "</pre>";
        
        // Show existing events
        $stmt = $conn->query("SELECT COUNT(*) as count FROM events");
        $result = $stmt->fetch();
        echo "<p><strong>Total Events in Database:</strong> " . $result['count'] . "</p>";
    } else {
        echo "<p>❌ <strong>Events table does not exist!</strong></p>";
        echo "<p>Please run the setup.sql file from /config/ folder to create the database and tables.</p>";
        echo "<p><a href='../config/test_connection.php'>Check Database Setup</a></p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='create_event.php'>Back to Create Event</a></p>";
?>
