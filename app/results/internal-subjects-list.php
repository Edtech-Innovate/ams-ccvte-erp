<?php
require '../../includes/db-config.php';
require '../../includes/helpers.php';

session_start();

// echo "<pre>";
// print_r($_SESSION);
// echo "</pre>";
// exit;
$enroll = isset($_POST['enroll']) ? $_POST['enroll'] : "";
$duration = isset($_POST['duration']) ? $_POST['duration'] : "";
$user_code = isset($_POST['user_code']) ? $_POST['user_code'] : "";
$centerQuery = "";
$uni_id = $_SESSION['university_id'];
$marks_type = ($uni_id == 48) ? "External" : "Internal";

if (
    (!empty($user_code) && !empty($enroll) && !empty($duration) && $uni_id == 48) ||
    (!empty($enroll) && !empty($duration) && $uni_id == 47)
) {

    if ($uni_id == 48) {
        $check = $conn->query("SELECT ID, Role, Code FROM Users WHERE Code = '$user_code'");
        if ($check->num_rows === 0) {
            $data = ['status' => 400, 'message' => 'User Not Found!'];
        }
        $userArr = $check->fetch_assoc();
        if ($userArr['Role'] == "Sub-Center") {
            $center_id = getCenterIdFunc($conn, $userArr['ID']);
            $checkArr = $conn->query("SELECT Code FROM Users WHERE ID = '$center_id' AND Role ='Center'");
            if ($checkArr->num_rows === 0) {
                $data = ['status' => 400, 'message' => 'Center Code Not Found!'];
            }
            $centerArr = $checkArr->fetch_assoc();
            $user_code = $centerArr['Code'];
        }
        $user_code = json_encode($user_code, JSON_UNESCAPED_SLASHES);
        $centerQuery = " AND JSON_CONTAINS(User_ID, '$user_code')";
        // $centerQuery = "";
    }

    $subjectQuery = ' and (sy.Exam_Type="1" OR sy.Exam_Type is null) ';

    $check_user = $conn->query("SELECT Added_By, Sub_Course_ID,Mode_ID from Students where Enrollment_No='$enroll' or Unique_ID='$enroll'");
    $stuData = $check_user->fetch_assoc();
    
    $added_by = $stuData['Added_By'];
    $sub_course_id = $stuData['Sub_Course_ID'];
    $vartical_type = checkVerticalType($conn, $added_by);
    if($stuData['Mode_ID']==1){
        $duration = $duration;
    }


    $getSubject = $conn->query("select sy.Min_Marks,sy.Max_Marks,sy.Paper_Type, sy.Name as subject_name,sy.User_ID,sy.Paper_Type, sy.Code, sy.ID from Syllabi as sy left join Students on sy.Sub_Course_ID= Students.Sub_Course_ID AND sy.Course_ID= Students.Course_ID where (Enrollment_No = '$enroll' or Unique_ID='$enroll') AND sy.Semester = '$duration'  $centerQuery  $subjectQuery ORDER BY sy.Code ASC ");
    // $getSubject = $conn->query("select sy.Min_Marks,sy.Max_Marks,sy.Paper_Type, sy.Name as subject_name,sy.Paper_Type, sy.Code, sy.ID from Syllabi as sy left join Students on sy.Sub_Course_ID= Students.Sub_Course_ID AND sy.Course_ID= Students.Course_ID where (Students.Enrollment_No = '$enroll' or Students.Unique_ID='$enroll') AND sy.Semester = '$duration'  $subjectQuery ORDER BY sy.Code ASC ");
    // print_r("select sy.Min_Marks,sy.Max_Marks,sy.Paper_Type, sy.Name as subject_name,sy.Paper_Type, sy.Code, sy.ID from Syllabi as sy left join Students on sy.Sub_Course_ID= Students.Sub_Course_ID AND sy.Course_ID= Students.Course_ID where (Students.Enrollment_No = '$enroll' or Students.Unique_ID='$enroll') AND sy.Semester = '$duration'  $subjectQuery ORDER BY sy.Code ASC ");die;

    $getdata = [];
    while ($row = $getSubject->fetch_assoc()) {
        $getdata[] = $row;
    }


    if (count($getdata) == 0) {
        $message = 'Subject Not Uploaded of this Duration & Sub-Course';
        if ($uni_id == 48) {
            $message .= ' or Exam Type is not a Center Type.';
        }
        $data = ['status' => 400, 'message' => $message];
    } else {
        $data = ['status' => 200];
    }
} else {
    $data = ['status' => 400, 'message' => 'Something went wrong. Please try again!'];
}

?>

