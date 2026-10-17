<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

<style>
	@import url('https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap');

	body {
		font-family: 'Roboto', sans-serif;
	}

	/* @media print
{    
    .no-print, .no-print *
    {
        display: none !important;
    }
} */
	/* @media print {
		.body {
			background-image: url('https://erpglocal.iitseducation.org/assets/img/marksheet.jpeg') !important;
			background-size: contain !important;
			background-repeat: no-repeat !important;
		}
	} */

	.top-box-inline {
		height: 115px;
	}

	.img-pp {
		position: absolute;
		right: 1px;
		top: 10px;

	}

	.table-box-bottom table {
		border-collapse: collapse;
		border: 2px solid #8b8b8b;
		width: 100%;
	}

	.table-box-bottom th {
		border: 2px solid #8b8b8b;
	}

	.table-box-bottom td {
		border: 2px solid #8b8b8b;
	}



	@page {
		margin: 0;
	}

	body {
		margin: 0.7cm;
	}

	.text-black {
		color: black;
	}

	td.col {
    padding: 10px;
    border: 2px solid #8b8b8b;
}

	th {
    padding: 9px;
    border-bottom: 2px solid #8b8b8b;
	border-left: 2px solid #8b8b8b;
}
	.main-result-box {
		width: 92%;
	}

	.des {
		padding: 2%;
		text-align: justify;
	}
</style>
<?php
ini_set('display_errors', 1);
require '../../includes/db-config.php';
require '../../includes/helpers.php';

session_start();

$url = "https://erpglocal.iitseducation.org";
$passFail = "PASS";

$student = $conn->query("SELECT Students.*, Courses.Name as program_Type, Sub_Courses.Name as course,Modes.Name as mode, Course_Types.Name as Course_Type, Admission_Sessions.Name as Admission_Session, Admission_Types.Name as Admission_Type, CONCAT(Courses.Short_Name, ' (',Sub_Courses.Name,')') as Course_Sub_Course, TIMESTAMPDIFF(YEAR, DOB, CURDATE()) AS Age FROM Students LEFT JOIN Modes on Students.University_ID=Modes.University_ID LEFT JOIN Courses ON Students.Course_ID = Courses.ID LEFT JOIN Course_Types ON Courses.Course_Type_ID = Course_Types.ID LEFT JOIN Sub_Courses ON Students.Sub_Course_ID = Sub_Courses.ID LEFT JOIN Admission_Sessions ON Students.Admission_Session_ID = Admission_Sessions.ID LEFT JOIN Admission_Types ON Students.Admission_Type_ID = Admission_Types.ID WHERE Students.Enrollment_No = '" . $_SESSION['Enrollment_No'] . "'");
$Students_temps = [];
if ($student->num_rows > 0) {
	$Students_temps = $student->fetch_assoc();
} else {
	echo '<div class="mt-5 mb-4 text-center" style="margin-top:220px;"><h5>Invalid credentials!</h5></div>';
	die;
}

$photo = $conn->query("SELECT Location FROM Student_Documents WHERE Student_ID = " . $Students_temps['ID'] . " AND Type = 'Photo'");
if ($photo->num_rows > 0) {
	$photo = $photo->fetch_assoc();
	$Students_temps['Photo'] = $photo['Location'];
}


$total_obt = 0;
$total_max = 0;
$percentage=0;
$getDataSQL = $conn->query("SELECT s.Name as subject_name, s.Code,s.Max_Marks, s.Min_Marks,m.obt_marks,m.remarks,m.obt_marks_ext,m.obt_marks_int From marksheets AS m LEFT JOIN Syllabi AS s ON m.subject_id = s.ID WHERE m.enrollment_no = '" . $Students_temps['Enrollment_No'] . "' AND s.Course_ID = " . $Students_temps['Course_ID'] . " AND  s.Sub_Course_ID = " . $Students_temps['Sub_Course_ID'] . " ");
while ($getDataArr = $getDataSQL->fetch_assoc()) {
	if ($getDataArr['remarks'] != "Pass") {
		$getDataArr['remarks'] = "FAIL";
	} else {
		$getDataArr['remarks'] = "Pass";
	}
	$obt_marks_ext = $getDataArr['obt_marks_ext'];
	$obt_marks_int = $getDataArr['obt_marks_int'];
	$total_obt = $total_obt + $obt_marks_ext + $obt_marks_int;
	$total_max = $total_max + $getDataArr['Max_Marks'];
	$percentage = ($total_obt / $total_max) * 100;

	$Students_temps['marks'][] = $getDataArr;
}
$Students_temps['total_max'] = $total_max;
$Students_temps['total_obt'] = $total_obt;
$Students_temps['in_word_marks'] = ucwords(strtolower(numberToWordFunc($total_obt)));
$Students_temps['percentage'] = $percentage;
$durMonthYear = "";
if ($Students_temps['mode'] == "Monthly") {
	$durMonthYear = " Months";
} elseif ($Students_temps['mode'] == "Sem") {
	$durMonthYear = " Semesters";
} else {
	$durMonthYear = " Years";
}

