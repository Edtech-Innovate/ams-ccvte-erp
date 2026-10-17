<?php
ini_set('display_errors', 1);
require '../../includes/db-config.php';
// echo "<pre>";print_r($_POST);die;
list($student_id, $duration, $university_id, $enrollment_no) = explode('|', $_POST['student_id']);
$created_at = date("Y-m-d:H:i:s");
$obt_marks = array_filter($_POST['obt_marks']);
$subject_ids = array_filter($_POST['subject_id']);
$obt_marks_ints = array_filter($_POST['max_marks_int']);
$obt_marks_exts = array_filter($_POST['max_marks_ext']);
$updated_enrollment_no = isset($_POST['enrollment_no']) ? $_POST['enrollment_no'] : '';

$inserted_count = 0;
$updated_count = 0;

foreach ($obt_marks as $i => $obt_mark) {
  $subject_id = isset($subject_ids[$i]) ? $subject_ids[$i] : '';
  $obt_marks_int = isset($obt_marks_ints[$i]) ? $obt_marks_ints[$i] : '';
  $obt_marks_ext = isset($obt_marks_exts[$i]) ? $obt_marks_exts[$i] : '';

  $get_min_marks_subject = $conn->query("SELECT s.Min_Marks FROM Syllabi as s WHERE ID = $subject_id");
  $get_min_marks_arr = $get_min_marks_subject->fetch_assoc(); 
  $min_marks = $get_min_marks_arr['Min_Marks'];
//   if($obt_marks_ext >$min_marks){
      $remark = "Pass";
    // } else {
    //   $remark = "Fail";
    // }

  if ($updated_enrollment_no) {
  //  echo "<pre>"; echo "UPDATE `marksheets` SET `obt_marks_ext` = '" . $obt_marks_ext . "', `obt_marks_int` = '" . $obt_marks_int . "',  `obt_marks` = '" . $obt_mark . "', `created_at` = '" . $created_at . "', `remarks`= '".$remark."' WHERE `enrollment_no` = '" . $updated_enrollment_no . "' AND `subject_id` = '" . $subject_id . "'";
    $result = $conn->query("UPDATE `marksheets` SET `obt_marks_ext` = '" . $obt_marks_ext . "', `obt_marks_int` = '" . $obt_marks_int . "',  `obt_marks` = '" . $obt_mark . "', `created_at` = '" . $created_at . "', `remarks`= '".$remark."' WHERE `enrollment_no` = '" . $updated_enrollment_no . "' AND `subject_id` = '" . $subject_id . "'");
    if ($result) {
      $updated_count++;
    }
  } else {
    $result = $conn->query("INSERT INTO `marksheets`(`enrollment_no`, `subject_id`, `obt_marks_ext`, `obt_marks_int`,`obt_marks`,`remarks`,`status`, `created_at`) VALUES ('" . $enrollment_no . "', '" . $subject_id . "', '" . $obt_marks_ext . "', '" . $obt_marks_int . "','" . $obt_mark . "','".$remark."', '1', '" . $created_at . "' )");
    if ($result) {
      $inserted_count++;
    }
  }
}
if ($inserted_count > 0 || $updated_count > 0) {
  echo json_encode(['status' => 200, 'message' => "Result added succefully!!"]);
} else {
  echo json_encode(['status' => 400, 'message' => 'Something went to wrong!!']);
}
