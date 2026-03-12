<?php
$servername = "localhost";
$username = "root"; // adjust as needed
$password = "";     // adjust as needed
$dbname = "blood_donation_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$email = trim($_POST['email'] ?? '');
$phone_number = trim($_POST['phone_number'] ?? '');
$password_plain = $_POST['password'] ?? '';
$age = intval($_POST['age'] ?? 0);
$gender = trim($_POST['gender'] ?? '');
$blood_group = trim($_POST['blood_group'] ?? '');
$available_for_blood = isset($_POST['available_for_blood']) ? 1 : 0;
$available_for_organ = isset($_POST['available_for_organ']) ? 1 : 0;

if (!$email || !$phone_number || !$password_plain || !$age || !$gender || !$blood_group) {
    die("All fields except donation checkboxes are required.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Invalid email format.");
}

if (!preg_match('/^[0-9]{10,15}$/', $phone_number)) {
    die("Invalid phone number format.");
}

if ($age < 18) {
    die("Minimum age for registration is 18.");
}

if (strlen($password_plain) < 6) {
    die("Password must be at least 6 characters.");
}

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    die("Email is already registered.");
}
$stmt->close();

$password_hash = password_hash($password_plain, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users 
  (email, phone_number, password_hash, age, gender, blood_group, available_for_blood, available_for_organ) 
  VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssissii", $email, $phone_number, $password_hash, $age, $gender, $blood_group, $available_for_blood, $available_for_organ);

if ($stmt->execute()) {
    echo "<script>alert('Registration successful. Please login.'); window.location.href='login.html';</script>";
} else {
    echo "Error: " . htmlspecialchars($stmt->error);
}
$stmt->close();
$conn->close();
?>