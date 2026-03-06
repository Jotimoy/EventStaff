<?php
/**
 * User Registration Page
 * EventStaff Platform
 */

require_once '../config/database.php';
require_once '../includes/session.php';

// Redirect if already logged in
redirect_if_logged_in();

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';
    
    // Validation
    if (empty($email) || empty($password) || empty($confirm_password) || empty($role)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (!in_array($role, ['organizer', 'employee'])) {
        $error = 'Invalid role selected.';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = 'Email already registered. Please login.';
        } else {
            // Insert new user
            try {
                $hashed_password = md5($password); // Simple MD5 for academic project
                $stmt = $conn->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, ?)");
                $stmt->execute([$email, $hashed_password, $role]);
                
                // Get the newly created user ID
                $user_id = $conn->lastInsertId();
                
                // Set session
                set_user_session($user_id, $email, $role);
                
                // Redirect to profile creation
                if ($role === 'employee') {
                    header('Location: /EventStaff/employee/profile.php?first_time=1');
                } else {
                    header('Location: /EventStaff/organizer/profile.php?first_time=1');
                }
                exit();
                
            } catch (PDOException $e) {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - EventStaff</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        * {
            --primary: #667eea;
            --primary-dark: #764ba2;
        }

        body {
            overflow-x: hidden;
        }

        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 20px;
            position: relative;
        }

        /* Animated background shapes */
        .bg-shape {
            position: absolute;
            border-radius: 50%;
            opacity: 0.1;
            animation: float 20s ease-in-out infinite;
        }

        .shape-1 {
            width: 300px;
            height: 300px;
            background: white;
            top: -50px;
            right: -50px;
            animation-delay: 0s;
        }

        .shape-2 {
            width: 200px;
            height: 200px;
            background: white;
            bottom: 50px;
            left: -50px;
            animation-delay: 5s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(20px); }
        }

        .register-card {
            background: white;
            border-radius: 25px;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            animation: slideUp 0.6s ease-out;
            position: relative;
            z-index: 10;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .register-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
        }

        .register-header h2 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }

        .register-header p {
            font-size: 1.1rem;
            opacity: 0.95;
        }

        /* Progress Steps */
        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }

        .progress-steps::before {
            content: '';
            position: absolute;
            top: 25px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e0e0e0;
            z-index: 0;
        }

        .step {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .step-number {
            width: 50px;
            height: 50px;
            background: #e0e0e0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: 700;
            color: #999;
            transition: all 0.3s ease;
        }

        .step.active .step-number {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .step-label {
            font-size: 0.85rem;
            color: #999;
            font-weight: 500;
        }

        .step.active .step-label {
            color: var(--primary);
            font-weight: 600;
        }

        /* Form Group Enhancements */
        .form-group {
            margin-bottom: 25px;
            animation: slideUp 0.6s ease-out;
        }

        .form-group:nth-child(2) { animation-delay: 0.1s; }
        .form-group:nth-child(3) { animation-delay: 0.2s; }
        .form-group:nth-child(4) { animation-delay: 0.3s; }
        .form-group:nth-child(5) { animation-delay: 0.4s; }

        .form-label {
            font-weight: 600;
            color: #333;
            font-size: 0.95rem;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9ff;
        }

        .form-control:focus {
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        .form-control.is-valid {
            border-color: #28a745;
            background-image: none;
        }

        .form-control.is-invalid {
            border-color: #dc3545;
            background-image: none;
        }

        /* Password Strength Indicator */
        .password-strength {
            margin-top: 8px;
            height: 5px;
            background: #e0e0e0;
            border-radius: 3px;
            overflow: hidden;
        }

        .strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s ease;
            border-radius: 3px;
        }

        .strength-bar.weak {
            width: 33%;
            background: #dc3545;
        }

        .strength-bar.fair {
            width: 66%;
            background: #ffc107;
        }

        .strength-bar.strong {
            width: 100%;
            background: #28a745;
        }

        .strength-text {
            font-size: 0.85rem;
            margin-top: 5px;
            font-weight: 600;
            display: none;
        }

        .strength-text.weak { color: #dc3545; display: block; }
        .strength-text.fair { color: #ffc107; display: block; }
        .strength-text.strong { color: #28a745; display: block; }

        .validation-feedback {
            font-size: 0.85rem;
            display: none;
            margin-top: 8px;
        }

        .invalid-feedback {
            color: #dc3545;
        }

        .valid-feedback {
            color: #28a745;
        }

        /* Role Selection Cards */
        .role-selector {
            display: flex;
            gap: 20px;
            margin: 25px 0;
        }

        .role-card {
            flex: 1;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            padding: 25px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            text-align: center;
            background: #f8f9ff;
        }

        .role-card:hover {
            border-color: var(--primary);
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.2);
        }

        .role-card.selected {
            border-color: var(--primary);
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.08) 0%, rgba(118, 75, 162, 0.08) 100%);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.15);
        }

        .role-card input[type="radio"] {
            display: none;
        }

        .role-icon {
            font-size: 3.5rem;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .role-card.selected .role-icon {
            transform: scale(1.1);
        }

        .role-card h5 {
            font-weight: 700;
            margin: 15px 0 8px;
            color: #333;
        }

        .role-card p {
            color: #666;
            font-size: 0.9rem;
            margin: 0;
        }

        .role-badge {
            display: inline-block;
            padding: 6px 12px;
            background: var(--primary);
            color: white;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 10px;
            opacity: 0;
            transition: all 0.3s ease;
        }

        .role-card.selected .role-badge {
            opacity: 1;
        }

        /* Buttons */
        .btn-register {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            padding: 14px 24px;
            font-weight: 700;
            letter-spacing: 0.5px;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-size: 1.05rem;
        }

        .btn-register:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-register:active {
            transform: translateY(-1px);
        }

        /* Links and Footer */
        .auth-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #e0e0e0;
        }

        .auth-footer p {
            color: #666;
            margin-bottom: 15px;
        }

        .auth-links {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .auth-links a {
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .btn-login {
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-login:hover {
            background: var(--primary);
            color: white;
        }

        .btn-home {
            color: #666;
        }

        .btn-home:hover {
            color: var(--primary);
        }

        /* Security Features */
        .security-info {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            border-left: 4px solid var(--primary);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            color: #555;
        }

        .security-info i {
            color: var(--primary);
            margin-right: 8px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .register-header h2 {
                font-size: 1.5rem;
            }

            .register-header {
                padding: 30px 20px;
            }

            .role-selector {
                flex-direction: column;
            }

            .progress-steps {
                display: none;
            }

            .role-icon {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <!-- Background shapes -->
        <div class="bg-shape shape-1"></div>
        <div class="bg-shape shape-2"></div>

        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-8">
                    <div class="register-card">
                        <div class="register-header">
                            <h2><i class="bi bi-person-plus-fill me-2"></i>Create Account</h2>
                            <p>Join EventStaff Community Today</p>
                        </div>
                        
                        <div class="p-4 p-lg-5">
                            <!-- Progress Steps -->
                            <div class="progress-steps mb-4">
                                <div class="step active" id="step1">
                                    <div class="step-number">1</div>
                                    <div class="step-label">Account</div>
                                </div>
                                <div class="step" id="step2">
                                    <div class="step-number">2</div>
                                    <div class="step-label">Role</div>
                                </div>
                                <div class="step" id="step3">
                                    <div class="step-number">3</div>
                                    <div class="step-label">Complete</div>
                                </div>
                            </div>

                            <!-- Alerts -->
                            <?php if ($error): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="bi bi-exclamation-circle-fill me-2"></i>
                                    <strong>Error!</strong> <?php echo $error; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <!-- Security Info -->
                            <div class="security-info">
                                <i class="bi bi-shield-check"></i>
                                Your data is encrypted and secure. We never share your information.
                            </div>

                            <form method="POST" action="" id="registerForm" novalidate>
                                <!-- Email Field -->
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="bi bi-envelope-fill"></i>Email Address
                                    </label>
                                    <div class="input-group">
                                        <input type="email" name="email" id="email" class="form-control form-control-lg" 
                                               placeholder="your@email.com" required 
                                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                        <span class="input-group-text bg-transparent border-0" style="pointer-events: none;">
                                            <i class="bi bi-check-circle text-success d-none" id="emailCheck"></i>
                                        </span>
                                    </div>
                                    <div class="invalid-feedback d-block" id="emailFeedback"></div>
                                </div>

                                <!-- Password Field -->
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="bi bi-lock-fill"></i>Password
                                    </label>
                                    <input type="password" name="password" id="password" class="form-control form-control-lg" 
                                           placeholder="Min 6 characters" required minlength="6">
                                    <div class="password-strength">
                                        <div class="strength-bar" id="strengthBar"></div>
                                    </div>
                                    <div class="strength-text" id="strengthText"></div>
                                </div>

                                <!-- Confirm Password Field -->
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="bi bi-lock"></i>Confirm Password
                                    </label>
                                    <div class="input-group">
                                        <input type="password" name="confirm_password" id="confirmPassword" class="form-control form-control-lg" 
                                               placeholder="Re-enter password" required minlength="6">
                                        <span class="input-group-text bg-transparent border-0" style="pointer-events: none;">
                                            <i class="bi bi-check-circle text-success d-none" id="matchCheck"></i>
                                        </span>
                                    </div>
                                    <div class="invalid-feedback d-block" id="matchFeedback"></div>
                                </div>

                                <!-- Role Selection -->
                                <div class="form-group">
                                    <label class="form-label mb-3">
                                        <i class="bi bi-person-badge"></i>I want to register as:
                                    </label>
                                    <div class="role-selector">
                                        <label class="role-card" data-role="organizer">
                                            <input type="radio" name="role" value="organizer" required>
                                            <div class="role-icon text-primary">
                                                <i class="bi bi-briefcase-fill"></i>
                                            </div>
                                            <h5>Event Organizer</h5>
                                            <p>Post events and hire workers</p>
                                            <span class="role-badge">Post Events</span>
                                        </label>
                                        <label class="role-card" data-role="employee">
                                            <input type="radio" name="role" value="employee" required>
                                            <div class="role-icon text-success">
                                                <i class="bi bi-person-badge-fill"></i>
                                            </div>
                                            <h5>Event Worker</h5>
                                            <p>Find jobs and apply for shifts</p>
                                            <span class="role-badge">Find Jobs</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <button type="submit" class="btn btn-primary btn-lg w-100 btn-register">
                                    <i class="bi bi-rocket-takeoff me-2"></i>Create Account
                                </button>
                            </form>

                            <!-- Footer Links -->
                            <div class="auth-footer">
                                <p>Already have an account?</p>
                                <div class="auth-links">
                                    <a href="login.php" class="btn btn-login">
                                        <i class="bi bi-box-arrow-in-right me-1"></i>Login
                                    </a>
                                    <a href="../index.php" class="btn btn-home">
                                        <i class="bi bi-house me-1"></i>Home
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password Strength Checker
        function checkPasswordStrength(password) {
            let strength = 0;
            if (password.length >= 6) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z\d]/.test(password)) strength++;
            
            return strength;
        }

        // Update Password Strength
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');

        passwordInput.addEventListener('input', function() {
            const strength = checkPasswordStrength(this.value);
            
            strengthBar.classList.remove('weak', 'fair', 'strong');
            strengthText.classList.remove('weak', 'fair', 'strong');
            
            if (strength === 0) {
                strengthBar.style.width = '0';
                strengthText.textContent = '';
            } else if (strength <= 2) {
                strengthBar.classList.add('weak');
                strengthText.classList.add('weak');
                strengthText.textContent = '<i class="bi bi-exclamation-triangle"></i> Weak password';
            } else if (strength === 3) {
                strengthBar.classList.add('fair');
                strengthText.classList.add('fair');
                strengthText.textContent = '<i class="bi bi-info-circle"></i> Fair password - Add special characters';
            } else {
                strengthBar.classList.add('strong');
                strengthText.classList.add('strong');
                strengthText.textContent = '<i class="bi bi-check-circle"></i> Strong password';
            }
        });

        // Email Validation
        const emailInput = document.getElementById('email');
        const emailCheck = document.getElementById('emailCheck');
        const emailFeedback = document.getElementById('emailFeedback');

        emailInput.addEventListener('blur', function() {
            const email = this.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email && emailRegex.test(email)) {
                emailCheck.classList.remove('d-none');
                emailFeedback.textContent = '';
            } else if (email) {
                emailCheck.classList.add('d-none');
                emailFeedback.textContent = 'Please enter a valid email address';
            }
        });

        // Password Match Checker
        const confirmPassword = document.getElementById('confirmPassword');
        const matchCheck = document.getElementById('matchCheck');
        const matchFeedback = document.getElementById('matchFeedback');

        confirmPassword.addEventListener('input', function() {
            if (this.value && passwordInput.value === this.value) {
                matchCheck.classList.remove('d-none');
                matchFeedback.textContent = '';
            } else if (this.value) {
                matchCheck.classList.add('d-none');
                matchFeedback.textContent = 'Passwords do not match';
            } else {
                matchCheck.classList.add('d-none');
                matchFeedback.textContent = '';
            }
        });

        // Role Card Selection
        document.querySelectorAll('.role-card').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.role-card').forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                this.querySelector('input[type="radio"]').checked = true;
                
                // Move to step 2
                document.getElementById('step2').classList.add('active');
            });
        });

        // Form Submission
        const form = document.getElementById('registerForm');
        form.addEventListener('submit', function(e) {
            const password = passwordInput.value;
            const confirmPass = confirmPassword.value;
            const email = emailInput.value.trim();
            const role = document.querySelector('input[name="role"]:checked');
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (!emailRegex.test(email)) {
                e.preventDefault();
                emailFeedback.textContent = 'Please enter a valid email address';
                return;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long!');
                return;
            }
            
            if (password !== confirmPass) {
                e.preventDefault();
                alert('Passwords do not match!');
                return;
            }
            
            if (!role) {
                e.preventDefault();
                alert('Please select a role!');
                return;
            }

            // Show step 3
            document.getElementById('step3').classList.add('active');
        });

        // Update steps as user fills form
        document.addEventListener('input', function() {
            const email = emailInput.value.trim();
            const password = passwordInput.value;
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (emailRegex.test(email) && password.length >= 6) {
                document.getElementById('step1').classList.add('active');
            }
        });
    </script>
</body>
</html>
