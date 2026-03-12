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

// Fetch basic user profile info including points
$stmt = $conn->prepare("SELECT email, blood_group, available_for_blood, available_for_organ, points FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($email, $blood_group, $available_for_blood, $available_for_organ, $user_points);
$stmt->fetch();
$stmt->close();

// Fallback points check if null
$user_points = $user_points ?? 0;

// Fetch Unread Notifications
$unread_notifications = 0;
$stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = false");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($unread_notifications);
$stmt->fetch();
$stmt->close();

// Fetch Latest Notifications
$notifications_html = '<div class="notification-empty">No notifications yet.</div>';
$stmt = $conn->prepare("SELECT message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notif_res = $stmt->get_result();
if ($notif_res->num_rows > 0) {
    $notifications_html = '';
    while ($notif = $notif_res->fetch_assoc()) {
        $unread_class = $notif['is_read'] ? '' : 'unread';
        $time_formatted = date("M j, g:i a", strtotime($notif['created_at']));
        $notifications_html .= "
            <a href='#' class='notification-item {$unread_class}'>
                <div style='margin-bottom: 5px;'>{$notif['message']}</div>
                <div style='font-size: 0.75rem; color: #888;'>{$time_formatted}</div>
            </a>
        ";
    }
}
$stmt->close();

// Fetch most critical pending urgent request
$urgent_banner = null;
$ur = $conn->query("SELECT blood_group, hospital, city, urgency, user_id FROM urgent_requests WHERE is_active = true ORDER BY FIELD(urgency, 'critical', 'high', 'moderate'), created_at DESC LIMIT 1");
if ($ur && $ur->num_rows > 0) {
    $urgent_banner = $ur->fetch_assoc();
}

// Set Page variables for header
$page_title = "Red Hope | Professional Dashboard";
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-header">
        <div>
            <h1>Overview</h1>
            <p style="color: #666;">Welcome back! Here's your blood donation summary.</p>
        </div>
        <div class="user-profile-widget" style="display: flex; align-items: center; gap: 15px;">
            <!-- Notifications Bell -->
            <div class="notification-wrapper" onclick="toggleNotifications(event)">
                <i class="fa-solid fa-bell notification-bell"></i>
                <?php if ($unread_notifications > 0): ?>
                    <span class="notification-badge"><?= $unread_notifications ?></span>
                <?php endif; ?>

                <!-- Dropdown -->
                <div class="notification-dropdown" id="notifDropdown">
                    <div class="notification-header">
                        <span>Notifications</span>
                        <?php if ($unread_notifications > 0): ?>
                            <a href="#" style="font-size: 0.8rem; color: var(--primary-color); text-decoration: none;">Mark
                                all read</a>
                        <?php endif; ?>
                    </div>
                    <?= $notifications_html ?>
                </div>
            </div>

            <!-- Points Display -->
            <div
                style="background: rgba(46, 204, 113, 0.1); color: #2ecc71; padding: 5px 10px; border-radius: 20px; font-weight: 600; font-size: 0.9rem; border: 1px solid rgba(46, 204, 113, 0.3); display: flex; align-items: center; gap: 5px; margin-right: 5px; cursor: pointer;">
                <i class="fa-solid fa-star"></i> <?= number_format($user_points) ?> Pts
            </div>

            <div style="display: flex; flex-direction: column; text-align: right; margin-right: 5px;">
                <span
                    style="font-weight: 600; font-size: 0.9rem;"><?= htmlspecialchars(explode('@', $email)[0] ?? 'User') ?></span>
                <span style="font-size: 0.8rem; color: #888;">Active Status</span>
            </div>
            <div class="user-avatar"><i class="fa-solid fa-user"></i></div>
        </div>
    </div>

    <!-- Urgent Request Alert Banner -->
    <?php if ($urgent_banner): ?>
        <?php $urg_color = $urgent_banner['urgency'] === 'critical' ? '#e74c3c' : ($urgent_banner['urgency'] === 'high' ? '#e67e22' : '#f1c40f'); ?>
        <div
            style="background: <?= $urg_color ?>15; border: 1px solid <?= $urg_color ?>50; border-left: 4px solid <?= $urg_color ?>; padding: 15px 20px; border-radius: 12px; display: flex; align-items: center; gap: 15px; animation: pulse-border 2s infinite;">
            <div style="font-size: 2rem; color: <?= $urg_color ?>; animation: shake 0.6s infinite;">⚠️</div>
            <div style="flex: 1;">
                <strong
                    style="color: <?= $urg_color ?>; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px;"><?= $urgent_banner['urgency'] ?>
                    Urgent Need</strong>
                <div style="font-weight: 600; font-size: 1.05rem; margin: 2px 0;">
                    <span
                        style="color: <?= $urg_color ?>; font-size: 1.3rem; font-weight: 800;"><?= htmlspecialchars($urgent_banner['blood_group']) ?></span>
                    blood needed at <?= htmlspecialchars($urgent_banner['hospital']) ?>,
                    <?= htmlspecialchars($urgent_banner['city']) ?>
                </div>
                <div style="font-size: 0.85rem; color: #888;">Can you help? Tap to view all urgent requests.</div>
            </div>
            <a href="urgent_requests.php"
                style="background: <?= $urg_color ?>; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 700; white-space: nowrap; box-shadow: 0 4px 15px <?= $urg_color ?>40;">
                View All Requests
            </a>
        </div>
    <?php endif; ?>

    <!-- Metrics Cards Row -->
    <div class="content-grid">
        <div class="glass-card">
            <h3 style="color: var(--primary-color); margin-bottom: 10px;">
                <i class="fa-solid fa-flask"></i> My Blood Type
            </h3>
            <p style="font-size: 2rem; font-weight: 700;"><?= htmlspecialchars($blood_group ?? 'N/A') ?></p>
            <p style="color: #666; font-size: 0.9rem; margin-top: 5px;">Matches nearby requests</p>
        </div>

        <div class="glass-card">
            <h3 style="color: #3498db; margin-bottom: 10px;">
                <i class="fa-solid fa-award"></i> Reward Points
            </h3>
            <p style="font-size: 2rem; font-weight: 700;"><?= htmlspecialchars($points ?? 0) ?></p>
            <p style="color: #666; font-size: 0.9rem; margin-top: 5px;">Milestone:
                <?= htmlspecialchars($milestones ?? 'None') ?>
            </p>
        </div>

        <div class="glass-card">
            <h3 style="color: #2ecc71; margin-bottom: 10px;">
                <i class="fa-solid fa-check-double"></i> Availabilities
            </h3>
            <p style="margin-top: 5px;">
                <i class="fa-solid <?= ($available_for_blood ?? 0) ? 'fa-check' : 'fa-xmark' ?>"
                    style="color: <?= ($available_for_blood ?? 0) ? '#2ecc71' : '#e74c3c' ?>"></i> Blood Donation
            </p>
            <p style="margin-top: 5px;">
                <i class="fa-solid <?= ($available_for_organ ?? 0) ? 'fa-check' : 'fa-xmark' ?>"
                    style="color: <?= ($available_for_organ ?? 0) ? '#2ecc71' : '#e74c3c' ?>"></i> Organ Donation
            </p>
        </div>
    </div>

    <!-- Chart Row -->
    <div class="glass-card" style="margin-top: 20px;">
        <h3>Donation Activity (This Year)</h3>
        <canvas id="donationChart" height="80"></canvas>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('donationChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Community Donations Initiated',
                data: [12, 19, 15, 25, 22, 30],
                borderColor: '#e74c3c',
                backgroundColor: 'rgba(231, 76, 60, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(200, 200, 200, 0.1)' } },
                x: { grid: { display: false } }
            }
        }
    });
</script>

<?php include 'includes/footer.php'; ?>