if ($Students_temps['University_ID'] == 48) {
	$Students_temps['mode_type'] = "Duration";
} else {
	$Students_temps['mode_type'] = "Semester";
}



$Students_temps['durMonthYear'] = $Students_temps['Duration'] . $durMonthYear;

$Students_temps['Enrollment_No'] = isset($Students_temps['Enrollment_No']) ? $Students_temps['Enrollment_No'] : '';
$result_test = $conn->query("SELECT obt_marks_ext, obt_marks_int,obt_marks,marksheets.status,remarks,created_at FROM marksheets WHERE enrollment_no = '" . $Students_temps['Enrollment_No'] . "' ");
if ($result_test->num_rows == 0) {
	echo '<div class="mt-5 mb-4 text-center" style="margin-top:220px;"><h5>Result Not Published Yet.</h5></div>';
	die;
}
$durMonthYear = "";
if ($Students_temps['mode'] == "Monthly") {
	$durMonthYear = " Months";
} elseif ($Students_temps['mode'] == "Sem") {
	$durMonthYear = " Semesters";
} else {
	$durMonthYear = " Years";
}
if ($Students_temps['University_ID'] == 48) {
	$Students_temps['university_name'] = "Skill Education Development";
} else {
	$Students_temps['university_name'] = "Vocational Studies";
}


$durations = '';
if ($Students_temps['University_ID'] == 48) {
	if ($Students_temps['Duration'] == 3) {
		$durations = "Certification Course";
	} else if ($Students_temps['Duration'] == 6) {
		$durations = "Certified Skill Diploma";
	} else if ($Students_temps['Duration'] == '11/advance-diploma') {
		$durations = "Certification Skill Diploma";
	} else if ($Students_temps['Duration'] == '11/certified') {
		$durations = "Certified Skill Diploma";
	}
} else {
	$durations = "B. VOC";
}
$Students_temps['duration_val'] = $durations;


$durMonthYear = "";
if ($Students_temps['mode'] == "Monthly") {
	$durMonthYear = " Months";
} elseif ($Students_temps['mode'] == "Sem") {
	$durMonthYear = " Semesters";
} else {
	$durMonthYear = " Years";
}

