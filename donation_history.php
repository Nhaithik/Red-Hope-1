<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'blood_donation_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle log submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_donation'])) {
    $type = $conn->real_escape_string($_POST['donation_type']);
    $organ = isset($_POST['organ_donated']) ? $conn->real_escape_string($_POST['organ_donated']) : null;
    $bg = $conn->real_escape_string($_POST['blood_group']);
    $location = $conn->real_escape_string($_POST['location']);
    $date = $conn->real_escape_string($_POST['donation_date']);
    $units = (int) ($_POST['units'] ?? 1);
    $notes = $conn->real_escape_string($_POST['notes'] ?? '');

    $stmt = $conn->prepare("INSERT INTO donation_history (user_id, donation_type, organ_donated, blood_group, location, donation_date, units, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssls", $user_id, $type, $organ, $bg, $location, $date, $units, $notes);
    if ($stmt->execute()) {
        // Award points: blood = 500 pts, plasma/platelet = 500 pts
        $pts = 500;
        $conn->query("UPDATE users SET points = points + $pts WHERE id = $user_id");
        $notif_msg = "🎉 You earned +$pts points for logging a $type donation!";
        $conn->query("INSERT INTO notifications (user_id, message) VALUES ($user_id, '$notif_msg')");
        $success = "Donation logged successfully! You earned +$pts points 🎉";
    } else {
        $error = "Failed to log donation. Please try again.";
    }
    $stmt->close();
}

// Fetch user's donation history
$my_donations = [];
$res = $conn->query("SELECT * FROM donation_history WHERE user_id = $user_id ORDER BY donation_date DESC");
while ($row = $res->fetch_assoc()) {
    $my_donations[] = $row;
}

// Fetch user blood_group from users table
$bg_res = $conn->query("SELECT blood_group, email, points FROM users WHERE id = $user_id");
$user_data = $bg_res->fetch_assoc();
$user_bg = $user_data['blood_group'] ?? 'N/A';
$user_pts = $user_data['points'] ?? 0;

// Stats
$total_donations = count($my_donations);
$total_blood_units = 0;
$organ_donations = 0;
foreach ($my_donations as $d) {
    if ($d['donation_type'] === 'blood' || $d['donation_type'] === 'platelet' || $d['donation_type'] === 'plasma') {
        $total_blood_units += $d['units'];
    } elseif ($d['donation_type'] === 'organ') {
        $organ_donations++;
    }
}

