<?php
/**
 * Database Connection Test Page
 * Visit: http://localhost/EventStaff/config/test_connection.php
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Test - EventStaff</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-database-check me-2"></i>Database Connection Test</h4>
                    </div>
                    <div class="card-body p-4">
                        <?php
                        // Test database connection
                        try {
                            require_once 'database.php';
                            
                            echo '<div class="alert alert-success" role="alert">';
                            echo '<h5 class="alert-heading"><i class="bi bi-check-circle-fill me-2"></i>Connection Successful!</h5>';
                            echo '<p>Database connection established successfully.</p>';
                            echo '<hr>';
                            echo '<p class="mb-0">Database: <strong>' . DB_NAME . '</strong></p>';
                            echo '<p class="mb-0">Host: <strong>' . DB_HOST . '</strong></p>';
                            echo '</div>';
                            
                            // Test query - count users
                            $stmt = $conn->query("SELECT COUNT(*) as user_count FROM users");
                            $result = $stmt->fetch();
                            
                            echo '<div class="alert alert-info" role="alert">';
                            echo '<h6><i class="bi bi-info-circle-fill me-2"></i>Database Statistics:</h6>';
                            echo '<p class="mb-0">Total Users: <strong>' . $result['user_count'] . '</strong></p>';
                            echo '</div>';
                            
                            // List all tables
                            $stmt = $conn->query("SHOW TABLES");
                            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                            
                            echo '<h5 class="mt-4"><i class="bi bi-table me-2"></i>Database Tables:</h5>';
                            echo '<div class="list-group">';
                            foreach($tables as $table) {
                                echo '<div class="list-group-item">';
                                echo '<i class="bi bi-check text-success me-2"></i>' . $table;
                                echo '</div>';
                            }
                            echo '</div>';
                            
                            // Show sample users
                            $stmt = $conn->query("SELECT id, email, role FROM users LIMIT 5");
                            $users = $stmt->fetchAll();
                            
                            if(count($users) > 0) {
                                echo '<h5 class="mt-4"><i class="bi bi-people me-2"></i>Sample Users:</h5>';
                                echo '<div class="table-responsive">';
                                echo '<table class="table table-striped">';
                                echo '<thead><tr><th>ID</th><th>Email</th><th>Role</th></tr></thead>';
                                echo '<tbody>';
                                foreach($users as $user) {
                                    $badge_color = $user['role'] == 'admin' ? 'danger' : ($user['role'] == 'organizer' ? 'primary' : 'success');
                                    echo '<tr>';
                                    echo '<td>' . $user['id'] . '</td>';
                                    echo '<td>' . $user['email'] . '</td>';
                                    echo '<td><span class="badge bg-' . $badge_color . '">' . ucfirst($user['role']) . '</span></td>';
                                    echo '</tr>';
                                }
                                echo '</tbody></table>';
                                echo '</div>';
                            }
                            
                        } catch(PDOException $e) {
                            echo '<div class="alert alert-danger" role="alert">';
                            echo '<h5 class="alert-heading"><i class="bi bi-x-circle-fill me-2"></i>Connection Failed!</h5>';
                            echo '<p>Could not connect to database.</p>';
                            echo '<hr>';
                            echo '<p class="mb-0"><strong>Error:</strong> ' . $e->getMessage() . '</p>';
                            echo '</div>';
                            
                            echo '<div class="alert alert-warning mt-3" role="alert">';
                            echo '<h6><i class="bi bi-exclamation-triangle-fill me-2"></i>Troubleshooting Steps:</h6>';
                            echo '<ol class="mb-0">';
                            echo '<li>Make sure XAMPP MySQL is running</li>';
                            echo '<li>Run the setup.sql file in phpMyAdmin</li>';
                            echo '<li>Check database credentials in database.php</li>';
                            echo '<li>Verify database name is: <strong>eventstaff_db</strong></li>';
                            echo '</ol>';
                            echo '</div>';
                        }
                        ?>
                        
                        <div class="mt-4 d-flex gap-2">
                            <a href="../index.php" class="btn btn-primary">
                                <i class="bi bi-house-fill me-2"></i>Back to Home
                            </a>
                            <a href="http://localhost/phpmyadmin" target="_blank" class="btn btn-outline-secondary">
                                <i class="bi bi-database me-2"></i>Open phpMyAdmin
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
