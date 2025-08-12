<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../login.php?error=Unauthorized access.");
    exit();
}
$teacher_id = $_SESSION['user_id'];
$date = date('Y-m-d');
$time = date('H:i:s');

// Check if attendance already marked
$sql = "SELECT id FROM teacher_attendance WHERE teacher_id=? AND date=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $teacher_id, $date);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    header("Location: ../teacher_dashboard.php?tab=myattendance&error=Attendance already marked!");
    exit();
}
$stmt->close();

// Mark attendance
$sql = "INSERT INTO teacher_attendance (teacher_id, date, mark_time) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $teacher_id, $date, $time);
if ($stmt->execute()) {
    header("Location: ../teacher_dashboard.php?tab=myattendance&success=Attendance marked successfully!");
} else {
    header("Location: ../teacher_dashboard.php?tab=myattendance&error=Could not mark attendance.");
}
exit();
?>