$page_title = "Red Hope | Donation History";
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-header">
        <div>
            <h1><i class="fa-solid fa-timeline" style="color: var(--primary-color);"></i> My Donation History</h1>
            <p style="color: #666;">Track all your life-saving contributions.</p>
        </div>
        <button onclick="document.getElementById('logModal').style.display='flex'"
            style="background: var(--primary-color); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 15px rgba(231,76,60,0.3);">
            <i class="fa-solid fa-plus"></i> Log a Donation
        </button>
    </div>

    <?php if ($success): ?>
        <div
            style="background: rgba(46,204,113,0.1); border: 1px solid #2ecc71; color: #2ecc71; padding: 15px; border-radius: 10px;">
            <i class="fa-solid fa-circle-check"></i>
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <!-- Stats Row -->
    <div class="content-grid" style="grid-template-columns: repeat(4, 1fr);">
        <div class="glass-card" style="text-align: center; padding: 20px;">
            <div style="font-size: 2.5rem; font-weight: 800; color: var(--primary-color);">
                <?= $total_donations ?>
            </div>
            <div style="color: #888; font-size: 0.9rem; margin-top: 5px;">Total Donations</div>
        </div>
        <div class="glass-card" style="text-align: center; padding: 20px;">
            <div style="font-size: 2.5rem; font-weight: 800; color: #3498db;">
                <?= $total_blood_units ?>
            </div>
            <div style="color: #888; font-size: 0.9rem; margin-top: 5px;">Blood Units Donated</div>
        </div>
        <div class="glass-card" style="text-align: center; padding: 20px;">
            <div style="font-size: 2.5rem; font-weight: 800; color: #9b59b6;">
                <?= $organ_donations ?>
            </div>
            <div style="color: #888; font-size: 0.9rem; margin-top: 5px;">Other Donations</div>
        </div>
        <div class="glass-card" style="text-align: center; padding: 20px;">
            <div style="font-size: 2.5rem; font-weight: 800; color: #2ecc71;">
                <?= number_format($user_pts) ?>
            </div>
            <div style="color: #888; font-size: 0.9rem; margin-top: 5px;">Total Points</div>
        </div>
    </div>

    <!-- Donation Timeline -->
    <?php if (empty($my_donations)): ?>
        <div class="glass-card" style="text-align: center; padding: 50px;">
            <i class="fa-solid fa-heart-pulse" style="font-size: 3rem; color: var(--primary-color); opacity: 0.3;"></i>
            <h3 style="margin-top: 15px; color: #888;">No donations logged yet</h3>
            <p style="color: #aaa; margin-top: 8px;">Log your first donation above to start your journey as a lifesaver!</p>
        </div>
    <?php else: ?>
        <div style="position: relative; padding-left: 30px;">
            <!-- Timeline line -->
            <div
                style="position: absolute; left: 10px; top: 0; bottom: 0; width: 2px; background: linear-gradient(to bottom, var(--primary-color), rgba(231,76,60,0.1));">
            </div>

            <?php foreach ($my_donations as $d):
                $type_colors = ['blood' => '#e74c3c', 'organ' => '#9b59b6', 'platelet' => '#f39c12', 'plasma' => '#3498db'];
                $type_icons = ['blood' => 'fa-droplet', 'organ' => 'fa-heart', 'platelet' => 'fa-flask', 'plasma' => 'fa-vial'];
                $color = $type_colors[$d['donation_type']] ?? '#888';
                $icon = $type_icons[$d['donation_type']] ?? 'fa-syringe';
                $formatted_date = date("F j, Y", strtotime($d['donation_date']));
                ?>
                <div style="position: relative; margin-bottom: 25px;">
                    <!-- Dot -->
                    <div
                        style="position: absolute; left: -34px; top: 18px; width: 18px; height: 18px; border-radius: 50%; background: <?= $color ?>; border: 3px solid var(--bg-color); box-shadow: 0 0 0 3px <?= $color ?>40;">
                    </div>

                    <div class="glass-card" style="border-left: 3px solid <?= $color ?>; padding: 18px 22px;">
                        <div
                            style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px;">
                            <div>
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                                    <span
                                        style="background: <?= $color ?>20; color: <?= $color ?>; padding: 4px 10px; border-radius: 20px; font-weight: 600; font-size: 0.8rem; text-transform: uppercase;">
                                        <i class="fa-solid <?= $icon ?>"></i>
                                        <?= ucfirst($d['donation_type']) ?>
                                    </span>
                                    <span style="font-weight: 700; font-size: 1.1rem; color: <?= $color ?>;">
                                        <?= htmlspecialchars($d['blood_group']) ?>
                                    </span>
                                    <?php if ($d['organ_donated']): ?>
                                        <span style="font-size: 0.85rem; color: #888;">—
                                            <?= htmlspecialchars($d['organ_donated']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size: 0.9rem; color: var(--text-color); font-weight: 500;">
                                    <i class="fa-solid fa-location-dot" style="color: #888; width: 18px;"></i>
                                    <?= htmlspecialchars($d['location']) ?>
                                </div>
                                <?php if ($d['notes']): ?>
                                    <div style="font-size: 0.85rem; color: #888; font-style: italic; margin-top: 5px;">"
                                        <?= htmlspecialchars($d['notes']) ?>"
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div style="text-align: right; flex-shrink: 0;">
                                <div style="font-weight: 600;">
                                    <?= $formatted_date ?>
                                </div>
                                <div style="font-size: 0.8rem; color: #888; margin-top: 2px;">
                                    <?= $d['units'] ?> unit
                                    <?= $d['units'] > 1 ? 's' : '' ?>
                                </div>
                                <div style="font-size: 0.75rem; color: #2ecc71; font-weight: 600; margin-top: 5px;">+500 pts
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Log Donation Modal -->
<div id="logModal"
    style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
    <div class="glass-card"
        style="width: 90%; max-width: 520px; max-height: 90vh; overflow-y: auto; position: relative;">
        <button onclick="document.getElementById('logModal').style.display='none'"
            style="position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-color);">×</button>

        <h3 style="margin-bottom: 5px; color: var(--primary-color);"><i class="fa-solid fa-heart"></i> Log a Donation
        </h3>
        <p style="color: #888; font-size: 0.9rem; margin-bottom: 20px;">Record your donation to earn points and track
            your history.</p>

        <form method="POST" style="display: flex; flex-direction: column; gap: 15px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <label style="font-weight: 600; display: block; margin-bottom: 5px;">Donation Type *</label>
                    <select name="donation_type" id="donTypeSelect" onchange="toggleOrganLog()" required
                        style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color);">
                        <option value="blood">🩸 Blood</option>
                        <option value="organ">🩺 Organ Pledge</option>
                        <option value="platelet">🟡 Platelet</option>
                        <option value="plasma">💙 Plasma</option>
                    </select>
                </div>
                <div>
                    <label style="font-weight: 600; display: block; margin-bottom: 5px;">Blood Group *</label>
                    <select name="blood_group" required
                        style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color);">
                        <?php $bgs = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                        foreach ($bgs as $bg): ?>
                            <option value="<?= $bg ?>" <?= $bg === $user_bg ? 'selected' : '' ?>>
                                <?= $bg ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div id="organLogDiv" style="display: none;">
                <label style="font-weight: 600; display: block; margin-bottom: 5px;">Organ Donated</label>
                <input type="text" name="organ_donated" placeholder="e.g. Kidney, Liver, Cornea"
                    style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color);">
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px;">
                <div>
                    <label style="font-weight: 600; display: block; margin-bottom: 5px;">Hospital / Location *</label>
                    <input type="text" name="location" placeholder="e.g. City Medical Center" required
                        style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color);">
                </div>
                <div>
                    <label style="font-weight: 600; display: block; margin-bottom: 5px;">Units *</label>
                    <input type="number" name="units" value="1" min="1" max="10" required
                        style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color);">
                </div>
            </div>

            <div>
                <label style="font-weight: 600; display: block; margin-bottom: 5px;">Donation Date *</label>
                <input type="date" name="donation_date" value="<?= date('Y-m-d') ?>" required
                    style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color);">
            </div>

            <div>
                <label style="font-weight: 600; display: block; margin-bottom: 5px;">Notes <span
                        style="color: #888; font-weight: 400;">(optional)</span></label>
                <textarea name="notes" rows="2" placeholder="Any notes about this donation..."
                    style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color); resize: vertical;"></textarea>
            </div>

            <div
                style="background: rgba(46,204,113,0.1); border: 1px solid rgba(46,204,113,0.3); padding: 10px 15px; border-radius: 8px; font-size: 0.9rem; color: #2ecc71;">
                <i class="fa-solid fa-star"></i> You'll earn <strong>+500 points</strong> for logging this donation!
            </div>

            <button type="submit" name="log_donation"
                style="background: var(--primary-color); color: white; border: none; padding: 12px; border-radius: 8px; font-weight: 700; font-size: 1rem; cursor: pointer; box-shadow: 0 4px 15px rgba(231,76,60,0.3);">
                <i class="fa-solid fa-heart"></i> Log Donation
            </button>
        </form>
    </div>
</div>

<script>
    function toggleOrganLog() {
        var type = document.getElementById('donTypeSelect').value;
        document.getElementById('organLogDiv').style.display = type === 'organ' ? 'block' : 'none';
    }
<?php if ($success): ?>
            // Auto-close modal on success
            document.getElementById('logModal').style.display = 'none';
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>