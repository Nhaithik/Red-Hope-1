<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$servername = "localhost";
$username = "root";      // Update if needed
$password = "";          // Update if needed
$dbname = "blood_donation_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

// --- Fetch user profile data ---
$query1 = "SELECT email, phone_number, age, gender, blood_group, available_for_blood, available_for_organ, medical_history, current_medications, allergies, notification_pref FROM users WHERE id = ?";
$stmt1 = $conn->prepare($query1);
$stmt1->bind_param("i", $user_id);
$stmt1->execute();
$stmt1->bind_result(
    $email,
    $phone_number,
    $age,
    $gender,
    $blood_group,
    $available_for_blood,
    $available_for_organ,
    $medical_history,
    $current_medications,
    $allergies,
    $notification_pref
);
$stmt1->fetch();
$stmt1->close();

// --- Fetch user rewards data ---
$query2 = "SELECT points, milestones FROM user_rewards WHERE user_id = ?";
$stmt2 = $conn->prepare($query2);
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$stmt2->bind_result($points, $milestones);
$stmt2->fetch();
$stmt2->close();

$page_title = "Red Hope | My Profile";
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-header" style="margin-bottom: 20px;">
        <div>
            <h1>My Profile</h1>
            <p style="color: #666;">View and manage your personal donation settings.</p>
        </div>
        <div>
            <a href="edit_profile.php"
                style="background: var(--primary-color); color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; box-shadow: 0 4px 10px rgba(231, 76, 60, 0.4);"><i
                    class="fa-solid fa-pen-to-square"></i> Edit Profile</a>
        </div>
    </div>

    <!-- Personal Info Card -->
    <div class="glass-card" style="margin-bottom: 25px;">
        <h3
            style="color: var(--primary-color); border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 20px;">
            <i class="fa-solid fa-address-card"></i> Personal Information
        </h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div><strong style="display:block; color: #888; font-size: 0.85rem;">Email</strong>
                <?= htmlspecialchars($email ?? 'N/A') ?></div>
            <div><strong style="display:block; color: #888; font-size: 0.85rem;">Phone Number</strong>
                <?= htmlspecialchars($phone_number ?? 'N/A') ?></div>
            <div><strong style="display:block; color: #888; font-size: 0.85rem;">Age</strong>
                <?= htmlspecialchars($age ?? 'N/A') ?></div>
            <div><strong style="display:block; color: #888; font-size: 0.85rem;">Gender</strong>
                <?= htmlspecialchars($gender ?? 'N/A') ?></div>
            <div><strong style="display:block; color: #888; font-size: 0.85rem;">Blood Group</strong> <span
                    style="font-weight: bold; color: #e74c3c;"><?= htmlspecialchars($blood_group ?? 'N/A') ?></span>
            </div>
        </div>
    </div>

    <!-- Medical Details Card -->
    <div class="glass-card" style="margin-bottom: 25px;">
        <h3
            style="color: #3498db; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 20px;">
            <i class="fa-solid fa-notes-medical"></i> Medical Details
        </h3>

        <div style="display: flex; gap: 40px; margin-bottom: 25px;">
            <div>
                <strong style="display:block; color: #888; font-size: 0.85rem;">Blood Donation Status</strong>
                <span style="font-weight: bold; color: <?= ($available_for_blood ?? 0) ? '#2ecc71' : '#e74c3c' ?>;">
                    <i class="fa-solid <?= ($available_for_blood ?? 0) ? 'fa-check' : 'fa-xmark' ?>"></i>
                    <?= ($available_for_blood ?? 0) ? "Available" : "Not Available" ?>
                </span>
            </div>
            <div>
                <strong style="display:block; color: #888; font-size: 0.85rem;">Organ/Tissue Pledge Status</strong>
                <span style="font-weight: bold; color: <?= ($available_for_organ ?? 0) ? '#2ecc71' : '#e74c3c' ?>;">
                    <i class="fa-solid <?= ($available_for_organ ?? 0) ? 'fa-check' : 'fa-xmark' ?>"></i>
                    <?= ($available_for_organ ?? 0) ? "Available" : "Not Available" ?>
                </span>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr; gap: 20px;">
            <div style="background: rgba(0,0,0,0.03); padding: 15px; border-radius: 8px;">
                <strong style="display:block; color: var(--text-color); margin-bottom: 5px;">Medical History</strong>
                <?= nl2br(htmlspecialchars($medical_history ?? 'None provided')) ?>
            </div>
            <div style="background: rgba(0,0,0,0.03); padding: 15px; border-radius: 8px;">
                <strong style="display:block; color: var(--text-color); margin-bottom: 5px;">Current
                    Medications</strong>
                <?= nl2br(htmlspecialchars($current_medications ?? 'None provided')) ?>
            </div>
            <div style="background: rgba(0,0,0,0.03); padding: 15px; border-radius: 8px;">
                <strong style="display:block; color: var(--text-color); margin-bottom: 5px;">Allergies</strong>
                <?= nl2br(htmlspecialchars($allergies ?? 'None provided')) ?>
            </div>
        </div>
    </div>

    <!-- Account Preferences Card -->
    <div class="glass-card">
        <h3
            style="color: #9b59b6; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 20px;">
            <i class="fa-solid fa-gear"></i> Account Preferences
        </h3>
        <div>
            <strong style="display:block; color: #888; font-size: 0.85rem;">Email Notifications</strong>
            <?= ($notification_pref ?? 0) ? "Enabled <i class='fa-solid fa-bell' style='color:#f1c40f;'></i>" : "Muted <i class='fa-solid fa-bell-slash'></i>" ?>
        </div>
    </div>

</div>

<?php include 'includes/footer.php'; ?>