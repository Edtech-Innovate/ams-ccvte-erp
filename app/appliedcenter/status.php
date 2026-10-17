<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require '../../includes/db-config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 405, 'message' => 'Invalid request method.']);
    exit;
}

$id     = isset($_POST['id']) ? intval($_POST['id']) : 0;
$status = isset($_POST['status']) ? intval($_POST['status']) : 0;

// Validate inputs
if (!$id || !in_array($status, [1, 2, 3])) {
    echo json_encode(['status' => 400, 'message' => 'Invalid ID or status value.']);
    exit;
}

// Check if user exists with form_type not null
$checkQuery = "SELECT ID FROM Users WHERE ID = $id AND Role = 'Center' AND form_type IS NOT NULL";
$checkResult = $conn->query($checkQuery);

if (!$checkResult || $checkResult->num_rows === 0) {
    echo json_encode(['status' => 404, 'message' => 'Center not found or form_type is null.']);
    exit;
}

// Update the user's status
$updateQuery = "UPDATE Users SET form_type = $status WHERE ID = $id AND Role = 'Center' AND form_type IS NOT NULL";
if (!$conn->query($updateQuery)) {
    echo json_encode(['status' => 500, 'message' => 'Failed to update status in the database.']);
    exit;
}

// Fetch updated user info
$result = $conn->query("SELECT ID, Name, Email, Status, form_type FROM Users WHERE ID = $id AND Role = 'Center' AND form_type IS NOT NULL");
if (!$result || $result->num_rows === 0) {
    echo json_encode(['status' => 404, 'message' => 'Center not found after update.']);
    exit;
}

$user = $result->fetch_assoc();

$statusLabels = [1 => 'Pending', 2 => 'Approved', 3 => 'Rejected'];
$statusText = $statusLabels[$status] ?? 'Unknown';

echo json_encode([
    'status'  => 200,
    'message' => "Status updated to '$statusText'.",
    'data'    => $user
]);
exit;
