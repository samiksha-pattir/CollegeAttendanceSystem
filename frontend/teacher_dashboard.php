<?php
session_start();
include '../backend-php/db.php';
date_default_timezone_set('Asia/Kolkata');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.php?error=Unauthorized access.");
    exit();
}

$teacher_id = $_SESSION['user_id'];

// Fetch teacher details
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Image logic
$img = $user['img'] ?? '';
if (!$img || $img == 'default-avatar.png') {
    $teacher_photo = '/CollegeAttendanceSystem/frontend/images/default-avatar.png';
} else {
    $teacher_photo = '/CollegeAttendanceSystem/frontend/images/' . $img;
}
$total_students = isset($user['total_students']) ? (int)$user['total_students'] : 0;

// Fetch all students for this teacher's trade & session
$sql = "SELECT id, name FROM users WHERE role = 'student' AND LOWER(TRIM(trade)) = ? AND LOWER(TRIM(session)) = ?";
$stmt = $conn->prepare($sql);
$trade = strtolower(trim($user['trade']));
$session = strtolower(trim($user['session']));
$stmt->bind_param("ss", $trade, $session);
$stmt->execute();
$students_result = $stmt->get_result();
$student_ids = [];
while ($row = $students_result->fetch_assoc()) {
    $student_ids[] = $row['id'];
}
$stmt->close();

// Fetch attendance history for this teacher's students
$attendance_history = [];
if (count($student_ids) > 0) {
    $placeholders = implode(',', array_fill(0, count($student_ids), '?'));
    $types = str_repeat('i', count($student_ids));
    $sql = "SELECT ar.*, u.name as student_name FROM attendance_requests ar
            INNER JOIN users u ON ar.student_id = u.id
            WHERE ar.teacher_id = ? AND ar.student_id IN ($placeholders)
            ORDER BY ar.date DESC";
    $stmt = $conn->prepare($sql);
    $params = array_merge([$teacher_id], $student_ids);
    $stmt->bind_param('i' . $types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $attendance_history[] = $row;
    }
    $stmt->close();
}

// Fetch pending attendance requests
$pending_requests = [];
if (count($student_ids) > 0) {
    $sql = "SELECT ar.*, u.name as student_name FROM attendance_requests ar
        INNER JOIN users u ON ar.student_id = u.id
        WHERE ar.teacher_id = ? AND ar.status IN ('pending', 'approved', 'rejected') AND ar.student_id IN ($placeholders)
        ORDER BY ar.date DESC";
    $stmt = $conn->prepare($sql);
    $params = array_merge([$teacher_id], $student_ids);
    $stmt->bind_param('i' . $types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $pending_requests[] = $row;
    }
    $stmt->close();
}

// Fetch teacher's own attendance history
$my_attendance = [];
$sql = "SELECT * FROM attendance_requests WHERE student_id = ? ORDER BY date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $my_attendance[] = $row;
}
$stmt->close();

// Today's attendance summary for overview
$today = date('Y-m-d');
$present = 0; $pending = 0;
foreach ($attendance_history as $row) {
    if ($row['date'] === $today && $row['status'] === 'approved') $present++;
    if ($row['date'] === $today && $row['status'] === 'pending') $pending++;
}
$absent = $total_students - $present;
if ($absent < 0) $absent = 0;

// Fetch notifications
$notifications = [];
$sql = "SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}
$stmt->close();

// Check if teacher has marked attendance today
$today_attendance_status = null;
$sql = "SELECT status FROM attendance_requests WHERE student_id = ? AND date = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $teacher_id, $today);
$stmt->execute();
$stmt->bind_result($status_today);
if ($stmt->fetch()) {
    $today_attendance_status = $status_today;
}
$stmt->close();

if ($today_attendance_status === "pending") {
    $my_status_badge = '<span id="myAttendanceStatus" class="badge bg-warning text-dark">Pending</span>';
    $my_btn_disabled = 'disabled';
} elseif ($today_attendance_status === "approved") {
    $my_status_badge = '<span id="myAttendanceStatus" class="badge bg-success">Approved</span>';
    $my_btn_disabled = 'disabled';
} elseif ($today_attendance_status === "rejected") {
    $my_status_badge = '<span id="myAttendanceStatus" class="badge bg-danger">Rejected</span>';
    $my_btn_disabled = '';
} else {
    $my_status_badge = '<span id="myAttendanceStatus" class="badge bg-warning text-dark">Not Marked</span>';
    $my_btn_disabled = '';
}

