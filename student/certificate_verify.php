<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

<style>
	@import url('https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap');

    body {
        font-family: 'Roboto', sans-serif;
        /*background: linear-gradient(135deg, #e8ecf3, #f8fafc);*/
        background:#f7e5da !important;
        min-height: 100vh;
        padding: 40px 15px;
    }

    .verify-card {
        max-width: 900px;
        margin: auto;
        background: #ffffffcc;
        backdrop-filter: blur(10px);
        border-radius: 18px;
        padding: 35px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        border: 1px solid #d7dce3;
    }

    .header-box {
        text-align: center;
        padding-bottom: 25px;
        /*border-bottom: 1px solid #dcdcdc;*/
        margin-bottom: 30px;
    }

    .header-title {
        font-size: 27px;
        font-weight: 700;
        color: #663400;
    }

    .profile-img {
        width: 130px;
        height: 130px;
        object-fit: cover;
        border-radius: 12px;
        border: 3px solid #d4d4d4;
        box-shadow: 0px 3px 8px rgba(0,0,0,0.18);
    }

    .student-name {
        font-size: 24px;
        font-weight: 700;
        color: #222;
        margin-bottom: 4px;
    }

    .student-id {
        color: #6c757d;
        font-size: 15px;
        margin-bottom: 15px;
    }

    .info-table {
        margin-top: 25px;
    }

    .info-table th {
        background: #663400;
        color: white;
        font-weight: 600;
        width: 30%;
        padding: 14px;
        font-size: 15px;
    }

    .info-table td {
        padding: 14px;
        background: #f9fbfd;
        font-size: 15px;
        border-left: 1px solid #e5e7eb;
    }

    .verified-badge {
        background: #28a745;
        color: white;
        padding: 7px 16px;
        border-radius: 30px;
        font-size: 14px;
        font-weight: 600;
        display: inline-block;
        margin-top: 8px;
    }
</style>
<?php
ini_set('display_errors', 1);
require $_SERVER['DOCUMENT_ROOT'].'/includes/db-config.php';
require $_SERVER['DOCUMENT_ROOT'].'/includes/helpers.php';

// $id = $_GET['id'];
$id = base64_decode($_GET['id']);
// echo('<pre>');print_r($_GET['id']);die;
$studentData = $conn->query("Select Students.*,Student_Documents.Location AS student_photo, Sub_Courses.Name as subCourseName,Modes.Name as modeName from Students left join Student_Documents ON Students.ID= Student_Documents.Student_ID and Student_Documents.Type= 'Photo' left join Sub_Courses on Sub_Courses.ID = Students.Sub_Course_ID left join Modes on Modes.ID = Students.Mode_ID where Students.ID=$id")->fetch_assoc();
// session_start();
// echo "<pre>";
// print_r("Select Students.*,Student_Documents.Location AS student_photo, Sub_Courses.Name as subCourseName,Modes.Name as modeName from Students left join Student_Documents ON Students.ID= Student_Documents.Student_ID and Student_Documents.Type= 'Photo' left join Sub_Courses on Sub_Courses.ID = Students.Sub_Course_ID left join Modes on Modes.ID = Students.Mode_ID where Students.ID=$id");die;
// echo "<pre>"; print_r($Students_temps);
?>

<!--<div class="container">-->

<!--    <div class="card card-custom mb-4">-->
<!--        <div class="title-box">-->
<!--            <h3 class="mb-0">Student Certificate Verification</h3>-->
<!--        </div>-->

<!--        <div class="card-body">-->
<!--            <div class="row align-items-center mb-4">-->

<!--                <div class="col-md-9">-->
<!--                    <h4 class="fw-bold mb-1 text-primary">-->
<!--                        <?= $studentData['First_Name']." ".$studentData['Middle_Name']." ".$studentData['Last_Name'] ?>-->
<!--                    </h4>-->
<!--                    <p class="text-muted mb-0">ID: <?= $studentData['ID'] ?></p>-->
<!--                </div>-->

<!--                <div class="col-md-3 text-md-end text-center mt-3 mt-md-0">-->
<!--                    <img src="<?= $studentData['student_photo'] ?>" class="profile-img" alt="Student Photo">-->
<!--                </div>-->

<!--            </div>-->

<!--            <table class="table table-bordered table-custom">-->
<!--                <tbody>-->
<!--                    <tr>-->
<!--                        <th width="30%">Course</th>-->
<!--                        <td><?= $studentData['subCourseName'] ?></td>-->
<!--                    </tr>-->

<!--                    <tr>-->
<!--                        <th>Duration</th>-->
<!--                        <td><?= $studentData['Duration']." ".$studentData['modeName'] ?></td>-->
<!--                    </tr>-->

<!--                    <tr>-->
<!--                        <th>Website</th>-->
<!--                        <td>-->
<!--                            <a href="https://ams.ccvte.org/" target="_blank">https://ams.ccvte.org/</a>-->
<!--                        </td>-->
<!--                    </tr>-->
<!--                </tbody>-->
<!--            </table>-->

<!--        </div>-->
<!--    </div>-->

<!--</div>-->
<div class="verify-card">

    <div class="header-box">
        <h2 class="header-title">Central Council for Vocational Training and Skill Education (CCVTE)</h2>
        
    </div>

    <div class="text-center mb-4">
        <?php
            if($studentData['student_photo']!=""){
                ?>
                <img src="<?= $studentData['student_photo'] ?>" class="profile-img">
                <?php
            }
        ?>
        <h3 class="student-name">
            <?= $studentData['First_Name']." ".$studentData['Middle_Name']." ".$studentData['Last_Name'] ?>
        </h3>
        <p class="student-id">Registration No.: <?= $studentData['Unique_ID'] ?></p>
    </div>

    <table class="table table-bordered info-table">
        <tr>
            <th>Course</th>
            <td><?= $studentData['subCourseName'] ?></td>
        </tr>
        <tr>
            <th>Duration</th>
            <td><?= $studentData['Duration']." ".$studentData['modeName'] ?></td>
        </tr>
        <tr>
            <th>Institute Website</th>
            <td>
                <a href="https://ams.ccvte.org/" target="_blank">
                    https://ams.ccvte.org/
                </a>
            </td>
        </tr>
    </table>
     <div class="text-center">
         <span class="verified-badge">✔ Verified</span>
     </div>
</div>

