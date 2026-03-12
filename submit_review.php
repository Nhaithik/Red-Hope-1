<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$servername = "localhost";
$username = "root";  // adjust as needed
$password = "";      // adjust as needed
$dbname = "blood_donation_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$location_type = $_GET['location_type'] ?? '';
$location_id = intval($_GET['location_id'] ?? 0);
$error_msg = '';
$success_msg = '';

// Validate location_type and location_id
if (!in_array($location_type, ['blood_bank', 'camp']) || $location_id <= 0) {
    die("<div style='padding:20px; text-align:center;'><h2>Invalid Location Information</h2><a href='map_view.php'>Return to Map</a></div>");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = intval($_POST['rating'] ?? 0);
    $review_text = trim($_POST['review_text'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $error_msg = "Please provide a rating between 1 and 5.";
    } else {
        // Check if user already reviewed this location
        $stmt = $conn->prepare("SELECT id FROM reviews WHERE user_id = ? AND location_type = ? AND location_id = ?");
        $stmt->bind_param("isi", $user_id, $location_type, $location_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Update existing review
            $stmt->close();
            $stmt = $conn->prepare("UPDATE reviews SET rating = ?, review_text = ?, updated_at = NOW() WHERE user_id = ? AND location_type = ? AND location_id = ?");
            $stmt->bind_param("isisi", $rating, $review_text, $user_id, $location_type, $location_id);
        } else {
            // Insert new review
            $stmt->close();
            $stmt = $conn->prepare("INSERT INTO reviews (user_id, location_type, location_id, rating, review_text) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isiss", $user_id, $location_type, $location_id, $rating, $review_text);
        }

        if ($stmt->execute()) {
            $success_msg = "Thank you! Your review has been successfully submitted.";
        } else {
            $error_msg = "Error submitting your review: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch existing review to pre-fill form
$stmt = $conn->prepare("SELECT rating, review_text FROM reviews WHERE user_id = ? AND location_type = ? AND location_id = ?");
$stmt->bind_param("isi", $user_id, $location_type, $location_id);
$stmt->execute();
$stmt->bind_result($existing_rating, $existing_review_text);
$stmt->fetch();
$stmt->close();

$page_title = "Red Hope | Submit Review";
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-header" style="margin-bottom: 20px;">
        <div>
            <h1>Submit a Review</h1>
            <p style="color: #666;">Rate your experience at the
                <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $location_type))) ?>.
            </p>
        </div>
        <div>
            <a href="map_view.php"
                style="color: var(--text-color); padding: 10px 20px; border-radius: 8px; text-decoration: none; border: 1px solid var(--border-color); background: var(--glass-bg);"><i
                    class="fa-solid fa-arrow-left"></i> Back to Map</a>
        </div>
    </div>

    <div class="glass-card" style="max-width: 600px; margin: 0 auto;">

        <?php if ($error_msg): ?>
            <div
                style="background: rgba(231, 76, 60, 0.1); border-left: 4px solid #e74c3c; padding: 15px; margin-bottom: 20px; border-radius: 4px; color: #e74c3c;">
                <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error_msg) ?>
            </div>
        <?php elseif ($success_msg): ?>
            <div
                style="background: rgba(46, 204, 113, 0.1); border-left: 4px solid #2ecc71; padding: 15px; margin-bottom: 20px; border-radius: 4px; color: #2ecc71;">
                <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success_msg) ?>
            </div>
        <?php endif; ?>

        <form method="POST" style="display: flex; flex-direction: column; gap: 20px;">

            <div>
                <label for="rating" style="display: block; margin-bottom: 8px; font-weight: 600;">Rating (1 to
                    5):</label>
                <div
                    style="display: flex; align-items: center; gap: 15px; background: rgba(0,0,0,0.03); padding: 15px; border-radius: 8px;">
                    <input type="range" id="rating" name="rating" min="1" max="5"
                        value="<?= htmlspecialchars($existing_rating ?? 5) ?>"
                        oninput="document.getElementById('ratingVal').innerText = this.value"
                        style="flex: 1; accent-color: #f1c40f;">
                    <span id="ratingVal"
                        style="font-size: 1.5rem; font-weight: bold; color: #f1c40f; width: 30px; text-align: center;"><?= htmlspecialchars($existing_rating ?? 5) ?></span>
                    <i class="fa-solid fa-star" style="color: #f1c40f; font-size: 1.2rem;"></i>
                </div>
            </div>

            <div>
                <label for="review_text" style="display: block; margin-bottom: 8px; font-weight: 600;">Your
                    Experience:</label>
                <textarea id="review_text" name="review_text" rows="5"
                    placeholder="Share your experience here (optional)..."
                    style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color); background: rgba(255,255,255,0.5); color: var(--text-color); resize: vertical;"><?= htmlspecialchars($existing_review_text ?? '') ?></textarea>
            </div>

            <button type="submit"
                style="background: var(--primary-color); color: white; border: none; padding: 15px; border-radius: 8px; font-weight: 600; font-size: 1rem; cursor: pointer; transition: transform 0.2s; box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3); margin-top: 10px;">
                <i class="fa-solid fa-paper-plane"></i> Submit Review
            </button>

        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>