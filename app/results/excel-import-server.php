<?php
/* error_reporting(E_ALL);
 ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);*/
if (isset($_FILES['file'])) {
  require '../../includes/db-config.php';
  require '../../includes/helpers.php';

  require('../../extras/vendor/shuchkin/simplexlsxgen/src/SimpleXLSXGen.php');
  require('../../extras/vendor/nuovo/spreadsheet-reader/SpreadsheetReader.php');
  session_start();
  
   //ini_set('display_errors', 0);
   //ini_set('display_startup_errors', 0);
   //error_reporting(E_ALL);
  ini_set('max_execution_time', 1400);
  $export_data = array();
  $header = array('Course', 'Sub-Course', 'Enrollment Number', 'Subject Code', 'Obtained External Marks', 'Obtained Internal Marks', 'Semester', 'Exam Month', 'Exam Year', 'Paper Code','certificate_date','exam_date', 'Remark');
  $export_data[] = $header;
  $mimes = ['application/vnd.ms-excel', 'text/xls', 'text/xlsx', 'application/vnd.oasis.opendocument.spreadsheet', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];

  if (in_array($_FILES["file"]["type"], $mimes)) {
     
    $uploadFilePath = basename($_FILES['file']['name']);
    move_uploaded_file($_FILES['file']['tmp_name'], $uploadFilePath);
    $reader = new SpreadsheetReader($uploadFilePath);
    $totalSheet = count($reader->sheets());
    for ($i = 0; $i < $totalSheet; $i++) {
      $reader->ChangeSheet($i);

      foreach ($reader as $key=> $row) {
          


        $course = mysqli_real_escape_string($conn, trim($row[0]));
        $sub_course = mysqli_real_escape_string($conn, trim($row[1]));
        $enrollment = mysqli_real_escape_string($conn, $row[2]);
        $subject_code = mysqli_real_escape_string($conn, trim($row[3]));
        $obt_ext_marks = (isset($row[4]) && $row[4] != '') ? mysqli_real_escape_string($conn, trim($row[4])) : 0;
        $obt_int_marks = (isset($row[5]) && $row[5] != '') ? mysqli_real_escape_string($conn, trim($row[5])) : 0;
        $semester = mysqli_real_escape_string($conn, $row[6]);
        $exam_month = isset($row[7]) ? mysqli_real_escape_string($conn, $row[7]) : '';
        $exam_year = isset($row[8]) ? ($row[8]) : '';
        $paper_code = mysqli_real_escape_string($conn, $row[9]);
        $certificate_date = mysqli_real_escape_string($conn, $row[10]);
        $exam_date = mysqli_real_escape_string($conn, $row[11]);
        $created_at = date("Y-m-d:H:i:s");
        // if($key>0)
        $course = $conn->query("SELECT ID,University_ID FROM Courses WHERE University_ID = " . $_SESSION['university_id'] . " AND (Name = '$course' or Short_Name = '$course') ");

        if ($course->num_rows == 0) {
          $export_data[] = array_merge($row, ['Course not found!']);
          continue;
        }

        $course_ids = array();
        while ($course_id = $course->fetch_assoc()) {
          $course_ids[] = $course_id['ID'];
        }
//print_r("SELECT ID, Course_ID,Name FROM Sub_Courses WHERE University_ID = " . $_SESSION['university_id'] . " AND Name = '$sub_course' AND Course_ID IN (" . implode(',', $course_ids) . ")");die;
        $sub_course = $conn->query("SELECT ID, Course_ID,Name FROM Sub_Courses WHERE University_ID = " . $_SESSION['university_id'] . " AND Name = '$sub_course' AND Course_ID IN (" . implode(',', $course_ids) . ")");
        if ($sub_course->num_rows == 0) {
          $export_data[] = array_merge($row, ['Sub-Course not found!']);
          continue;
        }

        $sub_course = $sub_course->fetch_assoc();
        $course_id = $sub_course['Course_ID'];
        $sub_course_id = $sub_course['ID'];
        $sub_course_name = $sub_course['Name'];
       
        if((empty($obt_int_marks) || $obt_int_marks=='' || $obt_int_marks==0)){
          $checkres = $conn->query("SELECT marksheets.obt_marks_int FROM marksheets LEFT JOIN Syllabi on subject_id = Syllabi.ID  WHERE enrollment_no ='$enrollment' AND University_ID = " . $_SESSION['university_id'] . " AND Code = '$subject_code' AND Syllabi.Semester =  '".$semester."'  AND Course_ID = $course_id AND Sub_Course_ID = $sub_course_id");
          if($checkres->num_rows > 0){
           $obt_int_marks = $checkres->fetch_assoc()['obt_marks_int'];
          }
         }

         if((empty($obt_ext_marks) || $obt_ext_marks=='' || $obt_ext_marks==0) ){
          $checkres = $conn->query("SELECT marksheets.obt_marks_ext FROM marksheets LEFT JOIN Syllabi on subject_id = Syllabi.ID  WHERE enrollment_no ='$enrollment' AND University_ID = " . $_SESSION['university_id'] . " AND Code = '$subject_code' AND Syllabi.Semester = '".$semester."'  AND Course_ID = $course_id AND Sub_Course_ID = $sub_course_id");
          if($checkres->num_rows > 0){
           $obt_ext_marks = $checkres->fetch_assoc()['obt_marks_ext'];
          }
         }
        
        $check_enrollment = $conn->query("SELECT ID FROM Students WHERE Unique_ID = '$enrollment'");
        if ($check_enrollment->num_rows == 0) {
            $export_data[] = array_merge($row, ['Invalid Enrollment No.']);
            continue;
        }


        $check_student_sub_course = $conn->query("SELECT Users.Code,Users.ID, Role FROM Students LEFT JOIN Users on Added_For= Users.ID WHERE Unique_ID = '$enrollment' AND Sub_Course_ID = '$sub_course_id'");
        if ($check_student_sub_course->num_rows == 0) {
          $export_data[] = array_merge($row, ['This Sub-Course is not assigned to this Student.']);
          continue;
        }

        $subjects = $conn->query("SELECT ID, Min_Marks,Max_Marks,University_ID FROM Syllabi WHERE University_ID = " . $_SESSION['university_id'] . " AND Code = '$subject_code' AND Semester = '" . $semester . "'  AND Course_ID = $course_id AND Sub_Course_ID = $sub_course_id");
     
           //print_r("SELECT ID, Min_Marks,Max_Marks,University_ID FROM Syllabi WHERE University_ID = " . $_SESSION['university_id'] . " AND Code = '$subject_code' AND Semester = '" . $semester . "'  AND Course_ID = $course_id AND Sub_Course_ID = $sub_course_id");die;
       // print_r($subjects->num_rows);die;
        if ($subjects->num_rows == 0) {
          $error_message = "Subject not found!";

          $export_data[] = array_merge($row, [$error_message]);
          continue;
        }

        $subject_ids = array();
        $subject_arr = $subjects->fetch_assoc();
        $subject_ids = $subject_arr['ID'];

        if ($obt_ext_marks == 'AB' || $obt_int_marks == 'AB' || $obt_ext_marks == 0 || $obt_int_marks == 0) {
          $remarks = "Fail";
          $obt_ext_marks = ($obt_ext_marks == 'AB') ? 'AB' : $obt_ext_marks;
          $obt_int_marks = ($obt_int_marks == 'AB') ? 'AB' : $obt_int_marks;
        }
     
        
        if ($obt_ext_marks != 'AB' && $obt_int_marks !== 'AB') {
          $total = (int)$obt_ext_marks + (int)$obt_int_marks;
          $total = 0;
        }


        $min_marks = ($subject_arr['Min_Marks'] + $subject_arr['Max_Marks']) * 40 / 100;
        if ($total < $min_marks) {
          $remarks = "Fail";
        } else {
          $remarks = "Pass";
        }

        $status = ($remarks == 'Fail') ? 0 : 1 ;
        $yearandmonth = getLastExamSession($enrollment);
        
				// $exam_month = $yearandmonth[0];
				// $exam_year = $yearandmonth[1];
				$month = $yearandmonth[2];
				// $paper_code = 'P1';
					$remarks = "Pass";
					$status = 1;
				$total = (int)$obt_int_marks + (int)$obt_ext_marks;
    
		        $makeSeries = makeSeries($enrollment,$month,$exam_year);
		      //  print_r($makeSeries);die;
		     
		        
		      //   print_r($exam_month);die;
                $monthName = $exam_month;
                $certificateNumber = getCertificateNumber($makeSeries);
                // print_r($certificateNumber);die;
                $date = DateTime::createFromFormat('M', $exam_month);
                $monthNumber = $date->format('m');
                // 
                //$certification_date = "20".$exam_year.'-'.($exam_month+1).'-'."14";
                // print_r($monthNumber);die;
        $check = $conn->query("SELECT * FROM marksheets WHERE enrollment_no ='$enrollment' AND subject_id='$subject_ids' ");

        if ($check->num_rows > 0) {

          $update = $conn->query("UPDATE marksheets SET exam_month = '$row[7]', exam_year='$row[8]', exam_date = '$exam_date', certification_date='$certificate_date',certificate_number='$certificateNumber',obt_marks_ext = '$obt_ext_marks',obt_marks_int='$obt_int_marks',obt_marks='$total',series='$makeSeries' WHERE enrollment_no='$enrollment' AND subject_id = $subject_ids");
          if ($update) {
            $export_data[] = array_merge($row, ['Result Updated successfully!']);
          } else {
            $export_data[] = array_merge($row, ['Something went wrong!']);
          }
        } else {
        //   $Marksheetresponse = getSerialNumberAndDateOfIssue($enrollment,$exam_month,$exam_year);
        //   if($Marksheetresponse['status'] == 400) {
        //     $export_data[] = array_merge($row,[$Marksheetresponse['message']]);
        //     continue;
        //   } 
        //   $certificateSerialNUmber = fetchCertificateSerialNum($enrollment);
        //   if($certificateSerialNUmber['status'] == 400) {
        //     $export_data[] = array_merge($row,[$certificateSerialNUmber['message']]);
        //     continue;
        //   } 
        //   list($serialNum,$dateOfIssue) = $Marksheetresponse['message'];
        //   $certificateSerNum = $certificateSerialNUmber['message'];

        //   $add = $conn->query("INSERT INTO `marksheets`(`enrollment_no`, `subject_id`, `obt_marks_ext`, `obt_marks_int`, `obt_marks`, `remarks`, `status`, `exam_month`,`exam_year`,`created_at`,`paper_code`,`date_of_issue`,`serial_number`,`certificateSerialNum`) VALUES ('" . $enrollment . "', " . $subject_ids . ", '" . $obt_ext_marks . "','" . $obt_int_marks . "', '" . $total . "', '" . $remarks . "', '" .$status. "', '" . $exam_month . "', $exam_year,'$created_at','" . $paper_code . "' , '" . $dateOfIssue . "','" .$serialNum. "','" . $certificateSerNum . "')");
          //echo "<pre>";print_r("INSERT INTO marksheets (enrollment_no, subject_id, obt_marks_ext,obt_marks_int,obt_marks,remarks,status,exam_month,series,certificate_number,certification_date,exam_date,exam_year) VALUES ('$enrollment', '$subject_ids', '$obt_ext_marks','$obt_int_marks','$total','Pass',1,'$monthName','$makeSeries','$certificateNumber','$certificate_date','$exam_date','$exam_year')");die;
          $add = $conn->query("INSERT INTO marksheets (enrollment_no, subject_id, obt_marks_ext,obt_marks_int,obt_marks,remarks,status,exam_month,series,certificate_number,certification_date,exam_date,exam_year) VALUES ('$enrollment', '$subject_ids', '$obt_ext_marks','$obt_int_marks','$total','Pass',1,'$monthName','$makeSeries','$certificateNumber','$certificate_date','$exam_date','$exam_year')");
          $export_data[] = ($add) ? array_merge($row,['Result added successfully!']) : array_merge($row, ['Something went wrong!']);
        }
      }
    }
    unlink($uploadFilePath);
    $xlsx = SimpleXLSXGen::fromArray($export_data)->downloadAs('Result Status ' . date('h m s') . '.xlsx');
  }
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
     // $examMonth = $month+$explodeSessions['duration'];
    //  echo('<pre>');print_r($admissionSession);die;
     $examMonth = $monthNumber+$admissionSession['Duration'];
  
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
