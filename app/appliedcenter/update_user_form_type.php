<?php
header('Content-Type: application/json');
require '../../includes/db-config.php';

// ===== Logging Setup =====
$logFile = 'formtype-update-log.txt';
file_put_contents($logFile, "\n==== Request: " . date("Y-m-d H:i:s") . " ====\n", FILE_APPEND);
file_put_contents($logFile, "RAW POST DATA:\n" . print_r($_POST, true), FILE_APPEND);

// ===== Check if POST Request =====
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    file_put_contents($logFile, "ERROR: Invalid request method\n", FILE_APPEND);
    echo json_encode(['status' => 405, 'message' => 'Invalid request method.']);
    exit;
}

// ===== Validate Database Connection =====
if ($conn->connect_error) {
    file_put_contents($logFile, "DB ERROR: " . $conn->connect_error . "\n", FILE_APPEND);
    echo json_encode(['status' => 500, 'message' => 'Database connection error.']);
    exit;
}

// ===== Get and Sanitize Inputs =====
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$form_type = isset($_POST['form_type']) ? intval($_POST['form_type']) : 0;

if ($id <= 0 || !in_array($form_type, [1, 2, 3])) {
    file_put_contents($logFile, "ERROR: Invalid id or form_type\n", FILE_APPEND);
    echo json_encode(['status' => 400, 'message' => 'Invalid id or form_type.']);
    exit;
}

// ===== Build and Execute Query =====
$updateSql = "UPDATE Users SET form_type = $form_type WHERE ID = $id AND Role = 'Center'";
$result = mysqli_query($conn, $updateSql);

if ($result) {
    if (mysqli_affected_rows($conn) > 0) {
        file_put_contents($logFile, "SUCCESS: Updated form_type = $form_type for ID = $id\n", FILE_APPEND);
        echo json_encode(['status' => 200, 'message' => 'form_type updated successfully.']);
    } else {
        file_put_contents($logFile, "ERROR: No rows affected or user not found for ID = $id\n", FILE_APPEND);
        echo json_encode(['status' => 404, 'message' => 'User not found or form_type already set.']);
    }
} else {
    file_put_contents($logFile, "QUERY ERROR: " . mysqli_error($conn) . "\n", FILE_APPEND);
    echo json_encode(['status' => 500, 'message' => 'Query failed.']);
}

$conn->close();
