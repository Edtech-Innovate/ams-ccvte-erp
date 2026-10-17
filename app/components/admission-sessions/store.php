<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
require '../../../includes/db-config.php';
session_start();

if(isset($_REQUEST['month']) && isset($_REQUEST['year']) && isset($_REQUEST['scheme'])){

  $month = mysqli_real_escape_string($conn, $_REQUEST['month']);
  $year = intval($_REQUEST['year']);
  $exam_session = [0];
  $scheme = intval($_REQUEST['scheme']);
  $university_id = intval($_REQUEST['university_id']);
  $name = $month . '-' . $year;
  $admission_session_type = ($month == 'Jul') ? 1 : 2;

  if (empty($name) || empty($university_id)) {
    exit(sendResponse(false,"All fields are mandatory!"));
  }

  $checkAdmSessionQuery = "SELECT ID FROM Admission_Sessions WHERE Name LIKE '$name' AND University_ID = $university_id";
    $check = $conn->query($checkAdmSessionQuery);
    if ($check->num_rows>0) {
      exit(json_encode(sendResponse(false,$name.' already exists!')));
    }

    $exam_session = json_encode($exam_session);
    $insertAdmSessionQuery = "INSERT INTO `Admission_Sessions` (`Name`, `Exam_Session`, `admission_session_type` , `Scheme_ID`, `University_ID`) VALUES ('$name', '$exam_session', '$admission_session_type' , $scheme, $university_id)";
    $insertAdmSession = $conn->query($insertAdmSessionQuery);
    echo json_encode(['status' => 200 , 'message' => 'Admission Session Created']);
    exit;
}

function insertAdmissionSession($name,$exam_session,$scheme,$university_id , $admission_session_type) {

  global $conn;
  try {
    $checkAdmSessionQuery = "SELECT ID FROM Admission_Sessions WHERE Name LIKE '$name' AND University_ID = $university_id";
    $check = $conn->query($checkAdmSessionQuery);
    if ($check->num_rows>0) {
      exit(json_encode(sendResponse(false,$name.' already exists!')));
    }

    $exam_session = json_encode($exam_session);
    $insertAdmSessionQuery = "INSERT INTO `Admission_Sessions` (`Name`, `Exam_Session`, `admission_session_type` , `Scheme_ID`, `University_ID`) VALUES ('$name', '$exam_session', '$admission_session_type' , $scheme, $university_id)";
    $insertAdmSession = $conn->query($insertAdmSessionQuery);
    
    return ($insertAdmSession) ? sendResponse(true,"Adm. Session Inserted") : sendResponse(false);
  } catch (Exception $e) {
    return sendResponse(false,"Exception : " . $e->getMessage()); 
  }
}

function updateExamSessionDetails() {

  global $conn;

  try { 
    $exam_session = $_REQUEST['exam_session'];
    $checkUpdateRequiredStatus_query = "SELECT ID ,  CASE WHEN serialNum IS NULL OR serialNum_range IS NULL OR dateOfIssue IS NULL THEN 'Update Required' ELSE 'Update Not Required' END AS `exam_session_status` FROM `Exam_Sessions` WHERE ID IN (" . implode(',', $exam_session) . ")";
    $checkUpdateRequiredStatus = $conn->query($checkUpdateRequiredStatus_query);
    $checkUpdateRequiredStatus = mysqli_fetch_all($checkUpdateRequiredStatus,MYSQLI_ASSOC);
    $examSessionStatus = array_column($checkUpdateRequiredStatus,'exam_session_status','ID');

    foreach($examSessionStatus as $id => $message) {

      if ($message == 'Update Required') {
        $serialNum = intval($_REQUEST['serial_number_'.$id]);
        $serialNum_range = intval($_REQUEST['range_'.$id]);
        $dateOfIssue = $_REQUEST['multi_date_'.$id];
        $certificateSerialNum = $_REQUEST['certificate_serial_number_'.$id];

        $updateExamSessionQuery = "UPDATE Exam_Sessions SET serialNum = '$serialNum' , serialNum_range = '$serialNum_range' , dateOfIssue = '$dateOfIssue' , certificate_serialNum = '$certificateSerialNum' WHERE ID = '$id'";
        $conn->query($updateExamSessionQuery);
      }
    }

    return sendResponse(true);
  } catch (Exception $e) {
    return sendResponse(false,"Exception : " . $e->getMessage());
  }
}

function sendResponse($response,$message = "Something went wrong") {

  $result = ($response) ? ['status' => 200 , 'message' => $message] : ['status' => 400 , 'message' => $message];
  return $result;
}
?>
