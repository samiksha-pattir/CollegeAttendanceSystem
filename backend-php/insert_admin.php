<?php
include 'db.php'; // apni db connection file

$name = "Ms. Naina Nagpal";
$email = "naina.nagpal@dgt.gov.in";
$plainPassword = "admin@123";
$role = "admin";
$contact_number = "9999999999";
$date_of_birth = "1980-01-01"; // yyyy-mm-dd format
$trade = "none";
$session = "2024-25";
$gender = "female";

// Password hash karo
$hash = password_hash($plainPassword, PASSWORD_DEFAULT);

$sql = "INSERT INTO users (name, email, password, role, contact_number, date_of_birth, trade, session, gender) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssssss", $name, $email, $hash, $role, $contact_number, $date_of_birth, $trade, $session, $gender);

if ($stmt->execute()) {
    echo "Admin inserted successfully!";
} else {
    echo "Error: " . $conn->error;
}
$stmt->close();
$conn->close();
?>