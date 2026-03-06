<?php
/**
 * Notification System Helper & Service
 * EventStaff Platform
 */

require_once 'database.php';

class NotificationService {
    private $conn;
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }
    
    /**
     * Create a new notification
     */
    public function notify($user_id, $type, $title, $message, $related_id = null, $send_email = true) {
        try {
            // Insert notification record
            $stmt = $this->conn->prepare("
                INSERT INTO notifications (user_id, type, title, message, related_id, status, created_at)
                VALUES (?, ?, ?, ?, ?, 'unread', NOW())
            ");
            $stmt->execute([$user_id, $type, $title, $message, $related_id]);
            $notification_id = $this->conn->lastInsertId();
            
            // Send email if enabled
            if ($send_email) {
                $this->sendEmail($user_id, $type, $title, $message);
            }
            
            return $notification_id;
        } catch (PDOException $e) {
            error_log("Notification creation failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send email notification
     */
    public function sendEmail($user_id, $type, $title, $message) {
        try {
            // Get user email
            $stmt = $this->conn->prepare("SELECT email FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if (!$user) return false;
            
            $to = $user['email'];
            $subject = "[EventStaff] " . $title;
            
            // Build email HTML
            $email_body = $this->buildEmailTemplate($title, $message, $type);
            
            // Email headers
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
            $headers .= "From: notifications@eventstaff.local" . "\r\n";
            
            // Send email
            return mail($to, $subject, $email_body, $headers);
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Build email HTML template
     */
    private function buildEmailTemplate($title, $message, $type) {
        $icon = $this->getIconForType($type);
        $color = $this->getColorForType($type);
        
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background: #f5f7fa; }
                .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 20px; }
                .header h2 { margin: 0; font-size: 1.5rem; }
                .content { padding: 20px; }
                .message { line-height: 1.6; color: #333; }
                .footer { text-align: center; padding: 20px; color: #999; font-size: 0.9rem; border-top: 1px solid #eee; margin-top: 20px; }
                .button { display: inline-block; padding: 12px 24px; background: $color; color: white; text-decoration: none; border-radius: 6px; margin: 15px 0; }
                .status-badge { display: inline-block; padding: 6px 12px; background: rgba(0,0,0,0.1); border-radius: 4px; font-size: 0.85rem; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class=\"container\">
                <div class=\"header\">
                    <h2>$icon $title</h2>
                </div>
                <div class=\"content\">
                    <p class=\"message\">$message</p>
                    <p>
                        <a href=\"http://localhost/EventStaff/\" class=\"button\">View in EventStaff</a>
                    </p>
                </div>
                <div class=\"footer\">
                    <p>EventStaff Platform • Notifications for shift management</p>
                    <p>© 2026 EventStaff. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return $html;
    }
    
    /**
     * Get icon for notification type
     */
    private function getIconForType($type) {
        $icons = [
            'application_approved' => '✓',
            'application_rejected' => '✗',
            'application_pending' => '⏳',
            'payment_pending' => '💰',
            'payment_paid' => '✓',
            'payment_failed' => '✗',
            'event_created' => '📅',
            'shift_created' => '⏰',
            'shift_cancelled' => '❌'
        ];
        
        return $icons[$type] ?? '📬';
    }
    
    /**
     * Get color for notification type
     */
    private function getColorForType($type) {
        $colors = [
            'application_approved' => '#28a745',
            'application_rejected' => '#dc3545',
            'application_pending' => '#ff9800',
            'payment_pending' => '#ff9800',
            'payment_paid' => '#28a745',
            'payment_failed' => '#dc3545',
            'event_created' => '#667eea',
            'shift_created' => '#17a2b8',
            'shift_cancelled' => '#dc3545'
        ];
        
        return $colors[$type] ?? '#667eea';
    }
    
    /**
     * Get unread notifications count for user
     */
    public function getUnreadCount($user_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count FROM notifications 
                WHERE user_id = ? AND status = 'unread'
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetch()['count'];
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    /**
     * Get recent notifications for user
     */
    public function getRecent($user_id, $limit = 10) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$user_id, $limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($notification_id) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE notifications 
                SET status = 'read' 
                WHERE id = ?
            ");
            return $stmt->execute([$notification_id]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Mark all notifications as read for user
     */
    public function markAllAsRead($user_id) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE notifications 
                SET status = 'read' 
                WHERE user_id = ? AND status = 'unread'
            ");
            return $stmt->execute([$user_id]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Delete notification
     */
    public function delete($notification_id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM notifications WHERE id = ?");
            return $stmt->execute([$notification_id]);
        } catch (PDOException $e) {
            return false;
        }
    }
}
