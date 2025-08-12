<?php 
if (isset($_GET['success'])) {
    echo '<div class="alert alert-success">'.htmlspecialchars($_GET['success']).'</div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Attendance System Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- FontAwesome for the eye icon -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="css/login.css">
</head>
<body>
  <div class="login-section">
    <!-- Animated Blue Login Icon-->
    <span class="login-icon-animated">
      <!-- SVG icon code omitted for brevity -->
    </span>
    <div class="login-title">
      College Attendance System Login
    </div>
    <div class="login-subtitle">
      Welcome! Sign in to manage your attendance.<br>
      <span style="font-size:1.2rem;">🕒</span>
    </div>

    <!-- Error Area -->
    <div id="error-message" class="alert alert-danger" style="display:none"></div>

    <!-- Login Form -->
    <form class="login-form" action="../backend-php/loginData.php" method="post" autocomplete="off">
      <div class="mb-2">
        <label for="loginEmail" class="form-label">Email</label>
        <input type="email" class="form-control" id="loginEmail" name="loginEmail" placeholder="Enter your email" required autocomplete="email">
      </div>
      <div class="mb-2">
        <label for="loginPassword" class="form-label">Password</label>
        <div class="input-group">
          <input type="password" class="form-control" id="loginPassword" name="loginPassword" placeholder="Enter your password" required autocomplete="current-password">
          <button class="btn btn-outline-secondary" type="button" id="togglePassword" tabindex="-1">
            <span id="eyeIcon" class="fa fa-eye"></span>
          </button>
        </div>
      </div>
      <div class="mb-2 form-check d-flex align-items-center">
        <input type="checkbox" class="form-check-input" id="rememberMe" name="rememberMe">
        <label class="form-check-label ms-1" for="rememberMe">Remember me</label>
        <a href="#" class="ms-auto forgot-link">Forgot Password?</a>
      </div>
      <div class="d-grid">
        <button type="submit" class="btn btn-primary btn-sm">Login</button>
      </div>
      <div class="mt-2" style="font-size:0.93rem;">
        Don't have an account? <a href="registration.php" class="forgot-link">Register</a>
      </div>
    </form>
  </div>

  <!-- Bootstrap JS (optional) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Show/hide password, show backend error, etc. -->
  <script>
    // Show/hide password toggle
    document.getElementById('togglePassword').addEventListener('click', function () {
      const passwordInput = document.getElementById('loginPassword');
      const eyeIcon = document.getElementById('eyeIcon');
      if (passwordInput.type === "password") {
        passwordInput.type = "text";
        eyeIcon.classList.remove('fa-eye');
        eyeIcon.classList.add('fa-eye-slash');
      } else {
        passwordInput.type = "password";
        eyeIcon.classList.remove('fa-eye-slash');
        eyeIcon.classList.add('fa-eye');
      }
    });

    // Optional: Show backend error (if redirected with ?error=)
    window.addEventListener('DOMContentLoaded', function () {
      const params = new URLSearchParams(window.location.search);
      if (params.has('error')) {
        const msg = params.get('error');
        document.getElementById('error-message').innerText = decodeURIComponent(msg.replace(/\+/g, ' '));
        document.getElementById('error-message').style.display = "block";
      }
    });
  </script>
</body>
</html>