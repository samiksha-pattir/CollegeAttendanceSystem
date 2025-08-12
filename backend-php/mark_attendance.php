<?php
date_default_timezone_set('Asia/Kolkata');
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    http_response_code(403);
    echo "error:unauthorized";
    exit();
}
date_default_timezone_set('Asia/Kolkata');

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];
$date    = date('Y-m-d');
$mark_time = date('Y-m-d H:i:s');
$status  = 'pending';
$reason  = isset($_POST['reason']) ? trim($_POST['reason']) : null;
$latitude = isset($_POST['latitude']) ? $_POST['latitude'] : null;
$longitude = isset($_POST['longitude']) ? $_POST['longitude'] : null;

// --- STUDENT ATTENDANCE ---
if ($role === 'student') {
    $student_trade   = strtolower(trim($_SESSION['trade']));
    $student_session = strtolower(trim($_SESSION['session']));

    // Prevent double marking for the same day
    $stmt_check = $conn->prepare("SELECT id FROM attendance_requests WHERE student_id=? AND date=?");
    $stmt_check->bind_param("is", $user_id, $date);
    $stmt_check->execute();
    $stmt_check->store_result();
    if ($stmt_check->num_rows > 0) {
        http_response_code(409);
        echo "error:already_marked";
        $stmt_check->close();
        exit();
    }
    $stmt_check->close();

    // Find teacher of same trade & session
    $get_teacher = $conn->prepare(
        "SELECT id FROM users WHERE role='teacher' 
         AND LOWER(TRIM(trade))=? AND LOWER(TRIM(session))=? LIMIT 1"
    );
    $get_teacher->bind_param("ss", $student_trade, $student_session);
    $get_teacher->execute();
    $get_teacher->bind_result($teacher_id);
    $get_teacher->fetch();
    $get_teacher->close();

    if (empty($teacher_id)) {
        http_response_code(404);
        echo "error:no_teacher_found";
        exit();
    }

    // Insert attendance request WITH LOCATION
    $stmt = $conn->prepare(
        "INSERT INTO attendance_requests (student_id, date, mark_time, status, reason, teacher_id, latitude, longitude) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("issssiss", $user_id, $date, $mark_time, $status, $reason, $teacher_id, $latitude, $longitude);
    if ($stmt->execute()) {
        // Send notification to this teacher
        $message = "New attendance request from student ID $user_id on $date.";
        $notif_sql = "INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())";
        $notif_stmt = $conn->prepare($notif_sql);
        $notif_stmt->bind_param("is", $teacher_id, $message);
        $notif_stmt->execute();
        $notif_stmt->close();

        echo "success";
    } else {
    http_response_code(500);
    echo "error:insert_failed - " . $stmt->error;
}
    $stmt->close();
}

// --- TEACHER'S OWN ATTENDANCE ---
else if ($role === 'teacher') {
    // Prevent double marking for the same day
    $stmt_check = $conn->prepare("SELECT id FROM attendance_requests WHERE student_id=? AND date=?");
    $stmt_check->bind_param("is", $user_id, $date);
    $stmt_check->execute();
    $stmt_check->store_result();
    if ($stmt_check->num_rows > 0) {
        http_response_code(409);
        echo "error:already_marked";
        $stmt_check->close();
        exit();
    }
    $stmt_check->close();

    // Teacher's own attendance: both student_id and teacher_id = teacher's id, also WITH LOCATION
    $stmt = $conn->prepare(
        "INSERT INTO attendance_requests (student_id, date, mark_time, status, reason, teacher_id, latitude, longitude) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("issssiss", $user_id, $date, $mark_time, $status, $reason, $user_id, $latitude, $longitude);
    if ($stmt->execute()) {
        echo "success";
    } else {
        http_response_code(500);
        echo "error:insert_failed";
    }
    $stmt->close();
}
else {
    http_response_code(403);
    echo "error:unauthorized";
}

$conn->close();
?>