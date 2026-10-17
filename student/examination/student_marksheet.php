<?php
require '../../includes/db-config.php';
require '../../includes/helpers.php';

session_start();
$username = $_GET['user_id'];
$password  = $_GET['password'];
$url = "https://ams.ccvte.org/";
$passFail = "PASS";
$years = $conn->query("SELECT DISTINCT CAST(Year as UNSIGNED) as year FROM tbl_results WHERE tbl_results.Registration_No LIKE '$username'");
if ($years->num_rows > 0) {
  $studentYears = array();
  while ($year = $years->fetch_assoc()) {
    $studentYears[] = $year['year'];
  }

  echo json_encode(['status' => true, 'years' => $studentYears, 'userId'=>$username]);

} else {
  echo json_encode(array("message" => "Invalid username or password", "status" => false));
}

$conn->close();