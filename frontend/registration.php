<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Attendance System Registration</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Link to external CSS -->
  <link rel="stylesheet" href="css/registration.css">
</head>
<body>
    <div class="registration-box">
      <div class="welcome-animated-title">
        <span class="animated-icon">
          <!-- Animated waving hand SVG -->
          <svg class="svg-animated-hand" viewBox="0 0 64 64" fill="none">
            <g>
              <path d="M22 32c-2.5-5-1.6-13.5 5-13.5 7 0 4.5 11 4.5 14.5" stroke="#2563eb" stroke-width="2.5" stroke-linecap="round"/>
              <path d="M31.5 33.5V17.5" stroke="#358efa" stroke-width="2.5" stroke-linecap="round"/>
              <path d="M32 32c0-3.5-1.5-14.5 5-14.5 7 0 4.5 11 4.5 14.5" stroke="#2563eb" stroke-width="2.5" stroke-linecap="round"/>
              <ellipse cx="32" cy="43" rx="13" ry="10" fill="#2563eb" fill-opacity="0.18"/>
              <ellipse cx="32" cy="54" rx="11" ry="3" fill="#358efa" fill-opacity="0.18"/>
              <circle cx="32" cy="32" r="9" fill="#fff" fill-opacity="0.94" stroke="#2563eb" stroke-width="2"/>
            </g>
          </svg>
        </span>
        Welcome! <span style="color:#f8f9fa;">Register for Attendance</span>
      </div>
      <div class="form-title">
        Fill your details to create your attendance account.
      </div>
      
      <!-- Error Area -->
      <div id="error-message" class="alert alert-danger" style="display:none"></div>
      
      <form action="../backend-php/register.php" method="POST" enctype="multipart/form-data" autocomplete="off">
        <div class="row g-3">
          <!-- Left Column -->
          <div class="col-md-6">
            <div class="mb-2">
              <label for="fullName" class="form-label">Full Name *</label>
              <input type="text" class="form-control" id="fullName" name="fullName" placeholder="Enter your full name" required autocomplete="name">
            </div>
            <div class="mb-2">
              <label for="email" class="form-label">Email *</label>
              <input type="email" class="form-control" id="email" name="email" placeholder="e.g. student@college.edu" required autocomplete="email">
            </div>
            <div class="mb-2">
              <label for="password" class="form-label">Password *</label>
              <input type="password" class="form-control" id="password" name="password" placeholder="Create a password" required autocomplete="new-password">
            </div>
            <div class="mb-2">
              <label for="confirmPassword" class="form-label">Confirm Password *</label>
              <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" placeholder="Re-enter your password" required autocomplete="new-password">
            </div>
            <div class="mb-2">
              <label for="contact" class="form-label">Contact Number (Optional)</label>
              <input type="tel" class="form-control" id="contact" name="contact" placeholder="e.g. 9876543210" autocomplete="tel">
            </div>
          </div>

          <!-- Right Column -->
          <div class="col-md-6">
            <div class="mb-2">
              <label for="role" class="form-label">Role *</label>
              <select class="form-select" id="role" name="role" required>
                <option value="">Select Role</option>
                <option>Student</option>
                <option>Teacher</option>
              </select>
            </div>
            <div class="mb-2" id="totalStudentsBox" style="display:none;">
              <label for="total_students" class="form-label">Total Students (for Teachers)</label>
              <input type="number" min="1" class="form-control" id="total_students" name="total_students" placeholder="e.g. 25">
              <div class="form-text">Enter the number of students in your class/trade.</div>
            </div>
            <div class="mb-2">
              <label for="trade" class="form-label">Trade *</label>
              <select class="form-select" id="trade" name="trade" required>
                <option value="">Select Trade</option>
                <option>Electrician</option>
                <option>Welder</option>
                <option>Computer Software Application (CSA)</option>
                <option>Cosmetology</option>
                <option>Dress Making</option>
                <option>Fashion Design & Technology</option>
              </select>
            </div>
            <div class="mb-2">
              <label for="session" class="form-label">Session *</label>
              <select class="form-select" id="session" name="session" required>
                <option value="">Select Session</option>
                <option>2023-2024</option>
                <option>2024-2025</option>
                <option>2025-2026</option>
                <option>2026-2027</option>
              </select>
            </div>
            <div class="mb-2">
              <label class="form-label">Gender *</label>
              <div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input" type="radio" name="gender" id="genderMale" value="Male" required>
                  <label class="form-check-label" for="genderMale">Male</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input" type="radio" name="gender" id="genderFemale" value="Female" required>
                  <label class="form-check-label" for="genderFemale">Female</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input" type="radio" name="gender" id="genderOther" value="Other" required>
                  <label class="form-check-label" for="genderOther">Other</label>
                </div>
              </div>
            </div>
            <div class="mb-2">
              <label for="dob" class="form-label">Date of Birth *</label>
              <input type="date" class="form-control" id="dob" name="dob" required>
            </div>
          </div>
        </div>

        <!-- Submit Button -->
        <div class="row mt-2">
          <div class="col-12 d-grid gap-2">
            <button type="submit" class="btn btn-primary btn-sm">Register</button>
          </div>
        </div>
      </form>
      <div class="text-center mt-3">
        Already have an account? <a href="login.php" class="login-link">Login</a>
      </div>
    </div>
    
    <!-- Password Match & Error Display Script -->
    <script>
      document.querySelector("form").addEventListener("submit", function(e) {
        var pwd = document.getElementById("password").value;
        var cpwd = document.getElementById("confirmPassword").value;
        var errorDiv = document.getElementById('error-message');
        errorDiv.style.display = "none";
        errorDiv.innerText = "";
        if (pwd !== cpwd) {
          errorDiv.innerText = "Passwords do not match!";
          errorDiv.style.display = "block";
          e.preventDefault();
          return false;
        }
      });
      // Optional: Show backend error (if redirected with ?error=)
      window.addEventListener('DOMContentLoaded', function () {
        const params = new URLSearchParams(window.location.search);
        if (params.has('error')) {
          let msg = params.get('error');
          document.getElementById('error-message').innerText = decodeURIComponent(msg.replace(/\+/g, ' '));
          document.getElementById('error-message').style.display = "block";
        }
      });

      // Show/hide Total Students field based on role
      document.getElementById('role').addEventListener('change', function() {
        var totalBox = document.getElementById('totalStudentsBox');
        if (this.value === 'Teacher') {
          totalBox.style.display = '';
          document.getElementById('total_students').setAttribute('required', 'required');
        } else {
          totalBox.style.display = 'none';
          document.getElementById('total_students').removeAttribute('required');
          document.getElementById('total_students').value = '';
        }
      });
    </script>

</body>
</html>