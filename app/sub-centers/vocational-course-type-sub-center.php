<?php
ini_set('display_errors', 1);

if (isset($_GET['id']) && isset($_GET['university_id'])) {
  require '../../includes/db-config.php';
  require '../../includes/helpers.php';


  $university_id = intval($_GET['university_id']);
  $id = intval($_GET['id']);

  $subcenterQuery = $conn->query("SELECT Code, ID,Role FROM Users WHERE ID=$id AND Role='Sub-Center'");
  $subcenterArr = $subcenterQuery->fetch_assoc();
  $subcentercode = explode('.', $subcenterArr["Code"]);
  $centerCode = $subcentercode[0];
  $centerQuery = $conn->query("SELECT ID, Code, Role FROM Users WHERE Code='$centerCode' AND Role='Center'");
  $centerArr = $centerQuery->fetch_assoc();
  if ($university_id == 48) {
    $course_id = $conn->query("SELECT Course_ID, Sub_Course_ID FROM Center_Sub_Courses WHERE `User_ID` = " . $centerArr['ID'] . " AND University_ID = $university_id GROUP BY Sub_Course_ID");
  } else {
    $course_id = $conn->query("SELECT Course_ID, Sub_Course_ID FROM Center_Sub_Courses WHERE `User_ID` = " . $centerArr['ID'] . " AND University_ID = $university_id");
  }
  while ($courseIdArr = $course_id->fetch_assoc()) {

    $subCourseId = $courseIdArr['Sub_Course_ID'];
    $courseId = $courseIdArr['Course_ID'];
    // echo "SELECT Sub_Courses.ID, Sub_Courses.Name, Course_ID,CONCAT(Courses.Short_Name, ' (', Sub_Courses.Name, ')') AS Sub_Course, University_ID, Min_Duration as durections FROM Sub_Courses LEFT JOIN Courses ON Center_Sub_Courses.Course_ID = Courses.ID WHERE `Sub_Courses.ID` = $subCourseId AND `Sub_Courses.Course_ID` = $courseId AND Sub_Courses.University_ID = $university_id";die;
    $subCourseQuery = $conn->query("SELECT Sub_Courses.ID, Sub_Courses.Name, Course_ID,CONCAT(Courses.Short_Name, ' (', Sub_Courses.Name, ')') AS Sub_Course, Sub_Courses.University_ID, Min_Duration as durections FROM Sub_Courses LEFT JOIN Courses ON Sub_Courses.Course_ID = Courses.ID WHERE Sub_Courses.ID = $subCourseId AND Sub_Courses.Course_ID = $courseId AND Sub_Courses.University_ID = $university_id");
    $subCourseArr = $subCourseQuery->fetch_assoc();
    $subCourseData[] = $subCourseArr;
  }

  $courseTypeQuery = $conn->query("SELECT Course_Type_ID FROM Courses WHERE ID=$courseId AND Status =1");
  $courseTypeId = $courseTypeQuery->fetch_assoc();

  $user_id = $centerArr['ID'];

  $type_ids = $courseTypeId['Course_Type_ID'];
  if (empty($type_ids)) {
    exit;
  }
  $fees = [];
  $alloted_fees = $conn->query("SELECT Fee, Sub_Course_ID FROM Sub_Center_Sub_Courses WHERE `User_ID` = $id AND `University_ID` = $university_id");

  while ($alloted_fee = $alloted_fees->fetch_assoc()) {
    $fees[$alloted_fee['Sub_Course_ID']] = $alloted_fee['Fee'];
  }
  foreach ($subCourseData as $sub_course) {
?>
    <div class="row pb-2">
      <div class="col-md-9">
        <dt class="pt-1">
          <?= $sub_course['Sub_Course']." - ".getMonths($sub_course['durections']) ; ?>
        </dt>
      </div>
      <div class="col-md-3">
        <input type="hidden" id="course_type" name="course_type[]" value="<?= $type_ids ?>">
        <input type="number" min="0" step="500" placeholder="Fee" name="fee[<?= $sub_course['ID'] ?>]" value="<?php echo array_key_exists($sub_course['ID'], $fees) ? $fees[$sub_course['ID']] : '' ?>" class="form-control" />
      </div>
    </div>
<?php }
}
