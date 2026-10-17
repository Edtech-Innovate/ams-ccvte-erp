<?php
  ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if (isset($_POST['enroll'])) {

	require '../../includes/db-config.php';
	session_start();

	$enroll = mysqli_real_escape_string($conn, $_POST['enroll']);

	$intMarks = $_POST['obt_int_marks'] ?? [];
	$extMarks = $_POST['obt_ext_marks'] ?? [];
	$maxMarks = $_POST['max_marks'] ?? [];

	if (empty($intMarks) && empty($extMarks)) {
		echo json_encode(['status' => 400, 'message' => 'No marks received!']);
		exit;
	}

	foreach ($maxMarks as $sub_id => $max) {

		$obt_int = mysqli_real_escape_string($conn, $intMarks[$sub_id] ?? "");
		$obt_ext = mysqli_real_escape_string($conn, $extMarks[$sub_id] ?? "");

		// VALIDATION
		if ($obt_int !== "ab" && $obt_int !== "" && ($obt_int < 0 || $obt_int > $max)) {
			echo json_encode(['status' => 400, 'message' => "Invalid Internal Marks for subject $sub_id"]);
			exit;
		}

		if ($obt_ext !== "ab" && $obt_ext !== "" && ($obt_ext < 0 || $obt_ext > $max)) {
			echo json_encode(['status' => 400, 'message' => "Invalid External Marks for subject $sub_id"]);
			exit;
		}
    
		// CHECK EXISTING
		$check = $conn->query("SELECT id FROM marksheets WHERE enrollment_no='$enroll' AND subject_id='$sub_id' ");
			$yearandmonth = getLastExamSession($enroll);
				$exam_month = $yearandmonth[0];
				$exam_year = $yearandmonth[1];
				$month = $yearandmonth[2];
				// $paper_code = 'P1';
					$remarks = "Pass";
					$status = 1;
				$total = (int)$obt_int + (int)$obt_ext;
    
		        $makeSeries = makeSeries($enroll,$month,$exam_year);
		      //  print_r($makeSeries);die;
		        $date = DateTime::createFromFormat('!m', $exam_month);
                $monthName = $date->format('M');
                $certificateNumber = getCertificateNumber($makeSeries);
		      //  $check = $conn->query("SELECT * FROM marksheets WHERE enrollment_no='$enroll' AND subject_id = $sub_id ");
        if ($check->num_rows == 0) {
          $add = $conn->query("INSERT INTO marksheets (enrollment_no, subject_id, obt_marks_ext,obt_marks_int,obt_marks,remarks,status,exam_month,series,certificate_number) VALUES ('$enroll', '$sub_id', '$obt_ext','$obt_int','$total','Pass',1,'$monthName','$makeSeries','$certificateNumber')");
          if (!$add) {
            echo json_encode(['status' => 500, 'message' => 'Database error!']);
			exit;
          }
        } else {
          $update = $conn->query("UPDATE marksheets SET obt_marks_ext = '$obt_ext',obt_marks_int='$obt_int',obt_marks='$total',series='$makeSeries',certificate_number='$certificateNumber' WHERE enrollment_no='$enroll' AND subject_id = $sub_id");
          if (!$update) {
            echo json_encode(['status' => 500, 'message' => 'Database error!']);
			exit;
          }
        }
	}

	echo json_encode(['status' => 200, 'message' => 'Marks saved successfully!']);
}


function getLastExamSession($enrol){
  global $conn;
//   print_r("select Students.Enrollment_No,Students.Mode_ID, Admission_Sessions.Name as admission_session left Join Admission_Sessions on Admission_Sessions.ID = Students.Admission_Sessions_ID from Students where Students.Enrollment_No= '$enrol'");die;
  $admissionSession = $conn->query("select Students.Enrollment_No,Students.Duration,Students.Mode_ID,Sub_Courses.Name, Admission_Sessions.Name as admission_session from Students left join Sub_Courses on Sub_Courses.ID = Students.Sub_Course_ID left Join Admission_Sessions on Admission_Sessions.ID = Students.Admission_Session_ID  where (Students.Enrollment_No= '$enrol' or Unique_ID='$enrol')")->fetch_assoc();
  $explodeSessions = explode("-",$admissionSession['admission_session']);
//   print_r($admissionSession['admission_session']);die;
  $month = $explodeSessions[0];
  $year = $explodeSessions[1];
  $date = DateTime::createFromFormat('M', $month);
  $monthNumber = $date->format('m');
  if($admissionSession['Mode_ID']==1){
      $examMonth = $month+$explodeSessions['duration'];
  }
  else{
      $examMonth = $monthNumber+6;
  }
  return array($examMonth,$year,$monthNumber);
}

function makeSeries($enroll,$month,$year){
     global $conn;
    //  print_r("select series from marksheets where enrollment_no='$enroll'");die;
     $exists = $conn->query("select series from marksheets where enrollment_no='$enroll'")->fetch_assoc();
    //  print_r($exists);die;
     if(count($exists??[])==0 || $exists['series']==""){
        //  $exists = $conn->query("select series from marksheets where enrollment_no='$enroll'")->fetch_assoc();
        $prefix = $year.$month;
        //  $series = $prefix.'00001';
        // print_r("select max(series) from marksheets where series like '$prefix%'");die;
         $checkMarksheetsSeries = $conn->query("select max(series) as series from marksheets where series like '$prefix%'")->fetch_assoc();
         if($checkMarksheetsSeries['series']==""){
            $number = 1;
            $series = $prefix . str_pad($number, 5, '0', STR_PAD_LEFT);  
         }else{
             $series = $checkMarksheetsSeries['series']+1;
         }
     }else{
         $series = $exists['series'];
     }
     return $series;
}
function getCertificateNumber($no){
        $number = $no[2].$no[3].$no[0].$no[1];
        // unset($no[3]);
        // unset($no[2]);
        // unset($no[1]);
        // unset($no[0]);
        $result = substr($no, 4);
        return $number.$result;
}