if ($Students_temps['University_ID'] == 48) {
	$Students_temps['mode_type'] = "Duration";
} else {
	$Students_temps['mode_type'] = "Semester";
}
// echo "<pre>"; print_r($Students_temps);
$html = '<div id="content" class="html-content" style="background: #fff;">
            <div class="mt-5 body" style="border:3px solid #1e1919;height: 1111px;; width: 900px; margin: 0 auto; background-position: center; background-size: contain; background-repeat: no-repeat; padding: 0px;">
            <div class="" style="display:flex; justify-content:center;"><img src="https://vocational.glocaluniversity.edu.in/assets/images/downloadfooter.webp" alt=""style="margin-top: 15px;width:27%"></div>
              <p style="margin-top:1%;text-align: center;font-weight: 700;font-size: 20px!important;color:black !important;">(A University Established by UP Act 2 of 2012)</p>
            <div class="main-result-box" style="padding: 0px; height: 0px; margin: 0 auto; position: relative; top: 0px; right: 0px;">
                    
                    <p class="text-center text-dark fw-bold">STATEMENT OF MARKS</p>
                    <p class="text-center text-dark fw-bold">' . $Students_temps['duration_val'] . ' ' . 'in' . ' ' . $Students_temps['course'] . '</p>
                    <p class="text-center text-dark fw-bold">SESSION :' . $Students_temps['Admission_Session'] . '</p>
                          <img src="' . $url . $Students_temps['Photo'] . '" alt="" width="100" height="100" class="img-pp">
                    <div class="row">
                       <div class="col-lg-12 mb-4">
                          <div class="table-resposive">
                           <table class="table-bordered mb-3">
                             <tbody>
                            <tr>
                                <td class="col" style="width:600px; height:40px;"><span class="fw-bold " style="color: #05519E;">Name:</span> <span class="text-dark fw-bold">' . $Students_temps['First_Name'] . " " . $Students_temps['Middle_Name'] . " " . $Students_temps['Last_Name'] . '</span></td>
                                <td  class="col" style="width:400px; height:40px;"><span class="fw-bold " style="color: #05519E;"> Enrollment No:</span><span class="text-dark fw-bold"> ' . $Students_temps['Enrollment_No'] . '</span></td>
                            </tr>
                            <tr>
                                <td  class="col" style="width:600px; height:40px;"><span class="fw-bold"  style="color: #05519E;>School:</span> ' . '<span class="text-dark"> School : ' . 'School Of ' . ' ' . $Students_temps['university_name'] . '</span></td>
                                <td class="col" style="width:400px; height:40px;"><span class="fw-bold"  style="color: #05519E;>' . $Students_temps['mode_type'] . ' ' . ':</span> <span class="text-dark">' . ' ' . $Students_temps['durMonthYear'] . '</span></td>

                            </tr>
                           

                        </tbody>
                    </table>
                </div>
                
                       </div>';

			if($Students_temps['University_ID']==48){
				$html .= '<div class="table-box" >
				<table width="100%" style="border-collapse: collapse;border: 2px solid #8b8b8b;width: 100%;">
				<tr class="text-center" style="color: #05519E; font-weight: 700;">
				<th style="border: 2px solid #8b8b8b;" rowspan="2">Subject Code</th>
					<th  style="width: 350px; border: 2px solid #8b8b8b;">Subject Name</th>
					<th style="border: 2px solid #8b8b8b;" rowspan="2">Obtained Marks</th>
					<th style="border: 2px solid #8b8b8b;" rowspan="2">MIN. MARKS</th>
					<th style="border: 2px solid #8b8b8b;" rowspan="2">MAX. MARKS</th>
					<th style="border: 2px solid #8b8b8b;" rowspan="2" >REMARKS</th>
				</tr>
				<tr class="text-center" style="color: #05519E; font-weight: 700;">
				</tr> ';
			}else{
				$html .= '<div class="table-box" >
				<table width="100%" style="border-collapse: collapse;border: 2px solid #8b8b8b;width: 100%;">
				<tr class="text-center border-bottom-0">
										<th scope="col" class="col blue" style="width: 10%;border-bottom: 1px solid #fff;">Subcode</th>
										<th scope="col" class="col blue" style="width: 28%;border-bottom: 1px solid #fff;">Subject Name</th>
										<th scope="col" colspan="2" class="col blue" style="width: 10%;">Internal</th>
										<th scope="col" colspan="2" class="col blue" style="width: 10%;">External</th>
										<th scope="col" colspan="2" class="col blue" style="width: 10%;">Total</th>
									</tr>
									<tr class="border-top-0 text-center">
										<th scope="col"></th>
										<th scope="col"></th>
										<th scope="col" class="col border-top-1 blue" style="    border-top: 1px solid #8080804d;">Max</th>
										<th scope="col" class="col border-top-1 blue" style="    border-top: 1px solid #8080804d;">OBT</th>
										<th scope="col" class="col border-top-1 blue" style="    border-top: 1px solid #8080804d;">Max</th>
										<th scope="col" class="col border-top-1 blue" style="    border-top: 1px solid #8080804d;">OBT</th>
										<th scope="col" class="col border-top-1 blue" style="    border-top: 1px solid #8080804d;">Max</th>
										<th scope="col" class="col border-top-1 blue" style="    border-top: 1px solid #8080804d;">OBT</th>
									</tr> ';
			}
