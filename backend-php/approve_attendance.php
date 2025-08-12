<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    http_response_code(403);
    echo "Unauthorized access.";
    exit();
}

$teacher_id = $_SESSION['user_id'];
$request_id = $_POST['request_id'] ?? null;
$action     = $_POST['action'] ?? '';
$remarks    = trim($_POST['remarks'] ?? '');
$redirect_tab = $_POST['redirect_tab'] ?? 'profile';

if (!$request_id || !in_array($action, ['approve', 'reject', 'undo'])) {
    http_response_code(400);
    echo "Invalid request.";
    exit();
}

// **Always fetch request first**
$stmt = $conn->prepare("SELECT student_id, status, date FROM attendance_requests WHERE id=? AND teacher_id=?");
$stmt->bind_param("ii", $request_id, $teacher_id);
$stmt->execute();
$stmt->bind_result($student_id, $current_status, $request_date);
if (!$stmt->fetch()) {
    $stmt->close();
    header("Location: ../frontend/teacher_dashboard.php?tab=" . urlencode($redirect_tab) . "&error=Attendance request not found or not authorized.");
    exit();
}
$stmt->close();

// **Undo logic**
if ($action == 'undo') {
    // Only allow undo for approved/rejected
    if (!in_array($current_status, ['approved', 'rejected'])) {
        header("Location: ../frontend/teacher_dashboard.php?tab=" . urlencode($redirect_tab) . "&error=Only approved or rejected requests can be undone.");
        exit();
    }
    $stmt = $conn->prepare("UPDATE attendance_requests SET status='pending', approval_time=NULL WHERE id=?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $stmt->close();
    header("Location: ../frontend/teacher_dashboard.php?tab=" . urlencode($redirect_tab) . "&success=Attendance request undone. Now you can approve/reject again.");
    exit();
}

// **Approve/Reject logic**
if ($current_status !== 'pending') {
    header("Location: ../frontend/teacher_dashboard.php?tab=" . urlencode($redirect_tab) . "&error=Attendance request already processed.");
    exit();
}

$new_status = $action === 'approve' ? 'approved' : 'rejected';
$approval_time = date('Y-m-d H:i:s');

$stmt = $conn->prepare("UPDATE attendance_requests SET status=?, approval_time=?, remarks=? WHERE id=?");
$stmt->bind_param("sssi", $new_status, $approval_time, $remarks, $request_id);
if ($stmt->execute()) {
    // Send notification to student
    $notif_msg = $action === 'approve'
        ? "Your attendance for $request_date has been approved."
        : "Your attendance for $request_date has been rejected.";
    if ($remarks) $notif_msg .= " Remark: $remarks";

    $notif_sql = "INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())";
    $notif_stmt = $conn->prepare($notif_sql);
    $notif_stmt->bind_param("is", $student_id, $notif_msg);
    $notif_stmt->execute();
    $notif_stmt->close();

    header("Location: ../frontend/teacher_dashboard.php?tab=" . urlencode($redirect_tab) . "&success=Attendance $new_status successfully.");
    exit();
} else {
    header("Location: ../frontend/teacher_dashboard.php?tab=" . urlencode($redirect_tab) . "&error=Failed to update attendance.");
    exit();
}
?>