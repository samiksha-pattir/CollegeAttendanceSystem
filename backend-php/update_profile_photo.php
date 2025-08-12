<?php
session_start();
header('Content-Type: application/json');
include 'db.php';
session_start();
$_SESSION['user_id'] = 54; // admin id
$response = ['success' => false, 'message' => 'Unknown error'];

if (!isset($_POST['user_id']) || $_SESSION['user_id'] != $_POST['user_id']) {
    $response['message'] = "Unauthorized access.";
    echo json_encode($response);
    exit();
}

$user_id = intval($_POST['user_id']);
$name    = trim($_POST['name']);
$email   = trim($_POST['email']);
$contact = trim($_POST['contact_number']);
$dob     = trim($_POST['date_of_birth']);
$trade   = isset($_POST['trade']) ? trim($_POST['trade']) : '';
$session = isset($_POST['session']) ? trim($_POST['session']) : '';
$gender  = isset($_POST['gender']) ? trim($_POST['gender']) : '';

$total_students = isset($_POST['total_students']) ? intval($_POST['total_students']) : null;

// Fix the logic here with parentheses!
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