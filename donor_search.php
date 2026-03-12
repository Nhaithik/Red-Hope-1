<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$page_title = "Red Hope | Donor Search";
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-header">
        <div>
            <h1>Find Donors</h1>
            <p style="color: #666;">Search our network for available blood and organ donors.</p>
        </div>
    </div>

    <div class="glass-card" style="max-width: 800px;">
        <h3 style="margin-bottom: 20px; color: var(--primary-color);">Search Criteria</h3>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div>
                <label for="blood_group" style="display: block; margin-bottom: 8px; font-weight: 600;">Blood
                    Group</label>
                <select id="blood_group"
                    style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color); background: rgba(255,255,255,0.5); font-size: 1rem; color: var(--text-color);">
                    <option value="">-- Any --</option>
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
                <label for="location" style="display: block; margin-bottom: 8px; font-weight: 600;">Location</label>
                <input type="text" id="location" placeholder="e.g., New York"
                    style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color); background: rgba(255,255,255,0.5); font-size: 1rem; color: var(--text-color);">
            </div>
        </div>

        <button onclick="searchDonors()"
            style="background: var(--primary-color); color: white; border: none; padding: 12px 25px; border-radius: 8px; font-weight: 600; font-size: 1rem; cursor: pointer; transition: transform 0.2s; box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);">
            <i class="fa-solid fa-magnifying-glass"></i> Search Network
        </button>
    </div>

    <div id="results"
        style="margin-top: 20px; display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
        <!-- Results will be injected here -->
    </div>
</div>

<script>
    function searchDonors() {
        const bloodGroup = document.getElementById('blood_group').value;
        const location = document.getElementById('location').value.trim();
        const resultsDiv = document.getElementById('results');

        resultsDiv.innerHTML = '<p style="color: #666;">Searching...</p>';

        fetch(`search_donors.php?blood_group=${encodeURIComponent(bloodGroup)}&location=${encodeURIComponent(location)}`)
            .then(response => response.json())
            .then(data => {
                resultsDiv.innerHTML = '';

                if (data.length === 0) {
                    resultsDiv.innerHTML = '<div class="glass-card"><p>No donors found matching your criteria.</p></div>';
                    return;
                }

                data.forEach(donor => {
                    const isAvailable = donor.available_for_donation ? 'Yes' : 'No';
                    const availColor = donor.available_for_donation ? '#2ecc71' : '#e74c3c';

                    const card = document.createElement('div');
                    card.className = 'glass-card';
                    card.style.borderLeft = `5px solid ${availColor}`;
                    card.innerHTML = `
                        <h3 style="margin-bottom: 10px;">
                            <i class="fa-solid fa-user-circle"></i> ${donor.name}
                        </h3>
                        <p style="margin-bottom: 5px;"><strong>Blood Group:</strong> <span style="color: var(--primary-color); font-weight: bold;">${donor.blood_group}</span></p>
                        <p style="margin-bottom: 5px;"><strong>Location:</strong> ${donor.location}</p>
                        <p style="margin-bottom: 5px;"><strong>Phone:</strong> <a href="tel:${donor.phone_number}" style="color: #3498db; text-decoration: none;">${donor.phone_number}</a></p>
                        <p style="margin-top: 15px; font-size: 0.9rem; font-weight: 600; color: ${availColor};">
                            <i class="fa-solid ${donor.available_for_donation ? 'fa-check' : 'fa-xmark'}"></i> Available for Donation
                        </p>
                    `;
                    resultsDiv.appendChild(card);
                });
            })
            .catch(error => {
                resultsDiv.innerHTML = '<div class="glass-card"><p style="color: #e74c3c;">Error fetching donors.</p></div>';
                console.error('Error:', error);
            });
    }
</script>

<?php include 'includes/footer.php'; ?>