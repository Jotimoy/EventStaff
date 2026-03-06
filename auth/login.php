<?php
/**
 * User Login Page
 * EventStaff Platform
 */

require_once '../config/database.php';
require_once '../includes/session.php';

// Redirect if already logged in
redirect_if_logged_in();

$error = '';
$email_value = '';
$remember_me = false;

// Check for remembered email
if (isset($_COOKIE['eventstaff_email'])) {
    $email_value = $_COOKIE['eventstaff_email'];
    $remember_me = true;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember_me']);
    
    // Validation
    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        // Check user in database
        try {
            $stmt = $conn->prepare("SELECT id, email, password, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $error = 'Invalid email or password.';
            } else {
                // Verify password
                $hashed_password = md5($password);
                
                if ($hashed_password !== $user['password']) {
                    $error = 'Invalid email or password.';
                } else {
                    // Login successful
                    set_user_session($user['id'], $user['email'], $user['role']);
                    
                    // Handle remember me
                    if ($remember) {
                        setcookie('eventstaff_email', $email, time() + (30 * 24 * 60 * 60), '/');
                    } else {
                        if (isset($_COOKIE['eventstaff_email'])) {
                            setcookie('eventstaff_email', '', time() - 3600, '/');
                        }
                    }
                    
                    // Redirect to appropriate dashboard
                    if ($user['role'] === 'admin') {
                        header('Location: /EventStaff/admin/dashboard.php');
                    } elseif ($user['role'] === 'organizer') {
                        header('Location: /EventStaff/organizer/dashboard.php');
                    } else {
                        header('Location: /EventStaff/employee/dashboard.php');
                    }
                    exit();
                }
            }
            
        } catch (PDOException $e) {
            $error = 'Login failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EventStaff</title>
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

        .login-container {
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

        .login-card {
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

        .login-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
        }

        .login-header h2 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }

        .login-header p {
            font-size: 1.1rem;
            opacity: 0.95;
        }

        /* Form Group Enhancements */
        .form-group {
            margin-bottom: 20px;
            animation: slideUp 0.6s ease-out;
        }

        .form-group:nth-child(1) { animation-delay: 0.1s; }
        .form-group:nth-child(2) { animation-delay: 0.2s; }
        .form-group:nth-child(3) { animation-delay: 0.3s; }

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

        .input-group {
            position: relative;
        }

        .input-group .form-control {
            padding-right: 45px;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            background: none;
            border: none;
            font-size: 1.2rem;
            padding: 0;
            transition: color 0.3s ease;
            z-index: 10;
        }

        .password-toggle:hover {
            color: var(--primary);
        }

        /* Remember Me */
        .form-check {
            margin: 15px 0;
        }

        .form-check-input {
            width: 18px;
            height: 18px;
            border: 2px solid #e0e0e0;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .form-check-label {
            cursor: pointer;
            color: #666;
            font-weight: 500;
            margin-left: 8px;
        }

        /* Buttons */
        .btn-login {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            padding: 14px 24px;
            font-weight: 700;
            letter-spacing: 0.5px;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-size: 1.05rem;
            margin-top: 10px;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-login:active {
            transform: translateY(-1px);
        }

        /* Forgot Password Link */
        .forgot-password {
            text-align: right;
            margin-top: -10px;
            margin-bottom: 15px;
        }

        .forgot-password a {
            color: var(--primary);
            font-size: 0.9rem;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .forgot-password a:hover {
            text-decoration: underline;
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

        .btn-register {
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-register:hover {
            background: var(--primary);
            color: white;
        }

        .btn-home {
            color: #666;
        }

        .btn-home:hover {
            color: var(--primary);
        }

        /* Demo Accounts Info */
        .demo-accounts {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            border-left: 4px solid var(--primary);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 0.85rem;
            color: #555;
        }

        .demo-accounts strong {
            color: var(--primary);
            display: block;
            margin-bottom: 8px;
        }

        .demo-account {
            margin-bottom: 8px;
            padding-left: 20px;
        }

        .demo-account:before {
            content: '→ ';
            color: var(--primary);
            font-weight: bold;
            margin-left: -15px;
        }

        .demo-account code {
            background: rgba(102, 126, 234, 0.1);
            padding: 2px 6px;
            border-radius: 3px;
            color: var(--primary);
            font-weight: 600;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .login-header h2 {
                font-size: 1.5rem;
            }

            .login-header {
                padding: 30px 20px;
            }

            .demo-accounts {
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Background shapes -->
        <div class="bg-shape shape-1"></div>
        <div class="bg-shape shape-2"></div>

        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-5 col-md-8">
                    <div class="login-card">
                        <div class="login-header">
                            <h2><i class="bi bi-box-arrow-in-right me-2"></i>Welcome Back</h2>
                            <p>Login to your EventStaff account</p>
                        </div>
                        
                        <div class="p-4 p-lg-5">
                            <!-- Demo Accounts Info -->
                            <div class="demo-accounts">
                                <strong><i class="bi bi-info-circle me-1"></i>Demo Accounts:</strong>
                                <div class="demo-account">
                                    <code>admin@eventstaff.com</code> / <code>admin123</code>
                                </div>
                                <div class="demo-account">
                                    <code>organizer@test.com</code> / <code>password123</code>
                                </div>
                                <div class="demo-account">
                                    <code>employee@test.com</code> / <code>password123</code>
                                </div>
                            </div>

                            <!-- Error Alert -->
                            <?php if ($error): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="bi bi-exclamation-circle-fill me-2"></i>
                                    <strong>Login Failed!</strong> <?php echo $error; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="" id="loginForm" novalidate>
                                <!-- Email Field -->
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="bi bi-envelope-fill"></i>Email Address
                                    </label>
                                    <input type="email" name="email" id="email" class="form-control form-control-lg" 
                                           placeholder="your@email.com" required 
                                           value="<?php echo htmlspecialchars($email_value); ?>" autofocus>
                                </div>

                                <!-- Password Field -->
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="bi bi-lock-fill"></i>Password
                                    </label>
                                    <div class="input-group position-relative">
                                        <input type="password" name="password" id="password" class="form-control form-control-lg" 
                                               placeholder="Enter your password" required>
                                        <button type="button" class="password-toggle" id="passwordToggle">
                                            <i class="bi bi-eye-fill"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Remember Me & Forgot Password -->
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="remember_me" id="rememberMe" 
                                               <?php echo $remember_me ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="rememberMe">
                                            Remember email
                                        </label>
                                    </div>
                                    <div class="forgot-password">
                                        <a href="#" onclick="alert('Password reset not yet implemented'); return false;">
                                            Forgot password?
                                        </a>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <button type="submit" class="btn btn-primary btn-lg w-100 btn-login">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Login
                                </button>
                            </form>

                            <!-- Footer Links -->
                            <div class="auth-footer">
                                <p>Don't have an account?</p>
                                <div class="auth-links">
                                    <a href="register.php" class="btn btn-register">
                                        <i class="bi bi-person-plus me-1"></i>Register
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
        // Password Toggle
        const passwordInput = document.getElementById('password');
        const passwordToggle = document.getElementById('passwordToggle');

        passwordToggle.addEventListener('click', function(e) {
            e.preventDefault();
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Toggle icon
            if (type === 'text') {
                this.innerHTML = '<i class="bi bi-eye-slash-fill"></i>';
            } else {
                this.innerHTML = '<i class="bi bi-eye-fill"></i>';
            }
        });

        // Form Validation
        const form = document.getElementById('loginForm');
        form.addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address');
                return;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters');
                return;
            }
        });

        // Email input focus
        document.getElementById('email').addEventListener('focus', function() {
            this.select();
        });
    </script>
</body>
</html>
