<?php
/**
 * Session Management Functions
 * EventStaff Platform
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

/**
 * Get current user ID
 */
function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user role
 */
function get_user_role() {
    return $_SESSION['role'] ?? null;
}

/**
 * Get current user email
 */
function get_user_email() {
    return $_SESSION['email'] ?? null;
}

/**
 * Check if user has specific role
 */
function has_role($role) {
    return get_user_role() === $role;
}

/**
 * Redirect if not logged in
 */
function require_login() {
    if (!is_logged_in()) {
        header('Location: /EventStaff/auth/login.php');
        exit();
    }
}

/**
 * Redirect if already logged in
 */
function redirect_if_logged_in() {
    if (is_logged_in()) {
        $role = get_user_role();
        header('Location: /EventStaff/' . $role . '/dashboard.php');
        exit();
    }
}

/**
 * Require specific role
 */
function require_role($required_role) {
    require_login();
    if (get_user_role() !== $required_role) {
        header('Location: /EventStaff/dashboard.php');
        exit();
    }
}

/**
 * Set user session after login
 */
function set_user_session($user_id, $email, $role) {
    $_SESSION['user_id'] = $user_id;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = $role;
}

/**
 * Destroy user session (logout)
 */
function destroy_user_session() {
    session_unset();
    session_destroy();
}

/**
 * Check if profile is complete
 */
function has_complete_profile($conn, $user_id, $role) {
    if ($role === 'admin') {
        return true; // Admin doesn't need profile
    }
    
    if ($role === 'employee') {
        $stmt = $conn->prepare("SELECT id FROM employee_profiles WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch() !== false;
    }
    
    if ($role === 'organizer') {
        $stmt = $conn->prepare("SELECT id FROM organizer_profiles WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch() !== false;
    }
    
    return false;
}

/**
 * Set success message
 */
function set_success_message($message) {
    $_SESSION['success_message'] = $message;
}

/**
 * Get and clear success message
 */
function get_success_message() {
    if (isset($_SESSION['success_message'])) {
        $message = $_SESSION['success_message'];
        unset($_SESSION['success_message']);
        return $message;
    }
    return null;
}

/**
 * Set error message
 */
function set_error_message($message) {
    $_SESSION['error_message'] = $message;
}

/**
 * Get and clear error message
 */
function get_error_message() {
    if (isset($_SESSION['error_message'])) {
        $message = $_SESSION['error_message'];
        unset($_SESSION['error_message']);
        return $message;
    }
    return null;
}

?>
