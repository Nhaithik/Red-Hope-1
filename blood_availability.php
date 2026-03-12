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

// Handle admin update of availability (for any logged-in user for now)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_availability'])) {
    $bg = $conn->real_escape_string($_POST['blood_group']);
    $avail = (int) $_POST['units_available'];
    $needed = (int) $_POST['units_needed'];
    $conn->query("UPDATE blood_availability SET units_available = $avail, units_needed = $needed WHERE blood_group = '$bg'");
}

// Fetch availability data
$availability = [];
$res = $conn->query("SELECT * FROM blood_availability ORDER BY blood_group");
while ($row = $res->fetch_assoc()) {
    $availability[] = $row;
}

// Get user's blood group
$user_res = $conn->query("SELECT blood_group FROM users WHERE id = $user_id");
$user_bg = $user_res->fetch_assoc()['blood_group'] ?? null;

// Summary stats
$critical_count = 0;
$adequate_count = 0;
foreach ($availability as $a) {
    $ratio = $a['units_available'] > 0 ? ($a['units_available'] / max(1, $a['units_needed'])) : 0;
    if ($ratio < 0.5)
        $critical_count++;
    else
        $adequate_count++;
}

$page_title = "Red Hope | Blood Availability";
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<style>
    .blood-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 20px;
    }

    .blood-card {
        border-radius: 20px;
        padding: 25px;
        text-align: center;
        position: relative;
        overflow: hidden;
        transition: transform 0.3s;
    }

    .blood-card:hover {
        transform: translateY(-5px);
    }

    .blood-type-label {
        font-size: 3rem;
        font-weight: 900;
        line-height: 1;
        margin-bottom: 8px;
    }

    .status-bar-bg {
        background: rgba(0, 0, 0, 0.1);
        border-radius: 20px;
        height: 10px;
        margin: 12px 0;
        overflow: hidden;
    }

    .status-bar-fill {
        height: 100%;
        border-radius: 20px;
        transition: width 0.8s cubic-bezier(.25, .46, .45, .94);
    }

    .status-tag {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
</style>

<div class="main-content">
    <div class="top-header">
        <div>
            <h1><i class="fa-solid fa-droplet" style="color: var(--primary-color);"></i> Blood Availability</h1>
            <p style="color: #666;">Real-time blood group stock levels across our network.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <div
                style="background: rgba(231,76,60,0.1); border: 1px solid rgba(231,76,60,0.3); color: #e74c3c; padding: 8px 16px; border-radius: 20px; font-weight: 600; font-size: 0.85rem;">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <?= $critical_count ?> Critical
            </div>
            <div
                style="background: rgba(46,204,113,0.1); border: 1px solid rgba(46,204,113,0.3); color: #2ecc71; padding: 8px 16px; border-radius: 20px; font-weight: 600; font-size: 0.85rem;">
                <i class="fa-solid fa-circle-check"></i>
                <?= $adequate_count ?> Adequate
            </div>
        </div>
    </div>

    <!-- Your Blood Group Highlight -->
    <?php if ($user_bg): ?>
        <?php $my_data = array_filter($availability, fn($a) => $a['blood_group'] === $user_bg);
        $my_data = array_values($my_data); ?>
        <?php if (!empty($my_data)): ?>
            <?php $my = $my_data[0];
            $ratio = $my['units_available'] / max(1, $my['units_needed']); ?>
            <div
                style="background: linear-gradient(135deg, rgba(231,76,60,0.1), rgba(231,76,60,0.05)); border: 1px solid rgba(231,76,60,0.3); padding: 15px 20px; border-radius: 12px; display: flex; align-items: center; gap: 15px;">
                <div style="font-size: 2.5rem; font-weight: 900; color: var(--primary-color); min-width: 60px;">
                    <?= $user_bg ?>
                </div>
                <div style="flex: 1;">
                    <strong>Your blood group (
                        <?= $user_bg ?>)
                    </strong> —
                    <?php if ($ratio < 0.5): ?>
                        <span style="color: #e74c3c; font-weight: 600;">⚠️ CRITICALLY LOW — your donation is urgently needed!</span>
                    <?php elseif ($ratio < 1): ?>
                        <span style="color: #e67e22; font-weight: 600;">Stock is below the needed level</span>
                    <?php else: ?>
                        <span style="color: #2ecc71; font-weight: 600;">Stock is currently adequate ✓</span>
                    <?php endif; ?>
                    <div style="font-size: 0.85rem; color: #888; margin-top: 3px;">
                        <?= $my['units_available'] ?> units available /
                        <?= $my['units_needed'] ?> units needed
                    </div>
                </div>
                <?php if ($ratio < 1): ?>
                    <a href="donation_history.php"
                        style="background: var(--primary-color); color: white; text-decoration: none; padding: 10px 16px; border-radius: 8px; font-weight: 600; white-space: nowrap; font-size: 0.9rem;">Log
                        a Donation</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Blood Group Cards -->
    <div class="blood-grid">
        <?php foreach ($availability as $a):
            $ratio = $a['units_available'] / max(1, $a['units_needed']);
            $pct = min(100, round($ratio * 100));

            if ($pct < 30) {
                $card_bg = 'rgba(231, 76, 60, 0.12)';
                $accent = '#e74c3c';
                $status_label = '🔴 CRITICAL';
                $status_bg = 'rgba(231,76,60,0.2)';
            } elseif ($pct < 70) {
                $card_bg = 'rgba(230, 126, 34, 0.12)';
                $accent = '#e67e22';
                $status_label = '🟠 LOW';
                $status_bg = 'rgba(230,126,34,0.2)';
            } elseif ($pct < 100) {
                $card_bg = 'rgba(241, 196, 15, 0.12)';
                $accent = '#f1c40f';
                $status_label = '🟡 MODERATE';
                $status_bg = 'rgba(241,196,15,0.2)';
            } else {
                $card_bg = 'rgba(46, 204, 113, 0.12)';
                $accent = '#2ecc71';
                $status_label = '🟢 ADEQUATE';
                $status_bg = 'rgba(46,204,113,0.2)';
            }
            $is_mine = ($a['blood_group'] === $user_bg);
            ?>
            <div class="glass-card blood-card"
                style="border-top: 4px solid <?= $accent ?>; position: relative; <?= $is_mine ? "box-shadow: 0 0 0 2px {$accent}40;" : '' ?>">
                <?php if ($is_mine): ?>
                    <div
                        style="position: absolute; top: 12px; right: 12px; font-size: 0.7rem; background: <?= $accent ?>20; color: <?= $accent ?>; padding: 3px 8px; border-radius: 12px; font-weight: 700;">
                        YOURS</div>
                <?php endif; ?>

                <div class="blood-type-label" style="color: <?= $accent ?>;">
                    <?= htmlspecialchars($a['blood_group']) ?>
                </div>
                <div class="status-tag" style="background: <?= $status_bg ?>; color: <?= $accent ?>;">
                    <?= $status_label ?>
                </div>

                <div class="status-bar-bg" style="margin-top: 15px;">
                    <div class="status-bar-fill" style="width: <?= $pct ?>%; background: <?= $accent ?>;"></div>
                </div>

                <div
                    style="display: flex; justify-content: space-between; font-size: 0.8rem; color: #888; margin-bottom: 10px;">
                    <span>
                        <?= $a['units_available'] ?> available
                    </span>
                    <span>
                        <?= $a['units_needed'] ?> needed
                    </span>
                </div>

                <div style="font-size: 1.5rem; font-weight: 700; color: <?= $accent ?>;">
                    <?= $pct ?>%
                </div>
                <div style="font-size: 0.75rem; color: #aaa;">of target met</div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Legend -->
    <div class="glass-card" style="padding: 20px;">
        <h4 style="margin-bottom: 15px; color: var(--text-color);"><i class="fa-solid fa-circle-info"
                style="color: var(--primary-color);"></i> How Availability is Calculated</h4>
        <p style="font-size: 0.9rem; color: #888; line-height: 1.6;">
            The percentage shown represents how much of the <strong>target (needed) units</strong> are currently in
            stock. A level below 30% is considered <strong style="color:#e74c3c;">Critical</strong>, below 70% is
            <strong style="color:#e67e22;">Low</strong>, below 100% is <strong style="color:#f1c40f;">Moderate</strong>,
            and at or above 100% is <strong style="color:#2ecc71;">Adequate</strong>.
            Levels are updated in real time as donors log donations through the platform.
        </p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>