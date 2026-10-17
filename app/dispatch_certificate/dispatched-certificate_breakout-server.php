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
  $searchQuery = "AND (certificate_entry.Student_ID LIKE '%$searchValue%' OR certificate_entry.Marksheet_No LIKE '%$searchValue%' OR certificate_entry.Duration LIKE '%$searchValue%')";
}

## Total number of records without filtering
$all_count=$conn->query("SELECT Users.Name AS Center_Name, COUNT(DISTINCT Students.Unique_ID) AS Marksheet_count FROM `certificate_entry` LEFT JOIN Students ON Students.Unique_ID = certificate_entry.Student_ID LEFT JOIN Users ON Users.ID = certificate_entry.Added_For WHERE certificate_entry.Dispatch_status = '2' GROUP BY Users.Name , certificate_entry.Docket_Id,certificate_entry.Created_At");
$records = $all_count->num_rows;
$totalRecords = $records;

## Total number of record with filtering
$filter_count = $conn->query("SELECT Users.Name AS Center_Name, COUNT(DISTINCT Students.Unique_ID) AS Marksheet_count FROM `certificate_entry` LEFT JOIN Students ON Students.Unique_ID = certificate_entry.Student_ID LEFT JOIN Users ON Users.ID = certificate_entry.Added_For WHERE certificate_entry.Dispatch_status = '2' $searchQuery GROUP BY Users.Name , certificate_entry.Docket_Id,certificate_entry.Created_At");
$records = $filter_count->num_rows;
$totalRecordwithFilter = $records;

$data = [];
## Fetch records 
$certificateBreakOutRecords = $conn->query("SELECT certificate_entry.*, Users.Name AS Center_Name, COUNT(DISTINCT Students.Unique_ID) AS Marksheet_count , certificate_entry.Created_At as `insert_date` FROM `certificate_entry` LEFT JOIN Students ON Students.Unique_ID = certificate_entry.Student_ID LEFT JOIN Users ON Users.ID = certificate_entry.Added_For WHERE certificate_entry.Dispatch_status = '2' $searchQuery GROUP BY Users.Name , certificate_entry.Docket_Id ,certificate_entry.Created_At $orderby LIMIT $row, $rowperpage");

$a = 1;
while ($row = mysqli_fetch_assoc($certificateBreakOutRecords)) {
  $upload_file = '';
  if (!empty($row['Docket_Id'])) {
    $checkUploadfile = $conn->query("SELECT upload_file,dispatch_date FROM `dispatch_certificate` WHERE dockect_id = '".$row['Docket_Id']."'");
    $checkUploadfile = mysqli_fetch_assoc($checkUploadfile);
    if(!empty($checkUploadfile)) {
      $upload_file = !is_null($checkUploadfile['upload_file']) ? $checkUploadfile['upload_file'] : '';
      $dispatch_date = (!empty($checkUploadfile['dispatch_date'])) ? date_format(date_create($checkUploadfile['dispatch_date']),'d-m-Y') : '';
    }
  }
  $insert_date = date_format(date_create($row['insert_date']),'d-M-Y');
  $data[] = array(
    'Slno' => $a,
    'Center_Name' => $row['Center_Name'],
    'Marksheet_count' => $row['Marksheet_count'],   
    'Docket_Id' => $row['Docket_Id'],
    'Center_id' => $row['Added_For'] , 
    'upload_file' => $upload_file,
    'Dispatch_status' => $row['Dispatch_status'],
    'insert_date' => $insert_date,
    'dispatch_date' => $dispatch_date 
  );
  $a++;
}

## Response
$response = array(
  "draw" => intval($draw),
  "iTotalRecords" => $totalRecords,
  "iTotalDisplayRecords" => $totalRecordwithFilter,
  "aaData" => $data
);

echo json_encode($response);
