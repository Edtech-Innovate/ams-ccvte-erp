<?php
require '../../includes/db-config.php';
require '../../includes/helpers.php';

$username = mysqli_real_escape_string($conn, $_GET['user_id']);
$year = intval($_GET['year']);

$check = $conn->query("SELECT ID FROM tbl_results WHERE tbl_results.Registration_No LIKE '$username' AND CAST(Year as UNSIGNED) = $year");
if ($check->num_rows > 0) {
  
  // echo json_encode(['status' => true, 'url' => "http://ccvte-erp.local/student/examination/marksheets_pdf?registrationNo=$username&year=$year"]);
  echo json_encode(['status' => true, 'url' => "https://ams.ccvte.org/student/examination/marksheets_pdf?registrationNo=$username&year=$year"]);
} else {
  echo json_encode(array("message" => "Result not found!", "status" => false));
}

$conn->close();
