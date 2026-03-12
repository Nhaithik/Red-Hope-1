<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "root"; // adjust as needed
$password = "";     // adjust as needed
$dbname = "blood_donation_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode([]);
    exit();
}

$blood_group = $_GET['blood_group'] ?? '';
$location = $_GET['location'] ?? '';

// Basic sanitization
$blood_group = $conn->real_escape_string($blood_group);
$location = $conn->real_escape_string($location);

// Prepare SQL query to search for donors who are available and match filters
$sql = "SELECT id, email, phone_number, blood_group, available_for_blood, available_for_organ, age, gender FROM users WHERE (available_for_blood = 1 OR available_for_organ = 1)";

if ($blood_group) {
    $sql .= " AND blood_group = '$blood_group'";
}

// Location filter could be implemented with more sophisticated methods (e.g., city field, geo coords).
// Here, assuming location filtering via user input is not yet implemented in DB, so skipped.

// Execute query
$result = $conn->query($sql);

$donors = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $donors[] = [
            'id' => (int)$row['id'],
            'name' => explode('@', $row['email'])[0], // using email prefix as placeholder name
            'phone_number' => $row['phone_number'],
            'blood_group' => $row['blood_group'],
            'location' => $location ?: 'N/A', // placeholder since no city field present
            'available_for_donation' => (bool)($row['available_for_blood'] || $row['available_for_organ']),
        ];
    }
}

echo json_encode($donors);
$conn->close();
?>
