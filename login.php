<?php
session_start();

$servername = "localhost";
$username = "root"; // adjust as needed
$password = "";     // adjust as needed
$dbname = "blood_donation_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$email = trim($_POST['email'] ?? '');
$password_plain = $_POST['password'] ?? '';

if (!$email || !$password_plain) {
    die("Please enter both email and password.");
}

$stmt = $conn->prepare("SELECT id, password_hash FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($id, $password_hash);

if ($stmt->fetch()) {
    if (password_verify($password_plain, $password_hash)) {
        $_SESSION['user_id'] = $id;
        header("Location: dashboard.php");
        exit();
    } else {
        echo "<p>Incorrect password. <a href='login.html'>Try again</a>.</p>";
    }
} else {
    echo "<p>No user found with this email. <a href='register.html'>Register now</a>.</p>";
}
$stmt->close();
$conn->close();
?>