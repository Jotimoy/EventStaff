<?php
/**
 * Notification unread count API
 * Returns unread notification count for the logged in user
 */

require_once '../config/database.php';
require_once '../includes/session.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'count' => 0,
        'message' => 'Unauthorized'
    ]);
    exit();
}

$user_id = get_user_id();

try {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND status = 'unread'");
    $stmt->execute([$user_id]);
    $count = (int) ($stmt->fetch()['count'] ?? 0);

    echo json_encode([
        'success' => true,
        'count' => $count
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'count' => 0,
        'message' => 'Database error'
    ]);
}
