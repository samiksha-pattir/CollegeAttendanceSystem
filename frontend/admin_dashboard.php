<?php
session_start();
require_once("../backend-php/db.php");
date_default_timezone_set('Asia/Kolkata');

// Ensure admin session is properly set
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $admin_query = mysqli_query($conn, "SELECT * FROM users WHERE role='admin' LIMIT 1");
    if ($admin_data = mysqli_fetch_assoc($admin_query)) {
        $_SESSION['user_id'] = $admin_data['id'];
        $_SESSION['role'] = 'admin';
        $_SESSION['name'] = $admin_data['name'];
        $_SESSION['email'] = $admin_data['email'];
    }
}

$today = date('Y-m-d');

// Dashboard counts
$result_students = mysqli_query($conn, "SELECT SUM(total_students) AS total FROM users");
$row_students = mysqli_fetch_assoc($result_students); $total_students = $row_students['total'];

$result_teachers = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role='teacher'");
$row_teachers = mysqli_fetch_assoc($result_teachers); $total_teachers = $row_teachers['total'];

$result_trades = mysqli_query($conn, "SELECT COUNT(DISTINCT trade) AS total FROM users WHERE role='teacher'");
$row_trades = mysqli_fetch_assoc($result_trades); $total_trades = $row_trades['total'];
// Students present today
$result_students_present = mysqli_query($conn, "
    SELECT COUNT(DISTINCT ar.student_id) AS total
    FROM attendance_requests ar
    JOIN users u ON ar.student_id = u.id
    WHERE u.role='student' AND ar.date='$today' AND ar.status='approved'
");
$row_students_present = mysqli_fetch_assoc($result_students_present);
$total_students_present_today = $row_students_present['total'];

$result_present = mysqli_query($conn, "
    SELECT COUNT(DISTINCT ar.teacher_id) AS total
    FROM attendance_requests ar
    JOIN users u ON ar.teacher_id = u.id
    WHERE u.role='teacher' AND ar.date='$today' AND ar.status='approved'
");
$row_present = mysqli_fetch_assoc($result_present); 
$total_present_today = $row_present['total'];
// All trades for report filter
$all_trades = [];
$trade_query = mysqli_query($conn, "SELECT DISTINCT trade FROM users WHERE trade IS NOT NULL AND trade != ''");
while($row = mysqli_fetch_assoc($trade_query)) $all_trades[] = $row['trade'];

// --- Students Table AJAX and Delete Handler ---
if (isset($_GET['students_table'])) {
    $name = $_GET['name'] ?? "";
    $trade = $_GET['trade'] ?? "";
    $session = $_GET['session'] ?? "";
    $where = ["role='student'"];
    if($name) $where[] = "(name LIKE '%$name%' OR email LIKE '%$name%' OR trade LIKE '%$name%')";
    if($trade) $where[] = "trade='".mysqli_real_escape_string($conn, $trade)."'";
    if($session) $where[] = "session='".mysqli_real_escape_string($conn, $session)."'";
    $where_sql = implode(" AND ", $where);
    $res = mysqli_query($conn, "SELECT * FROM users WHERE $where_sql ORDER BY id DESC");
    ?>
    <table class="table table-bordered table-striped align-middle">
      <thead class="table-light">
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Trade</th>
          <th>Session</th>
          <th>Contact</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if(mysqli_num_rows($res)): while($row = mysqli_fetch_assoc($res)): ?>
          <tr>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= htmlspecialchars($row['trade']) ?></td>
            <td><?= htmlspecialchars($row['session']) ?></td>
            <td><?= htmlspecialchars($row['contact_number']) ?></td>
            <td>
              <button class="btn btn-sm btn-danger" onclick="deleteStudent(<?= $row['id'] ?>)">
                <i class="bi bi-trash"></i>
              </button>
            </td>
          </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="6" class="text-center text-secondary">No students found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
    <?php
    exit;
}

// --- Teachers Table AJAX and Delete Handler ---
if (isset($_GET['teachers_table'])) {
    $name = $_GET['name'] ?? "";
    $trade = $_GET['trade'] ?? "";
    $session = $_GET['session'] ?? "";
    $where = ["role='teacher'"];
    if($name) $where[] = "(name LIKE '%$name%' OR email LIKE '%$name%' OR trade LIKE '%$name%')";
    if($trade) $where[] = "trade='".mysqli_real_escape_string($conn, $trade)."'";
    if($session) $where[] = "session='".mysqli_real_escape_string($conn, $session)."'";
    $where_sql = implode(" AND ", $where);
    $res = mysqli_query($conn, "SELECT * FROM users WHERE $where_sql ORDER BY id DESC");
    ?>
    <table class="table table-bordered table-striped align-middle">
      <thead class="table-light">
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Trade</th>
          <th>Session</th>
          <th>Contact</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if(mysqli_num_rows($res)): while($row = mysqli_fetch_assoc($res)): ?>
          <tr>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= htmlspecialchars($row['trade']) ?></td>
            <td><?= htmlspecialchars($row['session']) ?></td>
            <td><?= htmlspecialchars($row['contact_number']) ?></td>
            <td>
              <button class="btn btn-sm btn-danger" onclick="deleteTeacher(<?= $row['id'] ?>)">
                <i class="bi bi-trash"></i>
              </button>
            </td>
          </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="6" class="text-center text-secondary">No teachers found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
    <?php
    exit;
}
// --- Pending Teacher Attendance AJAX Handler (with REMARK & UNDO) ---
if (isset($_GET['pending_teachers_attendance'])) {
    date_default_timezone_set('Asia/Kolkata');
    $today = date('Y-m-d');
    $res = mysqli_query($conn, "
      SELECT ar.id, u.name, u.email, u.trade, ar.date, ar.reason, ar.status, ar.remarks 
      FROM attendance_requests ar 
      JOIN users u ON ar.teacher_id = u.id 
      INNER JOIN (
          SELECT teacher_id, MAX(id) as max_id
          FROM attendance_requests
          WHERE date='$today'
          GROUP BY teacher_id
      ) latest ON ar.id = latest.max_id
      WHERE ar.date='$today' AND ar.teacher_id IS NOT NULL 
      AND u.role='teacher'
      ORDER BY ar.id DESC
  ");
    ?>
    <div class="table-responsive px-0">
      <table class="table table-bordered table-striped align-middle" style="width:100%;">
        <thead class="table-primary">
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Trade</th>
            <th>Date</th>
            <th>Reason</th>
            <th>Status</th>
            <th>Remark</th>
            <th class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if(mysqli_num_rows($res)): while($row = mysqli_fetch_assoc($res)): ?>
            <tr>
              <td><?= htmlspecialchars($row['name']) ?></td>
              <td><?= htmlspecialchars($row['email']) ?></td>
              <td><?= htmlspecialchars($row['trade']) ?></td>
              <td><?= htmlspecialchars($row['date']) ?></td>
              <td><?= htmlspecialchars($row['reason']) ?></td>
              <td>
                <span class="badge 
                  <?= $row['status']=='pending' ? 'bg-warning' : ($row['status']=='approved' ? 'bg-success' : 'bg-danger') ?>">
                  <?= htmlspecialchars($row['status']) ?>
                </span>
              </td>
              <td>
                <input type="text" class="form-control form-control-sm" id="remark-<?= $row['id'] ?>"
                  value="<?= htmlspecialchars($row['remarks'] ?? '') ?>" placeholder="Remark (optional)">
              </td>
              <td class="text-center">
                <?php if($row['status']=='pending'){ ?>
                  <button class="btn btn-sm btn-success" onclick="approveTeacherAttendance(<?= $row['id'] ?>)">Approve</button>
                  <button class="btn btn-sm btn-danger" onclick="rejectTeacherAttendance(<?= $row['id'] ?>)">Reject</button>
                <?php } else { ?>
                  <button class="btn btn-sm btn-secondary" onclick="undoTeacherAttendance(<?= $row['id'] ?>)">Undo</button>
                <?php } ?>
              </td>
            </tr>
          <?php endwhile; else: ?>
            <tr>
              <td colspan="8" class="text-center text-secondary">No teacher attendance requests today.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <style>
     .content {
        margin-left: 250px;
        width: auto;
        min-width: 0;
      }
      @media (max-width: 991.98px) {
        .content {
          margin-left: 0;
        }
      }
      .table-responsive {
        overflow-x: auto;
      }
      .table {
        width: 100%;
        min-width: 600px;
      }
    </style>
    <?php
    exit;
}
    // --- Delete Student/Teacher Handler ---
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if ($_POST['action'] == 'delete' && isset($_POST['id'])) {
            $id = intval($_POST['id']);
            mysqli_query($conn, "DELETE FROM users WHERE id=$id");
            echo "deleted";
            exit;
        }
        if ($_POST['action'] == 'delete_teacher' && isset($_POST['id'])) {
            $id = intval($_POST['id']);
            mysqli_query($conn, "DELETE FROM users WHERE id=$id");
            echo "deleted";
            exit;
        }
    }

// --- Approve/Reject/Undo Teacher Attendance (with Remarks) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST['id'] ?? 0);
    $remark = mysqli_real_escape_string($conn, $_POST['remark'] ?? '');
    
    if ($_POST['action'] == 'approve_teacher_attendance') {
        // Get teacher details for notification
        $teacher_query = mysqli_query($conn, "
            SELECT ar.teacher_id, ar.date, u.name 
            FROM attendance_requests ar 
            JOIN users u ON ar.teacher_id = u.id 
            WHERE ar.id = $id
        ");
        $teacher_data = mysqli_fetch_assoc($teacher_query);
        
        mysqli_query($conn, "UPDATE attendance_requests SET status='approved', approval_time=NOW(), remarks='$remark' WHERE id=$id");
        
        // Send notification to teacher
        if ($teacher_data) {
            $notif_msg = "Your attendance request for " . $teacher_data['date'] . " has been approved by admin.";
            if ($remark) $notif_msg .= " Remark: " . $remark;
            $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())");
            $notif_stmt->bind_param("is", $teacher_data['teacher_id'], $notif_msg);
            $notif_stmt->execute();
            $notif_stmt->close();
        }
        
        echo "approved";
        exit;
    } elseif ($_POST['action'] == 'reject_teacher_attendance') {
        // Get teacher details for notification
        $teacher_query = mysqli_query($conn, "
            SELECT ar.teacher_id, ar.date, u.name 
            FROM attendance_requests ar 
            JOIN users u ON ar.teacher_id = u.id 
            WHERE ar.id = $id
        ");
        $teacher_data = mysqli_fetch_assoc($teacher_query);
        
        mysqli_query($conn, "UPDATE attendance_requests SET status='rejected', approval_time=NOW(), remarks='$remark' WHERE id=$id");
        
        // Send notification to teacher
        if ($teacher_data) {
            $notif_msg = "Your attendance request for " . $teacher_data['date'] . " has been rejected by admin.";
            if ($remark) $notif_msg .= " Remark: " . $remark;
            $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())");
            $notif_stmt->bind_param("is", $teacher_data['teacher_id'], $notif_msg);
            $notif_stmt->execute();
            $notif_stmt->close();
        }
        
        echo "rejected";
        exit;
    } elseif ($_POST['action'] == 'undo_teacher_attendance') {
        mysqli_query($conn, "UPDATE attendance_requests SET status='pending', approval_time=NULL, remarks='$remark' WHERE id=$id");
        echo "undone";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard | College Attendance System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
   <!-- sweetalert for alerts -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="css/admin-dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
  <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
  <style>
    .quick-action-btn { min-width: 170px; }
    .modal-header { background: #343a40; }
    .circular-card { width: 230px; height: 230px; background: linear-gradient(135deg, #6c757d 0%, #e3f4fb 100%); border-radius: 50%; box-shadow: 0 4px 24px rgba(13,110,253,0.12), 0 2px 16px rgba(0,0,0,0.06); padding: 28px 18px 12px 18px; margin-bottom: 8px; display: flex; flex-direction: column; align-items: center; justify-content: center; border: 4px solid #0d6efd22; transition: transform .19s, box-shadow .19s, border-color .18s; position: relative;}
    .circular-card:hover { transform: scale(1.07) translateY(-8px); box-shadow: 0 8px 36px rgba(13,110,253,0.22), 0 4px 32px rgba(0,0,0,0.11); border-color: #0d6efd; cursor: pointer;}
    .circular-badge { width: 74px; height: 74px; margin: 0 auto 10px auto; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.09); border: 3px solid #fff;}
    .circle-primary   { background: linear-gradient(135deg, #0d6efd 60%, #4bb4ff 100%); }
    .circle-success   { background: linear-gradient(135deg, #25c481 60%, #39d98a 100%); }
    .circle-danger    { background: linear-gradient(135deg, #fa5252 60%, #fc7b7b 100%); }
    .circular-card .badge { font-size: 1.02rem; border-radius:1rem; padding: 0.5em 1.2em; }
    #attendanceTableModalTable { border-radius: 1rem; overflow: hidden; background: #fff;}
    .fullscreen-iframe-wrapper { width: 100vw; height: 100vh; background: #f8fbff; display: flex; flex-direction: column; position: fixed; z-index: 1500; top: 0; left: 0; transition: all 0.3s;}
    .fullscreen-iframe-header { background: #343a40; color: #fff; padding: 1rem 2rem; display: flex; align-items: center; justify-content: space-between; font-size: 1.2rem;}
    .fullscreen-iframe-close { background: none; border: none; color: #fff; font-size: 2rem; line-height: 1; cursor: pointer;}
    .fullscreen-iframe-content { flex: 1; padding: 0; overflow: hidden;}
    #fullscreenAddStudentWrapper { display: none; }
    .modal-fullscreen.report-modal .modal-content { border-radius: 0; }
    .modal-fullscreen.report-modal .modal-header { background: #0dcaf0; }
    #reportPreviewTableWrap { max-height: 400px; overflow-y: auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 12px #0001; margin-top: 1.3rem;}
    #reportPreviewTable th, #reportPreviewTable td { font-size: 15px; vertical-align: middle; white-space: nowrap;}
    #reportPreviewTable th { position: sticky; top: 0; background: #f0f5ff;}
    .content {
        margin-left: 250px;
      }
      @media (max-width: 991.98px) {
        .content {
          margin-left: 0;
        }
      }

        @media (max-width: 767px) {
        .table-responsive table { font-size: 13px; }
        .table-responsive th, .table-responsive td { padding: .45rem; }
        .table-responsive th { white-space: nowrap; }
  }
    .table-responsive {
        overflow-x: auto;
      }
      .table {
        width: 100%;
        min-width: 500px;
      }


  </style>
</head>
<body>
  <!-- SIDEBAR & NAVBAR -->
  <div class="sidebar d-flex flex-column p-3 position-fixed" style="width:250px; z-index:1041; height:100%;" id="sidebarMenu">
    <a href="#" class="mb-3 mb-md-0 me-md-auto text-white text-decoration-none d-flex align-items-center">
      <span class="fs-4 fw-bold"><i class="bi bi-mortarboard-fill"></i>
      <span class="d-none d-md-inline">College Admin</span></span>
    </a>
    <hr>
    <ul class="nav nav-pills flex-column mb-auto">
      <li><a href="#" class="nav-link active" onclick="showTab('dashboard')"><i class="bi bi-speedometer2"></i> <span class="d-none d-md-inline">Dashboard</span></a></li>
      <li><a href="#" class="nav-link" onclick="showTab('students')"><i class="bi bi-people-fill"></i> <span class="d-none d-md-inline">Students</span></a></li>
      <li><a href="#" class="nav-link" onclick="showTab('teachers')"><i class="bi bi-person-badge"></i> <span class="d-none d-md-inline">Teachers</span></a></li>
      <li><a href="#" class="nav-link" onclick="showTab('teacher-attendance')"><i class="bi bi-person-check"></i> <span class="d-none d-md-inline">Teacher's Attendance</span></a></li>
      <li><a href="#" class="nav-link"><i class="bi bi-gear"></i> <span class="d-none d-md-inline">Settings</span></a></li>
    </ul>
    <hr>
    <div class="d-flex align-items-center">
      <img src="images/Naina_Nagpal.jpg" alt="admin" class="avatar-sm">
      <span class="d-none d-md-inline ms-2">Admin</span>
    </div>
  </div>
  <div class="overlay position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-25" style="display:none; z-index:1040;" id="sidebarOverlay"></div>
  
  <!-- MAIN CONTENT -->
  <div class="content" id="mainContent">
    <!-- NAVBAR -->
    <nav class="navbar navbar-expand navbar-light px-3 py-2">
      <button class="btn d-lg-none me-2" id="menuToggle"><i class="bi bi-list fs-3"></i></button>
      <a class="navbar-brand fw-bold d-none d-lg-inline" href="#">Dashboard</a>
      <div class="ms-auto dropdown">
        <a href="#" class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
          <img src="images/Naina_Nagpal.jpg" alt="admin" class="avatar-sm">
          <span class="d-none d-md-inline fw-semibold">Ms. Naina Nagpal</span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end mt-2" aria-labelledby="profileDropdown">
          <li>
            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#adminProfileModal" id="openAdminProfile">
              <i class="bi bi-person-circle me-2"></i>Edit Profile
            </a>
          </li>
          <li>
            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal" id="openChangePassword">
              <i class="bi bi-key me-2"></i>Change Password
            </a>
          </li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-danger" href="login.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
        </ul>
      </div>
    </nav>
    <div class="container-fluid mt-4">
      <!-- DASHBOARD TAB (default) -->
      <div id="tab-dashboard">
        <div class="row g-4">
          <!-- Dashboard Cards -->
          <div class="col-12 col-sm-6 col-lg-3" data-aos="fade-up">
            <div class="card shadow-sm p-3 d-flex flex-row align-items-center">
              <div class="overview-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-people"></i></div>
              <div>
                <div class="fs-5 fw-bold" id="counterStudents"><?php echo $total_students; ?></div>
                <div class="text-muted small">Total<b style="color:black;"> CITS </b>Students</div>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
            <div class="card shadow-sm p-3 d-flex flex-row align-items-center">
              <div class="overview-icon bg-success bg-opacity-10 text-success"><i class="bi bi-person-badge"></i></div>
              <div>
                <div class="fs-5 fw-bold" id="counterTeachers"><?php echo $total_teachers ?></div>
                <div class="text-muted small">Total Teachers</div>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
            <div class="card shadow-sm p-3 d-flex flex-row align-items-center">
              <div class="overview-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-journal"></i></div>
              <div>
                <div class="fs-5 fw-bold" id="counterTrades"><?php echo $total_trades ?></div>
                <div class="text-muted small">Total Trades</div>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
            <div class="card shadow-sm p-3 d-flex flex-row align-items-center">
              <div class="overview-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-calendar-check"></i></div>
              <div>
                <div class="fs-5 fw-bold" id="counterAttendance"><?php echo $total_students_present_today; ?></div>
                <div class="text-muted small">Student <b style="color:black;"> Present Today </b></div>
              </div>
            </div>
          </div>
        </div>
        <div class="row g-4 mt-1">
          <div class="col-lg-8" data-aos="fade-up">
            <div class="card shadow-sm p-4">
              <div class="d-flex align-items-center mb-3">
                <i class="bi bi-bar-chart-line fs-4 text-primary me-2"></i>
                <span class="fw-bold fs-5">Attendance Analytics</span>
              </div>
              <div class="mb-3">
                <label for="tradeSelect" class="form-label fw-bold">Select Trade:</label>
                <select id="tradeSelect" class="form-select" style="max-width: 300px;">
                  <option value="all">All Trades</option>
                </select>
              </div>
              <canvas id="attendanceChart" height="120"></canvas>
            </div>
          </div>
          <div class="col-lg-4" data-aos="fade-up" data-aos-delay="150">
            <div class="card shadow-sm p-4">
              <div class="fw-bold mb-3">
                <i class="bi bi-lightning-charge text-warning me-2"></i>Quick Actions
              </div>
              <div class="d-flex flex-wrap">
                <button class="quick-action-btn mb-2" data-bs-toggle="modal" data-bs-target="#attendanceTableModal">
                  <i class="bi bi-table"></i> Table View Attendance
                </button>
                <button class="quick-action-btn mb-2" id="openAddStudentFullscreen">
                  <i class="bi bi-person-plus me-2"></i>Add Student/Teacher
                </button>
                <button class="quick-action-btn mb-2" data-bs-toggle="modal" data-bs-target="#generateReportModal">
                  <i class="bi bi-file-earmark-bar-graph me-2"></i>Generate Report
                </button>
                <button class="quick-action-btn mb-2" data-bs-toggle="modal" data-bs-target="#todaysTradeUpdateModal">
                  <i class="bi bi-people-fill me-2"></i>Today's Trade Update
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- STUDENTS TAB -->
      <div id="tab-students" style="display:none;">
        <div class="card shadow p-3 mt-3">
          <h4 class="mb-3"><i class="bi bi-people-fill"></i> Students</h4>
          <div class="row mb-3">
            <div class="col-md-4">
              <input type="text" id="searchName" class="form-control" placeholder="Search by Name/Email/Trade...">
            </div>
            <div class="col-md-3">
              <select id="filterTrade" class="form-select">
                <option value="">All Trades</option>
                <?php
                  $trade_query = mysqli_query($conn, "SELECT DISTINCT trade FROM users WHERE role='student'");
                  while($row = mysqli_fetch_assoc($trade_query)) {
                    echo '<option value="'.htmlspecialchars($row['trade']).'">'.htmlspecialchars($row['trade']).'</option>';
                  }
                ?>
              </select>
            </div>
            <div class="col-md-3">
              <select id="filterSession" class="form-select">
                <option value="">All Sessions</option>
                <?php
                  $session_query = mysqli_query($conn, "SELECT DISTINCT session FROM users WHERE role='student'");
                  while($row = mysqli_fetch_assoc($session_query)) {
                    echo '<option value="'.htmlspecialchars($row['session']).'">'.htmlspecialchars($row['session']).'</option>';
                  }
                ?>
              </select>
            </div>
          </div>
          <div id="studentsTableWrap"></div>
        </div>           
        </div>

        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="deleteStudentModal" tabindex="-1">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header"><h5 class="modal-title">Delete Student</h5></div>
              <div class="modal-body">Are you sure you want to delete this student?</div>
              <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
              </div>
            </div>
          </div>
        </div>
      </div>
     <!-- Teachers Tab Section -->
      <div id="tab-teachers" style="display:none;">
        <div class="card shadow p-3 mt-3">
          <h4 class="mb-3"><i class="bi bi-person-badge"></i> Teachers</h4>
          <div class="row mb-3">
            <div class="col-md-4">
              <input type="text" id="searchTeacherName" class="form-control" placeholder="Search by Name/Email/Trade...">
            </div>
            <div class="col-md-3">
              <select id="filterTeacherTrade" class="form-select">
                <option value="">All Trades</option>
                <?php
                  $trade_query = mysqli_query($conn, "SELECT DISTINCT trade FROM users WHERE role='teacher'");
                  while($row = mysqli_fetch_assoc($trade_query)) {
                    echo '<option value="'.htmlspecialchars($row['trade']).'">'.htmlspecialchars($row['trade']).'</option>';
                  }
                ?>
              </select>
            </div>
            <div class="col-md-3">
              <select id="filterTeacherSession" class="form-select">
                <option value="">All Sessions</option>
                <?php
                  $session_query = mysqli_query($conn, "SELECT DISTINCT session FROM users WHERE role='teacher'");
                  while($row = mysqli_fetch_assoc($session_query)) {
                    echo '<option value="'.htmlspecialchars($row['session']).'">'.htmlspecialchars($row['session']).'</option>';
                  }
                ?>
              </select>
            </div>
          </div>
          <div id="teachersTableWrap"></div>
        </div>
    
        <!-- Notification Modal -->
        <div class="modal fade" id="notifyTeachersModal" tabindex="-1">
          <div class="modal-dialog">
            <div class="modal-content">
                <button class="btn btn-outline-primary btn-sm mb-3" id="notifyTeachersBtn" style="font-weight:500;" data-bs-toggle="modal" data-bs-target="#notifyTeachersModal">
                  <i class="bi bi-bell"></i> Notify Teachers
                </button>
                <div class="modal-body">
                <textarea id="notifyMessage" class="form-control" rows="3" placeholder="Type your message"></textarea>
              </div>
              <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-success" id="sendNotificationBtn">Send</button>
              </div>
            </div>
          </div>
        </div>
      </div>
        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="deleteTeacherModal" tabindex="-1">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header"><h5 class="modal-title">Delete Teacher</h5></div>
              <div class="modal-body">Are you sure you want to delete this teacher?</div>
              <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-danger" id="confirmDeleteTeacherBtn">Delete</button>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- --- FULLSCREEN ADD STUDENT MODAL --- -->
      <div class="fullscreen-iframe-wrapper" id="fullscreenAddStudentWrapper">
        <div class="fullscreen-iframe-header">
          <div><i class="bi bi-person-plus me-2"></i>Add Student/Teacher</div>
          <button class="fullscreen-iframe-close" id="closeAddStudentFullscreen" aria-label="Close">&times;</button>
        </div>
        <div class="fullscreen-iframe-content">
          <iframe src="registration.php" width="100%" height="100%" frameborder="0" style="border:none;min-height:100%;"></iframe>
        </div>
      </div>
      <!-- --- GENERATE REPORT MODAL --- -->
      <div class="modal fade report-modal" id="generateReportModal" tabindex="-1" aria-labelledby="generateReportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
          <div class="modal-content p-0">
            <div class="modal-header bg-#343a40 text-white">
              <h5 class="modal-title" id="generateReportModalLabel">
                <i class="bi bi-file-earmark-bar-graph me-2"></i>Generate & Preview Attendance Report
              </h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
              <div class="container-fluid py-4">
                <form id="reportFilterForm" class="row mb-4" autocomplete="off" onsubmit="return false;">
                  <div class="col-md-3 mb-2">
                    <label class="fw-bold mb-1">From Date:</label>
                    <input type="date" name="from_date" class="form-control" required>
                  </div>
                  <div class="col-md-3 mb-2">
                    <label class="fw-bold mb-1">To Date:</label>
                    <input type="date" name="to_date" class="form-control" required>
                  </div>
                  <div class="col-md-3 mb-2">
                    <label class="fw-bold mb-1">Trade:</label>
                    <select name="trade" class="form-select">
                      <option value="">All Trades</option>
                      <?php foreach($all_trades as $tr): ?>
                        <option value="<?= htmlspecialchars($tr) ?>"><?= htmlspecialchars($tr) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-3 mb-2">
                    <label class="fw-bold mb-1">Status:</label>
                    <select name="status" class="form-select">
                      <option value="">All</option>
                      <option value="approved">Present</option>
                      <option value="absent">Absent</option>
                      <option value="pending">Pending</option>
                      <option value="rejected">Rejected</option>
                    </select>
                  </div>
                  <div class="col-12 text-end mt-2">
                    <button class="btn btn-info px-4" id="previewReportBtn" type="button">
                      <i class="bi bi-search me-1"></i>Preview
                    </button>
                    <button class="btn btn-success px-4 ms-2" id="downloadReportBtn" type="button">
                      <i class="bi bi-download me-1"></i>Download CSV
                    </button>
                  </div>
                </form>
                <div id="reportPreviewTableWrap" class="px-2" style="display:none;">
                  <table class="table table-bordered table-hover mb-0" id="reportPreviewTable">
                    <thead>
                      <tr>
                        <th>Date</th>
                        <th>Student Name</th>
                        <th>Role</th>
                        <th>Trade</th>
                        <th>Status</th>
                        <th>Reason</th>
                        <th>Remarks</th>
                      </tr>
                    </thead>
                    <tbody>
                      <!-- Preview rows will come here -->
                    </tbody>
                  </table>
                </div>
                <div class="text-center text-secondary mt-3" id="reportPreviewNoData" style="display:none;">No records found for selected filters.</div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- --- ATTENDANCE TABLE MODAL --- -->
      <div class="modal fade" id="attendanceTableModal" tabindex="-1" aria-labelledby="attendanceTableModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
          <div class="modal-content p-0" style="border-radius:0;">
            <div class="modal-header bg-#343a40 text-white">
              <h5 class="modal-title" id="attendanceTableModalLabel">
                <i class="bi bi-table me-2"></i>Attendance Entries
              </h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
              <div class="container-fluid py-4">
                <div class="d-flex flex-wrap align-items-center mb-4 gap-3">
                  <label for="attendanceDateModal" class="fw-bold mb-0">Date:</label>
                  <input type="date" id="attendanceDateModal" class="form-control" style="width:auto; min-width:160px;">
                  <label for="attendanceTypeModal" class="fw-bold ms-2 mb-0">Show:</label>
                  <select id="attendanceTypeModal" class="form-select" style="width:auto; min-width:150px;">
                    <option value="students">Students</option>
                    <option value="teachers">Teachers</option>
                  </select>
                </div>
                <div class="table-responsive">
                  <table class="table align-middle mb-0 table-hover bg-white rounded shadow-sm" id="attendanceTableModalTable"></table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- --- TODAY'S TRADE UPDATE MODAL --- -->
      <div class="modal fade" id="todaysTradeUpdateModal" tabindex="-1" aria-labelledby="todaysTradeUpdateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
          <div class="modal-content p-0" style="border-radius:0;">
            <div class="modal-header bg-#343a40 text-white">
              <h5 class="modal-title" id="todaysTradeUpdateModalLabel">
                <i class="bi bi-people-fill me-2"></i>Today's Trade Attendance Update
              </h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
              <div class="container-fluid py-4">
                <div class="row gy-4 gx-3 justify-content-center">
                  <?php
                  $trade_query = mysqli_query($conn, "SELECT DISTINCT trade FROM users WHERE role='teacher'");
                  $trades = [];
                  while($row = mysqli_fetch_assoc($trade_query)) $trades[] = $row['trade'];
                  foreach($trades as $trade) {
                      $teacher_q = mysqli_query($conn, "SELECT name, total_students FROM users WHERE role='teacher' AND trade='$trade' LIMIT 1");
                      $teacher_res = mysqli_fetch_assoc($teacher_q);
                      $teacher_name = $teacher_res ? $teacher_res['name'] : 'N/A';
                      $official_total_students = $teacher_res && isset($teacher_res['total_students']) ? (int)$teacher_res['total_students'] : 0;
                      $present_q = mysqli_query($conn, "
                          SELECT COUNT(DISTINCT ar.student_id) as present
                          FROM attendance_requests ar
                          JOIN users u ON ar.student_id = u.id
                          WHERE ar.date='$today' AND ar.status='approved' AND u.trade='$trade'
                      ");
                      $present_res = mysqli_fetch_assoc($present_q);
                      $present_students = $present_res['present'];
                      $absent_students = $official_total_students - $present_students;
                      $attendance_percent = $official_total_students ? round(($present_students / $official_total_students) * 100) : 0;
                      $badge = "";
                      if ($attendance_percent == 100 && $official_total_students > 0) {
                          $badge = '<span class="badge bg-success fs-6 mt-2">🎉 100% Attendance!</span>';
                      } elseif ($attendance_percent < 75 && $official_total_students > 0) {
                          $badge = '<span class="badge bg-danger fs-6 mt-2">Low Attendance!</span>';
                      }
                      $circleClass = 'circle-primary';
                      if ($attendance_percent == 100) $circleClass = 'circle-success';
                      elseif ($attendance_percent < 75 && $official_total_students > 0) $circleClass = 'circle-danger';
                      ?>
                      <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex justify-content-center">
                        <div class="circular-card shadow animate__animated animate__zoomIn">
                          <div class="circular-badge mb-2 <?= $circleClass ?>">
                            <span><?= $attendance_percent ?>%</span>
                          </div>
                          <div class="fw-bold fs-5 mb-1 text-primary"><?= htmlspecialchars($trade) ?></div>
                          <div class="text-muted small mb-1">Teacher: <b><?= htmlspecialchars($teacher_name) ?></b></div>
                          <div class="small mb-1">
                            <span class="text-success">Present: <b><?= $present_students ?></b></span> /
                            <span class="text-dark"><?= $official_total_students ?></span>
                          </div>
                          <div class="small mb-2">
                            <span class="text-danger">Absent: <b><?= $absent_students ?></b></span>
                          </div>
                          <?= $badge ?>
                        </div>
                      </div>
                      <?php
                  }
                  ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- END CONTENT CONTAINER -->
    </div>
    <div id="tab-teacher-attendance" style="display:none;">
      <div class="container py-5">
        <div class="row justify-content-center mb-4">
          <div class="col-12 col-md-4 mb-2">
            <div class="card shadow text-center p-3">
              <div class="fw-bold text-primary">Total Teachers</div>
              <div class="fs-4"><?php echo $total_teachers; ?></div>
            </div>
          </div>
          <div class="col-12 col-md-4 mb-2">
            <div class="card shadow text-center p-3">
              <div class="fw-bold text-success"> Teacher Present Today</div>
              <div class="fs-4"><?php echo $total_present_today; ?></div>
            </div>
          </div>
          <div class="col-12 col-md-4 mb-2">
            <div class="card shadow text-center p-3">
              <div class="fw-bold text-danger">Absent Today</div>
              <div class="fs-4"><?php echo $total_teachers - $total_present_today < 0 ? 0 : $total_teachers - $total_present_today; ?></div>
            </div>
          </div>
        </div>
        <div class="row justify-content-center">
          <div class="col-12 col-md-4 d-flex justify-content-center">
            <button class="btn btn-primary btn-lg" id="openTeacherAttendanceModal">
              <i class="bi bi-list-check me-2"></i>View Pending Requests
            </button>
          </div>
        </div>
      </div>
    </div>   
  <!-- ====================== JS ====================== -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
   <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Sidebar toggle (mobile)
      const sidebarMenu = document.getElementById('sidebarMenu');
      const sidebarOverlay = document.getElementById('sidebarOverlay');
      document.getElementById('menuToggle')?.addEventListener('click', function() {
        sidebarMenu.classList.toggle('show');
        sidebarOverlay.style.display = sidebarMenu.classList.contains('show') ? 'block' : 'none';
      });
      sidebarOverlay.onclick = function() { sidebarMenu.classList.remove('show'); sidebarOverlay.style.display = 'none'; };
      const openTeacherAttendanceModalBtn = document.getElementById('openTeacherAttendanceModal');
      if(openTeacherAttendanceModalBtn) {
        openTeacherAttendanceModalBtn.onclick = function() {
          fetch('?pending_teachers_attendance=1')
            .then(res => res.text())
            .then(html => { document.getElementById('teacherAttendanceTableWrap').innerHTML = html; });
          var modal = new bootstrap.Modal(document.getElementById('teacherAttendanceModal'));
          modal.show();
        };
      }

      // Tab switch
      window.showTab = function(tab) {
        document.getElementById('tab-dashboard').style.display = (tab === 'dashboard') ? '' : 'none';
        document.getElementById('tab-students').style.display = (tab === 'students') ? '' : 'none';
        document.getElementById('tab-teachers').style.display = (tab === 'teachers') ? '' : 'none';
        document.getElementById('tab-teacher-attendance').style.display = (tab === 'teacher-attendance') ? '' : 'none';
        document.querySelectorAll('.sidebar .nav-link').forEach(link => link.classList.remove('active'));
        if(tab === 'dashboard') document.querySelector('.sidebar .nav-link[onclick*="dashboard"]').classList.add('active');
        if(tab === 'students') document.querySelector('.sidebar .nav-link[onclick*="students"]').classList.add('active');
        if(tab === 'teachers') document.querySelector('.sidebar .nav-link[onclick*="teachers"]').classList.add('active');
        if(tab === 'teacher-attendance') document.querySelector('.sidebar .nav-link[onclick*="teacher-attendance"]').classList.add('active');
      };

      // Students table AJAX load
      function loadStudentsTable() {
        const name = document.getElementById('searchName').value;
        const trade = document.getElementById('filterTrade').value;
        const session = document.getElementById('filterSession').value;
        fetch(`?students_table=1&name=${encodeURIComponent(name)}&trade=${encodeURIComponent(trade)}&session=${encodeURIComponent(session)}`)
          .then(res => res.text())
          .then(html => { document.getElementById('studentsTableWrap').innerHTML = html; });
      }

      // Activate Students tab
      document.querySelector('.sidebar .nav-link[onclick*="students"]').addEventListener('click', function() {
        showTab('students');
        loadStudentsTable();
        setTimeout(() => {
          document.getElementById('searchName').oninput = loadStudentsTable;
          document.getElementById('filterTrade').onchange = loadStudentsTable;
          document.getElementById('filterSession').onchange = loadStudentsTable;
        }, 200);
      });
      

      // Delete Student (modal)
      let deleteStudentId = null;
      window.deleteStudent = function(id) {
        deleteStudentId = id;
        new bootstrap.Modal(document.getElementById('deleteStudentModal')).show();
      }
      document.getElementById('confirmDeleteBtn').onclick = function() {
        fetch('', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'action=delete&id=' + encodeURIComponent(deleteStudentId)
        })
        .then(res => res.text())
        .then(res => {
          bootstrap.Modal.getInstance(document.getElementById('deleteStudentModal')).hide();
          loadStudentsTable();
        });
      };


      function loadTeachersTable() {
        const name = document.getElementById('searchTeacherName').value;
        const trade = document.getElementById('filterTeacherTrade').value;
        const session = document.getElementById('filterTeacherSession').value;
        fetch(`?teachers_table=1&name=${encodeURIComponent(name)}&trade=${encodeURIComponent(trade)}&session=${encodeURIComponent(session)}`)
          .then(res => res.text())
          .then(html => { document.getElementById('teachersTableWrap').innerHTML = html; });
      }
      document.querySelector('.sidebar .nav-link[onclick*="teachers"]').addEventListener('click', function() {
        showTab('teachers');
        loadTeachersTable();
        setTimeout(() => {
          document.getElementById('searchTeacherName').oninput = loadTeachersTable;
          document.getElementById('filterTeacherTrade').onchange = loadTeachersTable;
          document.getElementById('filterTeacherSession').onchange = loadTeachersTable;
        }, 200);
      });
    
      // Delete Teacher Modal Logic
      let deleteTeacherId = null;
      window.deleteTeacher = function(id) {
        deleteTeacherId = id;
        new bootstrap.Modal(document.getElementById('deleteTeacherModal')).show();
      }
      document.getElementById('confirmDeleteTeacherBtn').onclick = function() {
        fetch('', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'action=delete_teacher&id=' + encodeURIComponent(deleteTeacherId)
        })
        .then(res => res.text())
        .then(res => {
          bootstrap.Modal.getInstance(document.getElementById('deleteTeacherModal')).hide();
          loadTeachersTable();
        });
      }
      // Notify Teachers Logic
      function loadPendingTeachersAttendance() {
    fetch('?pending_teachers_attendance=1')
      .then(res => res.text())
        .then(html => { document.getElementById('teacherAttendanceTableWrap').innerHTML = html; });
    }

    openTeacherAttendanceModalBtn.onclick = function() {
      loadPendingTeachersAttendance();
      var modal = new bootstrap.Modal(document.getElementById('teacherAttendanceModal'));
      modal.show();
    };
      // Approve/Reject Teacher Attendance
      window.approveTeacherAttendance = function(id) {
        Swal.fire({
          title: 'Approve Attendance?',
          html: 'Are you sure you want to <b>APPROVE</b> this teacher attendance request?',
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Yes, Approve',
          cancelButtonText: 'Cancel',
          focusCancel: true
        }).then((result) => {
          if (result.isConfirmed) {
            const remark = document.getElementById('remark-' + id)?.value || '';
            fetch('', {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
              body: 'action=approve_teacher_attendance&id=' + encodeURIComponent(id) + '&remark=' + encodeURIComponent(remark)
            })
            .then(res => res.text())
            .then(response => {
              if (response.trim() === 'approved') {
                Swal.fire('Approved!', 'Teacher attendance has been approved successfully. Notification sent to teacher.', 'success');
                loadPendingTeachersAttendance();
              } else {
                Swal.fire('Error!', 'Failed to approve attendance. Please try again.', 'error');
              }
            })
            .catch(error => {
              Swal.fire('Error!', 'Network error occurred. Please try again.', 'error');
            });
          }
        });
      };

      window.rejectTeacherAttendance = function(id) {
        Swal.fire({
          title: 'Reject Attendance?',
          html: 'Are you sure you want to <b>REJECT</b> this teacher attendance request?',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Yes, Reject',
          cancelButtonText: 'Cancel',
          focusCancel: true
        }).then((result) => {
          if (result.isConfirmed) {
            const remark = document.getElementById('remark-' + id)?.value || '';
            fetch('', {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
              body: 'action=reject_teacher_attendance&id=' + encodeURIComponent(id) + '&remark=' + encodeURIComponent(remark)
            })
            .then(res => res.text())
            .then(response => {
              if (response.trim() === 'rejected') {
                Swal.fire('Rejected!', 'Teacher attendance has been rejected. Notification sent to teacher.', 'success');
                loadPendingTeachersAttendance();
              } else {
                Swal.fire('Error!', 'Failed to reject attendance. Please try again.', 'error');
              }
            })
            .catch(error => {
              Swal.fire('Error!', 'Network error occurred. Please try again.', 'error');
            });
          }
        });
      };

      window.undoTeacherAttendance = function(id) {
        Swal.fire({
          title: 'Undo Attendance?',
          html: 'Are you sure you want to <b>UNDO</b> this attendance action?',
          icon: 'info',
          showCancelButton: true,
          confirmButtonText: 'Yes, Undo',
          cancelButtonText: 'Cancel',
          focusCancel: true
        }).then((result) => {
          if (result.isConfirmed) {
            const remark = document.getElementById('remark-' + id)?.value || '';
            fetch('', {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
              body: 'action=undo_teacher_attendance&id=' + encodeURIComponent(id) + '&remark=' + encodeURIComponent(remark)
            })
            .then(res => res.text())
            .then(response => {
              if (response.trim() === 'undone') {
                Swal.fire('Undone!', 'Attendance action has been undone successfully.', 'success');
                loadPendingTeachersAttendance();
              } else {
                Swal.fire('Error!', 'Failed to undo attendance. Please try again.', 'error');
              }
            })
            .catch(error => {
              Swal.fire('Error!', 'Network error occurred. Please try again.', 'error');
            });
          }
        });
      };
            
      // Show dashboard on load
      showTab('dashboard');

      // Animate Counters
      function animateCounter(id, target) {
        let count = 0;
        const step = Math.ceil(target / 40);
        const el = document.getElementById(id);
        if (!el) return;
        const timer = setInterval(() => {
          count += step;
          if (count >= target) {
            count = target;
            clearInterval(timer);
          }
          el.innerText = count;
        }, 25);
      }
      animateCounter('counterStudents', <?php echo $total_students; ?>);
      animateCounter('counterTeachers', <?php echo $total_teachers; ?>);
      animateCounter('counterTrades', <?php echo $total_trades; ?>);
      animateCounter('counterAttendance', <?php echo $total_students_present_today; ?>);

      // Attendance Analytics Chart with Trade Filter
      let chartInstance = null;
      let globalChartData = null;
      fetch('attendance_chart_data.php')
        .then(res => res.json())
        .then(chartData => {
          globalChartData = chartData;
          const tradeSelect = document.getElementById('tradeSelect');
          chartData.datasets.forEach(ds => {
            const opt = document.createElement('option');
            opt.value = ds.label;
            opt.text = ds.label;
            tradeSelect.appendChild(opt);
          });
          drawChart(chartData);
          tradeSelect.addEventListener('change', function() {
            if (this.value === 'all') {
              drawChart(globalChartData);
            } else {
              const selectedDS = globalChartData.datasets.find(ds => ds.label === this.value);
              drawChart({
                labels: globalChartData.labels,
                datasets: [selectedDS]
              });
            }
          });
        });
      function drawChart(data) {
        const ctx = document.getElementById('attendanceChart').getContext('2d');
        if (chartInstance) chartInstance.destroy();
        chartInstance = new Chart(ctx, {
          type: 'line',
          data: data,
          options: {
            plugins: { legend: { display: true } },
            scales: { y: { beginAtZero: true } }
          }
        });
      }

      // Attendance Table Modal
      const today = new Date().toISOString().split('T')[0];
      if(document.getElementById('attendanceDateModal')) {
        document.getElementById('attendanceDateModal').value = today;
      }
      function loadAttendanceTableModal(type = 'students', date = '') {
        if (!date) date = document.getElementById('attendanceDateModal').value;
        fetch('attendance_table.php?type=' + type + '&date=' + date)
          .then(res => res.text())
          .then(html => {
            document.getElementById('attendanceTableModalTable').innerHTML = html;
          });
      }
      if(document.getElementById('attendanceTableModal')) {
        document.getElementById('attendanceTableModal').addEventListener('show.bs.modal', function() {
          loadAttendanceTableModal(
            document.getElementById('attendanceTypeModal').value,
            document.getElementById('attendanceDateModal').value
          );
        });
        document.getElementById('attendanceTypeModal').onchange = function() {
          loadAttendanceTableModal(this.value, document.getElementById('attendanceDateModal').value);
        };
        document.getElementById('attendanceDateModal').onchange = function() {
          loadAttendanceTableModal(document.getElementById('attendanceTypeModal').value, this.value);
        };
      }

      // Add Student/Teacher Fullscreen Logic
      const addStudentBtn = document.getElementById('openAddStudentFullscreen');
      const addStudentWrapper = document.getElementById('fullscreenAddStudentWrapper');
      const closeAddStudentBtn = document.getElementById('closeAddStudentFullscreen');
      addStudentBtn.onclick = function() {
        addStudentWrapper.style.display = 'flex';
        document.body.style.overflow = "hidden";
      };
      closeAddStudentBtn.onclick = function() {
        addStudentWrapper.style.display = 'none';
        document.body.style.overflow = "";
      };

      // Report Preview Modal logic
      function serializeForm(form) {
        const formData = new FormData(form);
        return Array.from(formData.entries())
          .map(([k, v]) => encodeURIComponent(k) + '=' + encodeURIComponent(v))
          .join('&');
      }
      function loadReportPreview() {
        const form = document.getElementById('reportFilterForm');
        const params = serializeForm(form) + '&preview=1';
        document.getElementById('reportPreviewNoData').style.display = 'none';
        document.getElementById('reportPreviewTableWrap').style.display = 'none';
        fetch('attendance_report.php?' + params)
          .then(res => res.json())
          .then(data => {
            const wrap = document.getElementById('reportPreviewTableWrap');
            const tbody = document.querySelector('#reportPreviewTable tbody');
            tbody.innerHTML = '';
            if (data && data.length) {
              data.forEach(row => {
                const tr = document.createElement('tr');
                row.forEach(cell => {
                  const td = document.createElement('td');
                  td.textContent = cell;
                  tr.appendChild(td);
                });
                tbody.appendChild(tr);
              });
              wrap.style.display = '';
              document.getElementById('reportPreviewNoData').style.display = 'none';
            } else {
              wrap.style.display = 'none';
              document.getElementById('reportPreviewNoData').style.display = '';
            }
          }).catch(() => {
            document.getElementById('reportPreviewNoData').style.display = '';
            document.getElementById('reportPreviewTableWrap').style.display = 'none';
          });
      }
      document.getElementById('previewReportBtn').onclick = loadReportPreview;
      document.getElementById('downloadReportBtn').onclick = function() {
        const form = document.getElementById('reportFilterForm');
        const params = serializeForm(form);
        window.open('attendance_report.php?' + params, '_blank');
      };
     
    document.getElementById('sendNotificationBtn').onclick = function() {
    const msg = document.getElementById('notifyMessage').value.trim();
    if (!msg) { alert('Please type a message.'); return; }
    // TODO: Call backend AJAX to send notification
    alert('Notification sent: ' + msg); // Replace with AJAX
    bootstrap.Modal.getInstance(document.getElementById('notifyTeachersModal')).hide();
    document.getElementById('notifyMessage').value = '';
  };
  document.getElementById('openAdminProfile').addEventListener('click', function() {
    // Fill fields with current admin data (use PHP or fetch via AJAX)
    <?php
      // Ensure admin session is set
      $admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE role='admin' LIMIT 1"));
      if ($admin) {
        // Set session if not already set (for admin dashboard)
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
          $_SESSION['user_id'] = $admin['id'];
          $_SESSION['role'] = 'admin';
          $_SESSION['name'] = $admin['name'];
          $_SESSION['email'] = $admin['email'];
        }
        $admin_json = json_encode($admin);
        echo "var adminProfile = $admin_json;";
      } else {
        echo "var adminProfile = null;";
      }
    ?>
    
    if (adminProfile) {
      const form = document.getElementById('adminProfileForm');
      form.name.value = adminProfile.name || '';
      form.email.value = adminProfile.email || '';
      form.contact_number.value = adminProfile.contact_number || '';
      form.date_of_birth.value = adminProfile.date_of_birth || '';
      form.gender.value = adminProfile.gender || '';
      // Update the hidden user_id field
      const userIdField = form.querySelector('input[name="user_id"]');
      if (userIdField) userIdField.value = adminProfile.id;
      
      console.log('Admin profile loaded:', adminProfile);
    } else {
      console.warn('No admin profile found');
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'No admin profile found in database.'
      });
    }
  });
      // AOS Animation Init
      if(window.AOS) AOS.init({ duration: 700, once: true });
    });

  </script>
  <div class="modal fade" id="teacherAttendanceModal" tabindex="-1">
    <div class="modal-dialog modal-fullscreen">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Pending Teacher Attendance Requests</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div id="teacherAttendanceTableWrap"></div>
        </div>
      </div>
    </div>
  </div>
  <!-- Admin Profile Modal -->
  <div class="modal fade" id="adminProfileModal" tabindex="-1" aria-labelledby="adminProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <form id="adminProfileForm" action="../backend-php/update_profile.php" method="POST" class="modal-content">        
        <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id'] ?? ''; ?>">
        <input type="hidden" name="trade" value="">
        <input type="hidden" name="session" value="">
        <div class="modal-header">
          <h5 class="modal-title" id="adminProfileModalLabel">Edit Profile</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <!-- Fields will be filled with JS -->
          <div class="mb-2">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Contact Number</label>
            <input type="text" name="contact_number" class="form-control">
          </div>
          <div class="mb-2">
            <label class="form-label">Date of Birth</label>
            <input type="date" name="date_of_birth" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Gender</label>
            <select name="gender" class="form-select">
              <option value="male">Male</option>
              <option value="female">Female</option>
              <option value="other">Other</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Save Changes</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
  
  <!-- Change Password Modal -->
  <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <form id="changePasswordForm" action="../backend-php/change_password.php" method="POST" class="modal-content">
        <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id'] ?? ''; ?>">
        <div class="modal-header">
          <h5 class="modal-title" id="changePasswordModalLabel">
            <i class="bi bi-key me-2"></i>Change Password
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Current Password</label>
            <div class="input-group">
              <input type="password" name="current_password" class="form-control" required placeholder="Enter current password" id="currentPassword">
              <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('currentPassword', this)">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">New Password</label>
            <div class="input-group">
              <input type="password" name="new_password" class="form-control" required placeholder="Enter new password (min 6 characters)" minlength="6" id="newPassword">
              <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('newPassword', this)">
                <i class="bi bi-eye"></i>
              </button>
            </div>
            <div class="form-text">
              <small id="passwordStrength" class="text-muted">Password strength will be shown here</small>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Confirm New Password</label>
            <div class="input-group">
              <input type="password" name="confirm_password" class="form-control" required placeholder="Confirm new password" id="confirmPassword">
              <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirmPassword', this)">
                <i class="bi bi-eye"></i>
              </button>
            </div>
            <div class="form-text">
              <small id="passwordMatch" class="text-muted"></small>
            </div>
          </div>
          <div class="alert alert-info small">
            <i class="bi bi-info-circle me-1"></i>
            Password must be at least 6 characters long. For better security, use a mix of letters, numbers, and special characters.
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg me-1"></i>Change Password
          </button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
  <script>
  // Admin Profile Form Submission
  document.getElementById('adminProfileForm').onsubmit = async function(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    
    // Debug: Log form data
    console.log('Form data being sent:');
    for (let [key, value] of formData.entries()) {
      console.log(key, value);
    }

    try {
    const res = await fetch(form.action, {
      method: "POST",
      body: formData,
      credentials: "same-origin"
    });
    
    console.log('Response status:', res.status);
    const responseText = await res.text();
    console.log('Response text:', responseText);
    
    let data;
    try {
      data = JSON.parse(responseText);
    } catch (parseError) {
      console.error('JSON parse error:', parseError);
      console.log('Raw response:', responseText);
      throw new Error('Invalid JSON response');
    }
    
    if (data.success) {
      Swal.fire({
        icon: 'success',
        title: 'Profile updated!',
        text: 'Your changes have been saved.',
        confirmButtonColor: '#3085d6',
        timer: 1800,
        showConfirmButton: false
      });
      bootstrap.Modal.getInstance(document.getElementById('adminProfileModal')).hide();
      setTimeout(() => location.reload(), 1800);
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Oops...',
        text: data.message || 'Something went wrong.'
      });
    }
  } catch(err) {
    console.error('Form submission error:', err);
    Swal.fire({
      icon: 'error',
      title: 'Network Error',
      text: 'Please try again. Error: ' + err.message
    });
  }
  };

  // Change Password Form Submission
  document.getElementById('changePasswordForm').onsubmit = async function(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    
    // Add user_id to form data
    formData.append('user_id', '<?php echo $_SESSION['user_id'] ?? ''; ?>');
    
    // Validate password match before submitting
    const newPassword = form.new_password.value;
    const confirmPassword = form.confirm_password.value;
    
    if (newPassword !== confirmPassword) {
      Swal.fire({
        icon: 'error',
        title: 'Password Mismatch',
        text: 'New password and confirm password do not match.'
      });
      return;
    }

    try {
      const res = await fetch(form.action, {
        method: "POST",
        body: formData,
        credentials: "same-origin"
      });
      
      const responseText = await res.text();
      console.log('Change password response:', responseText);
      
      let data;
      try {
        data = JSON.parse(responseText);
      } catch (parseError) {
        console.error('JSON parse error:', parseError);
        throw new Error('Invalid JSON response');
      }
      
      if (data.success) {
        Swal.fire({
          icon: 'success',
          title: 'Password Changed!',
          text: 'Your password has been updated successfully.',
          confirmButtonColor: '#3085d6',
          timer: 2000,
          showConfirmButton: false
        });
        bootstrap.Modal.getInstance(document.getElementById('changePasswordModal')).hide();
        form.reset();
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: data.message || 'Failed to change password.'
        });
      }
    } catch(err) {
      console.error('Change password error:', err);
      Swal.fire({
        icon: 'error',
        title: 'Network Error',
        text: 'Please try again. Error: ' + err.message
      });
    }
  };

  // Password Toggle Function (Global)
  window.togglePassword = function(inputId, button) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
      input.type = 'text';
      icon.className = 'bi bi-eye-slash';
    } else {
      input.type = 'password';
      icon.className = 'bi bi-eye';
    }
  };

  // Password Strength Checker
  function checkPasswordStrength(password) {
    let strength = 0;
    let feedback = [];
    
    if (password.length >= 8) {
      strength += 25;
    } else {
      feedback.push('At least 8 characters');
    }
    
    if (/[a-z]/.test(password)) {
      strength += 25;
    } else {
      feedback.push('Lowercase letter');
    }
    
    if (/[A-Z]/.test(password)) {
      strength += 25;
    } else {
      feedback.push('Uppercase letter');
    }
    
    if (/[0-9]/.test(password)) {
      strength += 12.5;
    } else {
      feedback.push('Number');
    }
    
    if (/[^A-Za-z0-9]/.test(password)) {
      strength += 12.5;
    } else {
      feedback.push('Special character');
    }
    
    return { strength, feedback };
  }

  // Password validation event listeners
  document.addEventListener('DOMContentLoaded', function() {
    const newPasswordField = document.getElementById('newPassword');
    const confirmPasswordField = document.getElementById('confirmPassword');
    const strengthIndicator = document.getElementById('passwordStrength');
    const matchIndicator = document.getElementById('passwordMatch');

    if (newPasswordField && strengthIndicator) {
      newPasswordField.addEventListener('input', function() {
        const { strength, feedback } = checkPasswordStrength(this.value);
        
        if (this.value.length === 0) {
          strengthIndicator.textContent = 'Password strength will be shown here';
          strengthIndicator.className = 'text-muted';
        } else if (strength < 50) {
          strengthIndicator.textContent = `Weak (${Math.round(strength)}%) - Need: ${feedback.join(', ')}`;
          strengthIndicator.className = 'text-danger';
        } else if (strength < 75) {
          strengthIndicator.textContent = `Medium (${Math.round(strength)}%) - Consider: ${feedback.join(', ')}`;
          strengthIndicator.className = 'text-warning';
        } else {
          strengthIndicator.textContent = `Strong (${Math.round(strength)}%)`;
          strengthIndicator.className = 'text-success';
        }
      });
    }

    if (confirmPasswordField && matchIndicator && newPasswordField) {
      function checkPasswordMatch() {
        if (confirmPasswordField.value.length === 0) {
          matchIndicator.textContent = '';
          return;
        }
        
        if (newPasswordField.value === confirmPasswordField.value) {
          matchIndicator.textContent = '✓ Passwords match';
          matchIndicator.className = 'text-success';
        } else {
          matchIndicator.textContent = '✗ Passwords do not match';
          matchIndicator.className = 'text-danger';
        }
      }
      
      confirmPasswordField.addEventListener('input', checkPasswordMatch);
      newPasswordField.addEventListener('input', checkPasswordMatch);
    }
  });
  </script>
  
  <script>
  // Change Password Form Handler
  document.getElementById('changePasswordForm').onsubmit = async function(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    
    // Validate passwords match
    const newPassword = formData.get('new_password');
    const confirmPassword = formData.get('confirm_password');
    
    if (newPassword !== confirmPassword) {
      Swal.fire({
        icon: 'error',
        title: 'Password Mismatch',
        text: 'New password and confirm password do not match.'
      });
      return;
    }
    
    if (newPassword.length < 6) {
      Swal.fire({
        icon: 'error',
        title: 'Password Too Short',
        text: 'Password must be at least 6 characters long.'
      });
      return;
    }
    
    try {
      const res = await fetch(form.action, {
        method: "POST",
        body: formData,
        credentials: "same-origin"
      });
      
      const responseText = await res.text();
      let data;
      
      try {
        data = JSON.parse(responseText);
      } catch (parseError) {
        console.error('JSON parse error:', parseError);
        console.log('Raw response:', responseText);
        throw new Error('Invalid response from server');
      }
      
      if (data.success) {
        Swal.fire({
          icon: 'success',
          title: 'Password Changed!',
          text: 'Your password has been updated successfully.',
          confirmButtonColor: '#3085d6',
          timer: 2000,
          showConfirmButton: false
        });
        bootstrap.Modal.getInstance(document.getElementById('changePasswordModal')).hide();
        form.reset();
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Failed to Change Password',
          text: data.message || 'Something went wrong.'
        });
      }
    } catch(err) {
      console.error('Form submission error:', err);
      Swal.fire({
        icon: 'error',
        title: 'Network Error',
        text: 'Please try again. Error: ' + err.message
      });
    }
  };
  
  // Password toggle function
  function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
      input.type = 'text';
      icon.className = 'bi bi-eye-slash';
    } else {
      input.type = 'password';
      icon.className = 'bi bi-eye';
    }
  }
  
  // Password strength checker
  function checkPasswordStrength(password) {
    let strength = 0;
    let feedback = [];
    
    if (password.length >= 6) strength++;
    else feedback.push('at least 6 characters');
    
    if (/[a-z]/.test(password)) strength++;
    else feedback.push('lowercase letters');
    
    if (/[A-Z]/.test(password)) strength++;
    else feedback.push('uppercase letters');
    
    if (/[0-9]/.test(password)) strength++;
    else feedback.push('numbers');
    
    if (/[^A-Za-z0-9]/.test(password)) strength++;
    else feedback.push('special characters');
    
    const strengthElement = document.getElementById('passwordStrength');
    if (!strengthElement) return;
    
    if (password.length === 0) {
      strengthElement.textContent = 'Password strength will be shown here';
      strengthElement.className = 'text-muted';
      return;
    }
    
    const strengthText = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'][strength - 1] || 'Very Weak';
    const strengthClass = ['text-danger', 'text-warning', 'text-info', 'text-success', 'text-success'][strength - 1] || 'text-danger';
    
    strengthElement.textContent = `Strength: ${strengthText}`;
    strengthElement.className = strengthClass;
    
    if (feedback.length > 0 && strength < 4) {
      strengthElement.textContent += ` (Add: ${feedback.slice(0, 2).join(', ')})`;
    }
  }
  
  // Password match checker
  function checkPasswordMatch() {
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const matchElement = document.getElementById('passwordMatch');
    
    if (!matchElement) return;
    
    if (confirmPassword.length === 0) {
      matchElement.textContent = '';
      return;
    }
    
    if (newPassword === confirmPassword) {
      matchElement.textContent = '✓ Passwords match';
      matchElement.className = 'text-success';
    } else {
      matchElement.textContent = '✗ Passwords do not match';
      matchElement.className = 'text-danger';
    }
  }
  
  // Add event listeners when modal is shown
  document.getElementById('changePasswordModal').addEventListener('shown.bs.modal', function() {
    const newPasswordInput = document.getElementById('newPassword');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    
    newPasswordInput.addEventListener('input', function() {
      checkPasswordStrength(this.value);
      checkPasswordMatch();
    });
    
    confirmPasswordInput.addEventListener('input', checkPasswordMatch);
  });
  </script>
</body>
</html>