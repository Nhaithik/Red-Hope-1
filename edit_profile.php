<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: login.html");
  exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "blood_donation_db";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $phone_number = trim($_POST['phone_number'] ?? '');
  $age = intval($_POST['age'] ?? 0);
  $gender = trim($_POST['gender'] ?? '');
  $blood_group = trim($_POST['blood_group'] ?? '');
  $available_for_blood = isset($_POST['available_for_blood']) ? 1 : 0;
  $available_for_organ = isset($_POST['available_for_organ']) ? 1 : 0;
  $medical_history = trim($_POST['medical_history'] ?? '');
  $current_medications = trim($_POST['current_medications'] ?? '');
  $allergies = trim($_POST['allergies'] ?? '');
  $notification_pref = isset($_POST['notification_pref']) ? 1 : 0;

  // Basic validation
  if (!$phone_number || !$age || !$gender || !$blood_group) {
    $error = "Phone number, age, gender, and blood group are required.";
  } else {
    $stmt = $conn->prepare("UPDATE users SET phone_number = ?, age = ?, gender = ?, blood_group = ?, available_for_blood = ?, available_for_organ = ?, medical_history = ?, current_medications = ?, allergies = ?, notification_pref = ? WHERE id = ?");
    $stmt->bind_param("sissiissiii", $phone_number, $age, $gender, $blood_group, $available_for_blood, $available_for_organ, $medical_history, $current_medications, $allergies, $notification_pref, $user_id);
    if ($stmt->execute()) {
      $success = "Profile updated successfully.";
    } else {
      $error = "Update failed: " . $stmt->error;
    }
    $stmt->close();
  }
}

