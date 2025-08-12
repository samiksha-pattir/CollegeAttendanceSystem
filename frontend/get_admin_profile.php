<?php
require_once("../backend-php/db.php");
header('Content-Type: application/json');

$result = mysqli_query($conn, "SELECT * FROM users WHERE role='admin' LIMIT 1");
if($row = mysqli_fetch_assoc($result)){
    echo json_encode([
        "success" => true,
        "id" => $row['id'],
        "name" => $row['name'],
        "email" => $row['email'],
        "contact_number" => $row['contact_number'],
        "date_of_birth" => $row['date_of_birth'],
        "gender" => $row['gender'],
        "img" => $row['img'],
        "trade" => $row['trade'],
        "session" => $row['session']
    ]);
} else {
    echo json_encode(["success" => false, "msg" => "Admin not found"]);
}
?>