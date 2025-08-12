<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';

// Helper to sanitize input
function sanitize($conn, $val) {
    return htmlspecialchars(mysqli_real_escape_string($conn, trim($val)));
}

// Gather & sanitize inputs
$fullName = sanitize($conn, $_POST['fullName'] ?? '');
$email = sanitize($conn, $_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirmPassword'] ?? '';
$contact = sanitize($conn, $_POST['contact'] ?? '');
$role = sanitize($conn, $_POST['role'] ?? '');
$trade = sanitize($conn, $_POST['trade'] ?? '');
$session = sanitize($conn, $_POST['session'] ?? '');
$gender = sanitize($conn, $_POST['gender'] ?? '');
$dob = sanitize($conn, $_POST['dob'] ?? '');
// Store only filename, not path!
$img_path = 'default-avatar.png';

// Get total_students only if role is Teacher
$total_students = 0;
if (strtolower($role) === 'teacher') {
    $total_students = isset($_POST['total_students']) ? intval($_POST['total_students']) : 0;
    if ($total_students < 1) {
        header("Location: ../frontend/registration.php?error=Please+enter+a+valid+number+of+students+(at+least+1)+for+Teacher");
        exit();
    }
}

// Validation: check required fields
if (!$fullName || !$email || !$password || !$confirmPassword || !$role || !$trade || !$session || !$gender || !$dob) {
    header("Location: ../frontend/registration.php?error=Please+fill+all+required+fields");
    exit();
}

// Email validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: ../frontend/registration.php?error=Invalid+email+format");
    exit();
}

// Contact number validation (optional field)
if ($contact && !preg_match('/^[0-9]{10,15}$/', $contact)) {
    header("Location: ../frontend/registration.php?error=Invalid+contact+number+format");
    exit();
}

// Password match
if ($password !== $confirmPassword) {
    header("Location: ../frontend/registration.php?error=Passwords+do+not+match");
    exit();
}

// Password length
if (strlen($password) < 6) {
    header("Location: ../frontend/registration.php?error=Password+must+be+at+least+6+characters");
    exit();
}

// Duplicate email check
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    header("Location: ../frontend/registration.php?error=Email+already+registered");
    exit();
}
$stmt->close();

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Prepare SQL for insert
$stmt = $conn->prepare(
    "INSERT INTO users 
    (name, email, password, contact_number, date_of_birth, role, trade, session, gender, img, total_students) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param(
    "ssssssssssi",
    $fullName, $email, $hashed_password, $contact, $dob,
    $role, $trade, $session, $gender, $img_path, $total_students
);

try {
    if ($stmt->execute()) {
        $stmt->close();
        header("Location: ../frontend/login.php?success=Registration+successful!+Please+login.");
        exit();
    } else {
        $stmt->close();
        header("Location: ../frontend/registration.php?error=Registration+failed.+Please+try+again.");
        exit();
    }
} catch (Exception $e) {
    header("Location: ../frontend/registration.php?error=Server+error.+Please+try+again.");
    exit();
}
?>