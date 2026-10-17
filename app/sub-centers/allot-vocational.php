<?php
if (isset($_POST['university_id']) && isset($_POST['id']) && isset($_POST['counsellor'])) {
  require '../../includes/db-config.php';
  session_start();

  $id = intval($_POST['id']);
  $university_id = intval($_POST['university_id']);
  $counsellor = intval($_POST['counsellor']);
  $sub_counsellor = intval($_POST['sub_counsellor']);
  $course_types = is_array($_POST['course_type']) ? $_POST['course_type'] : [];
  $fees = isset($_POST['fee']) && is_array($_POST['fee']) ? $_POST['fee'] : [];
  $fees = array_filter($fees);

  if (empty($counsellor) || empty($university_id) || empty($id) || empty($fees)) {
    echo json_encode(['status' => 403, 'message' => 'Missing required fields!']);
    exit();
  }

  $check = $conn->query("SELECT ID FROM Alloted_Center_To_Counsellor WHERE Code = $id AND University_ID = $university_id");
  if ($check->num_rows > 0) {
    $update_allot_counsellor = $conn->query("UPDATE Alloted_Center_To_Counsellor SET Counsellor_ID = $counsellor WHERE Code = $id AND University_ID = $university_id");
  } else {
    $update_allot_counsellor = $conn->query("INSERT INTO Alloted_Center_To_Counsellor (`Counsellor_ID`, `Code`, `University_ID`) VALUES ($counsellor, $id, $university_id)");
  }

  $conn->query("DELETE FROM Sub_Center_Course_Types WHERE `User_ID` = $id AND University_ID = $university_id");
  foreach ($course_types as $course_type) {
    $conn->query("INSERT INTO Sub_Center_Course_Types (`User_ID`, `Course_Type_ID`, `University_ID`) VALUES ($id, $course_type, $university_id)");
  }

  $conn->query("DELETE FROM Sub_Center_Sub_Courses WHERE `User_ID` = $id AND University_ID = $university_id");
  foreach ($fees as $sub_course_id => $fee) {

    $course_id_sql = $conn->query("SELECT Course_ID, Min_Duration as duration, Mode_ID  FROM Sub_Courses WHERE ID = $sub_course_id AND University_ID = $university_id");
    $course_id_arr = $course_id_sql->fetch_assoc();
    $course_id = $course_id_arr['Course_ID'];
    $duration = $course_id_arr['duration'];
    if($course_id_arr['Mode_ID']==1){
        $duration = $duration*12;
    }
    // print_r($duration);die;
    $allot = $conn->query("INSERT INTO Sub_Center_Sub_Courses (`Fee`, `Duration`, `User_ID`, `Course_ID`, `Sub_Course_ID`, `University_ID`) VALUES ($fee, $duration, $id, $course_id, $sub_course_id, $university_id)");
  }

  if ($update_allot_counsellor) {
    echo json_encode(['status' => 200, 'message' => 'University alloted successfully!']);
  } else {
    echo json_encode(['status' => 403, 'message' => 'Unable to allot university!']);
  }
}
