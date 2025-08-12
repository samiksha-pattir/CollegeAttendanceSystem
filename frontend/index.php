<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College Attendance Management</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <!-- particles.js library -->
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
</head>
<body class="bg-clg-img">
        <!-- PARTICLES BACKGROUND -->
    <div id="particles-js"></div>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top shadow-sm custom-navbar">
      <div class="container-fluid d-flex align-items-center">
        <a class="d-flex align-items-center text-decoration-none" href="#home">
          <img src="images/logo.jpg" alt="NSTI Logo" class="logo-nav rounded-circle" />
          <span class="navbar-brand mb-0 ms-2">NSTI (W) Indore</span>
        </a>
        <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarNavDropdown">
          <ul class="navbar-nav align-items-center ms-auto">
            <li class="nav-item"><a class="nav-link active" href="#home">Home</a></li>
            <li class="nav-item"><a class="nav-link" href="#courses">Our Courses/CITS</a></li>
            <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
            <li class="nav-item"><a class="nav-link" href="#about">About Us</a></li>
            <li class="nav-item"><a class="nav-link" href="#team">Team</a></li>
            <li class="nav-item"><a class="nav-link" href="#teachers">Teachers</a></li>
            <li class="nav-item"><a class="nav-link" href="#contact">Contact Us</a></li>
            <li class="nav-item dropdown ms-3">
              <a class="nav-link dropdown-toggle btn-login" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">Login / Sign Up</a>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="login.php">Login</a></li>
                <li><a class="dropdown-item" href="registration.php">Sign Up</a></li>
              </ul>
            </li>
          </ul>
        </div>
      </div>
    </nav>
    <!-- END NAVBAR -->

    <!-- HERO SECTION -->
    <header id="home" class="hero d-flex align-items-center justify-content-center">
        <div class="hero-content text-center text-white">
            <h1 class="mb-3 fw-bold">Welcome to CITS Attendance Tracker</h1>
            <p class="lead mb-0">Effortless Attendance Tracking for Modern Education</p>
        </div>
    </header>
    <!-- END HERO SECTION -->

    <!-- COURSES SECTION -->
    <section id="courses" class="courses-section py-5 glassmorphism">
        <div class="container">
            <h2 class="text-center mb-4">Our Courses / CITS</h2>
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <p>
                        <b>Craft Instructor Training Scheme (CITS):</b> Our institute offers specialized training and certification for Craft Instructors, empowering them with the latest skills and pedagogical techniques in Computer Software Application (CSA) and other trades. For more details visit <a href="https://nstiwindore.dgt.gov.in/" target="_blank">NSTI Indore official website</a>.
                    </p>
                </div>
            </div>
        </div>
    </section>
    <!-- END COURSES SECTION -->

    <!-- FEATURES SECTION -->
    <section id="features" class="features py-5">
        <div class="container">
            <h2 class="text-center mb-5">Our Features</h2>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card glassmorphism">
                        <div class="card-content">
                            <i class="fas fa-calendar-check icon"></i>
                            <h4>Easy Attendance Tracking</h4>
                            <p>Mark attendance in just a few clicks, saving time and effort.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card glassmorphism">
                        <div class="card-content">
                            <i class="fas fa-chart-bar icon"></i>
                            <h4>Real-Time Reports</h4>
                            <p>Access detailed attendance reports instantly, anytime.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card glassmorphism">
                        <div class="card-content">
                            <i class="fas fa-lock icon"></i>
                            <h4>Secure Login</h4>
                            <p>Your data is protected with robust security measures.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card glassmorphism">
                        <div class="card-content">
                            <i class="fas fa-cogs icon"></i>
                            <h4>Customizable Dashboard</h4>
                            <p>Tailor your dashboard to display the information you need most.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card glassmorphism">
                        <div class="card-content">
                            <i class="fas fa-users icon"></i>
                            <h4>Multi-User Support</h4>
                            <p>Allow multiple users to manage and view attendance seamlessly.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card glassmorphism">
                        <div class="card-content">
                            <i class="fas fa-cloud-upload-alt icon"></i>
                            <h4>Cloud Backup</h4>
                            <p>Store your data securely in the cloud for easy access anytime.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- END FEATURES SECTION -->

    <!-- ABOUT US SECTION -->
    <section id="about" class="about-us pb-5 glassmorphism">
        <div class="container">
            <h2 class="text-center mb-4">About Us</h2>
            <div class="about-project-card p-4 shadow rounded-4 text-center mx-auto mb-5 glassmorphism">
                <h4 class="mb-3">Our Project</h4>
                <p>
                    <b>College Attendance Management System</b> is designed to simplify and digitize attendance for students and teachers. Our project allows real-time attendance tracking, transparent approval workflow, and data-driven insights for better academic administration. Built with modern technology, it ensures security, scalability, and user-friendliness.
                </p>
            </div>
        </div>
    </section>
    <!-- END ABOUT US SECTION -->

    <!-- TEAM SECTION -->
    <section id="team" class="people-section py-4">
        <div class="container">
            <h2 class="text-center mb-4 text-primary">Our Team</h2>
            <div class="row justify-content-center gy-4">
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="person-card text-center shadow glassmorphism">
                        <img src="images/rupali.jpg" alt="Rupali" class="person-img mb-2">
                        <h6 class="fw-bold mb-0">Rupali</h6>
                        <small class="text-muted">Frontend Developer<br>CSA Trainee (NSTI (W) Indore), Chhattisgarh<br>+91 62646 78607</small>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="person-card text-center shadow glassmorphism">
                        <img src="images/neelam.jpg" alt="Neelam Yadav" class="person-img mb-2">
                        <h6 class="fw-bold mb-0">Neelam Yadav</h6>
                        <small class="text-muted">Database Manager<br>CSA Trainee (NSTI (W) Indore), U.P<br>+91 91515 01255</small>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="person-card text-center shadow glassmorphism">
                        <img src="images/simi.jpg" alt="Samiksha" class="person-img mb-2">
                        <h6 class="fw-bold mb-0">Samiksha</h6>
                        <small class="text-muted">Backend Developer<br>CSA Trainee (NSTI (W) Indore), Haryana<br>+91 91534 92222</small>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- END TEAM SECTION -->

    <!-- TEACHERS SECTION -->
    <section id="teachers" class="people-section py-4">
        <div class="container">
            <h2 class="text-center mb-4 text-success">Our Teachers</h2>
            <div class="row justify-content-center gy-4">
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="person-card text-center shadow glassmorphism">
                        <img src="images/Naina_Nagpal.jpg" alt="Ms. Naina Nagpal" class="person-img mb-2">
                        <h6 class="fw-bold mb-0">Ms. Naina Nagpal</h6>
                        <small class="text-muted">Principal / Deputy Director<br>naina.nagpal@dgt.gov.in<br>0731-2991471</small>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="person-card text-center shadow glassmorphism">
                        <img src="images/psp.jpg" alt="Puspendra" class="person-img mb-2">
                        <h6 class="fw-bold mb-0">Puspendra</h6>
                        <small class="text-muted">Class Teacher, Trade - CSA<br>+91 89669 77928</small>
                    </div>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="person-card text-center shadow glassmorphism">
                        <img src="images/sharda.jpg" alt="Ms. Sharda Shekokar" class="person-img mb-2">
                        <h6 class="fw-bold mb-0">Ms. Sharda Shekokar</h6>
                        <small class="text-muted">Training Officer, Section Incharge<br>+91 99933 06515</small>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- END TEACHERS SECTION -->

    <!-- CONTACT US SECTION -->
    <section id="contact" class="py-5 contact-section position-relative glassmorphism">
        <div class="container">
            <h2 class="text-center mb-4">Contact Us</h2>
            <p class="text-center mb-5">Feel free to reach out to us through the following channels or send us a message!</p>
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4">
                    <div class="contact-info">
                        <div class="info-item d-flex align-items-center mb-4">
                            <i class="fas fa-map-marker-alt text-primary me-3"></i>
                            <div>
                                <h5>Address</h5> 
                                <p>
                                <a href="https://maps.app.goo.gl/idj1czux7MxbNTTC7" class="location-link">
                                    NSTI, Indore, Madhya Pradesh, India
                                </a>
                                </p>
                            </div>
                        </div>
                        <div class="info-item d-flex align-items-center mb-4">
                            <i class="fas fa-phone-alt text-success me-3"></i>
                            <div>
                                <h5>Phone</h5>
                                <p><a href="tel:07312991471" class="text-white text-decoration-none">0731 2991471</a></p>
                            </div>
                        </div>
                        <div class="info-item d-flex align-items-center mb-4">
                            <i class="fas fa-envelope text-danger me-3"></i>
                            <div>
                                <h5>Email</h5>
                                <p><a href="mailto:nstiw-indore@dgt.gov.in" class="text-white text-decoration-none">nstiw-indore@dgt.gov.in</a></p>
                            </div>
                        </div>
                        <div class="info-item d-flex align-items-center mb-4">
                            <i class="fab fa-linkedin text-info me-3"></i>
                            <div>
                                <h5>LinkedIn</h5>
                                <p><a href="https://www.linkedin.com/in/samiksha-pattir-97500a253/" target="_blank" class="text-white text-decoration-none">linkedin.com/in/samiksha-pattir</a></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <form class="contact-form p-4 shadow rounded-4 bg-white glassmorphism">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" placeholder="Enter your full name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" placeholder="Enter your email" required>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" rows="4" placeholder="Type your message..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Send</button>
                    </form>
                </div>
            </div>
        </div>
    </section>
    <!-- END CONTACT US SECTION -->

    <!-- FOOTER -->
    <footer class="footer mt-5 glassmorphism">
        <div class="container">
            <p class="mb-0">&copy; 2025 College Attendance Management System. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap Bundle JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- particles.js configuration -->
    <script>
    particlesJS("particles-js", {
      "particles": {
        "number": {"value": 60,"density": {"enable": true,"value_area": 800}},
        "color": {"value": "#6ef9ff"},
        "shape": {"type": "circle"},
        "opacity": {"value": 0.45,"random": true},
        "size": {"value": 2.6,"random": true},
        "move": {"enable": true,"speed": 1.25,"direction": "bottom","out_mode": "out"}
      },
      "interactivity": {
        "detect_on": "canvas",
        "events": {
          "onhover": {"enable": true,"mode": "repulse"},
          "onclick": {"enable": false}
        }
      },
      "retina_detect": true
    });
    </script>
    <!-- BG transition JS -->
    <script>
    window.addEventListener("DOMContentLoaded", function() {
        let isImage = true;
        setInterval(function() {
            if (isImage) {
                document.body.classList.remove("bg-dark");
                document.body.classList.add("bg-clg-img");
            } else {
                document.body.classList.remove("bg-clg-img");
                document.body.classList.add("bg-dark");
            }
            isImage = !isImage;
        }, 4000); //  seconds
    });
    </script>

</body>
</html>