<?php
header('Content-Type: application/json');
require '../../includes/db-config.php';
session_start();

// DataTables request parameters
$draw         = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
$start        = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length       = isset($_POST['length']) ? intval($_POST['length']) : 10;
$searchValue  = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';

// Sort options
$orderColumn = 'ID';
$orderDir = 'DESC';

if (isset($_POST['order'])) {
    $columnIndex = $_POST['order'][0]['column'];
    $orderColumn = $_POST['columns'][$columnIndex]['data'];
    $orderDir = $_POST['order'][0]['dir'];
}

// Optional filter: if user is not Administrator
$center_query = "";
if ($_SESSION['Role'] !== "Administrator") {
    $university_id = intval($_SESSION['university_id']);
    $check_unique = $conn->query("SELECT Center_Suffix FROM Universities WHERE ID = $university_id AND Has_Unique_Center = 1");

    if ($check_unique && $check_unique->num_rows > 0) {
        $suffix = mysqli_real_escape_string($conn, $check_unique->fetch_assoc()['Center_Suffix']);
        $center_query = " AND Code LIKE '$suffix%' AND Is_Unique = 1";
    } else {
        $center_query = " AND Is_Unique = 0";
    }
}

// Add form_type IS NOT NULL condition to all queries
$formTypeCondition = " AND form_type IS NOT NULL";

// Total records
$totalResult = $conn->query("SELECT COUNT(ID) AS total FROM Users WHERE Role = 'Center' $center_query $formTypeCondition");
$totalRecords = $totalResult ? (int) $totalResult->fetch_assoc()['total'] : 0;

// Filtering
$searchQuery = "";
if (!empty($searchValue)) {
    $escapedSearch = mysqli_real_escape_string($conn, $searchValue);
    $searchQuery = " AND (
        Name LIKE '%$escapedSearch%' OR
        Email LIKE '%$escapedSearch%' OR
        Contact_Name LIKE '%$escapedSearch%' OR
        Mobile LIKE '%$escapedSearch%'
    )";
}

// Total filtered
$filteredResult = $conn->query("SELECT COUNT(ID) AS filtered FROM Users WHERE Role = 'Center' $center_query $formTypeCondition $searchQuery");
$totalFiltered = $filteredResult ? (int) $filteredResult->fetch_assoc()['filtered'] : 0;

// Fetch records
$query = "
    SELECT 
        ID, Name, Short_Name AS DirectorName, Email, Mobile, Address AS Buliding_type, 
        Photo, Status, form_type,form_id 
    FROM Users 
    WHERE Role = 'Center' 
    $center_query 
    $formTypeCondition 
    $searchQuery 
    ORDER BY $orderColumn $orderDir 
    LIMIT $start, $length
";

$result = $conn->query($query);
$data = [];
$no = $start + 1;

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            "No" => $no++,
            "Photo" => $row['Photo'],
            "Name" => $row['Name'],
            "Email" => $row['Email'],
            "DirectorName" => $row['DirectorName'],
            "Mobile" => $row['Mobile'],
            "Buliding_type" => $row['Buliding_type'],
            "Status" => $row['form_type'],
            "form_type" => $row['form_type'],
            "ID" => $row['ID'],
            "form_id" => $row['form_id']
        ];
    }
}

// echo "<pre>";
// print_r($data);
// echo "</pre>";
// exit;

// Final response
echo json_encode([
    "draw" => $draw,
    "iTotalRecords" => $totalRecords,
    "iTotalDisplayRecords" => $totalFiltered,
    "aaData" => $data
]);