<?php if ($data['status'] == 200) { ?>
    <div class="row m-t-10">

        <?php if ($_SESSION['university_id'] == 47) { ?>
            <div class="col-md-4"><span style="font-weight:500; font-size:14px">Subject Name</span></div>
            <div class="col-md-1"><span style="font-weight:500; font-size:14px">Min/Max Marks</span></div>
            <div class="col-md-3 text-center"><span style="font-weight:500; font-size:14px"><?= $marks_type ?> Marks</span></div>
            <div class="col-md-1"><span style="font-weight:500; font-size:14px">Min/Max Marks</span></div>
            <div class="col-md-3 text-center"><span style="font-weight:500; font-size:14px">External Marks</span></div>
        <?php } else { ?>
            <div class="col-md-5"><span style="font-weight:500; font-size:14px">Subject Name</span></div>
            <div class="col-md-3"><span style="font-weight:500; font-size:14px">Min/Max Marks</span></div>
            <!-- <div class="col-md-4 text-center"><span style="font-weight:500; font-size:14px">Internal/External Marks</span></div> -->

            <div class="col-md-2 text-center">
                <span style="font-weight:500; font-size:14px">Internal Marks</span>
            </div>

            <div class="col-md-2 text-center">
                <span style="font-weight:500; font-size:14px">External Marks</span>
            </div>

        <?php } ?>


    </div>
    <input type="hidden" name="enroll" value="<?= $enroll ?>">
    <div class="row mt-4">
        <?php
        $readonly = "";
        // while ($row = $getSubject->fetch_assoc()) {
        foreach ($getdata as $row) {
            $getmarks = $conn->query("SELECT obt_marks_int,obt_marks_ext FROM marksheets WHERE enrollment_no= '$enroll' AND subject_id = '" . $row['ID'] . "'");
            $marks = $getmarks->fetch_assoc();
            $readonly_ext = "";
            if ($getmarks->num_rows > 0 && ($_SESSION['Role'] == 'Center' || $_SESSION['Role'] == 'Sub-Center')) {
                // $readonly = "readonly";
                $readonly = "";
                // $readonly_ext ="readonly";
                $readonly_ext = "";
                // print_r($marks['obt_marks_int']);die;
                if (empty($marks['obt_marks_ext'])) {
                    $readonly_ext = "";
                }
                if (empty($marks['obt_marks_int'])) {
                    $readonly = "";
                }
            } else if ($_SESSION['Role'] == "Operations") {
                // $readonly = "readonly";
                //  $readonly_ext ="readonly";
                if (empty($marks['obt_marks_ext'])) {
                    $readonly_ext = "";
                }
                if (empty($marks['obt_marks_int'])) {
                    $readonly = "";
                }
            }

            $obt_marks_int = isset($marks['obt_marks_int']) ? $marks['obt_marks_int'] : "";
            $obt_marks_ext = isset($marks['obt_marks_ext']) ? $marks['obt_marks_ext'] : "";

            if ($uni_id == 48) {
                $max_obt_ext = $row['Max_Marks'];
                $min_int_marks = $row['Min_Marks'];
                $min_max_marks = $row['Min_Marks'] . '/' . $row['Max_Marks'];
            } else {
                $max_obt_ext = $row['Min_Marks'];
                $min_int_marks = ($row['Min_Marks']) * 40 / 100;
                $min_max_marks = $min_int_marks . '/' . $row['Min_Marks'];
            }

            if ($uni_id == 47 && $row['Min_Marks'] == 0 && $row['Max_Marks'] == 100) {
                $max_obt_ext = $row['Max_Marks'];
                $min_max_marks = 40 . '/' . $row['Max_Marks'];
            }

            $sub_col = ($_SESSION['university_id'] == 47) ? "col-md-4" : "col-md-5";
            $min_max = ($_SESSION['university_id'] == 47) ? "col-md-1" : "col-md-3";
            $internal_marks_col = ($_SESSION['university_id'] == 47) ? "col-md-3" : "col-md-4";


        ?>

            <div class="<?= $sub_col ?>">
                <?= $row['subject_name'] . ' (' . $row['Code'] . ')' ?>
            </div>
            <div class="<?= $min_max ?>">
                <?php if ($uni_id == 47 && $row['Min_Marks'] == 0 && $row['Max_Marks'] == 100 && ($row['Paper_Type'] == 'Practical' or $row['Paper_Type'] == 'Project')) { ?>
                    0/0
                <?php } else { ?>
                    <?= $min_max_marks ?>
                <?php } ?>

            </div>
            <div class="<?= $internal_marks_col ?>">
                <!-- <div class="form-group form-group-default required"> -->
                <!-- <?php if ($_SESSION['university_id'] == 48) { ?>
                        <input type="text" onkeyup="markStatus(this.value, '<?= $min_int_marks ?>','<?= $row['ID'] ?>', '<?= $max_obt_ext ?>')" name="obt_ext_marks[<?= $row['ID'] ?>]" value="<?= $obt_marks_ext ?>"
                            class="form-control" placeholder="Enter marks less than <?= $max_obt_ext ?>" <?= $readonly_ext ?> required>
                        <div class="marks_status_<?= $row['ID'] ?>"></div>
                    <?php } else { ?>
                        <input type="text" onkeyup="markStatus(this.value, '<?= $min_int_marks ?>','<?= $row['ID'] ?>', '<?= $max_obt_ext ?>')" name="obt_marks_int[<?= $row['ID'] ?>]" value="<?= $obt_marks_int ?>"
                            class="form-control" placeholder="Enter marks less than <?= $obt_marks_int ?>" <?= $readonly ?> required>
                        <div class="marks_status_<?= $row['ID'] ?>"></div>
                    <?php } ?> -->
                <?php if ($_SESSION['university_id'] == 48) { ?>

                    <div class="row">

                        <!-- LEFT: INTERNAL MARKS -->
                        <div class="col-md-6">
                            <!-- <label><b>Internal Marks</b></label> -->
                            <div class="form-group form-group-default required">
                                <input type="text"
                                    onkeyup="markStatus(this.value, '<?= $min_int_marks ?>','<?= $row['ID'] ?>', '<?= $max_obt_ext ?>')"
                                    name="obt_int_marks[<?= $row['ID'] ?>]"
                                    value="<?= $obt_marks_int ?>"
                                    class="form-control"
                                    placeholder="Enter Internal marks less than <?= $max_obt_ext ?>"
                                    <?= $readonly ?> required>
                            </div>
                            <div class="marks_status_int_<?= $row['ID'] ?>"></div>
                        </div>

                        <!-- RIGHT: EXTERNAL MARKS -->
                        <div class="col-md-6">
                            <!-- <label><b>External Marks</b></label> -->
                            <div class="form-group form-group-default required">
                                <input type="text"
                                    onkeyup="markStatus(this.value, '<?= $min_int_marks ?>','<?= $row['ID'] ?>', '<?= $max_obt_ext ?>')"
                                    name="obt_ext_marks[<?= $row['ID'] ?>]"
                                    value="<?= $obt_marks_ext ?>"
                                    class="form-control"
                                    placeholder="Enter External marks less than <?= $max_obt_ext ?>"
                                    <?= $readonly_ext ?> required>
                            </div>
                            <div class="marks_status_ext_<?= $row['ID'] ?>"></div>
                        </div>

                        <!-- </div> -->

                    <?php } else { ?>

                        <!-- SINGLE INPUT FOR OTHER UNIVERSITIES -->
                        <div class="row">
                            <div class="col-md-12">
                                <label><b>Marks</b></label>
                                <input type="text"
                                    onkeyup="markStatus(this.value, '<?= $min_int_marks ?>','<?= $row['ID'] ?>', '<?= $max_obt_ext ?>')"
                                    name="obt_marks_int[<?= $row['ID'] ?>]"
                                    value="<?= $obt_marks_int ?>"
                                    class="form-control"
                                    placeholder="Enter marks less than <?= $obt_marks_int ?>"
                                    <?= $readonly ?> required>

                                <div class="marks_status_<?= $row['ID'] ?>"></div>
                            </div>
                        </div>

                    <?php } ?>


                    <input type="hidden" name="max_marks[<?= $row['ID'] ?>]" value="<?= $max_obt_ext ?>">
                    </div>


            </div>



            <!-- start practical marks -->
            <?php if ($_SESSION['university_id'] == 47 && (trim($row['Paper_Type']) === "Practical" || trim($row['Paper_Type']) === "Project")) { ?>

                <div class="col-md-1">
                    <?= $min_max_marks ?>
                </div>
                <div class="col-md-3">
                    <div class="form-group form-group-default required">
                        <input type="text" name="obt_marks_ext_p[<?= $row['ID'] ?>]" value="<?= $obt_marks_ext ?>"
                            class="form-control" placeholder="Enter marks less than <?= $max_obt_ext ?>" <?= $readonly_ext ?> required>
                        <!-- <div class="marks_status_<?= $row['ID'] ?>"></div> -->

                        <input type="hidden" name="p_max_marks[<?= $row['ID'] ?>]" value="<?= $max_obt_ext ?>">
                    </div>
                </div>
                <!-- end practical marks -->
        <?php } else {
                if ($_SESSION['university_id'] == 47) {
                    echo '<div class="col-md-4"></div>';
                }
            }
        } ?>
    </div>
<?php } else { ?>
    <div class="row mt-2">
        <div class="col-md-12">
            <!--<p style="color:red; text-align:center"><?= $data['message'] ?></p>-->
            <div class="card-body text-center">
                <img src="/assets/cross.png" alt="Info Icon" style="width: 80px; height: 80px; opacity: 0.6;">
                <h2 class="mt-3" style="color: #555;">
                    <?= $data['message'] ?>
            </div>
        </div>
    </div>
<?php } ?>