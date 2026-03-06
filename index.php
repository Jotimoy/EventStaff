<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EventStaff - Part-Time Job Hiring Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top glass-nav" style="backdrop-filter: blur(10px); background: rgba(13, 110, 253, 0.95);">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-calendar-event me-2"></i><strong>EventStaff</strong>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="auth/login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-light text-primary ms-2 px-3 rounded-pill" href="auth/register.php">Register</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-gradient">
        <div class="container">
            <div class="row align-items-center" style="min-height: 100vh; padding-top: 80px;">
                <div class="col-lg-6 py-5 animate-fade-in">
                    <span class="badge bg-light text-primary mb-3 px-3 py-2">
                        <i class="bi bi-lightning-charge-fill"></i> #1 Event Hiring Platform
                    </span>
                    <h1 class="display-3 fw-bold mb-4" style="line-height: 1.2;">
                        Find Your Perfect
                        <span class="text-gradient">Event Job</span>
                    </h1>
                    <p class="lead mb-4 text-muted" style="font-size: 1.25rem;">
                        Connect event organizers with talented part-time workers for seminars, exhibitions, and promotional campaigns. Start earning today!
                    </p>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="auth/register.php" class="btn btn-primary btn-lg px-4 py-3 shadow-lg btn-modern">
                            <i class="bi bi-rocket-takeoff me-2"></i>Get Started Free
                        </a>
                        <a href="#features" class="btn btn-outline-dark btn-lg px-4 py-3 btn-modern">
                            <i class="bi bi-play-circle me-2"></i>Learn More
                        </a>
                    </div>
                    <!-- Trust Badges -->
                    <div class="mt-5 pt-3">
                        <p class="text-muted small mb-2">Trusted by leading organizations</p>
                        <div class="d-flex gap-3 align-items-center flex-wrap">
                            <span class="badge bg-light text-dark px-3 py-2"><i class="bi bi-shield-check"></i> Secure</span>
                            <span class="badge bg-light text-dark px-3 py-2"><i class="bi bi-clock-history"></i> 24/7 Support</span>
                            <span class="badge bg-light text-dark px-3 py-2"><i class="bi bi-star-fill text-warning"></i> 4.9/5 Rating</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 text-center animate-float d-none d-lg-block">
                    <div class="hero-illustration">
                        <div class="illustration-card">
                            <i class="bi bi-calendar-event" style="font-size: 8rem; color: #0d6efd;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Animated background shapes -->
        <div class="floating-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5 bg-light">
        <div class="container py-5">
            <div class="text-center mb-5">
                <span class="badge bg-primary mb-3 px-3 py-2">How It Works</span>
                <h2 class="display-5 fw-bold mb-3">Choose Your Path</h2>
                <p class="text-muted lead">Simple process to connect organizers and workers</p>
            </div>
            <div class="row g-4">
                <!-- For Organizers -->
                <div class="col-lg-6">
                    <div class="card modern-card h-100 border-0 shadow-lg card-hover-lift">
                        <div class="card-body p-4 p-lg-5">
                            <div class="icon-box mb-4">
                                <i class="bi bi-briefcase-fill text-primary" style="font-size: 3rem;"></i>
                            </div>
                            <div class="mb-4">
                                <span class="badge bg-primary-gradient fs-6 px-3 py-2">For Organizers</span>
                            </div>
                            <h3 class="card-title mb-4">Hire Part-Time Workers</h3>
                            <ul class="list-unstyled feature-list">
                                <li class="mb-3"><i class="bi bi-check-circle-fill text-primary me-2"></i> Create events and shifts</li>
                                <li class="mb-3"><i class="bi bi-check-circle-fill text-primary me-2"></i> Receive applications from workers</li>
                                <li class="mb-3"><i class="bi bi-check-circle-fill text-primary me-2"></i> Approve or reject applicants</li>
                                <li class="mb-3"><i class="bi bi-check-circle-fill text-primary me-2"></i> Track payment status</li>
                                <li class="mb-3"><i class="bi bi-check-circle-fill text-primary me-2"></i> Manage multiple events</li>
                            </ul>
                            <a href="auth/register.php" class="btn btn-primary btn-lg w-100 mt-4 btn-modern">
                                <i class="bi bi-arrow-right-circle me-2"></i>Register as Organizer
                            </a>
                        </div>
                    </div>
                </div>
                <!-- For Workers -->
                <div class="col-lg-6">
                    <div class="card modern-card h-100 border-0 shadow-lg card-hover-lift">
                        <div class="card-body p-4 p-lg-5">
                            <div class="icon-box mb-4">
                                <i class="bi bi-person-badge-fill text-success" style="font-size: 3rem;"></i>
                            </div>
                            <div class="mb-4">
                                <span class="badge bg-success-gradient fs-6 px-3 py-2">For Workers</span>
                            </div>
                            <h3 class="card-title mb-4">Find Event Jobs</h3>
                            <ul class="list-unstyled feature-list">
                                <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i> Browse available shifts</li>
                                <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i> Apply for multiple jobs</li>
                                <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i> Track application status</li>
                                <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i> Build your profile</li>
                                <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i> Get paid for your work</li>
                            </ul>
                            <a href="auth/register.php" class="btn btn-success btn-lg w-100 mt-4 btn-modern">
                                <i class="bi bi-arrow-right-circle me-2"></i>Register as Worker
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-gradient text-white py-5">
        <div class="container py-5">
            <div class="row text-center g-4">
                <div class="col-md-4">
                    <div class="stat-card p-4">
                        <div class="stat-icon mb-3">
                            <i class="bi bi-calendar-check" style="font-size: 3rem;"></i>
                        </div>
                        <h2 class="display-3 fw-bold mb-2 counter">100</h2>
                        <p class="lead mb-0">Events Organized</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card p-4">
                        <div class="stat-icon mb-3">
                            <i class="bi bi-people" style="font-size: 3rem;"></i>
                        </div>
                        <h2 class="display-3 fw-bold mb-2 counter">500</h2>
                        <p class="lead mb-0">Active Workers</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card p-4">
                        <div class="stat-icon mb-3">
                            <i class="bi bi-building" style="font-size: 3rem;"></i>
                        </div>
                        <h2 class="display-3 fw-bold mb-2 counter">50</h2>
                        <p class="lead mb-0">Companies</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5 bg-light">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <div class="cta-box p-4 p-lg-5 rounded-4 shadow-lg">
                        <i class="bi bi-rocket-takeoff-fill text-primary mb-4" style="font-size: 4rem;"></i>
                        <h2 class="display-5 fw-bold mb-4">Ready to Get Started?</h2>
                        <p class="lead mb-4 text-muted">Join EventStaff today and start connecting with opportunities</p>
                        <a href="auth/register.php" class="btn btn-primary btn-lg px-5 py-3 shadow-lg btn-modern">
                            <i class="bi bi-person-plus me-2"></i>Create Free Account
                        </a>
                        <p class="text-muted mt-3 small">No credit card required • Free forever • Get started in 2 minutes</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer-gradient text-white py-5">
        <div class="container py-4">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h4 class="fw-bold mb-3">
                        <i class="bi bi-calendar-event me-2"></i>EventStaff
                    </h4>
                    <p class="text-white-50">Connecting event organizers with talented part-time workers.</p>
                </div>
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h5 class="fw-bold mb-3">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="index.php" class="text-white-50 text-decoration-none hover-link">Home</a></li>
                        <li class="mb-2"><a href="auth/login.php" class="text-white-50 text-decoration-none hover-link">Login</a></li>
                        <li class="mb-2"><a href="auth/register.php" class="text-white-50 text-decoration-none hover-link">Register</a></li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h5 class="fw-bold mb-3">Contact</h5>
                    <p class="text-white-50 mb-2">
                        <i class="bi bi-envelope me-2"></i> info@eventstaff.com
                    </p>
                    <p class="text-white-50">
                        <i class="bi bi-phone me-2"></i> +1 (555) 123-4567
                    </p>
                </div>
            </div>
            <hr class="my-4 bg-white opacity-25">
            <div class="text-center text-white-50">
                <p class="mb-0">&copy; 2026 EventStaff Platform. Academic Project. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
