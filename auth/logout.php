<?php
/**
 * Logout Page
 * EventStaff Platform
 */

require_once '../includes/session.php';

// Destroy session
destroy_user_session();

// Redirect to home page
header('Location: /EventStaff/index.php');
exit();
?>
