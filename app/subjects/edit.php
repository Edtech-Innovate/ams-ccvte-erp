<?php
require '../../includes/db-config.php';
session_start();
// if (isset($_GET['id'])) {
$id = $_GET['id'];
$fetch_data = "SELECT * FROM Syllabi WHERE ID = $id";
$data = $conn->query($fetch_data)->fetch_assoc();

?>

<div class="modal-header clearfix text-left">
    <button aria-label="" type="button" class="close" data-dismiss="modal" aria-hidden="true"><i
            class="pg-icon">close</i>
    </button>
    <h5 class="text-center">Update <span class="semi-bold"></span>Subject</h5>
</div>
<div class="modal-body" style="height: 100%;">
    <form id="resultForm" action="/app/subjects/editsyll" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="ID" value="<?php echo $data['ID'] ?>" id="ID">
        <div class="row">

            <div class="col-md-6">
                <div class="form-group">
                    <label for="coursetype">Course Type</label>
                    <select class="full-width" id="coursetype" onchange="getSpecialization(this.value)"
                        name="coursetype">
                        <option value="">Select Course Type</option>
                        <?php
                        $result = $conn->query("SELECT ID, Name FROM Courses WHERE Status=1 AND University_ID = ".$_SESSION['university_id']." ORDER BY Name ASC");
                        while ($row = $result->fetch_assoc()) {
                            $courseId = $row["ID"];
                            $courseName = $row["Name"];
                            $selected = ($courseId == $data['Course_ID']) ? 'selected' : '';
                            echo '<option value="' . htmlspecialchars($courseId) . '" ' . $selected . '>' . htmlspecialchars($courseName) . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="subcourse_id">Sub Course Type</label>
                    <select class="full-width" id="subcourse_id" name="subcourse_id" onchange="getSemester(this.value);">
                        <option value="">Select Sub Course Type</option>
                        
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="semester">Duration</label>
                    <select class="full-width" id="seme" name="seme">
                        <option value="">Select Duration Type</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="subjectname">Subject Name</label>
                    <input type="text" class="form-control" name="subjectname" id="subjectname"
                        value="<?php echo $data['Name']; ?>">
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="subjectcode">Subject Code</label>
                    <input type="text" class="form-control" name="subjectcode" id="subjectcode"
                        value="<?php echo $data['Code']; ?>">
                </div>
            </div>
     
            <div class="col-md-6">
                <div class="form-group">
                    <label for="paper_type">Select Paper Type</label>
                    <select class="form-control" name="paper_type" id="paper_type">
                        <option value="">Select Paper Type</option>
                        <option value="Theory" <?php echo (isset($data['Paper_Type']) && $data['Paper_Type'] == 'Theory') ? 'selected' : ''; ?>>Theory</option>
                        <option value="Practical" <?php echo (isset($data['Paper_Type']) && $data['Paper_Type'] == 'Practical') ? 'selected' : ''; ?>>Practical</option>
                        <option value="Project" <?php echo (isset($data['Paper_Type']) && $data['Paper_Type'] == 'Project') ? 'selected' : ''; ?>>Project</option>
                    </select>
                </div>
            </div>
            <?php if ($_SESSION['university_id'] == 48) { ?>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="exam_type">Select Exam Type</label>
                        <select class="form-control" name="exam_type" id="exam_type">
                            <option value="">Select Exam Type</option>
                            <option value="1" <?php echo (isset($data['Exam_Type']) && $data['Exam_Type'] == 1) ? 'selected' : ''; ?>>Center</option>
                            <option value="0" <?php echo (isset($data['Exam_Type']) && $data['Exam_Type'] == 0) ? 'selected' : ''; ?>>Online</option>
                        </select>
                    </div>
                </div>
            <?php } ?>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="subjectcredit">Subject Credit</label>
                    <input type="text" class="form-control" name="subjectcredit" id="subjectcredit"
                        value="<?php echo $data['Credit']; ?>">
                </div>
            </div>

            <div class="col-md-6">
                <label for="minMarks">Minimum Marks</label>
                <input type="text" class="form-control" name="minMarks" id="minMarks"
                    value="<?php echo $data['Min_Marks']; ?>">
            </div>

            <div class="col-md-6">
                <label for="maxMarks">Maximum Marks</label>
                <input type="text" class="form-control" name="maxMarks" id="maxMarks"
                    value="<?php echo $data['Max_Marks']; ?>">
            </div>
        </div>
        </br>
        <button type="submit" id="update" class="btn btn-success btn btn-lg">Update</button>
        <button type="button" class="btn btn-danger btn btn-lg" data-dismiss="modal">Close</button>
    </form>
</div>


<script>
    $(document).ready(function () {
        $("#coursetype, #subcourse_id, #seme").select2({
            searchable: true,
            dropdownParent: $('#lg-modal-content')
        });
        getSpecialization('<?=$data['Course_ID']?>')

    })

    $("#resultForm").on("submit", function (e) {
        // if ($('#form-edit-sub-counsellors').valid()) {
        $(':input[type="submit"]').prop('disabled', true);
        var formData = new FormData(this);
        formData.append('id', '<?= $id ?>');
        $.ajax({
            url: this.action,
            type: 'post',
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function (data) {
                if (data.status == 200) {
                    $('.modal').modal('hide');
                    notification('success', data.message);
                    $('#users-table').DataTable().ajax.reload(null, false);
                } else {
                    $(':input[type="submit"]').prop('disabled', false);
                    notification('danger', data.message);
                }
            }
        });
        e.preventDefault();
        // }
    });


    function getSpecialization(courseId) {
        $.ajax({
            type: 'POST',
            url: '/app/assignments/get_subcourses',
            data: {
                courseId: courseId
            },
            success: function (response) {
                $('#subcourse_id').html(response);
                $("#subcourse_id").val('<?= $data['Sub_Course_ID'] ?>');
            },
            error: function (xhr, status, error) {
                console.error(xhr.responseText);
            }
        });
    }
</script>
<script>
    function getSemester(subCourseId) {
        var University_id = '<?= $_SESSION['university_id'] ?>'
        $.ajax({
            type: 'POST',
            url: '/app/subjects/get-duration',
            data: {
                id: subCourseId,
                University_id: University_id
            },
            success: function (response) {
                $('#seme').html(response);
                $('#seme').val('<?= $data['Semester'] ?>');
            },
            error: function (xhr, status, error) {
                console.error("Error: " + xhr.responseText);
            }
        });
    }
    $(document).ready(function () {
        setTimeout(() => {
            var subcourseid = $('#subcourse_id').val();
            getSemester(subcourseid);
        }, 1000);
    })
</script>
<script>
    function selectcourse(universitiesId) {
        $.ajax({
            type: 'POST',
            url: '/app/subjects/getcourses',
            data: {
                universitiesId: universitiesId
            },
            success: function (response) {
                $('#coursetype').html(response);
            },
            error: function (xhr, status, error) {
                console.error(xhr.responseText);
            }
        });
    }
</script>