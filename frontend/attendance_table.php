<?php
// Set content type and error reporting for debugging
header("Content-Type: text/html; charset=UTF-8");
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once("../backend-php/db.php");

// Set timezone for India so PHP date() returns local time
date_default_timezone_set('Asia/Kolkata');

// Debug: Show current server date/time (remove in production)
echo date('Y-m-d H:i:s');

// Get the type (students/teachers) and date from GET parameters; set defaults if not present
$date = isset($_GET['date']) && $_GET['date'] !== "" ? $_GET['date'] : date('Y-m-d');
$type = isset($_GET['type']) ? $_GET['type'] : 'students';

// Helper function for badge color based on status
function getStatusBadgeClass($status) {
    $status = strtolower($status);
    if ($status == 'approved' || $status == 'present') return 'bg-success';
    if ($status == 'pending') return 'bg-warning';
    if ($status == 'rejected' || $status == 'absent') return 'bg-danger';
    return 'bg-secondary';
}

if ($type == 'teachers') {
    // === Teachers Attendance Table ===
    $sql = "SELECT t.id, t.name, a.date, a.status, a.marked_by 
            FROM teachers t
            JOIN attendance_requests a ON a.teacher_id = t.id
            WHERE DATE(a.date) = '$date'
            ORDER BY a.date DESC
            LIMIT 20";
    $result = mysqli_query($conn, $sql);
    ?>
    <!-- Table Header for Teachers -->
    <thead class="table-light">
      <tr>
        <th>#</th>
        <th>Teacher</th>
        <th>Date</th>
        <th>Status</th>
        <th>Marked By</th>
      </tr>
    </thead>
    <tbody>
    <?php
    $count = 1;
    if ($result) {
      while($row = mysqli_fetch_assoc($result)) {
        // Render each teacher attendance row
        echo "<tr>
                <td>{$count}</td>
                <td>{$row['name']}</td>
                <td>{$row['date']}</td>
                <td>
                  <span class='badge " . getStatusBadgeClass($row['status']) . "'>"
                    . ucfirst($row['status']) .
                  "</span>
                </td>
                <td>{$row['marked_by']}</td>
              </tr>";
        $count++;
      }
    }
    // Show 'No data' row if no records found
    if($count == 1) {
      echo "<tr><td colspan='5' class='text-center text-muted'>No data found for this date.</td></tr>";
    }
    ?>
    </tbody>
    <?php
} else {
    // === Students Attendance Table ===
    $sql = "SELECT s.id, s.img, s.name, s.trade, a.status, a.date
            FROM users s
            JOIN attendance_requests a ON a.student_id = s.id
            WHERE s.role = 'student' AND DATE(a.date) = '$date'
            ORDER BY a.date DESC
            LIMIT 20";
    $result = mysqli_query($conn, $sql);
    ?>
    <!-- Table Header for Students -->
    <thead class="table-light">
      <tr>
        <th>#</th>
        <th>Profile</th>
        <th>Student</th>
        <th>Trade</th>
        <th>Status</th>
        <th>Date</th>
        <!-- <th>Marked By</th> -->
      </tr>
    </thead>
    <tbody>
    <?php
    $count = 1;
    if ($result) {
      while($row = mysqli_fetch_assoc($result)) {
        // Use default avatar if img is not available
        $profileImg = $row['img'] ? "images/{$row['img']}" : "images/default-avatar.png";
        // Get badge color based on status
        $badgeClass = getStatusBadgeClass($row['status']);
        // Render each student attendance row
        echo "<tr>
                <td>{$count}</td>
                <td>
                  <img src='{$profileImg}' alt='Profile' style='width:32px;height:32px;object-fit:cover;border-radius:50%;'>
                </td>
                <td>{$row['name']}</td>
                <td>{$row['trade']}</td>
                <td>
                  <span class='badge {$badgeClass}'>".ucfirst($row['status'])."</span>
                </td>
                <td>{$row['date']}</td>
              </tr>";
        $count++;
      }
    }
    // Show 'No data' row if no records found
    if($count == 1) {
      echo "<tr><td colspan='6' class='text-center text-muted'>No data found for this date.</td></tr>";
    }
    ?>
    </tbody>
    <?php
}
?>