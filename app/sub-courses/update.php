<?php
if (isset($_POST['name']) && isset($_POST['course']) && isset($_POST['university_id']) && isset($_POST['scheme']) || isset($_POST['mode']) && isset($_POST['min_duration']) && isset($_POST['max_duration']) && isset($_POST['id'])) {
  require '../../includes/db-config.php';
  require '../../includes/helpers.php';

  session_start();
  $id = intval($_POST['id']);
  $university_id = intval($_POST['university_id']);
  $course = intval($_POST['course']);
  $name = mysqli_real_escape_string($conn, $_POST['name']);
  $short_name = mysqli_real_escape_string($conn, $_POST['short_name']);

  $scheme = intval($_POST['scheme']);
  $mode = intval($_POST['mode']);
  
  $department = intval($_POST['department']);
  $eligibilities = is_array($_POST['eligibilities']) ? array_filter($_POST['eligibilities']) : [];
  $applicable = array_key_exists('applicable_in', $_POST) ? $_POST['applicable_in'] : [];
  $min_duration =  isset($_POST['min_duration']) ? $_POST['min_duration'] : null;
  $max_duration = 0;
  $lateral = 0;
  $le_start = '';
  $le_sol = 0;
  $ct_transfer = 0;
  $ct_start = 0;
  $ct_sol = 0;

  if (empty($name) || empty($short_name) || empty($course) || empty($university_id) || empty($scheme) || empty($mode) || empty($id)) {
    echo json_encode(['status' => 403, 'message' => 'All fields are mandatory!']);
    exit();
  }

  if (!empty($lateral) && (empty($le_start) || empty($le_sol))) {
    echo json_encode(['status' => 403, 'message' => 'Please fill all LE fields!']);
    exit();
  }

  if (!empty($ct_transfer) && (empty($ct_start) || empty($ct_sol))) {
    echo json_encode(['status' => 403, 'message' => 'Please fill all CT fields!']);
    exit();
  }

  $check = $conn->query("SELECT ID FROM Sub_Courses WHERE (Name like '$name' OR Short_Name LIKE '$short_name') AND University_ID = $university_id AND Course_ID = $course  AND Department_ID = $department AND Scheme_ID = $scheme AND ID <> $id");
  if ($check->num_rows > 0) {
    echo json_encode(['status' => 400, 'message' => $short_name . ' already exists!']);
    exit();
  }

  $update = $conn->query("UPDATE `Sub_Courses` SET `Name` = '$name', `Short_Name` = '$short_name', `Course_ID` = $course, `Scheme_ID` = $scheme, `Mode_ID` = $mode, `Department_ID` = $department, `Min_Duration` = '$min_duration', `SOL` = $max_duration, `Lateral` = $lateral, `LE_Start` = '$le_start', `LE_SOL` = $le_sol, `Credit_Transfer` = $ct_transfer, `CT_Start` = $ct_start, `CT_SOL` = $ct_sol, Eligibility = '" . json_encode($eligibilities) . "'WHERE ID = $id");
  if ($update) {
    echo json_encode(['status' => 200, 'message' => $short_name . ' updated successlly!']);
  } else {
    echo json_encode(['status' => 400, 'message' => 'Something went wrong!']);
  }
}
