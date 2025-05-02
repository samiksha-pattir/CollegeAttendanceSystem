<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College Attendance Management</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Animate.css for smooth animations -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <!-- Font Awesome for Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top shadow">
        <div class="container">
            <a class="navbar-brand" href="#">NSTI logo</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact Us</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header id="home" class="hero">
        <div class="hero-content text-center">
            <h1 class="animate__animated animate__fadeInDown">Welcome to CITS Attendance Tracker</h1>
            <p class="animate__animated animate__fadeInUp">Effortless Attendance Tracking for Modern Education</p>
            <a href="login.php" class="btn btn-primary btn-lg">Login</a>
            <a href="register.php" class="btn btn-outline-light btn-lg">Sign Up</a>
        </div>
    </header>

    <!-- Features Section -->
    <section id="features" class="features">
        <div class="container">
            <h2 class="text-center mb-4">Features</h2>
            <div class="row text-center">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="p-4 feature-card border shadow-sm">
                        <i class="icon fas fa-calendar-check"></i>
                        <h4 class="mt-3">Easy Attendance Tracking</h4>
                        <p>Mark attendance in just a few clicks, saving time and effort.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="p-4 feature-card border shadow-sm">
                        <i class="icon fas fa-chart-bar"></i>
                        <h4 class="mt-3">Real-Time Reports</h4>
                        <p>Access detailed attendance reports instantly, anytime.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="p-4 feature-card border shadow-sm">
                        <i class="icon fas fa-lock"></i>
                        <h4 class="mt-3">Secure Login</h4>
                        <p>Your data is protected with robust security measures.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Us Section -->
    <section id="about" class="about-us">
        <div class="container">
            <h2 class="text-center">About Us</h2>
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="about-card p-4">
                        <div class="circle-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <h5>Our Mission</h5>
                        <p>To provide exceptional education and foster an environment of innovation and growth for our students.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="about-card p-4">
                        <div class="circle-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h5>Who We Serve</h5>
                        <p>We serve students, faculty, and staff with a focus on academic excellence and community development.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="about-card p-4">
                        <div class="circle-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h5>Why Choose Us</h5>
                        <p>Proven track record of success, state-of-the-art facilities, and a commitment to student success.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="about-card p-4">
                        <div class="circle-icon">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <h5>Our Vision</h5>
                        <p>To be a leading institution recognized globally for innovation, research, and excellence in education.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Us Section -->
    <section id="contact" class="py-5">
        <div class="container">
            <h2 class="text-center mb-4">Contact Us</h2>
            <p class="text-center mb-5">Feel free to reach out to us through the following channels:</p>
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <ul class="list-group">
                        <li class="list-group-item d-flex align-items-center">
                            <i class="fas fa-envelope me-3 text-primary"></i>
                            <strong>Email:</strong>
                            <a href="mailto:nstiw-indore@dgt.gov.in" class="ms-2">nstiw-indore@dgt.gov.in</a>
                        </li>
                        <li class="list-group-item d-flex align-items-center">
                            <i class="fas fa-phone-alt me-3 text-success"></i>
                            <strong>Phone:</strong>
                            <span class="ms-2">0731 2991471</span>
                        </li>
                        <li class="list-group-item d-flex align-items-center">
                            <i class="fab fa-linkedin me-3 text-info"></i>
                            <strong>LinkedIn:</strong>
                            <a href="https://www.linkedin.com/in/samiksha-pattir-97500a253/" target="_blank" class="ms-2">LinkedIn</a>
                        </li>
                        <li class="list-group-item d-flex align-items-center">
                            <i class="fab fa-instagram me-3 text-danger"></i>
                            <strong>Instagram:</strong>
                            <a href="#" target="_blank" class="ms-2">NSTI Insta</a>
                        </li>
                        <li class="list-group-item d-flex align-items-center">
                            <i class="fab fa-github me-3 text-dark"></i>
                            <strong>GitHub:</strong>
                            <a href="https://github.com/samiksha-pattir" target="_blank" class="ms-2">GitHub</a>
                        </li>
                        <li class="list-group-item d-flex align-items-center">
                            <i class="fas fa-map-marker-alt me-3 text-danger"></i>
                            <strong>Address:</strong>
                            <span class="ms-2">NSTI, Indore, Madhya Pradesh, India</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <p>&copy; 2025 College Attendance Management System. All rights reserved.</p>
    </footer>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>