$current_day = date('l');
$current_date = date('Y-m-d');

// Get success/error from URL
$success_msg = isset($_GET['success']) ? $_GET['success'] : '';
$error_msg = isset($_GET['error']) ? $_GET['error'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Teacher Dashboard | Attendance System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- sweetalert for alerts -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/dashboard.css">
  <style>
    #profile-content, #pending-content, #history-content, #notifications-content, #myattendance-content, #overview-content { display: none; }
    #profile-content { display: block; }
    .sidebar-profile {
        text-align: center;
        padding: 20px 0;
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
    .alert-center { text-align: center; }
    .dashboard-overview-card .card { min-height: 140px; }
  </style>
</head>
<body>
  <div class="dashboard-wrapper d-flex">
    <!-- Sidebar -->
    <nav class="sidebar flex-shrink-0 border-end">
      <div class="sidebar-profile">
        <img src="<?= htmlspecialchars($teacher_photo) ?>" id="sidebarProfilePic" alt="Profile Photo">
        <div class="sidebar-email" id="sidebarUserEmail"><?= htmlspecialchars($user['email']) ?></div>
      </div>
      <ul class="nav flex-column mt-3">
        <li class="nav-item"><a class="nav-link active" data-content="profile" href="#"><i class="fa fa-user"></i> Profile</a></li>
        <li class="nav-item"><a class="nav-link" data-content="pending" href="#"><i class="fa fa-user-check"></i> Pending Requests</a></li>
        <li class="nav-item"><a class="nav-link" data-content="history" href="#"><i class="fa fa-history"></i> Attendance History</a></li>
        <li class="nav-item"><a class="nav-link" data-content="myattendance" href="#"><i class="fa fa-clock"></i> My Attendance</a></li>
        <li class="nav-item"><a class="nav-link" data-content="overview" href="#"><i class="fa fa-chart-bar"></i> Overview</a></li>
        <li class="nav-item"><a class="nav-link" data-content="notifications" href="#"><i class="fa fa-bell"></i> Notifications</a></li>
        <li class="nav-item mt-auto"><a class="nav-link text-danger" href="login.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
      </ul>
    </nav>
    <!-- Main Content -->
    <div class="main-content flex-grow-1">
      <div id="welcomeMsg" class="alert alert-primary alert-dismissible fade show mt-2" role="alert">
        👋 Welcome, <b><?= htmlspecialchars($user['name']) ?></b>!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php if ($success_msg): ?>
        <div class="alert alert-success alert-dismissible fade show mt-2" id="autoHideMainAlert">
          <i class="fa fa-check-circle"></i> <?= htmlspecialchars($success_msg) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php elseif ($error_msg): ?>
        <div class="alert alert-danger alert-dismissible fade show mt-2" id="autoHideMainAlert">
          <i class="fa fa-exclamation-triangle"></i> <?= htmlspecialchars($error_msg) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
      <div id="profileAlert"></div>
      <!-- Profile Section -->
      <div id="profile-content">
        <div class="card p-4 mb-3">
          <div class="d-flex align-items-center mb-4">
            <div class="profile-photo-edit me-3 position-relative">
              <img src="<?= htmlspecialchars($teacher_photo) ?>" id="profilePhoto" width="90" height="90" class="border border-3 shadow-sm" alt="Profile Photo">
              <form id="photoUploadForm" enctype="multipart/form-data" method="POST" class="mt-2">
                <label class="btn btn-outline-secondary btn-sm mb-0" for="profilePhotoInput"><i class="fa fa-camera"></i> Change Photo</label>
                <input type="file" name="profile_photo" id="profilePhotoInput" class="custom-file-input" accept="image/*" style="display:none;">
                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
              </form>
            </div>
            <div>
              <h5 class="mb-1" id="profileNameView"><?= htmlspecialchars($user['name']) ?></h5>
              <div class="text-muted small" id="profileEmailView"><?= htmlspecialchars($user['email']) ?></div>
            </div>
          </div>
          <!-- Profile Display/Edit -->
          <div id="profileReadonly">
            <div class="row g-3 mb-2">
              <div class="col-md-6"><b>Name:</b> <span id="profileNameVal"><?= htmlspecialchars($user['name']) ?></span></div>
              <div class="col-md-6"><b>Email:</b> <span id="profileEmailVal"><?= htmlspecialchars($user['email']) ?></span></div>
              <div class="col-md-6"><b>Contact Number:</b> <span id="profileContactVal"><?= htmlspecialchars($user['contact_number']) ?></span></div>
              <div class="col-md-6"><b>Date of Birth:</b> <span id="profileDOBVal"><?= htmlspecialchars($user['date_of_birth']) ?></span></div>
              <div class="col-md-6"><b>Gender:</b> <span id="profileGenderVal"><?= htmlspecialchars($user['gender']) ?></span></div>
              <div class="col-md-6"><b>Role:</b> <span id="profileRoleVal"><?= htmlspecialchars($user['role']) ?></span></div>
              <div class="col-md-6"><b>Trade:</b> <span id="profileTradeVal"><?= htmlspecialchars($user['trade']) ?></span></div>
              <div class="col-md-6"><b>Session:</b> <span id="profileSessionVal"><?= htmlspecialchars($user['session']) ?></span></div>
              <div class="col-md-6"><b>Registration Date:</b> <span id="profileRegDateVal"><?= htmlspecialchars($user['created_at']) ?></span></div>
              <div class="col-md-6"><b>Total Students:</b> <span id="profileTotalStudentsVal"><?= htmlspecialchars($user['total_students']) ?></span></div>
            </div>
            <button class="btn btn-primary btn-sm mt-2" id="editProfileBtn">Edit Profile</button>
          </div>
          <form id="profileEditForm" action="../backend-php/update_profile.php" method="post" style="display:none;">
            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
            <div class="row g-3 mb-2">
              <div class="col-md-6">
                <label class="form-label">Name:</label>
                <input type="text" name="name" class="form-control form-control-sm" value="<?= htmlspecialchars($user['name']) ?>" required>
              </div>
              <div class="col-md-6" id="profileTotalStudentsBox" style="display:<?= ($user['role']=="teacher"?"block":"none") ?>;">
                <label class="form-label">Total Students</label>
                <input type="number" name="total_students" class="form-control form-control-sm"
                value="<?= htmlspecialchars($user['total_students']) ?>" min="1" <?= ($user['role']=="teacher"?"required":"readonly") ?>>
              </div>
              <div class="col-md-6">
                <label class="form-label">Email:</label>
                <input type="email" name="email" class="form-control form-control-sm" value="<?= htmlspecialchars($user['email']) ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Contact Number:</label>
                <input type="text" name="contact_number" class="form-control form-control-sm" value="<?= htmlspecialchars($user['contact_number']) ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Date of Birth:</label>
                <input type="date" name="date_of_birth" class="form-control form-control-sm" value="<?= htmlspecialchars($user['date_of_birth']) ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Gender:</label>
                <select name="gender" class="form-control form-control-sm">
                  <option value="Male" <?= $user['gender']=="Male"?"selected":"" ?>>Male</option>
                  <option value="Female" <?= $user['gender']=="Female"?"selected":"" ?>>Female</option>
                  <option value="Other" <?= $user['gender']=="Other"?"selected":"" ?>>Other</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Trade:</label>
                <input type="text" name="trade" class="form-control form-control-sm" value="<?= htmlspecialchars($user['trade']) ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Session:</label>
                <input type="text" name="session" class="form-control form-control-sm" value="<?= htmlspecialchars($user['session']) ?>" required>
              </div>
            </div>
            <button type="submit" class="btn btn-success btn-sm mt-2">Save Changes</button>
            <button type="button" class="btn btn-secondary btn-sm mt-2" id="cancelEditProfileBtn">Cancel</button>
          </form>
        </div>
      </div>
      <!-- Pending Requests Section -->
      <div id="pending-content">
        <div class="card p-4 mb-3">
          <h5 class="mb-3"><i class="fa fa-user-check"></i> Pending Attendance Requests</h5>
          <?php if (count($pending_requests) > 0): ?>
            <div class="table-responsive">
            <table class="table table-bordered align-middle" id="pendingTable">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>Student Name</th>
                  <th>Date</th>
                  <th>Time</th>
                  <!-- <th>Latitude</th>       
                  <th>Longitude</th>        for location just for ref--> 
                  <th>Location</th> 
                  <th>Reason</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($pending_requests as $i => $req): ?>
                <tr>
                  <td><?= $i+1 ?></td>
                  <td class="student_name"><?= htmlspecialchars($req['student_name']) ?></td>
                  <td class="req_date"><?= htmlspecialchars($req['date']) ?></td>
                  <td><?= htmlspecialchars($req['mark_time']) ?></td>
                  <td>
                    <?php if ($req['latitude'] && $req['longitude']): ?>
                      <a href="https://maps.google.com/?q=<?= $req['latitude'] ?>,<?= $req['longitude'] ?>" target="_blank" title="<?= htmlspecialchars($req['latitude']) ?>,<?= htmlspecialchars($req['longitude']) ?>"><h2 class="fas fa-map-marker-alt text-primary me-3"></h2></a>
                    <?php else: ?>
                      N/A
                    <?php endif; ?>
                  </td>
                  <!-- <td><?= htmlspecialchars($req['latitude']) ?></td>    
                  <td><?= htmlspecialchars($req['longitude']) ?></td>    -->

                  <td><?= htmlspecialchars($req['reason']) ?></td>
                  <td class="req_status"><span class="badge bg-warning text-dark"><?= htmlspecialchars($req['status']) ?></span></td>
                  <td>
                      <?php if ($req['status'] == 'pending'): ?>
                      <form class="action-form" data-action="approve" method="post" action="../backend-php/approve_attendance.php" style="display:inline-block;">
                        <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="redirect_tab" value="pending">
                        <input type="text" name="remarks" placeholder="Remark (optional)" class="form-control form-control-sm mb-1">
                        <button type="submit" class="btn btn-success btn-sm">Approve</button>
                      </form>

                      <form class="action-form" data-action="reject" method="post" action="../backend-php/approve_attendance.php" style="display:inline-block;">
                        <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="redirect_tab" value="pending">
                        <input type="text" name="remarks" placeholder="Reason (optional)" class="form-control form-control-sm mb-1">
                        <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                      </form>
                      <?php else: ?>
                          <form class="action-form" data-action="undo" method="post" action="../backend-php/approve_attendance.php" style="display:inline-block;">                              <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                              <input type="hidden" name="action" value="undo">
                              <input type="hidden" name="redirect_tab" value="pending">
                              <button type="submit" class="btn btn-secondary btn-sm">Undo</button>
                          </form>
                      <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            </div>
          <?php else: ?>
            <div class="alert alert-info alert-center">
                <i class="fa fa-info-circle"></i> No pending attendance requests for your class.
            </div>
          <?php endif; ?>
        </div>
      </div>
      <!-- Attendance History Section -->
      <div id="history-content">
        <div class="card p-4 mb-3">
          <h5 class="mb-3"><i class="fa fa-history"></i> Attendance History (Your Students)</h5>
          <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0" id="historyTable">
              <thead class="table-light">
                <tr>
                  <th>Date</th>
                  <th>Student</th>
                  <th>Mark Time</th>
                  <th>Status</th>
                  <th>Reason</th>
                  <th>Remarks</th>
                  <!-- <th>Latitude</th>    
                  <th>Longitude</th>      -->
                  <!-- <th>Location</th> -->


                </tr>
              </thead>
              <tbody>
              <?php if (count($attendance_history) == 0): ?>
                <tr>
                  <td colspan="6" class="text-center text-muted">
                    <i class="fa fa-info-circle"></i> No attendance history found for your students.
                  </td>
                </tr>
              <?php else: foreach($attendance_history as $row): ?>
                <tr>
                  <td class="history_date"><?= htmlspecialchars($row['date']) ?></td>
                  <td class="history_student"><?= htmlspecialchars($row['student_name']) ?></td>
                  <td><?= htmlspecialchars($row['mark_time']) ?></td>
                  <td class="history_status">
                    <?php if ($row['status']=="approved"): ?>
                      <span class="badge bg-success">Approved</span>
                    <?php elseif ($row['status']=="pending"): ?>
                      <span class="badge bg-warning text-dark">Pending</span>
                    <?php elseif ($row['status']=="rejected"): ?>
                      <span class="badge bg-danger">Rejected</span>
                    <?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars($row['reason']) ?></td>
                  <td><?= htmlspecialchars($row['remarks']) ?></td>
                  <!-- <td><?= htmlspecialchars($row['latitude']) ?></td>   
                  <td><?= htmlspecialchars($row['longitude']) ?></td>   -->
              
                </tr>
              <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <!-- My Attendance Section -->
      <div id="myattendance-content">
        <div class="card p-4 mb-3">
          <h5 class="mb-3"><i class="fa fa-clock"></i> My Attendance</h5>
          <div class="row justify-content-center mb-3">
            <div class="col-md-5 col-12">
              <table class="table table-sm mb-0 border">
                <tbody>
                  <tr>
                    <th>Status</th>
                    <td><?= $my_status_badge ?></td>
                  </tr>
                  <tr>
                    <th>Date</th>
                    <td id="myAttendanceDate">--</td>
                  </tr>
                  <tr>
                    <th>Day</th>
                    <td id="myAttendanceDay">--</td>
                  </tr>
                  <tr>
                    <th>Time</th>
                    <td id="myAttendanceTime">--</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <form id="myAttendanceForm" action="../backend-php/mark_attendance.php" method="POST">
            <input type="hidden" name="teacher_self" value="1">
            <input type="text" name="reason" placeholder="Reason (optional)" class="form-control mb-2" />
            <button class="btn btn-success px-4 py-2 fs-6" type="submit" <?= $my_btn_disabled ?>>
              <i class="fa fa-calendar-check"></i> Mark Attendance
            </button>
          </form>
          <div id="myAttendanceMessage" class="mt-2"></div>
          <hr>
          <h6 class="mb-2">My Attendance History</h6>
          <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Date</th>
                  <th>Mark Time</th>
                  <th>Status</th>
                  <th>Reason</th>
                </tr>
              </thead>
              <tbody>
              <?php if (count($my_attendance) == 0): ?>
                <tr>
                  <td colspan="4" class="text-center text-muted">
                    <i class="fa fa-info-circle"></i> No attendance history found.
                  </td>
                </tr>
              <?php else: foreach($my_attendance as $row): ?>
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
                    <?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars($row['reason']) ?></td>
                </tr>
              <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <!-- Overview Section -->
      <div id="overview-content">
        <div class="card p-4 mb-3 dashboard-overview-card">
          <div class="mb-2">
            <h5 class="mb-1"><i class="fa fa-chart-bar"></i> Attendance Overview (Today)</h5>
            <div class="small text-secondary"><?= $current_day ?>, <?= $current_date ?></div>
            <div class="mb-2">Here is your today’s overview:</div>
          </div>
          <div class="row text-center">
            <div class="col-md-4">
              <div class="card bg-success bg-opacity-10 mb-2 shadow-sm border-0">
                <div class="card-body">
                  <i class="fa fa-user-check fa-2x text-success mb-2"></i>
                  <h6 class="mb-1">Present</h6>
                  <span class="display-5"><?= $present ?></span>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="card bg-warning bg-opacity-10 mb-2 shadow-sm border-0">
                <div class="card-body">
                  <i class="fa fa-clock fa-2x text-warning mb-2"></i>
                  <h6 class="mb-1">Pending</h6>
                  <span class="display-5"><?= $pending ?></span>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="card bg-danger bg-opacity-10 mb-2 shadow-sm border-0">
                <div class="card-body">
                  <i class="fa fa-user-times fa-2x text-danger mb-2"></i>
                  <h6 class="mb-1">Absent</h6>
                  <span class="display-5"><?= $absent ?></span>
                </div>
              </div>
            </div>
          </div>
          <div class="mt-2 text-muted small">Total students: <?= $total_students ?> | Marked present: <?= $present ?> | Pending: <?= $pending ?> | Absent: <?= $absent ?></div>
        </div>
      </div>
      <!-- Notifications Section -->
      <div id="notifications-content">
        <div class="card p-4 mb-3">
          <h5 class="mb-3"><i class="fa fa-bell text-warning"></i> Notifications</h5>
          <ul class="mb-0 small" id="notifications">
          <?php if (count($notifications) == 0): ?>
            <li class="text-muted alert-center">
              <i class="fa fa-info-circle"></i> No new notifications.
            </li>
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
    </div>
  </div>
  <!-- JS Section -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Sidebar nav tab switching
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
          document.getElementById(link.getAttribute('data-content')+'-content').style.display = 'block';
          if (link.getAttribute('data-content') === 'myattendance') startMyAttendanceClock();
          else stopMyAttendanceClock();
        });
      });
      // Tab from URL
      const urlParams = new URLSearchParams(window.location.search);
      const tabParam = urlParams.get('tab');
      if(tabParam) {
        // Remove 'active' from all nav links
        document.querySelectorAll('.sidebar .nav-link[data-content]').forEach(function(link) {
          link.classList.remove('active');
        });

        // Show only the selected tab's content
        document.querySelectorAll('.main-content > div').forEach(function(panel) {
          panel.style.display = 'none';
        });

        // Activate the right nav link and panel
        let found = false;
        document.querySelectorAll('.sidebar .nav-link[data-content]').forEach(function(link) {
          if(link.getAttribute('data-content') === tabParam) {
            link.classList.add('active');
            let panel = document.getElementById(tabParam + '-content');
            if(panel) panel.style.display = 'block';
            found = true;
          }
        });
        // If not found, default to profile tab
        if(!found) {
          document.querySelector('.sidebar .nav-link[data-content="profile"]').classList.add('active');
          document.getElementById('profile-content').style.display = 'block';
        }
      }

      // Profile edit mode
      if(document.getElementById('editProfileBtn')) {
        document.getElementById('editProfileBtn').addEventListener('click', function() {
          document.getElementById('profileReadonly').style.display = 'none';
          document.getElementById('profileEditForm').style.display = 'block';
        });
      }
      if(document.getElementById('cancelEditProfileBtn')) {
        document.getElementById('cancelEditProfileBtn').addEventListener('click', function() {
          document.getElementById('profileReadonly').style.display = 'block';
          document.getElementById('profileEditForm').style.display = 'none';
        });
      }
      // AJAX Profile Form Submission
      if(document.getElementById("profileEditForm")) {
        document.getElementById("profileEditForm").onsubmit = async function(e) {
          e.preventDefault();
          document.getElementById('profileAlert').innerHTML = '';
          const form = e.target;
          const formData = new FormData(form);
          try {
            const res = await fetch(form.action, { method: "POST", body: formData, credentials: "same-origin" });
            const data = await res.json();
            if(data.success) {
              document.getElementById("profileNameVal").textContent = data.data.name;
              document.getElementById("profileEmailVal").textContent = data.data.email;
              document.getElementById("profileContactVal").textContent = data.data.contact_number;
              document.getElementById("profileDOBVal").textContent = data.data.date_of_birth;
              document.getElementById("profileTradeVal").textContent = data.data.trade;
              document.getElementById("profileSessionVal").textContent = data.data.session;
              document.getElementById("profileNameView").textContent = data.data.name;
              document.getElementById("profileEmailView").textContent = data.data.email;
              document.getElementById("sidebarUserEmail").textContent = data.data.email;
              if(document.getElementById("profileTotalStudentsVal") && data.data.total_students) {
                document.getElementById("profileTotalStudentsVal").textContent = data.data.total_students;
              }
              document.getElementById("profileReadonly").style.display = "block";
              document.getElementById("profileEditForm").style.display = "none";
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
          setTimeout(function() {
            let alert = document.querySelector('#profileAlert .alert-dismissible');
            if(alert && alert.classList.contains('show')) {
              let closeBtn = alert.querySelector('.btn-close');
              if(closeBtn) closeBtn.click();
            }
          }, 2000);
        };
      }
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
            let newSrc = '/CollegeAttendanceSystem/frontend/images/' + data.img + '?t=' + Date.now();
            document.getElementById('profilePhoto').src = newSrc;
            if(document.getElementById('sidebarProfilePic'))
              document.getElementById('sidebarProfilePic').src = newSrc;
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
      // My Attendance Clock
      let myAttendanceClockInterval;
      function updateMyAttendanceClock() {
        const now = new Date();
        const days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        if(document.getElementById("myAttendanceDate"))
          document.getElementById("myAttendanceDate").textContent = now.toLocaleDateString('en-CA');
        if(document.getElementById("myAttendanceDay"))
          document.getElementById("myAttendanceDay").textContent = days[now.getDay()];
        if(document.getElementById("myAttendanceTime"))
          document.getElementById("myAttendanceTime").textContent = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
      }
      window.startMyAttendanceClock = function() {
        updateMyAttendanceClock();
        myAttendanceClockInterval = setInterval(updateMyAttendanceClock, 1000);
      }
      window.stopMyAttendanceClock = function() {
        if (myAttendanceClockInterval) clearInterval(myAttendanceClockInterval);
      }
      // My Attendance AJAX Form
      if(document.getElementById("myAttendanceForm")) {
        document.getElementById("myAttendanceForm").onsubmit = function(e) {
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
              document.getElementById("myAttendanceMessage").innerHTML = '<div class="alert alert-success">Attendance marked! Status: <b>Pending</b>. Awaiting admin approval.</div>';
              const statusBadge = document.getElementById("myAttendanceStatus");
              statusBadge.textContent = "Pending";
              statusBadge.className = "badge bg-warning text-dark";
              this.reset();
              document.querySelector("#myAttendanceForm button[type=submit]").disabled = true;
            } else if (data.includes("already_marked")) {
              document.getElementById("myAttendanceMessage").innerHTML = '<div class="alert alert-warning">You have already marked attendance for today.</div>';
              const statusBadge = document.getElementById("myAttendanceStatus");
              statusBadge.textContent = "Pending";
              statusBadge.className = "badge bg-warning text-dark";
              document.querySelector("#myAttendanceForm button[type=submit]").disabled = true;
            } else {
              document.getElementById("myAttendanceMessage").innerHTML = '<div class="alert alert-danger">Could not mark attendance. Maybe already marked or server error.</div>';
            }
          })
          .catch(err => {
            document.getElementById("myAttendanceMessage").innerHTML = '<div class="alert alert-danger">Network error. Try again.</div>';
          });
        };
      }
      // Auto-hide alerts after 3 seconds
      if(document.getElementById('autoHideMainAlert')) {
        setTimeout(function() {
          let alert = document.getElementById('autoHideMainAlert');
          if(alert && alert.classList.contains('show')) {
            let closeBtn = alert.querySelector('.btn-close');
            if(closeBtn) closeBtn.click();
          }
        }, 3000);
      }
    });
    // SweetAlert2 confirm for Approve/Reject/Undo
    document.querySelectorAll('.action-form').forEach(function(form) {
      form.addEventListener('submit', function(e) {
        e.preventDefault();
        let actionType = form.getAttribute('data-action');
        let msg = '';
        if(actionType === 'approve')
          msg = 'Are you sure you want to <b>APPROVE</b> this attendance?';
        else if(actionType === 'reject')
          msg = 'Are you sure you want to <b>REJECT</b> this attendance?';
        else
          msg = 'Are you sure you want to <b>UNDO</b> this action?';

        Swal.fire({
          title: 'Confirm Action',
          html: msg,
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Yes, Confirm',
          cancelButtonText: 'Cancel',
          focusCancel: true
        }).then((result) => {
          if (result.isConfirmed) {
            form.submit();
          }
        });
      });
    });


  </script>
</body>
</html>