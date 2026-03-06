<?php
/**
 * Employee Profile Page
 * EventStaff Platform
 */

require_once '../config/database.php';
require_once '../includes/session.php';

// Require employee role
require_role('employee');

$user_id = get_user_id();
$is_first_time = isset($_GET['first_time']);

// Fetch existing profile if any
$stmt = $conn->prepare("SELECT * FROM employee_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $skills = trim($_POST['skills'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    
    if (empty($full_name)) {
        $error = 'Full name is required.';
    } else {
        try {
            if ($profile) {
                // Update existing profile
                $stmt = $conn->prepare("UPDATE employee_profiles SET full_name=?, phone=?, skills=?, experience=? WHERE user_id=?");
                $stmt->execute([$full_name, $phone, $skills, $experience, $user_id]);
                $success = 'Profile updated successfully!';
            } else {
                // Create new profile
                $stmt = $conn->prepare("INSERT INTO employee_profiles (user_id, full_name, phone, skills, experience) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $full_name, $phone, $skills, $experience]);
                $success = 'Profile created successfully!';
                
                // Redirect to dashboard after first-time setup
                if ($is_first_time) {
                    header('Location: /EventStaff/employee/dashboard.php');
                    exit();
                }
            }
            
            // Refresh profile data
            $stmt = $conn->prepare("SELECT * FROM employee_profiles WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $profile = $stmt->fetch();
            
        } catch (PDOException $e) {
            $error = 'Failed to save profile. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Profile - EventStaff</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-success">
        <div class="container">
            <span class="navbar-brand"><i class="bi bi-person-badge me-2"></i>Employee Dashboard</span>
            <a href="../auth/logout.php" class="btn btn-light btn-sm">Logout</a>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <?php if ($is_first_time): ?>
                    <div class="alert alert-info">
                        <h5><i class="bi bi-info-circle me-2"></i>Welcome! Complete Your Profile</h5>
                        <p class="mb-0">Please fill out your profile information to start applying for shifts.</p>
                    </div>
                <?php endif; ?>
                
                <div class="card shadow border-0">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0"><i class="bi bi-person-circle me-2"></i>My Profile</h4>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Full Name *</label>
                                <input type="text" name="full_name" class="form-control" required 
                                       value="<?php echo htmlspecialchars($profile['full_name'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Phone Number</label>
                                <input type="text" name="phone" class="form-control" 
                                       value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Skills</label>
                                <textarea name="skills" class="form-control" rows="3" 
                                          placeholder="e.g., Customer Service, Event Management, Photography"><?php echo htmlspecialchars($profile['skills'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold">Experience</label>
                                <textarea name="experience" class="form-control" rows="3" 
                                          placeholder="Describe your previous experience"><?php echo htmlspecialchars($profile['experience'] ?? ''); ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="bi bi-save me-2"></i>Save Profile
                            </button>
                            
                            <?php if (!$is_first_time): ?>
                                <a href="dashboard.php" class="btn btn-outline-secondary btn-lg ms-2">Cancel</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
