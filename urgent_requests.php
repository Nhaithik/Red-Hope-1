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

// Handle new request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    $blood_group = $conn->real_escape_string($_POST['blood_group']);
    $request_type = $conn->real_escape_string($_POST['request_type']);
    $organ_type = $request_type !== 'blood' ? $conn->real_escape_string($_POST['organ_type']) : null;
    $hospital = $conn->real_escape_string($_POST['hospital']);
    $city = $conn->real_escape_string($_POST['city']);
    $urgency = $conn->real_escape_string($_POST['urgency']);
    $description = $conn->real_escape_string($_POST['description']);

    $stmt = $conn->prepare("INSERT INTO urgent_requests (user_id, blood_group, request_type, organ_type, hospital, city, urgency, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssss", $user_id, $blood_group, $request_type, $organ_type, $hospital, $city, $urgency, $description);
    if ($stmt->execute()) {
        $success = "Your urgent request has been posted successfully!";
        // Give user 10 points for posting a request
        $conn->query("UPDATE users SET points = points + 10 WHERE id = $user_id");
        // Add notification for the poster
        $msg = "Your urgent request for $blood_group blood at $hospital has been broadcast to donors in $city.";
        $conn->query("INSERT INTO notifications (user_id, message) VALUES ($user_id, '$msg')");
    } else {
        $error = "Failed to submit request. Please try again.";
    }
    $stmt->close();
}

// Handle deactivating own request
if (isset($_GET['deactivate'])) {
    $rid = (int) $_GET['deactivate'];
    $conn->query("UPDATE urgent_requests SET is_active = false WHERE id = $rid AND user_id = $user_id");
    header("Location: urgent_requests.php");
    exit();
}

