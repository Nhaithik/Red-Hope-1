<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

// Database Connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "blood_donation_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch Top 10 Users by Points
$leaderboard = [];
$stmt = $conn->prepare("SELECT id, email, points FROM users ORDER BY points DESC, id ASC LIMIT 10");
$stmt->execute();
$result = $stmt->get_result();
$rank = 1;
while ($row = $result->fetch_assoc()) {
    $row['rank'] = $rank++;
    // Extract name from email for display purposes
    $row['display_name'] = ucfirst(explode('@', $row['email'])[0]);
    $leaderboard[] = $row;
}
$stmt->close();

$page_title = "Red Hope | Top Donors";
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-header">
        <div>
            <h1>Top Donors Leaderboard</h1>
            <p style="color: #666;">Recognizing our community's most generous lifesavers.</p>
        </div>
    </div>

    <!-- Podium Section for Top 3 -->
    <?php if (count($leaderboard) >= 3): ?>
        <div
            style="display: flex; justify-content: center; align-items: flex-end; gap: 20px; margin: 40px 0; height: 250px;">
            <!-- 2nd Place -->
            <div style="display: flex; flex-direction: column; align-items: center; width: 150px;">
                <div
                    style="background: var(--card-bg); padding: 10px; border-radius: 50%; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border: 3px solid #bdc3c7; height: 80px; width: 80px; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: #7f8c8d; margin-bottom: -15px; z-index: 2;">
                    <i class="fa-solid fa-medal"></i>
                </div>
                <div
                    style="background: linear-gradient(to top, rgba(189, 195, 199, 0.3), rgba(189, 195, 199, 0.1)); border: 1px solid rgba(189, 195, 199, 0.5); width: 100%; height: 120px; border-radius: 15px 15px 0 0; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <strong style="color: var(--text-color);">
                        <?= htmlspecialchars($leaderboard[1]['display_name']) ?>
                    </strong>
                    <span style="color: #2ecc71; font-weight: bold;">
                        <?= number_format($leaderboard[1]['points']) ?> Pts
                    </span>
                </div>
            </div>

            <!-- 1st Place -->
            <div style="display: flex; flex-direction: column; align-items: center; width: 180px;">
                <div
                    style="background: var(--card-bg); padding: 10px; border-radius: 50%; box-shadow: 0 4px 20px rgba(241, 196, 15, 0.4); border: 4px solid #f1c40f; height: 100px; width: 100px; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: #f39c12; margin-bottom: -20px; z-index: 2;">
                    <i class="fa-solid fa-crown"></i>
                </div>
                <div
                    style="background: linear-gradient(to top, rgba(241, 196, 15, 0.3), rgba(241, 196, 15, 0.1)); border: 1px solid rgba(241, 196, 15, 0.5); width: 100%; height: 160px; border-radius: 15px 15px 0 0; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <strong style="font-size: 1.2rem; color: var(--text-color);">
                        <?= htmlspecialchars($leaderboard[0]['display_name']) ?>
                    </strong>
                    <span style="color: #2ecc71; font-weight: bold; font-size: 1.1rem;">
                        <?= number_format($leaderboard[0]['points']) ?> Pts
                    </span>
                </div>
            </div>

            <!-- 3rd Place -->
            <div style="display: flex; flex-direction: column; align-items: center; width: 150px;">
                <div
                    style="background: var(--card-bg); padding: 10px; border-radius: 50%; box-shadow: 0 4px 15px rgba(211, 84, 0, 0.2); border: 3px solid #cd7f32; height: 80px; width: 80px; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: #e67e22; margin-bottom: -15px; z-index: 2;">
                    <i class="fa-solid fa-medal"></i>
                </div>
                <div
                    style="background: linear-gradient(to top, rgba(205, 127, 50, 0.3), rgba(205, 127, 50, 0.1)); border: 1px solid rgba(205, 127, 50, 0.5); width: 100%; height: 100px; border-radius: 15px 15px 0 0; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <strong style="color: var(--text-color);">
                        <?= htmlspecialchars($leaderboard[2]['display_name']) ?>
                    </strong>
                    <span style="color: #2ecc71; font-weight: bold;">
                        <?= number_format($leaderboard[2]['points']) ?> Pts
                    </span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Full Leaderboard List contained in Glass Card -->
    <div class="glass-card" style="padding: 0; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr
                    style="background: rgba(var(--primary-color-rgb), 0.1); border-bottom: 2px solid var(--border-color);">
                    <th style="padding: 15px 20px; text-align: left; width: 80px;">Rank</th>
                    <th style="padding: 15px 20px; text-align: left;">Donor Name</th>
                    <th style="padding: 15px 20px; text-align: left;">Badges</th>
                    <th style="padding: 15px 20px; text-align: right;">Total Points</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leaderboard)): ?>
                    <tr>
                        <td colspan="4" style="padding: 30px; text-align: center; color: #888; font-style: italic;">No
                            donors have earned points yet. Be the first!</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($leaderboard as $user): ?>
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.3s;"
                            onmouseover="this.style.background='var(--hover-bg)'"
                            onmouseout="this.style.background='transparent'">
                            <td
                                style="padding: 15px 20px; font-weight: bold; color: <?= $user['rank'] <= 3 ? 'var(--primary-color)' : 'var(--text-color)' ?>;">
                                #
                                <?= $user['rank'] ?>
                            </td>
                            <td style="padding: 15px 20px; font-weight: 500;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div
                                        style="width: 30px; height: 30px; border-radius: 50%; background: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: bold;">
                                        <?= strtoupper(substr($user['display_name'], 0, 1)) ?>
                                    </div>
                                    <?= htmlspecialchars($user['display_name']) ?>
                                    <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                        <span
                                            style="font-size: 0.7rem; background: var(--border-color); padding: 2px 6px; border-radius: 10px; margin-left: 5px;">You</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td style="padding: 15px 20px;">
                                <?php if ($user['points'] >= 500): ?>
                                    <span
                                        style="display: inline-block; padding: 4px 8px; background: rgba(155, 89, 182, 0.1); color: #9b59b6; border: 1px solid rgba(155, 89, 182, 0.3); border-radius: 12px; font-size: 0.75rem; font-weight: 600; margin-right: 5px;"><i
                                            class="fa-solid fa-star"></i> Legendary</span>
                                <?php elseif ($user['points'] >= 100): ?>
                                    <span
                                        style="display: inline-block; padding: 4px 8px; background: rgba(52, 152, 219, 0.1); color: #3498db; border: 1px solid rgba(52, 152, 219, 0.3); border-radius: 12px; font-size: 0.75rem; font-weight: 600; margin-right: 5px;"><i
                                            class="fa-solid fa-shield-heart"></i> Saver</span>
                                <?php else: ?>
                                    <span
                                        style="display: inline-block; padding: 4px 8px; background: rgba(149, 165, 166, 0.1); color: #95a5a6; border: 1px solid rgba(149, 165, 166, 0.3); border-radius: 12px; font-size: 0.75rem; font-weight: 600; margin-right: 5px;">Initiate</span>
                                <?php endif; ?>
                            </td>
                            <td
                                style="padding: 15px 20px; text-align: right; color: #2ecc71; font-weight: bold; font-size: 1.1rem;">
                                <?= number_format($user['points']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Explainer Card -->
    <div class="glass-card"
        style="margin-top: 20px; background: linear-gradient(135deg, rgba(231, 76, 60, 0.05), rgba(231, 76, 60, 0.1)); border-left: 4px solid var(--primary-color);">
        <h4 style="margin-bottom: 10px; color: var(--primary-color);"><i class="fa-solid fa-circle-question"></i> How to
            earn points?</h4>
        <p style="font-size: 0.95rem; line-height: 1.5; margin-bottom: 10px;">Points are our way of saying thank you for
            being an active lifesaver in the Red Hope community. Earn points by logging in daily, registering at
            donation camps, or having your donation request accepted by a hospital.</p>
        <ul style="font-size: 0.9rem; margin-left: 20px; color: var(--text-color);">
            <li><strong style="color: #2ecc71;">+10 Pts</strong>: Daily Login Streak</li>
            <li><strong style="color: #2ecc71;">+50 Pts</strong>: Registering for a Donation Camp</li>
            <li><strong style="color: #2ecc71;">+500 Pts</strong>: Successful Blood Donation</li>
        </ul>
    </div>
</div>

<?php include 'includes/footer.php'; ?>