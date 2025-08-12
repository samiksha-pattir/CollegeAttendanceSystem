<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['loginEmail']);
    $password = $_POST['loginPassword'];

    // Basic validation
    if (empty($email) || empty($password)) {
        header("Location: ../frontend/login.php?error=" . urlencode('Please enter both email and password.'));
        exit();
    }

    // Prepare SQL to prevent SQL injection
    $stmt = $conn->prepare("SELECT id, name, email, password, role, trade, session, contact_number, date_of_birth, img FROM users WHERE email = ?");
    if (!$stmt) {
        header("Location: ../frontend/login.php?error=" . urlencode('Database error.'));
        exit();
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($id, $name, $email_db, $hashed_password, $role, $trade, $session, $contact, $dob, $img);
        $stmt->fetch();

        // Secure password check
        if (password_verify($password, $hashed_password)) {
            // Set all necessary session variables for the dashboard
            $_SESSION['user_id'] = $id;
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email_db;
            $_SESSION['role'] = $role;
            $_SESSION['trade'] = $trade;
            $_SESSION['session'] = $session;
            $_SESSION['contact_number'] = $contact;
            $_SESSION['date_of_birth'] = $dob;
            $_SESSION['img'] = $img;

            // Redirect based on role
            if ($role == "student") {
                header("Location: ../frontend/student_dashboard.php");
            } elseif ($role == "teacher") {
                header("Location: ../frontend/teacher_dashboard.php");
            } elseif ($role == "admin") {
                header("Location: ../frontend/admin_dashboard.php");
            } else {
                header("Location: ../frontend/login.php?error=" . urlencode('Unknown role.'));
            }
            exit();
        } else {
            header("Location: ../frontend/login.php?error=" . urlencode('Incorrect password.'));
            exit();
        }
    } else {
        header("Location: ../frontend/login.php?error=" . urlencode('No account found with that email.'));
        exit();
    }
    $stmt->close();
} else {
    header("Location: ../frontend/login.php?error=" . urlencode('Invalid request.'));
    exit();
}
?>