$total_obt = 0;
$total_max = 0;

$temp_subjects = $conn->query("SELECT * FROM Syllabi WHERE Sub_Course_ID = " . $Students_temps['Sub_Course_ID'] . " ");
$resultPublishDay = "";
if ($temp_subjects->num_rows > 0) {
	while ($temp_subject = $temp_subjects->fetch_assoc()) {
		$mark_subjects = $conn->query("SELECT marksheets.obt_marks_ext, marksheets.obt_marks_int,marksheets.obt_marks,marksheets.status,marksheets.remarks,marksheets.created_at,Syllabi.Code FROM marksheets LEFT JOIN Syllabi ON marksheets.subject_id = Syllabi.ID WHERE enrollment_no = '" . $Students_temps['Enrollment_No'] . "' AND subject_id = " . $temp_subject['ID'] . " ");
		$mark_subjects = $mark_subjects->fetch_assoc();

		if ($mark_subjects != null) {
			$resultPublishDay = date("d/m/Y", strtotime($mark_subjects['created_at']));
			if ($mark_subjects['remarks'] != "Pass") {
				$passFail = "FAIL";
			}
			$obt_marks_ext = $mark_subjects['obt_marks_ext'];
			$obt_marks_int = $mark_subjects['obt_marks_int'];
			$total_obt = $total_obt + $obt_marks_ext + $obt_marks_int;
			$total_max = $total_max + $temp_subject['Max_Marks'];
			$percentage = ($total_obt / $total_max) * 100;
		}
		if($Students_temps['University_ID']==48){
		$html .= '<tr class="text-center" style="font-weight: 700;">                    
					<td style="padding: 6px;border-left: 2px solid #8b8b8b;border-radius: 2px solid #8b8b8b;font-size: 14px;"  class="text-dark">' . $mark_subjects['Code'] . '</td>
					<td class="text-left text-dark" style="padding: 6px;border-left: 2px solid #8b8b8b;border-radius: 2px solid #8b8b8b;font-size: 14px;text-align:start !important;">' . $temp_subject['Name'] . '</td>
					<td style="padding: 6px;border-left: 2px solid #8b8b8b;border-radius: 2px solid #8b8b8b;font-size: 14px;" class="text-dark">' . ($obt_marks_ext + $obt_marks_int) . '</td>
					<td style="padding: 6px;border-left: 2px solid #8b8b8b;border-radius: 2px solid #8b8b8b;font-size: 14px;"  class="text-dark">' . $temp_subject['Min_Marks'] . '</td>
					<td style="padding: 6px;border-left: 2px solid #8b8b8b;border-radius: 2px solid #8b8b8b;font-size: 14px;"  class="text-dark">' . $temp_subject['Max_Marks'] . '</td>
					<td style="padding: 6px;border-left: 2px solid #8b8b8b;border-radius: 2px solid #8b8b8b;font-size: 14px;"  class="text-dark">' . (($mark_subjects != null) ? $mark_subjects['remarks'] : '') . '</td>
				</tr>';
}else{
			$html .= '<tr class="text-center" style="font-weight: 700;">                    
				<td style="padding: 6px;border-left: 2px solid #8b8b8b;border-radius: 2px solid #8b8b8b;font-size: 14px;"  class="text-dark">' . $mark_subjects['Code'] . '</td>
				<td class="text-left text-dark" style="padding: 6px;border-left: 2px solid #8b8b8b;border-radius: 2px solid #8b8b8b;font-size: 14px;text-align:start !important;">' . $temp_subject['Name'] . '</td>
				<td style="padding: 6px;border-left: 2px solid #8b8b8b;border-radius: 2px solid #8b8b8b;font-size: 14px;" class="text-dark">' . ($obt_marks_ext + $obt_marks_int) . '</td>
				<td style="padding: 6px;border-left: 2px solid #8b8b8b;border-radius: 2px solid #8b8b8b;font-size: 14px;"  class="text-dark">' . $temp_subject['Min_Marks'] . '</td>
				<td style="padding: 6px;border-left: 2px solid #8b8b8b;border-radius: 2px solid #8b8b8b;font-size: 14px;"  class="text-dark">' . $temp_subject['Max_Marks'] . '</td>
				<td style="padding: 6px;border-left: 2px solid #8b8b8b;border-radius: 2px solid #8b8b8b;font-size: 14px;"  class="text-dark">' . (($mark_subjects != null) ? $mark_subjects['remarks'] : '') . '</td>
				<td style="padding: 6px;border-left: 2px solid #8b8b8b;border-radius: 2px solid #8b8b8b;font-size: 14px;"  class="text-dark">' . $temp_subject['Min_Marks'] . '</td>
				<td style="padding: 6px;border-left: 2px solid #8b8b8b;border-radius: 2px solid #8b8b8b;font-size: 14px;"  class="text-dark">' . $temp_subject['Max_Marks'] . '</td>
			</tr>';
		}
	}
}
$marksWords =  ucwords(strtolower(numberToWordFunc($total_obt)));
$html .= '                </table>
                </div>';