// Fetch all active urgent requests
$requests = [];
$res = $conn->query("
    SELECT ur.*, u.email
    FROM urgent_requests ur
    JOIN users u ON ur.user_id = u.id
    WHERE ur.is_active = true
    ORDER BY FIELD(ur.urgency, 'critical', 'high', 'moderate'), ur.created_at DESC
");
while ($row = $res->fetch_assoc()) {
    $requests[] = $row;
}

$page_title = "Red Hope | Urgent Requests";
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-header">
        <div>
            <h1><i class="fa-solid fa-triangle-exclamation" style="color: var(--primary-color);"></i> Urgent Requests
            </h1>
            <p style="color: #666;">Critical blood needs from our community.</p>
        </div>
        <button onclick="document.getElementById('newRequestModal').style.display='flex'"
            style="background: var(--primary-color); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 15px rgba(231,76,60,0.3);">
            <i class="fa-solid fa-plus"></i> Post Urgent Request
        </button>
    </div>

    <?php if ($success): ?>
        <div
            style="background: rgba(46,204,113,0.1); border: 1px solid #2ecc71; color: #2ecc71; padding: 15px; border-radius: 10px;">
            <i class="fa-solid fa-circle-check"></i>
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div
            style="background: rgba(231,76,60,0.1); border: 1px solid var(--primary-color); color: var(--primary-color); padding: 15px; border-radius: 10px;">
            <i class="fa-solid fa-circle-xmark"></i>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Urgency Legend -->
    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
        <span style="display: flex; align-items: center; gap: 5px; font-size: 0.85rem; font-weight: 500;">
            <span
                style="width: 10px; height: 10px; background: #e74c3c; border-radius: 50%; display: inline-block;"></span>
            Critical
        </span>
        <span style="display: flex; align-items: center; gap: 5px; font-size: 0.85rem; font-weight: 500;">
            <span
                style="width: 10px; height: 10px; background: #e67e22; border-radius: 50%; display: inline-block;"></span>
            High
        </span>
        <span style="display: flex; align-items: center; gap: 5px; font-size: 0.85rem; font-weight: 500;">
            <span
                style="width: 10px; height: 10px; background: #f1c40f; border-radius: 50%; display: inline-block;"></span>
            Moderate
        </span>
    </div>

    <!-- Requests Grid -->
    <?php if (empty($requests)): ?>
        <div class="glass-card" style="text-align: center; padding: 50px;">
            <i class="fa-solid fa-heart-pulse" style="font-size: 3rem; color: var(--primary-color); opacity: 0.3;"></i>
            <p style="margin-top: 15px; color: #888; font-style: italic;">No active urgent requests right now. The community
                is safe! 🎉</p>
        </div>
    <?php else: ?>
        <div class="content-grid">
            <?php foreach ($requests as $req):
                $urgency_color = $req['urgency'] === 'critical' ? '#e74c3c' : ($req['urgency'] === 'high' ? '#e67e22' : '#f1c40f');
                $days_ago = floor((time() - strtotime($req['created_at'])) / 86400);
                $time_ago = $days_ago > 0 ? "$days_ago days ago" : "Today";
                $requester_name = ucfirst(explode('@', $req['email'])[0]);
                ?>
                <div class="glass-card" style="border-left: 4px solid <?= $urgency_color ?>; position: relative;">
                    <!-- Urgency Badge -->
                    <div
                        style="position: absolute; top: 15px; right: 15px; background: <?= $urgency_color ?>20; color: <?= $urgency_color ?>; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; border: 1px solid <?= $urgency_color ?>50; text-transform: uppercase;">
                        <?= $req['urgency'] ?>
                    </div>

                    <!-- Blood Group -->
                    <div
                        style="font-size: 2.5rem; font-weight: 800; color: <?= $urgency_color ?>; line-height: 1; margin-bottom: 5px;">
                        <?= htmlspecialchars($req['blood_group']) ?>
                    </div>
                    <div
                        style="font-size: 0.8rem; color: #888; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 1px;">
                        <?= $req['request_type'] === 'both' ? 'Blood & Organ' : ucfirst($req['request_type']) ?>
                        <?= $req['organ_type'] ? '— ' . htmlspecialchars($req['organ_type']) : '' ?>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 6px; margin-bottom: 15px; font-size: 0.9rem;">
                        <div><i class="fa-solid fa-hospital" style="color: var(--primary-color); width: 20px;"></i>
                            <?= htmlspecialchars($req['hospital']) ?>
                        </div>
                        <div><i class="fa-solid fa-location-dot" style="color: var(--primary-color); width: 20px;"></i>
                            <?= htmlspecialchars($req['city']) ?>
                        </div>
                        <div><i class="fa-solid fa-user" style="color: #888; width: 20px;"></i> <span
                                style="color: #888;">Posted by
                                <?= htmlspecialchars($requester_name) ?> ·
                                <?= $time_ago ?>
                            </span></div>
                    </div>

                    <?php if ($req['description']): ?>
                        <p
                            style="font-size: 0.85rem; color: #777; font-style: italic; border-top: 1px solid var(--border-color); padding-top: 10px; margin-bottom: 15px;">
                            "
                            <?= htmlspecialchars($req['description']) ?>"
                        </p>
                    <?php endif; ?>

                    <div style="display: flex; gap: 10px;">
                        <a href="messages.php?to=<?= $req['user_id'] ?>"
                            style="flex: 1; background: var(--primary-color); color: white; text-decoration: none; padding: 8px; border-radius: 8px; font-weight: 600; text-align: center; font-size: 0.9rem;">
                            <i class="fa-solid fa-message"></i> I Can Help
                        </a>
                        <?php if ($req['user_id'] == $user_id): ?>
                            <a href="urgent_requests.php?deactivate=<?= $req['id'] ?>"
                                onclick="return confirm('Mark this request as resolved?')"
                                style="padding: 8px 12px; border: 1px solid var(--border-color); color: var(--text-color); text-decoration: none; border-radius: 8px; font-size: 0.9rem;">
                                <i class="fa-solid fa-check"></i> Resolved
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- New Request Modal -->
<div id="newRequestModal"
    style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
    <div class="glass-card"
        style="width: 90%; max-width: 550px; max-height: 90vh; overflow-y: auto; position: relative;">
        <button onclick="document.getElementById('newRequestModal').style.display='none'"
            style="position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-color);">×</button>

        <h3 style="margin-bottom: 5px; color: var(--primary-color);"><i class="fa-solid fa-triangle-exclamation"></i>
            Post Urgent Request</h3>
        <p style="color: #888; font-size: 0.9rem; margin-bottom: 20px;">This will be broadcast to all active donors. Use
            only for genuine urgent needs.</p>

        <form method="POST" style="display: flex; flex-direction: column; gap: 15px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <label style="font-weight: 600; display: block; margin-bottom: 5px;">Blood Group *</label>
                    <select name="blood_group" required
                        style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color);">
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                    </select>
                </div>
                <div>
                    <label style="font-weight: 600; display: block; margin-bottom: 5px;">Urgency Level *</label>
                    <select name="urgency" required
                        style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color);">
                        <option value="critical">🔴 Critical</option>
                        <option value="high" selected>🟠 High</option>
                        <option value="moderate">🟡 Moderate</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <label style="font-weight: 600; display: block; margin-bottom: 5px;">Request Type *</label>
                    <select name="request_type" id="requestTypeSelect" onchange="toggleOrganField()" required
                        style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color);">
                        <option value="blood">Blood Only</option>
                        <option value="organ">Organ Pledge</option>
                        <option value="both">Blood & Organ</option>
                    </select>
                </div>
                <div id="organTypeDiv" style="display: none;">
                    <label style="font-weight: 600; display: block; margin-bottom: 5px;">Organ Type</label>
                    <input type="text" name="organ_type" placeholder="e.g. Kidney, Liver"
                        style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color);">
                </div>
            </div>

            <div>
                <label style="font-weight: 600; display: block; margin-bottom: 5px;">Hospital / Location *</label>
                <input type="text" name="hospital" placeholder="e.g. City Medical Center" required
                    style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color);">
            </div>
            <div>
                <label style="font-weight: 600; display: block; margin-bottom: 5px;">City *</label>
                <input type="text" name="city" placeholder="e.g. Mumbai" required
                    style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color);">
            </div>
            <div>
                <label style="font-weight: 600; display: block; margin-bottom: 5px;">Additional Details</label>
                <textarea name="description" rows="3" placeholder="Any additional information for donors..."
                    style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color); resize: vertical;"></textarea>
            </div>

            <button type="submit" name="submit_request"
                style="background: var(--primary-color); color: white; border: none; padding: 12px; border-radius: 8px; font-weight: 700; font-size: 1rem; cursor: pointer; box-shadow: 0 4px 15px rgba(231,76,60,0.3);">
                <i class="fa-solid fa-paper-plane"></i> Broadcast Request
            </button>
        </form>
    </div>
</div>

<script>
    function toggleOrganField() {
        var type = document.getElementById('requestTypeSelect').value;
        document.getElementById('organTypeDiv').style.display = (type === 'organ' || type === 'both') ? 'block' : 'none';
    }
// Auto-open modal if posting was attempted
<?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error): ?>
            document.getElementById('newRequestModal').style.display = 'flex';
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>