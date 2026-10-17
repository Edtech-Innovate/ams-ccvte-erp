<?php
## Database configuration
include '../../../includes/db-config.php';
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
  $orderby = "ORDER BY MarkSheet_Entry.ID ASC";
}

## Search 
$searchQuery = "";
if($searchValue != '') {
  $searchQuery .= "AND (MarkSheet_Entry.Student_ID LIKE '%$searchValue%' OR MarkSheet_Entry.Marksheet_No LIKE '%$searchValue%' OR MarkSheet_Entry.Duration LIKE '%$searchValue%')";
}

if (isset($_POST['center_id'])) {
    $center_id = mysqli_real_escape_string($conn,$_POST['center_id']);
}

if(isset($_POST['docket_id'])) {
  $docket_id = mysqli_real_escape_string($conn,$_POST['docket_id']);
  $searchQuery .= ($docket_id == 'null') ? 'AND MarkSheet_Entry.Docket_Id IS NULL' : "AND MarkSheet_Entry.Docket_Id = '$docket_id'";
}

## Total number of records without filtering
$all_count=$conn->query("SELECT COUNT(ID) as allcount FROM `MarkSheet_Entry` WHERE Added_For = '$center_id' $searchQuery");
$records = mysqli_fetch_assoc($all_count);
$totalRecords = $records['allcount'];

## Total number of record with filtering
$filter_count = $conn->query("SELECT COUNT(ID) as filtered FROM `MarkSheet_Entry` WHERE Added_For = '$center_id' $searchQuery");
$records = mysqli_fetch_assoc($filter_count);
$totalRecordwithFilter = $records['filtered'];

$data = [];
## Fetch records
$certificateRecords = $conn->query("SELECT MarkSheet_Entry.* , CONCAT(MarkSheet_Entry.Duration,'/',Students.Course_Category) as 'duration_concat' , Students.Unique_ID as `Student_id`  FROM `MarkSheet_Entry` LEFT JOIN Students ON Students.Student_ID = MarkSheet_Entry.Student_ID WHERE MarkSheet_Entry.Added_For = '$center_id' $searchQuery $orderby LIMIT $row, $rowperpage");

while ($row = mysqli_fetch_assoc($certificateRecords)) {
  $data[] = array(
    'ID' => $row['ID'],
    'Student_ID' => $row['Student_ID'],
    'Student_id' => $row['Student_id'],
    'Marksheet_No' => $row['Marksheet_No'],
    "Exam_session" => $row['Exam_Session'],
    "Duration" => $row['duration_concat'],
    "Docket_Id" => $row['Docket_Id']
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
