<?php

## Database configuration
include '../../includes/db-config.php';
session_start();
## Read value
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length']; // Rows display per page
if(isset($_POST['order'])){
  $columnIndex = $_POST['order'][0]['column']; // Column index
  $columnName = $_POST['columns'][$columnIndex]['data']; // Column name
  $columnSortOrder = $_POST['order'][0]['dir']; // asc or desc
}
$searchValue = mysqli_real_escape_string($conn,$_POST['search']['value']); // Search value

if(isset($columnSortOrder)){
  $orderby = "ORDER BY $columnName $columnSortOrder";
}else{
  $orderby = "ORDER BY certificate_entry.ID ASC";
}

## Search 
$searchQuery = "";
if($searchValue != '') {
  $searchQuery = "WHERE certificate_entry.Student_ID LIKE '%$searchValue%' OR certificate_entry.Certificate_No LIKE '%$searchValue%' OR certificate_entry.Duration LIKE '%$searchValue%'";
}

## Total number of records without filtering
$all_count=$conn->query("SELECT COUNT(ID) as allcount FROM `certificate_entry`");
$records = mysqli_fetch_assoc($all_count);
$totalRecords = $records['allcount'];

## Total number of record with filtering
$filter_count = $conn->query("SELECT COUNT(ID) as filtered FROM `certificate_entry` $searchQuery");
$records = mysqli_fetch_assoc($filter_count);
$totalRecordwithFilter = $records['filtered'];

$data = [];
## Fetch records
$certificateRecords = $conn->query("SELECT certificate_entry.* , CONCAT(certificate_entry.Duration,'/',Students.Course_Category) as 'duration_concat' , Students.Unique_ID as `Student_id`  FROM `certificate_entry` LEFT JOIN Students ON Students.Unique_ID = certificate_entry.Student_ID $searchQuery $orderby LIMIT $row, $rowperpage");

while ($row = mysqli_fetch_assoc($certificateRecords)) {
    // echo('<pre>');print_r( $row['duration_concat']);die;
  $data[] = array(
    'ID' => $row['ID'],
    'Student_ID' => $row['Student_ID'],
    "Certificate_No" => $row['Certificate_No'],
    "Exam_session" => $row['Exam_Session'],
    "Duration" => $row['Duration'],
    "Docket_Id" => $row['Docket_Id'],
    "Dispatch_status" =>$row['Dispatch_status']
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

?>