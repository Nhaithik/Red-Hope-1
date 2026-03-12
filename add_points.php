<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit();
}

$servername = "localhost";
$username = "root"; // adjust as needed
$password = "";
$dbname = "blood_donation_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "DB connection failed"]);
    exit();
}

$user_id = $_SESSION['user_id'];
$activity = $_POST['activity'] ?? ''; // 'blood' or 'organ'
$points_to_add = 10; // Example: 10 points per donation

if (!in_array($activity, ['blood', 'organ'])) {
    echo json_encode(["status" => "error", "message" => "Invalid activity"]);
    exit();
}

// Check if user has rewards record
$stmt = $conn->prepare("SELECT points, milestones FROM user_rewards WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($current_points, $milestones);
if ($stmt->fetch()) {
    $stmt->close();
    $new_points = $current_points + $points_to_add;

    // Update milestones if needed
    $new_milestones = $milestones;
    // Example milestone unlocks
    if ($new_points >= 50 && stripos($milestones, "Bronze Donor") === false) {
        $new_milestones .= "Bronze Donor, ";
    }
    if ($new_points >= 100 && stripos($milestones, "Silver Donor") === false) {
        $new_milestones .= "Silver Donor, ";
    }

    $stmt = $conn->prepare("UPDATE user_rewards SET points = ?, milestones = ?, last_updated = NOW() WHERE user_id = ?");
    $stmt->bind_param("isi", $new_points, $new_milestones, $user_id);
    $stmt->execute();
    $stmt->close();
} else {
    $stmt->close();
    $stmt = $conn->prepare("INSERT INTO user_rewards (user_id, points, milestones) VALUES (?, ?, ?)");
    $empty_milestones = "";
    $stmt->bind_param("iis", $user_id, $points_to_add, $empty_milestones);
    $stmt->execute();
    $stmt->close();
}

// Also update donation count
if ($activity === 'blood') {
$stmt = $conn->prepare("UPDATE users SET blood_donations_count = blood_donations_count + 1 WHERE id = ?");
} else {
    $stmt = $conn->prepare("UPDATE users SET organ_donations_count = organ_donations_count + 1 WHERE id = ?");
}
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
} else {
    $conn->close();
    echo json_encode(["status" => "error", "message" => "Failed to update donation count"]);
    exit();
}

$conn->close();
echo json_encode(["status" => "success", "message" => "Points added successfully"]);
?>