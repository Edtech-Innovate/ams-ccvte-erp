<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

require '../../includes/db-config.php';

// Required for DataTables server-side
$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$searchValue = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';

// Fetch data from external API
$apiUrl = BASE_URL . "admin/app/center-online-partner/get-data.php";

// Initialize CURL
$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => $apiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,     // Follow 301/302 Redirect
    CURLOPT_MAXREDIRS => 5,
    CURLOPT_CONNECTTIMEOUT => 30,
    CURLOPT_TIMEOUT => 60,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json'
    ]
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode([
        "draw" => $draw,
        "iTotalRecords" => 0,
        "iTotalDisplayRecords" => 0,
        "aaData" => [],
        "error" => curl_error($ch)
    ]);
    curl_close($ch);
    exit;
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

curl_close($ch);

// HTTP Error
if ($httpCode != 200) {
    echo json_encode([
        "draw" => $draw,
        "iTotalRecords" => 0,
        "iTotalDisplayRecords" => 0,
        "aaData" => [],
        "error" => "HTTP Error : " . $httpCode,
        "url" => $effectiveUrl
    ]);
    exit;
}

// Remove UTF-8 BOM if exists
$response = preg_replace('/^\xEF\xBB\xBF/', '', $response);
$response = trim($response);

// Decode JSON
$apiData = json_decode($response, true);

// JSON Error
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        "draw" => $draw,
        "iTotalRecords" => 0,
        "iTotalDisplayRecords" => 0,
        "aaData" => [],
        "error" => json_last_error_msg(),
        "response" => substr($response, 0, 500)
    ]);
    exit;
}

// Check response structure
if (!$apiData || !isset($apiData['status']) || $apiData['status'] !== 'success') {
    echo json_encode([
        "draw" => $draw,
        "iTotalRecords" => 0,
        "iTotalDisplayRecords" => 0,
        "aaData" => [],
        "error" => "Invalid API response"
    ]);
    exit;
}

$data = $apiData['data'];

$totalRecords = count($data);

// Filter data manually
if (!empty($searchValue)) {
    $data = array_filter($data, function ($item) use ($searchValue) {

        $searchValue = strtolower($searchValue);

        return (
            stripos($item['Name'] ?? '', $searchValue) !== false ||
            stripos($item['Email'] ?? '', $searchValue) !== false ||
            stripos($item['DirectorName'] ?? '', $searchValue) !== false ||
            stripos($item['Mobile'] ?? '', $searchValue) !== false
        );
    });
}

$totalFiltered = count($data);

// Slice data for pagination
$data = array_values(array_slice($data, $start, $length));

// Prepare DataTables response
$response = [
    "draw" => $draw,
    "iTotalRecords" => $totalRecords,
    "iTotalDisplayRecords" => $totalFiltered,
    "aaData" => $data
];

echo json_encode($response);