$html .= '<p class="text-center mt-3 mb-3" style="text-align:center;font-size: 22px; font-weight: 900; color: #05519E;"> AGGREGATE MARKS </p>
				<div class="table-box-bottom">
					<table class="text-center" style="border-collapse: collapse;border: 2px solid #8b8b8b;width: 100%;">

						<tr style="color: #05519E; font-weight: 700;">
							<th style="border: 2px solid #8b8b8b;">Marks</th>
							<th style="border: 2px solid #8b8b8b;">GRAND TOTAL</th>
							<th style="border: 2px solid #8b8b8b;">RESULT</th>
							<th style="border: 2px solid #8b8b8b;">PERCENTAGE</th>
						</tr>

						<tr>
							<th style="border: 2px solid #8b8b8b;"  class="text-dark"> Maximum Mark</th>
							<td style="border: 2px solid #8b8b8b;"  class="text-dark">' . $total_max . '</td>
							<td rowspan="2" style="border: 2px solid #8b8b8b;"  class="text-dark">' . $passFail . '</td>
							<td rowspan="2" style="border: 2px solid #8b8b8b;"  class="text-dark">' . round($percentage, 2) . '%</td>
						</tr>

						<tr>
							<th style="border: 2px solid #8b8b8b;"  class="text-dark">Obtained Mark</th>
							<td style="border: 2px solid #8b8b8b;"  class="text-dark">' . $total_obt . '</td>
						</tr>

					</table>
				</div>
			<div class="des">
				<p style="position: relative;font-size: 20px; top: 14px; right: 10px;color: #05519E;font-weight: 700;display: inline-block;"><span class="top-heading-u"></span>Disclaimer :</p>
				<p style="position: relative; top: 10px;color: #05519E;font-weight: 700;display: inline-block;"><span class="top-heading-u"></span>
					The published result is provisional only. Glocal University is not responsible for any inadvertent error that may have crept in the data / results being published online.
					This is being published just for the immediate information to the examinees. The final mark sheet(s) issued by Glocal University will only be treated authentic & final in this regard.

				</p></div>';
$html .= '</div>
                </div>
            </div>
        </div>';

  $html .= '<div class="text-center no-print mb-4 mt-3">
  <button type="button" class="btn btn-primary" id="cmd" onclick="printDiv(\'content\')">Download as PDF/Print</button>
</div>';

echo $html;


?>

<script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.5.3/jspdf.min.js"></script>
<script type="text/javascript" src="https://html2canvas.hertzen.com/dist/html2canvas.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.3/jquery.min.js"></script>
<script type="text/javascript">
	$(document).ready(function() {
		$("#year_semester").change(function() {
			var year_sem = $("#year_semester").val();
			window.location.href = '/student/examination/results?year_sem=' + year_sem;
		});
	});
</script>
<script>
	function printDiv(divName) {
		var printContents = document.getElementById(divName).innerHTML;
		var originalContents = document.body.innerHTML;
		document.body.innerHTML = printContents;
		window.print();
		document.body.innerHTML = originalContents;
	}

	function toRoman(type) {
		var roman = ["st", "nd", "rd", "th", "th", "th", "th", "th"];
		$('.semsyear').text(roman[type - 1]);
	}
</script>