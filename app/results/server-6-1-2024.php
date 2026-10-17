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
  $orderby = "ORDER BY m.id DESC ";
}
// Admin Query
$query = "";

## Search 
$searchQuery = " ";
if($searchValue != ''){
  $searchQuery = " AND (s.Name like '%".$searchValue."%' OR m.enrollment_no like '%".$searchValue."%' OR m.max_marks_ext like '%".$searchValue."%' OR m.max_marks_ext like '%".$searchValue."%' OR m.obt_marks like '%".$searchValue."%')";
}

## Total number of records without filtering

$all_count=$conn->query("SELECT COUNT(m.id) as allcount from marksheets as m LEFT JOIN Syllabi AS s ON m.subject_id=s.ID WHERE m.status=1 $query");
$records = mysqli_fetch_assoc($all_count);
$totalRecords = $records['allcount'];

## Total number of record with filtering

$filter_count = $conn->query("SELECT COUNT(m.id) as filtered from marksheets as m LEFT JOIN Syllabi AS s ON m.subject_id=s.ID WHERE m.status=1 $searchQuery $query");
$records = mysqli_fetch_assoc($filter_count);
$totalRecordwithFilter = $records['filtered'];

## Fetch records
 $result_record ="SELECT m.id,m.enrollment_no, m.max_marks_ext,s.Min_Marks, m.max_marks_int, m.obt_marks AS total, m.status,s.Name AS subject_name  from marksheets as m LEFT JOIN Syllabi AS s ON m.subject_id=s.ID WHERE m.status=1 $orderby";
$results = mysqli_query($conn, $result_record);
$data = array();
while ($row = mysqli_fetch_assoc($results)) {
  // echo "<pre>"; print_r($row);
   if($row['max_marks_ext'] < $row['Min_Marks']){
    $status= "Pass";
   }else{
    $status = "Fail";
   }

    $data[] = array( 
      "enrollment_no" => $row["enrollment_no"],
      "max_marks_ext" => $row["max_marks_ext"],
      "max_marks_int" => $row["max_marks_int"],
      "total"=>$row['total'],
      "status" =>$status,
      "subject_name"=> $row['subject_name'],
      "ID" => $row["id"],
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
