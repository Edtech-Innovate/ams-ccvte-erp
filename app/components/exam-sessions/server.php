<?php
## Database configuration
include '../../../includes/db-config.php';
session_start();
## Read value
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length']; // Rows display per page
if (isset($_POST['order'])) {
  $columnIndex = $_POST['order'][0]['column']; // Column index
  $columnName = $_POST['columns'][$columnIndex]['data']; // Column name
  $columnSortOrder = $_POST['order'][0]['dir']; // asc or desc
}
$searchValue = mysqli_real_escape_string($conn, $_POST['search']['value']); // Search value
$university_id = intval($_POST['university_id']);

if (isset($columnSortOrder)) {
  $orderby = "ORDER BY $columnName $columnSortOrder";
} else {
  $orderby = "ORDER BY Exam_Sessions.ID ASC";
}

$admission_sessions = array();
$sessions = $conn->query("SELECT ID, Name FROM Admission_Sessions WHERE University_ID = $university_id");
while ($session = $sessions->fetch_assoc()) {
  $admission_sessions[$session['ID']] = $session['Name'];
}

## Search 
$searchQuery = " ";
if ($searchValue != '') {
  $searchQuery = " AND (Exam_Sessions.Name like '%" . $searchValue . "%')";
}

## Total number of records without filtering
$all_count = $conn->query("SELECT COUNT(ID) as allcount FROM Exam_Sessions WHERE University_ID = $university_id");
$records = mysqli_fetch_assoc($all_count);
$totalRecords = $records['allcount'];

## Total number of record with filtering
$filter_count = $conn->query("SELECT COUNT(ID) as filtered FROM Exam_Sessions WHERE University_ID = $university_id $searchQuery");

$records = mysqli_fetch_assoc($filter_count);
$totalRecordwithFilter = $records['filtered'];

## Fetch records
$result_record = "SELECT Exam_Sessions.ID, Exam_Sessions.Name , `RR_Status`, `BP_Status` , `serialNum` , `serialNum_range` , `dateOfIssue` , `certificate_serialNum` FROM Exam_Sessions WHERE Exam_Sessions.University_ID = $university_id $searchQuery $orderby LIMIT " . $row . "," . $rowperpage;
$empRecords = mysqli_query($conn, $result_record);
$data = array();

while ($row = mysqli_fetch_assoc($empRecords)) {

  $data[] = array(
    "ID" => $row["ID"],
    "Name" => $row["Name"],
    "serialNum" => $row['serialNum'] ,
    "range" => $row['serialNum_range'] , 
    "dateOfIssue" => $row['dateOfIssue'],
    "RR_Status" => $row["RR_Status"],
    "BP_Status" => $row["BP_Status"],
    "certificate_serialNum" => $row['certificate_serialNum']
  );
}

## Response
$response = array(
  "draw" => intval($draw),
  "iTotalRecords" => $totalRecords,
  "iTotalDisplayRecords" => $totalRecordwithFilter,
  "aaData" => $data
);

echo json_encode($response);
