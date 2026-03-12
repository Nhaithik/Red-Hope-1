<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$page_title = "Red Hope | Donation Map";
include 'includes/header.php';
?>
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="top-header">
        <div>
            <h1>Donation Map</h1>
            <p style="color: #666;">Find nearby blood banks, donation camps, and available blood donors.</p>
        </div>
        <div>
            <button onclick="locateUser()" style="background: var(--primary-color); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 1rem; cursor: pointer; transition: transform 0.2s; box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);">
                <i class="fa-solid fa-location-crosshairs"></i> Locate Me
            </button>
        </div>
    </div>

    <!-- Filters Section nested in Glass Card -->
    <div class="glass-card" style="margin-bottom: 20px;">
        <h3 style="margin-bottom: 15px; color: var(--primary-color);">Filter Map</h3>
        <div style="display: flex; gap: 20px; flex-wrap: wrap; align-items: center;">
            <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                <input type="checkbox" id="showBloodBanks" checked style="accent-color: var(--primary-color); width: 16px; height: 16px;">
                <span style="font-weight: 500;">Show Blood Banks</span>
            </label>
            <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                <input type="checkbox" id="showCamps" checked style="accent-color: #2ecc71; width: 16px; height: 16px;">
                <span style="font-weight: 500;">Show Donation Camps</span>
            </label>
            <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                <input type="checkbox" id="showDonors" checked style="accent-color: #3498db; width: 16px; height: 16px;">
                <span style="font-weight: 500;">Show Individual Donors</span>
            </label>
            
            <div style="margin-left: auto; display: flex; align-items: center; gap: 10px;">
                <label for="bloodGroupFilter" style="font-weight: 600;">Blood Group:</label>
                <select id="bloodGroupFilter" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: rgba(255,255,255,0.5); color: var(--text-color);">
                    <option value="">Any</option>
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
        </div>
    </div>

    <!-- Map Container -->
    <div class="glass-card" style="padding: 10px; z-index: 1; position: relative;">
        <!-- Loading Overlay -->
        <div id="map-loading" style="display: none; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.7); z-index: 1000; border-radius: 15px; align-items: center; justify-content: center; flex-direction: column;">
            <i class="fa-solid fa-spinner fa-spin" style="font-size: 3rem; color: var(--primary-color);"></i>
            <p style="margin-top: 15px; font-weight: 600; color: #333;">Finding your location...</p>
        </div>
        <div id="map" style="height: 65vh; width: 100%; border-radius: 15px;"></div>
    </div>
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    let map;
    let markersLayer = new L.LayerGroup();
    let userMarker = null;

    // Fixed dummy data for initial view (Fallback)
    let dynamicData = {
        bloodBanks: [
            {name: "City Blood Bank", lat: 20.2961, lng: 85.8245, bloodGroups: ["A+","B+"]},
            {name: "HealthCare Blood Bank", lat: 20.3140, lng: 85.8240, bloodGroups: ["O-","AB+"]}
        ],
        camps: [
            {name: "Health Camp 1", lat: 20.3050, lng: 85.8260, date: "2025-11-10"},
        ],
        donors: [
            {name: "John Doe", lat: 20.3000, lng: 85.8200, bloodGroup: "A+", available: true},
        ]
    };

    // Helper to generate random coordinates within a small radius (~10km)
    function getRandomOffset() {
        return (Math.random() - 0.5) * 0.15;
    }

    // Generate fresh dynamic data based on a center point
    function generateNearbyData(centerLat, centerLng) {
        return {
            bloodBanks: [
                {name: "Central Reserve Bank", lat: centerLat + getRandomOffset(), lng: centerLng + getRandomOffset(), bloodGroups: ["A+","B+","O-"]},
                {name: "Hope Blood Center", lat: centerLat + getRandomOffset(), lng: centerLng + getRandomOffset(), bloodGroups: ["O+","AB+","A-"]}
            ],
            camps: [
                {name: "Community Drive 2026", lat: centerLat + getRandomOffset(), lng: centerLng + getRandomOffset(), date: "This Sunday"},
                {name: "City Square Camp", lat: centerLat + getRandomOffset(), lng: centerLng + getRandomOffset(), date: "Next Friday"}
            ],
            donors: [
                {name: "James Smith", lat: centerLat + getRandomOffset(), lng: centerLng + getRandomOffset(), bloodGroup: "A+", available: true},
                {name: "Maria Garcia", lat: centerLat + getRandomOffset(), lng: centerLng + getRandomOffset(), bloodGroup: "O-", available: true},
                {name: "David Chen", lat: centerLat + getRandomOffset(), lng: centerLng + getRandomOffset(), bloodGroup: "B+", available: true},
                {name: "Sarah Williams", lat: centerLat + getRandomOffset(), lng: centerLng + getRandomOffset(), bloodGroup: "AB+", available: true},
                {name: "Michael Brown", lat: centerLat + getRandomOffset(), lng: centerLng + getRandomOffset(), bloodGroup: "O+", available: true},
            ]
        };
    }

    // Map configuration
    map = L.map('map').setView([20.5937, 78.9629], 5); // Default to India
    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
        maxZoom: 19
    }).addTo(map);

    markersLayer.addTo(map);

    // Custom Icons via HTML divs
    const createCustomIcon = (color, pulse = false) => {
        let style = `background-color: ${color}; width: 16px; height: 16px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 6px rgba(0,0,0,0.5);`;
        if (pulse) {
            style += "animation: pulse 2s infinite;";
        }
        return L.divIcon({
            className: 'custom-icon',
            html: `<div style="${style}"></div>`,
            iconSize: [20, 20],
            iconAnchor: [10, 10],
            popupAnchor: [0, -10]
        });
    };

    // Add pulse animation to document head for user marker
    const styleSheet = document.createElement("style");
    styleSheet.innerText = `
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(241, 196, 15, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(241, 196, 15, 0); }
            100% { box-shadow: 0 0 0 0 rgba(241, 196, 15, 0); }
        }
    `;
    document.head.appendChild(styleSheet);

    const userIcon = createCustomIcon('#f1c40f', true); // Yellow pulsing
    const redIcon = createCustomIcon('#e74c3c');
    const greenIcon = createCustomIcon('#2ecc71');
    const blueIcon = createCustomIcon('#3498db');

    function loadMarkers() {
        markersLayer.clearLayers();
        const showBloodBanks = document.getElementById('showBloodBanks').checked;
        const showCamps = document.getElementById('showCamps').checked;
        const showDonors = document.getElementById('showDonors').checked;
        const bloodGroupFilter = document.getElementById('bloodGroupFilter').value;

        if (showBloodBanks) {
            dynamicData.bloodBanks.forEach(bank => {
                if (!bloodGroupFilter || bank.bloodGroups.includes(bloodGroupFilter) || bank.bloodGroups.some(bg => bg === bloodGroupFilter)) {
                    L.marker([bank.lat, bank.lng], {icon: redIcon})
                        .bindPopup(`<div style="font-family:'Inter',sans-serif; padding:5px;"><strong>${bank.name}</strong><br>Blood Groups: <span style="color:#e74c3c;font-weight:600;">${bank.bloodGroups.join(", ")}</span><br><a href="submit_review.php?location_type=blood_bank&location_id=1" style="display:inline-block;margin-top:5px;text-decoration:none;color:#3498db;font-weight:500;">Leave a Review &rarr;</a></div>`)
                        .addTo(markersLayer);
                }
            });
        }

        if (showCamps) {
            dynamicData.camps.forEach(camp => {
                L.marker([camp.lat, camp.lng], {icon: greenIcon})
                    .bindPopup(`<div style="font-family:'Inter',sans-serif; padding:5px;"><strong style="color: #2ecc71;">${camp.name}</strong><br>Date: ${camp.date}<br><a href="submit_review.php?location_type=camp&location_id=1" style="display:inline-block;margin-top:5px;text-decoration:none;color:#3498db;font-weight:500;">Leave a Review &rarr;</a></div>`)
                    .addTo(markersLayer);
            });
        }

        if (showDonors) {
            dynamicData.donors.forEach(donor => {
                if ((!bloodGroupFilter || donor.bloodGroup === bloodGroupFilter) && donor.available) {
                    L.marker([donor.lat, donor.lng], {icon: blueIcon})
                        .bindPopup(`<div style="font-family:'Inter',sans-serif; padding:5px;"><strong>${donor.name}</strong><br>Blood Group: <span style="color:#3498db;font-weight:bold;">${donor.bloodGroup}</span></div>`)
                        .addTo(markersLayer);
                }
            });
        }
    }

    // Locate User Function
    function locateUser() {
        if (!navigator.geolocation) {
            alert("Geolocation is not supported by your browser");
            return;
        }

        document.getElementById('map-loading').style.display = 'flex';

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                document.getElementById('map-loading').style.display = 'none';
                
                // Center Map
                map.setView([lat, lng], 12);
                
                // Add/Update User Marker overlay (outside of the markersLayer so it never gets cleared by filters)
                if (userMarker) {
                    map.removeLayer(userMarker);
                }
                userMarker = L.marker([lat, lng], {icon: userIcon, zIndexOffset: 1000})
                    .bindPopup(`<div style="font-family:'Inter',sans-serif; padding:5px;"><strong>This is You</strong><br>Your exact location.</div>`)
                    .addTo(map);
                userMarker.openPopup();
                
                // Generate and inject fresh data near the user's location
                dynamicData = generateNearbyData(lat, lng);
                loadMarkers();
            },
            (error) => {
                document.getElementById('map-loading').style.display = 'none';
                alert("Unable to retrieve your location. Please check browser permissions.");
                console.error(error);
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    }

    // Initial Load
    loadMarkers();

    // Event Listeners for Filters
    document.getElementById('showBloodBanks').addEventListener('change', loadMarkers);
    document.getElementById('showCamps').addEventListener('change', loadMarkers);
    document.getElementById('showDonors').addEventListener('change', loadMarkers);
    document.getElementById('bloodGroupFilter').addEventListener('change', loadMarkers);

    // Minor fix: Fix leaflet map overlapping rounded corners
    document.querySelector('.leaflet-container').style.borderRadius = '15px';
</script>

<?php include 'includes/footer.php'; ?>