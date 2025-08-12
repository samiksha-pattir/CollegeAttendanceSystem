<?php
$servername = "localhost";
$username = "samiksha";
$password = ""; 
$database = "college_attendance"; //  actual DB name

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
