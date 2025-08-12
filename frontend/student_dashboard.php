<?php
session_start();
include '../backend-php/db.php';

// Show success/error messages (for fallback, used only on full reload)
$success_message = '';
$error_message = '';
if (isset($_GET['success'])) {
    $success_message = htmlspecialchars($_GET['success']);
}
if (isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
}
date_default_timezone_set('Asia/Kolkata');

// Dynamic student info from session
$student_id      = $_SESSION['user_id'] ?? 0;
$student_name    = $_SESSION['name'] ?? "Student";
$student_email   = $_SESSION['email'] ?? "student@example.com";
$student_trade   = $_SESSION['trade'] ?? "Unknown";
$student_session = $_SESSION['session'] ?? "Unknown";
$student_contact = $_SESSION['contact_number'] ?? "N/A";
$student_dob     = $_SESSION['date_of_birth'] ?? "N/A";

// Handles missing, null, or empty img in session
$img = $_SESSION['img'] ?? '';
if (!$img || $img == 'default-avatar.png') {
    $student_photo = '/CollegeAttendanceSystem/frontend/images/default-avatar.png';
} else {
    $student_photo = '/CollegeAttendanceSystem/frontend/images/' . $img;
}

// Fetch Notifications (latest 10)
$notifications = [];
if ($student_id) {
    $noti_sql = "SELECT message, created_at FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 10";
    $stmt = $conn->prepare($noti_sql);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $noti_result = $stmt->get_result();
    while ($row = $noti_result->fetch_assoc()) {
        $notifications[] = $row;
    }
    $stmt->close();
}

// Fetch Attendance History (ALL statuses)
$attendance_history = [];
if ($student_id) {
    $hist_sql = "SELECT date, mark_time, status FROM attendance_requests WHERE student_id=? ORDER BY date DESC";
    $stmt = $conn->prepare($hist_sql);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $hist_result = $stmt->get_result();
    while ($row = $hist_result->fetch_assoc()) {
        $attendance_history[] = $row;
    }
    $stmt->close();
}

