<?php
session_start();
header('Content-Type: application/json');
include 'db.php';

$response = ['success' => false, 'message' => 'Unknown error'];

// Debug: Check session data
error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'not set'));
error_log("Posted user_id: " . ($_POST['user_id'] ?? 'not set'));
error_log("Session role: " . ($_SESSION['role'] ?? 'not set'));

// For admin, if session is not set, try to set it
if (!isset($_SESSION['user_id']) && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    $user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
    if ($user_data = mysqli_fetch_assoc($user_query)) {
        if ($user_data['role'] === 'admin') {
            $_SESSION['user_id'] = $user_data['id'];
            $_SESSION['role'] = $user_data['role'];
            $_SESSION['name'] = $user_data['name'];
            $_SESSION['email'] = $user_data['email'];
        }
    }
}

// Check authorization - allow if it's the same user or if user is admin
$posted_user_id = intval($_POST['user_id'] ?? 0);
$session_user_id = intval($_SESSION['user_id'] ?? 0);

if (!$posted_user_id || (!$session_user_id || ($session_user_id != $posted_user_id && $_SESSION['role'] !== 'admin'))) {
    $response['message'] = "Unauthorized access. Session: $session_user_id, Posted: $posted_user_id, Role: " . ($_SESSION['role'] ?? 'none');
    echo json_encode($response);
    exit();
}

$user_id = intval($_POST['user_id']);
$name    = trim($_POST['name']);
$email   = trim($_POST['email']);
$contact = trim($_POST['contact_number']);
$dob     = trim($_POST['date_of_birth']);
$trade   = trim($_POST['trade']);
$session = trim($_POST['session']);
$gender  = isset($_POST['gender']) ? trim($_POST['gender']) : '';

$total_students = isset($_POST['total_students']) ? intval($_POST['total_students']) : null;

// Check if teacher, only then update total_students
if ($total_students !== null && ($_SESSION['role'] == 'teacher' || $_SESSION['role'] == 'admin')) {
    $sql = "UPDATE users SET name=?, email=?, contact_number=?, date_of_birth=?, trade=?, session=?, gender=?, total_students=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssssssii', $name, $email, $contact, $dob, $trade, $session, $gender, $total_students, $user_id);
} else {
    $sql = "UPDATE users SET name=?, email=?, contact_number=?, date_of_birth=?, trade=?, session=?, gender=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssssssi', $name, $email, $contact, $dob, $trade, $session, $gender, $user_id);
}

if ($stmt->execute()) {
    $_SESSION['name'] = $name;
    $_SESSION['email'] = $email;
    $_SESSION['contact_number'] = $contact;
    $_SESSION['date_of_birth'] = $dob;
    $_SESSION['trade'] = $trade;
    $_SESSION['session'] = $session;
    $_SESSION['gender'] = $gender;
    if ($total_students !== null && $_SESSION['role'] == 'teacher') {
        $_SESSION['total_students'] = $total_students;
    }
    $stmt->close();
    $response['success'] = true;
    $response['message'] = "Profile updated successfully! 🎉";
    $response['data'] = [
        'name' => $name,
        'email' => $email,
        'contact_number' => $contact,
        'date_of_birth' => $dob,
        'trade' => $trade,
        'session' => $session,
        'gender' => $gender,
        'total_students' => $total_students
    ];
    echo json_encode($response);
} else {
    $stmt->close();
    $response['message'] = "Failed to update profile. Please try again.";
    echo json_encode($response);
}
exit();
?>