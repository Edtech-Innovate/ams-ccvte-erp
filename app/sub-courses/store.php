<?php

if (isset($_POST['name']) && isset($_POST['course']) && isset($_POST['university_id']) && isset($_POST['scheme']) || isset($_POST['mode']) && isset($_POST['duractions'])) {
  require '../../includes/db-config.php';
  session_start();
  $university_id = intval($_POST['university_id']);
  $course = intval($_POST['course']);
  $name = mysqli_real_escape_string($conn, $_POST['name']);
  $short_name = mysqli_real_escape_string($conn, $_POST['short_name']);
  $scheme = intval($_POST['scheme']);
  $mode = intval($_POST['mode']);
  $department = intval($_POST['department']);
  $durations = isset($_POST['min_duration']) ? $_POST['min_duration'] : NULL;
  $max_duration = 0;
  $applicable = array_key_exists('applicable_in', $_POST) ? $_POST['applicable_in'] : [];
  $lateral = 1;
  $le_start = 1;
  $le_sol = 1;
  $ct_transfer = 1;
  $ct_start = 1;
  $ct_sol = 1;
  $eligibilities = is_array($_POST['eligibilities']) ? array_filter($_POST['eligibilities']) : [];

  if (empty($name) || empty($short_name) || empty($course) || empty($university_id) || empty($scheme) || empty($mode) || empty($eligibilities)) {
    echo json_encode(['status' => 403, 'message' => 'All fields are mandatory!']);
    exit();
  }

  $check = $conn->query("SELECT ID FROM Sub_Courses WHERE (Name like '$name' OR Short_Name LIKE '$short_name') AND University_ID = $university_id AND Course_ID = $course AND Scheme_ID = $scheme");
  if ($check->num_rows > 0) {
    echo json_encode(['status' => 400, 'message' => $short_name . ' already exists!']);
    exit();
  }

  $add = $conn->query("INSERT INTO `Sub_Courses`(`Name`, `Short_Name`, `Course_ID`, `University_ID`, `Scheme_ID`, `Mode_ID`, `Department_ID`,`Min_Duration`, `SOL`, `Lateral`, `LE_Start`, `LE_SOL`, `Credit_Transfer`, `CT_Start`, `CT_SOL`, `Eligibility`) VALUES  ('$name', '$short_name', $course, $university_id, $scheme, $mode,$department,$durations, $max_duration, $lateral, '$le_start', $le_sol, $ct_transfer, $ct_start, $ct_sol, '" . json_encode($eligibilities) . "')");
  if ($add) {
    echo json_encode(['status' => 200, 'message' => $name . ' added successlly!']);
  } else {
    $conn->query("DELETE FROM Sub_Courses WHERE ID = $sub_course_id");
  }
} else {
  echo json_encode(['status' => 400, 'message' => 'Something went wrong!']);
}