// Fetch today's attendance for status/badge and button
$attendance_today = null;
if ($student_id) {
    $today = date('Y-m-d');
    $sql = "SELECT status FROM attendance_requests WHERE student_id=? AND date=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $student_id, $today);
    $stmt->execute();
    $stmt->bind_result($status_today);
    if ($stmt->fetch()) {
        $attendance_today = $status_today; // could be "pending", "approved", "rejected"
    }
    $stmt->close();
}
// Set badge and button state for attendance tab
if ($attendance_today === "pending") {
    $status_badge = '<span id="attendanceStatus" class="badge bg-warning text-dark">Pending</span>';
    $btn_disabled = 'disabled';
} elseif ($attendance_today === "approved") {
    $status_badge = '<span id="attendanceStatus" class="badge bg-success">Approved</span>';
    $btn_disabled = 'disabled';
} elseif ($attendance_today === "rejected") {
    $status_badge = '<span id="attendanceStatus" class="badge bg-danger">Rejected</span>';
    $btn_disabled = '';
} else {
    $status_badge = '<span id="attendanceStatus" class="badge bg-warning text-dark">Not Marked</span>';
    $btn_disabled = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Student Dashboard | Attendance System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/dashboard.css">
  <style>
    #profile-content, #attendance-content, #history-content, #notifications-content, #feedback-content { display: none; }
    #profile-content { display: block; }
    .sidebar-profile {
        text-align: center;
        padding: 20px 0;
    }
    .btn-primary{
        background-color:var(--bs-green);
        border-color: #2563eb;
    }
    --bs-btn-border-color{
      --bs-btn-border-color:var(--bs-green);
      --bs-btn-hover-bg:
    }
    .sidebar-profile img {
        width: 90px;
        height: 90px;
        object-fit: cover;
        border-radius: 50%;
        margin-bottom: 10px;
        border: 2px solid #fff;
        background: #f8f9fa;
    }
    .sidebar-name {
        font-weight: bold;
        margin-bottom: 3px;
    }
    .sidebar-email {
        font-size: 0.92em;
        color: #555;
    }
  </style>
</head>
<body >
  <div class="dashboard-wrapper d-flex">
    <!-- Sidebar -->
    <nav class="sidebar flex-shrink-0 border-end">
      <div class="sidebar-profile">
        <img src="<?= htmlspecialchars($student_photo) ?>" id="sidebarProfilePhoto" alt="Profile Photo">
        <div class="sidebar-email" id="sidebarUserEmail"><?= htmlspecialchars($student_email) ?></div>
      </div>
      <ul class="nav flex-column mt-3">
        <li class="nav-item"><a class="nav-link active" data-content="profile" href="#"><i class="fa fa-user"></i> Profile</a></li>
        <li class="nav-item"><a class="nav-link" data-content="attendance" href="#"><i class="fa fa-calendar-check"></i> Mark Attendance</a></li>
        <li class="nav-item"><a class="nav-link" data-content="history" href="#"><i class="fa fa-history"></i>  View  History</a></li>
        <li class="nav-item"><a class="nav-link" data-content="notifications" href="#"><i class="fa fa-bell"></i> Notifications</a></li>
        <li class="nav-item"><a class="nav-link" data-content="feedback" href="#"><i class="fa fa-star"></i> Feedback</a></li>
        <li class="nav-item mt-auto"><a class="nav-link text-danger" href="login.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
      </ul>
    </nav>
    <!-- Main Content -->
    <div class="main-content flex-grow-1">
      <!-- Profile Alerts (AJAX or Redirect) -->
      <div id="profileAlert">
        <?php if ($success_message): ?>
          <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
            <i class="fa fa-check-circle"></i>
            <?= $success_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>
        <?php if ($error_message): ?>
          <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
            <i class="fa fa-exclamation-triangle"></i>
            <?= $error_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>
      </div>
      <!-- Profile Section -->
      <div id="profile-content">
        <div class="card p-4 mb-3">
          <div class="mb-4">
            <h4 class="fw-bold text-primary mb-2">
              👋 Welcome, <span id="welcomeName"><?= htmlspecialchars($student_name) ?></span>!
            </h4>
            <div class="text-secondary mb-3">
              This is your student dashboard. Here you can view and update your profile, mark attendance, and track your academic presence.
            </div>
          </div>
          <div class="d-flex align-items-center mb-4">
            <div class="profile-photo-edit me-3 text-center">
              <img src="<?= htmlspecialchars($student_photo) ?>" id="profilePhoto" width="90" height="90" class="border border-3 shadow-sm" alt="Profile Photo">
              <form id="photoUploadForm" enctype="multipart/form-data" method="POST">
                <input type="hidden" name="user_id" value="<?= $student_id ?>">
                <input type="file" name="profile_photo" id="profilePhotoInput" accept="image/*" required class="form-control form-control-sm mb-1">
                <button type="submit" class="btn btn-primary btn-sm">Upload Photo</button>
              </form>
            </div>
            <div>
              <h5 class="mb-1" id="profileNameView"><?= htmlspecialchars($student_name) ?></h5>
              <div class="text-muted small" id="profileTradeView">Trade: <?= htmlspecialchars($student_trade) ?></div>
              <div class="text-muted small" id="profileSessionView">Session: <?= htmlspecialchars($student_session) ?></div>
            </div>
          </div>
          <div id="profileReadonly">
            <div class="row g-3 mb-2">
              <div class="col-md-6"><b>Name:</b> <span id="profileNameVal"><?= htmlspecialchars($student_name) ?></span></div>
              <div class="col-md-6"><b>Email:</b> <span id="profileEmailVal"><?= htmlspecialchars($student_email) ?></span></div>
              <div class="col-md-6"><b>Contact Number:</b> <span id="profileContactVal"><?= htmlspecialchars($student_contact) ?></span></div>
              <div class="col-md-6"><b>Date of Birth:</b> <span id="profileDOBVal"><?= htmlspecialchars($student_dob) ?></span></div>
              <div class="col-md-6"><b>Trade:</b> <span id="profileTradeVal"><?= htmlspecialchars($student_trade) ?></span></div>
              <div class="col-md-6"><b>Session:</b> <span id="profileSessionVal"><?= htmlspecialchars($student_session) ?></span></div>
            </div>
            <button class="btn btn-outline-primary btn-sm mt-3" id="editProfileBtn"><i class="fa fa-edit"></i> Edit Profile</button>
          </div>
          <form id="editProfileForm" action="../backend-php/update_profile.php" method="post" style="display:none;">
            <input type="hidden" name="user_id" value="<?= $student_id ?>">
            <div class="row g-3 mb-2">
              <div class="col-md-6">
                <label class="form-label">Name</label>
                <input type="text" class="form-control" id="editName" name="name" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" id="editEmail" name="email" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Contact Number</label>
                <input type="text" class="form-control" id="editContact" name="contact_number">
              </div>
              <div class="col-md-6">
                <label class="form-label">Date of Birth</label>
                <input type="date" class="form-control" id="editDOB" name="date_of_birth">
              </div>
              <div class="col-md-6">
                <label class="form-label">Trade</label>
                <input type="text" class="form-control" id="editTrade" name="trade" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Session</label>
                <input type="text" class="form-control" id="editSession" name="session" required>
              </div>
            </div>
            <button class="btn btn-primary btn-sm" type="submit"><i class="fa fa-save"></i> Save Changes</button>
            <button class="btn btn-secondary btn-sm ms-2" type="button" id="cancelProfileEdit"><i class="fa fa-times"></i> Cancel</button>
          </form>
        </div>
      </div>
      <!-- Mark Attendance Section -->
      <div id="attendance-content">
        <div class="card p-4 mb-3 text-center">
          <div id="attendanceGreeting" class="mb-2 fs-5 fw-semibold text-primary"></div>
          <div id="attendanceInstructions" class="mb-3 text-secondary small">
            Click the button below to mark your attendance for today.<br>
            Your request will be sent to your teacher for approval.
          </div>
          <div class="row justify-content-center mb-3">
            <div class="col-md-5 col-12">
              <table class="table table-sm mb-0 border">
                <tbody>
                  <tr>
                    <th>Status</th>
                    <td><?= $status_badge ?></td>
                  </tr>
                  <tr>
                    <th>Date</th>
                    <td id="attendanceDate">--</td>
                  </tr>
                  <tr>
                    <th>Day</th>
                    <td id="attendanceDay">--</td>
                  </tr>
                  <tr>
                    <th>Time</th>
                    <td id="attendanceTime">--</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <form id="attendanceForm" action="../backend-php/mark_attendance.php" method="POST">
            <input type="hidden" name="latitude" id="latitude">
            <input type="hidden" name="longitude" id="longitude">
            <input type="text" name="reason" placeholder="Reason (optional)" class="form-control mb-2" />
            <button class="btn btn-success px-4 py-2 fs-6" type="submit" <?= $btn_disabled ?>>
              <i class="fa fa-calendar-check"></i> Mark Attendance
            </button>
          </form>
          <div id="attendanceMessage" class="mt-2"></div>
        </div>
      </div>
      <!-- Attendance History Section -->
      <div id="history-content">
        <div class="card p-4 mb-3">
          <h5 class="mb-0 mb-3">Here Your Attendance History</h5>
          <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Date</th>
                  <th>Mark Time</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody id="attendanceHistory">
              <?php if (count($attendance_history) == 0): ?>
                <tr><td colspan="3">No attendance history found.</td></tr>
              <?php else: foreach($attendance_history as $row): ?>
                <tr>
                  <td><?= htmlspecialchars($row['date']) ?></td>
                  <td><?= htmlspecialchars($row['mark_time']) ?></td>
                  <td>
                    <?php if ($row['status']=="approved"): ?>
                      <span class="badge bg-success">Approved</span>
                    <?php elseif ($row['status']=="pending"): ?>
                      <span class="badge bg-warning text-dark">Pending</span>
                    <?php elseif ($row['status']=="rejected"): ?>
                      <span class="badge bg-danger">Rejected</span>
                    <?php else: ?>
                      <span class="badge bg-secondary"><?= htmlspecialchars($row['status']) ?></span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <!-- Notifications Section -->
      <div id="notifications-content">
        <div class="card p-4 mb-3">
          <h5 class="mb-3"><i class="fa fa-bell text-warning"></i> Notifications</h5>
          <ul class="mb-0 small" id="notifications">
          <?php if (count($notifications) == 0): ?>
            <li>No new notifications.</li>
          <?php else: ?>
            <?php foreach($notifications as $noti): ?>
              <li>
                <?= htmlspecialchars($noti['message']) ?><br>
                <small class="text-muted"><?= htmlspecialchars($noti['created_at']) ?></small>
              </li>
            <?php endforeach; ?>
          <?php endif; ?>
          </ul>
        </div>
      </div>
      <!-- Feedback Section -->
      <div id="feedback-content">
        <div class="card p-4 mb-3">
          <h5 class="mb-3"><i class="fa fa-star text-warning"></i> Feedback</h5>
          <form>
            <textarea class="form-control mb-2" rows="2" placeholder="Your feedback..."></textarea>
            <button type="submit" class="btn btn-primary btn-sm">Submit</button>
          </form>
        </div>
      </div>
    </div>
  </div>
  <script>
    let attendanceClockInterval;
    function updateAttendanceClock() {
      const now = new Date();
      const days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
      document.getElementById("attendanceDate").textContent = now.toLocaleDateString('en-CA');
      document.getElementById("attendanceDay").textContent = days[now.getDay()];
      document.getElementById("attendanceTime").textContent = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }
    function startAttendanceClock() {
      updateAttendanceClock();
      attendanceClockInterval = setInterval(updateAttendanceClock, 1000);
    }
    function stopAttendanceClock() {
      if (attendanceClockInterval) clearInterval(attendanceClockInterval);
    }
    // Sidebar tab switching with live clock logic
    document.querySelectorAll('.sidebar .nav-link[data-content]').forEach(function(link) {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelectorAll('.sidebar .nav-link[data-content]').forEach(function(l) {
          l.classList.remove('active');
        });
        link.classList.add('active');
        document.querySelectorAll('.main-content > div').forEach(function(panel) {
          panel.style.display = 'none';
        });
        const tab = link.getAttribute('data-content');
        document.getElementById(tab + '-content').style.display = 'block';
        if (tab === "attendance") startAttendanceClock();
        else stopAttendanceClock();
      });
    });
    if (document.getElementById("attendance-content").style.display === "block") {
      startAttendanceClock();
    }
    // Profile edit mode
    document.getElementById("editProfileBtn").onclick = function() {
      // Fill the edit form with current values
      document.getElementById("editName").value    = document.getElementById("profileNameVal").textContent;
      document.getElementById("editEmail").value   = document.getElementById("profileEmailVal").textContent;
      document.getElementById("editContact").value = document.getElementById("profileContactVal").textContent;
      document.getElementById("editDOB").value     = document.getElementById("profileDOBVal").textContent;
      document.getElementById("editTrade").value   = document.getElementById("profileTradeVal").textContent;
      document.getElementById("editSession").value = document.getElementById("profileSessionVal").textContent;
      document.getElementById("profileReadonly").style.display = "none";
      document.getElementById("editProfileForm").style.display = "";
    };
    document.getElementById("cancelProfileEdit").onclick = function() {
      document.getElementById("profileReadonly").style.display = "";
      document.getElementById("editProfileForm").style.display = "none";
    };
    // AJAX Profile Form Submission
    document.getElementById("editProfileForm").onsubmit = async function(e) {
      e.preventDefault();
      document.getElementById('profileAlert').innerHTML = '';
      const form = e.target;
      const formData = new FormData(form);
      try {
        const res = await fetch(form.action, {
          method: "POST",
          body: formData,
          credentials: "same-origin"
        });
        const data = await res.json();
        if(data.success) {
          // Update all fields visually
          document.getElementById("profileNameVal").textContent = data.data.name;
          document.getElementById("profileEmailVal").textContent = data.data.email;
          document.getElementById("profileContactVal").textContent = data.data.contact_number;
          document.getElementById("profileDOBVal").textContent = data.data.date_of_birth;
          document.getElementById("profileTradeVal").textContent = data.data.trade;
          document.getElementById("profileSessionVal").textContent = data.data.session;
          document.getElementById("profileNameView").textContent = data.data.name;
          document.getElementById("profileTradeView").textContent = "Trade: " + data.data.trade;
          document.getElementById("profileSessionView").textContent = "Session: " + data.data.session;
          document.getElementById("sidebarUserEmail").textContent = data.data.email;
          document.getElementById("welcomeName").textContent = data.data.name;
          document.getElementById("profileReadonly").style.display = "";
          document.getElementById("editProfileForm").style.display = "none";
          // Show success alert
          document.getElementById('profileAlert').innerHTML =
            '<div class="alert alert-success alert-dismissible fade show" role="alert">' +
            '<i class="fa fa-check-circle"></i> ' + data.message +
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
            '</div>';
        } else {
          document.getElementById('profileAlert').innerHTML =
            '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
            '<i class="fa fa-exclamation-triangle"></i> ' + data.message +
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
            '</div>';
        }
      } catch(err) {
        document.getElementById('profileAlert').innerHTML =
          '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
          '<i class="fa fa-exclamation-triangle"></i> Network error. Please try again.' +
          '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
          '</div>';
      }
      // Auto-dismiss after 4s
      setTimeout(function() {
        let alert = document.querySelector('#profileAlert .alert-dismissible');
        if(alert && alert.classList.contains('show')) {
          let closeBtn = alert.querySelector('.btn-close');
          if(closeBtn) closeBtn.click();
        }
      }, 2000);
    };
    // AJAX Attendance Form Submission (unchanged)
    document.getElementById("attendanceForm").onsubmit = function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      fetch(this.action, {
        method: "POST",
        body: formData,
        credentials: "same-origin"
      })
      .then(response => response.text())
      .then(data => {
        if (data.includes("success")) {
          document.getElementById("attendanceMessage").innerHTML = '<div class="alert alert-success">Attendance marked! Status: <b>Pending</b>. Awaiting teacher approval.</div>';
          // Update badge/status
          const statusBadge = document.getElementById("attendanceStatus");
          statusBadge.textContent = "Pending";
          statusBadge.className = "badge bg-warning text-dark";
          this.reset();
          document.querySelector("#attendanceForm button[type=submit]").disabled = true;
        } else if (data.includes("already_marked")) {
          document.getElementById("attendanceMessage").innerHTML = '<div class="alert alert-warning">You have already marked attendance for today.</div>';
          const statusBadge = document.getElementById("attendanceStatus");
          statusBadge.textContent = "Pending";
          statusBadge.className = "badge bg-warning text-dark";
          document.querySelector("#attendanceForm button[type=submit]").disabled = true;
        } else if (data.includes("no_teacher_found")) {
          document.getElementById("attendanceMessage").innerHTML = '<div class="alert alert-danger">No teacher found for your trade and session.</div>';
        } else {
          document.getElementById("attendanceMessage").innerHTML = '<div class="alert alert-danger">Could not mark attendance. Maybe already marked or server error.</div>';
        }
      })
      .catch(err => {
        document.getElementById("attendanceMessage").innerHTML = '<div class="alert alert-danger">Network error. Try again.</div>';
      });
    };
    // AJAX Profile Photo Upload
    document.getElementById('photoUploadForm').onsubmit = async function(e) {
      e.preventDefault();
      let form = this;
      let formData = new FormData(form);
      try {
        let res = await fetch('../backend-php/update_profile_photo.php', {
          method: 'POST',
          body: formData,
          credentials: 'same-origin'
        });
        let data = await res.json();
        if (data.success) {
          // Update photo everywhere
          let newSrc = '/CollegeAttendanceSystem/frontend/images/' + data.img + '?t=' + Date.now();
          document.getElementById('profilePhoto').src = newSrc;
          if(document.getElementById('sidebarProfilePhoto'))
            document.getElementById('sidebarProfilePhoto').src = newSrc;
          alert(data.message);
        } else {
          alert(data.message || "Upload failed.");
        }
      } catch(err) {
        alert("Network error. Try again.");
      }
    };
    document.getElementById('profilePhotoInput').onchange = function() {
      document.getElementById('photoUploadForm').dispatchEvent(new Event('submit', {cancelable: true, bubbles: true}));
    };


  // Set location hidden fields for attendance (runs when page loads)
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(function(position) {
      document.getElementById('latitude').value = position.coords.latitude;
      document.getElementById('longitude').value = position.coords.longitude;
    }, function(error) {
      // If user denies or error, leave blank
      document.getElementById('latitude').value = '';
      document.getElementById('longitude').value = '';
    });
  }
  </script>
</body>
</html>