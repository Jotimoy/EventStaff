<?php
/**
 * Organizer Profile Page
 * EventStaff Platform
 */

require_once '../config/database.php';
require_once '../includes/session.php';

// Require organizer role
require_role('organizer');

$user_id = get_user_id();
$is_first_time = isset($_GET['first_time']);

// Fetch existing profile if any
$stmt = $conn->prepare("SELECT * FROM organizer_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = trim($_POST['company_name'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    if (empty($company_name)) {
        $error = 'Company name is required.';
    } else {
        try {
            if ($profile) {
                // Update existing profile
                $stmt = $conn->prepare("UPDATE organizer_profiles SET company_name=?, contact_person=?, phone=?, address=? WHERE user_id=?");
                $stmt->execute([$company_name, $contact_person, $phone, $address, $user_id]);
                $success = 'Profile updated successfully!';
            } else {
                // Create new profile
                $stmt = $conn->prepare("INSERT INTO organizer_profiles (user_id, company_name, contact_person, phone, address) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $company_name, $contact_person, $phone, $address]);
                $success = 'Profile created successfully!';
                
                // Redirect to dashboard after first-time setup
                if ($is_first_time) {
                    header('Location: /EventStaff/organizer/dashboard.php');
                    exit();
                }
            }
            
            // Refresh profile data
            $stmt = $conn->prepare("SELECT * FROM organizer_profiles WHERE user_id = ?");
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
    <title>Organizer Profile - EventStaff</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-primary">
        <div class="container">
            <span class="navbar-brand"><i class="bi bi-briefcase me-2"></i>Organizer Dashboard</span>
            <a href="../auth/logout.php" class="btn btn-light btn-sm">Logout</a>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <?php if ($is_first_time): ?>
                    <div class="alert alert-info">
                        <h5><i class="bi bi-info-circle me-2"></i>Welcome! Complete Your Profile</h5>
                        <p class="mb-0">Please fill out your company information to start posting events.</p>
                    </div>
                <?php endif; ?>
                
                <div class="card shadow border-0">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-building me-2"></i>Company Profile</h4>
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
                                <label class="form-label fw-bold">Company Name *</label>
                                <input type="text" name="company_name" class="form-control" required 
                                       value="<?php echo htmlspecialchars($profile['company_name'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Contact Person</label>
                                <input type="text" name="contact_person" class="form-control" 
                                       value="<?php echo htmlspecialchars($profile['contact_person'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Phone Number</label>
                                <input type="text" name="phone" class="form-control" 
                                       value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold">Address</label>
                                <textarea name="address" class="form-control" rows="3" 
                                          placeholder="Company address"><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg">
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