// Fetch current profile data for form pre-fill
$stmt = $conn->prepare("SELECT email, phone_number, age, gender, blood_group, available_for_blood, available_for_organ, medical_history, current_medications, allergies, notification_pref FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($email, $phone_number, $age, $gender, $blood_group, $available_for_blood, $available_for_organ, $medical_history, $current_medications, $allergies, $notification_pref);
$stmt->fetch();
$stmt->close();

$page_title = "Red Hope | Edit Profile";
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
  <div class="top-header" style="margin-bottom: 20px;">
    <div>
      <h1>Edit Profile</h1>
      <p style="color: #666;">Update your personal info, medical history, and availability.</p>
    </div>
    <div>
      <a href="profile.php"
        style="color: var(--text-color); padding: 10px 20px; border-radius: 8px; text-decoration: none; border: 1px solid var(--border-color); background: var(--glass-bg);"><i
          class="fa-solid fa-arrow-left"></i> Back</a>
    </div>
  </div>

  <div class="glass-card" style="max-width: 800px; margin: 0 auto;">
    <?php if (!empty($error)): ?>
      <div
        style="background: rgba(231, 76, 60, 0.1); border-left: 4px solid #e74c3c; padding: 15px; margin-bottom: 20px; border-radius: 4px; color: #e74c3c;">
        <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
      </div>
    <?php elseif (!empty($success)): ?>
      <div
        style="background: rgba(46, 204, 113, 0.1); border-left: 4px solid #2ecc71; padding: 15px; margin-bottom: 20px; border-radius: 4px; color: #2ecc71;">
        <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="" style="display: flex; flex-direction: column; gap: 20px;">

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <!-- Left Column -->
        <div style="display: flex; flex-direction: column; gap: 15px;">
          <div>
            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Email (Unchangeable)</label>
            <input type="text" value="<?= htmlspecialchars($email) ?>" readonly
              style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: rgba(0,0,0,0.05); color: #888; cursor: not-allowed;" />
          </div>

          <div>
            <label for="phone_number" style="display: block; margin-bottom: 5px; font-weight: 600;">Phone Number</label>
            <input type="text" name="phone_number" id="phone_number" value="<?= htmlspecialchars($phone_number) ?>"
              required pattern="[0-9]{10,15}" title="Enter valid phone number"
              style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: rgba(255,255,255,0.5); color: var(--text-color);" />
          </div>

          <div>
            <label for="age" style="display: block; margin-bottom: 5px; font-weight: 600;">Age</label>
            <input type="number" name="age" id="age" min="18" max="100" value="<?= htmlspecialchars($age) ?>" required
              style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: rgba(255,255,255,0.5); color: var(--text-color);" />
          </div>
        </div>

        <!-- Right Column -->
        <div style="display: flex; flex-direction: column; gap: 15px;">
          <div>
            <label for="gender" style="display: block; margin-bottom: 5px; font-weight: 600;">Gender</label>
            <select name="gender" id="gender" required
              style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: rgba(255,255,255,0.5); color: var(--text-color);">
              <option value="">--Select--</option>
              <option value="Male" <?= $gender === "Male" ? "selected" : "" ?>>Male</option>
              <option value="Female" <?= $gender === "Female" ? "selected" : "" ?>>Female</option>
              <option value="Other" <?= $gender === "Other" ? "selected" : "" ?>>Other</option>
            </select>
          </div>

          <div>
            <label for="blood_group" style="display: block; margin-bottom: 5px; font-weight: 600;">Blood Group</label>
            <select name="blood_group" id="blood_group" required
              style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: rgba(255,255,255,0.5); color: var(--text-color);">
              <option value="">--Select--</option>
              <?php
              $blood_groups = ["A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"];
              foreach ($blood_groups as $bg) {
                $selected = ($blood_group === $bg) ? "selected" : "";
                echo "<option value='$bg' $selected>$bg</option>";
              }
              ?>
            </select>
          </div>

          <div style="background: rgba(0,0,0,0.03); padding: 15px; border-radius: 8px;">
            <label
              style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin-bottom: 10px; font-weight: 500;">
              <input type="checkbox" name="available_for_blood" <?= $available_for_blood ? "checked" : "" ?>
                style="width: 18px; height: 18px; accent-color: var(--primary-color);" /> Available for Blood Donation
            </label>
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 500;">
              <input type="checkbox" name="available_for_organ" <?= $available_for_organ ? "checked" : "" ?>
                style="width: 18px; height: 18px; accent-color: var(--primary-color);" /> Available for Organ Donation
            </label>
          </div>
        </div>
      </div>

      <!-- Full Width Medical Fields -->
      <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 10px 0;">

      <div>
        <label for="medical_history" style="display: block; margin-bottom: 5px; font-weight: 600;">Medical
          History</label>
        <textarea name="medical_history" id="medical_history"
          style="width: 100%; height: 80px; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: rgba(255,255,255,0.5); color: var(--text-color); resize: vertical;"><?= htmlspecialchars($medical_history) ?></textarea>
      </div>

      <div>
        <label for="current_medications" style="display: block; margin-bottom: 5px; font-weight: 600;">Current
          Medications</label>
        <textarea name="current_medications" id="current_medications"
          style="width: 100%; height: 80px; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: rgba(255,255,255,0.5); color: var(--text-color); resize: vertical;"><?= htmlspecialchars($current_medications) ?></textarea>
      </div>

      <div>
        <label for="allergies" style="display: block; margin-bottom: 5px; font-weight: 600;">Allergies</label>
        <textarea name="allergies" id="allergies"
          style="width: 100%; height: 80px; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: rgba(255,255,255,0.5); color: var(--text-color); resize: vertical;"><?= htmlspecialchars($allergies) ?></textarea>
      </div>

      <div style="background: rgba(0,0,0,0.03); padding: 15px; border-radius: 8px;">
        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 500;">
          <input type="checkbox" name="notification_pref" <?= $notification_pref ? "checked" : "" ?>
            style="width: 18px; height: 18px; accent-color: var(--primary-color);" /> Receive Important Donation
          Notifications & Alerts
        </label>
      </div>

      <button type="submit"
        style="background: var(--primary-color); color: white; border: none; padding: 15px; border-radius: 8px; font-weight: 600; font-size: 1rem; cursor: pointer; transition: transform 0.2s; box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3); margin-top: 10px;">
        <i class="fa-solid fa-floppy-disk"></i> Save Changes
      </button>
    </form>
  </div>
</div>

<?php include 'includes/footer.php'; ?>