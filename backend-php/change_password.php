<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

// Check if user is logged in (admin, teacher, or student)
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please login first.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validate inputs
if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit();
}

if ($new_password !== $confirm_password) {
    echo json_encode(['success' => false, 'message' => 'New password and confirm password do not match.']);
    exit();
}

if (strlen($new_password) < 6) {
    echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters long.']);
    exit();
}

// Get current user data
$stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

// Verify current password
if (!password_verify($current_password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
    exit();
}

// Hash new password
$hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);

// Update password
$update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
$update_stmt->bind_param("si", $hashed_new_password, $user_id);

if ($update_stmt->execute()) {
    $update_stmt->close();
    echo json_encode(['success' => true, 'message' => 'Password changed successfully.']);
} else {
    $update_stmt->close();
    echo json_encode(['success' => false, 'message' => 'Failed to update password. Please try again.']);
}

$conn->close();
?>
