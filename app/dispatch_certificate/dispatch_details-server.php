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
  $orderby = "ORDER BY dispatch_certificate.ID ASC";
}

## Search 
$searchQuery = "";
if($searchValue != '') {
  $searchQuery = "AND (dispatch_certificate.dockect_id LIKE '%$searchValue%' OR dispatch_certificate.consignment_no LIKE '%$searchValue%' OR dispatch_certificate.dispatch_by LIKE '%$searchValue%')";
}

if ($_POST['docket_id']) {
  $docket_id = mysqli_real_escape_string($conn,$_POST['docket_id']);
}

## Total number of records without filtering
$all_count=$conn->query("SELECT COUNT(ID) as allcount FROM `dispatch_certificate` WHERE dockect_id = '$docket_id'");
$records = mysqli_fetch_assoc($all_count);
$totalRecords = $records['allcount'];

## Total number of record with filtering
$filter_count = $conn->query("SELECT COUNT(ID) as filtered FROM `dispatch_certificate` WHERE dockect_id = '$docket_id' $searchQuery");
$records = mysqli_fetch_assoc($filter_count);
$totalRecordwithFilter = $records['filtered'];

$data = [];
## Fetch records
$certificateRecords = $conn->query("SELECT dispatch_certificate.* FROM `dispatch_certificate` WHERE dispatch_certificate.dockect_id = '$docket_id' $searchQuery $orderby LIMIT $row, $rowperpage");

while ($row = mysqli_fetch_assoc($certificateRecords)) {
    if($row['dispatch_date']) {
        $dispatch_date = date_format(date_create($row['dispatch_date']),'d-M-Y');
    }
    $dispatch_mode_name = ($row['mode'] == '1') ? 'By Courier Company' : 'By Person';
    $scan_copy = (!is_null($row['scan_copy'])) ? $row['scan_copy'] : '';
    $upload_file = (!is_null($row['upload_file'])) ? $row['upload_file'] : '';
    $data[] = array(
        'ID' => $row['id'],
        'consignment_no' => $row['consignment_no'],
        'dispatch_by' => $row['dispatch_by'],
        "dispatch_date" => $dispatch_date,
        "scan_copy" => $scan_copy,
        "upload_file" => $upload_file,
        "Docket_Id" => $row['dockect_id'] ,
        'courier_by' => $row['courier_by'],
        'dispatch_mode' => $row['mode'],
        'dispatch_mode_name' => $dispatch_mode_name
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
