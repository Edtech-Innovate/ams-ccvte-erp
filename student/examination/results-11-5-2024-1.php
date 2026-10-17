<!doctype html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css"> -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
	<title>Result</title>
	<style>
		@import url('https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap');

		body {
			font-family: 'Roboto', sans-serif;
		}

		@media print {

			.no-print,
			.no-print * {
				display: none !important;
			}
		}

		.top-box-inline {
			height: 115px;
		}

		.top-heading {
			color: #05519E;
			font-weight: 700;
			display: inline-block;
			width: 130px;
		}

		.top-heading-u {
			color: #05519E;
			font-weight: 700;
			display: inline-block;
		}

		.top-colon {
			color: #05519E;
			font-weight: 700;
			display: inline-block;
			margin-left: 15px;
			margin-right: 15px;
		}

		.top-name {
			color: #000;
			font-weight: 700;
		}

		.top-detail-p {
			margin-bottom: 20px;
		}

		.table-box table {
			border-collapse: collapse;
			border: 1.5px solid #05519E;
		}

		.table-box th {
			border: 1.5px solid #05519E;
		}

		.table-box td {
			padding: 6px;
			border-left: 1.5px solid #05519E;
			border-radius: 1.5px solid #05519E;
			font-size: 14px;
		}

		.table-box-bottom table {
			border-collapse: collapse;
			border: 1.5px solid #05519E;
			width: 100%;
		}

		.table-box-bottom th {
			border: 1.5px solid #05519E;
		}

		.table-box-bottom td {
			border: 1.5px solid #05519E;
		}

		.th-for-blank {
			width: 80px;
		}

		@page {
			margin: 0;
		}

		body {
			margin: 0.7cm;
		}

		/* th.col {
			width: 8%;
		} */

		.blue {
			color: #05519E !important;
		}

		.blue-f,
		col.blue-f {
			color: #05519E;
			font-weight: 700;
			display: inline-block;
			width: 130px;
			text-align: start;
		}

		.top-name {
			color: #000;
			font-weight: 700;
		}

		.style-n {
			display: flex;
			justify-content: start !important;
		}

		.mt-n {
			margin-left: 12px;
		}
		.w-top-h{
			width:253px !important;
		}
	</style>
</head>

<body>
	<div class="text-center mt-5">
		<?php
		ini_set('display_errors', 1);
		require '../../includes/db-config.php';
		require '../../includes/helpers.php';
		session_start();

		$passFail = "PASS";
		if (isset($_GET['year_sem'])) {
			$sem = $_GET['year_sem'];
		} else {
			$sem = 1;
		}

		$Students_temps_result = $conn->query("SELECT Students.*,Sub_Courses.Min_Duration as total_duration,Modes.Name as mode,Sub_Courses.Name as course,Courses.Name as program_Type FROM Students left join Sub_Courses on Sub_Courses.ID=Students.Sub_Course_ID left join Modes on Students.University_ID=Modes.University_ID left join Courses on Students.Course_ID=Courses.ID WHERE Students.Enrollment_No = '" . $_SESSION['Enrollment_No'] . "' ");    //AND Duration = $sem 
		if ($Students_temps_result->num_rows > 0) {
			$Students_temps = $Students_temps_result->fetch_assoc();
		} else {
			$Students_temps['duration'] = '';
		}

		$result_test = "";
		if ($Students_temps['University_ID'] == 47) {
			$result_test = $conn->query("SELECT s.Name,m.obt_marks_ext, m.obt_marks_int,m.obt_marks,m.status,m.remarks,m.created_at,s.Min_Marks,s.Max_Marks,s.Code,m.obt_marks as total FROM marksheets AS m LEFT JOIN Syllabi AS s ON m.subject_id=s.ID WHERE m.enrollment_no= '" . $Students_temps['Enrollment_No'] . "' ");

			// $result_test = $conn->query("SELECT Syllabi.* ,marksheets.obt_marks_ext, marksheets.obt_marks_int,marksheets.obt_marks,marksheets.status,marksheets.remarks,marksheets.created_at FROM marksheets  left join Syllabi on Syllabi.ID=marksheets.subject_id WHERE Syllabi.Sub_Course_ID = " . $Students_temps['Sub_Course_ID'] . "  AND marksheets.enrollment_no = '" . $Students_temps['Enrollment_No'] . "' AND Syllabi.Semester=$sem  ");
		} else {
			$result_test = $conn->query("SELECT obt_marks_ext, obt_marks_int,obt_marks,marksheets.status,remarks,created_at FROM marksheets WHERE enrollment_no = '" . $Students_temps['Enrollment_No'] . "' ");
		}
		if ($result_test->num_rows == 0) {
			echo '<div class="mt-50 text-center" style="margin-top:220px;"><h5>Result Not Published Yet.</h5></div>';
			die;
		}
		$durMonthYear = "";
		$duration_val = '';
		if ($Students_temps['mode'] == "Monthly") {
			$durMonthYear = " Months";
		} elseif ($Students_temps['mode'] == "Sem") {
			$durMonthYear = " Semster";
			$duration_val = " Sem";
		} else {
			$durMonthYear = " Years";
		}

		$sem = $_SESSION['Duration'] . get_ordinal_suffix($_SESSION['Duration']);
		?>


	</div>
	<div id="content" class="html-content" style="background: #fff;">
		<div class="mt-5" style="height: 1689px; width: 1221px; background: url('/assets/img/marksheet.jpeg'); margin: 0 auto; background-position: center; background-size: contain; background-repeat:no-repeat; padding: 0px;">
			<div class="main-result-box" style="padding: 10px; height: 840px; width: 1050px; margin: 0 auto; position: relative; top: 310px; right: 10px;">
				<p style="color: #C14635;position: absolute;top: -289px;right: 20px;font-size: 20px;font-weight: 600;"></p>
				<p style="font-size: 22px; font-weight: 900; color: #05519E;"></p>
				<?php
               $nameParts=[];
 $nameParts = explode("\n", wordwrap($Students_temps['program_Type'], 25));
//  print_r($nameParts);

?>
				<div class="row">
					<div class="col-md-10">
				
							<table>
								<tr>

									<th class="col" style="width: 80%;">
									
										<p class="top-detail-p"><span class="top-heading">Student Name</span> <span class="top-colon">:</span>
	                                   <span class="top-name"><?= $Students_temps['First_Name'] . " " . $Students_temps['Middle_Name'] . " " . $Students_temps['Last_Name'] ?></span></p>
									</th>
									
									<th  class="col " style="width: 80%; display:flex;flex-direction:row;">
										<p class="w-top-h"><span  class="top-heading ">Program Type</span>:</p>
										<p class="w-top-h"><span class="top-name"><?= strtoupper($Students_temps['program_Type']) ?></span> </p>
									</th>
								</tr>
								<tr>
									<th  class="col " style="width: 80%;">
										<p class="top-detail-p"><span class="top-heading">Father's Name</span> <span class="top-colon">:</span>  <span class="top-name"><?= $Students_temps['Father_Name'] ?></span></p>
									</th>
                                  <th  class="col " style="width: 80%; display:flex;flex-direction:row;">
										<p class="w-top-h"><span  class="top-heading ">Specialization</span>:</p>
										<p class="w-top-h"><span class="top-name"><?= strtoupper($Students_temps['course']); ?></span> </p>
									</th>
									
								
								</tr>
								<tr>
									<th>
										<p class="top-detail-p"><span class="top-heading">Enrollment No</span> <span class="top-colon">:</span> <span class="top-name"><?= $Students_temps['Enrollment_No'] ?></span>
									</th><th>
									<?php if ($_SESSION['University_ID'] == 47) { ?>
										<p class="top-detail-p">	<span class="top-heading"><?= $durMonthYear; ?></span> <span class="top-colon">:</span> 
										<?php } else { ?>
											<p class="top-detail-p"><span class="top-heading">Duration/Hours</span> <span class="top-colon">:</span>
										<?php } ?>
									 <span class="top-name"><?php echo ($Students_temps['University_ID'] == 47) ?  $sem : $Students_temps['Duration'] . " " . strtoupper($durMonthYear); ?></span></p>
									</th>
								</tr>
							</table>
						
					</div>
					<div class="col-md-2">
						<div style="position: relative; right: 40px; bottom: 20px; height: 170px; width:145px;">
							<img src="<?= $_SESSION['Photo'] ?> " style="border:1px solid #000; height: 100%; width: 100%;">
						</div>
					</div>
				</div>


				<!-- <div class="row" style="display:none">
					<div class="top-box-inline text-top-box col-5">
						<p class="top-detail-p"><span class="top-heading">Student Name</span> <span class="top-colon">:</span> <span class="top-name"><?= $Students_temps['First_Name'] . " " . $Students_temps['Middle_Name'] . " " . $Students_temps['Last_Name'] ?></span></p>
						<p><span class="top-heading">Father's Name</span> <span class="top-colon">:</span> <span class="top-name"><?= $Students_temps['Father_Name'] ?></span></p>
						<p><span class="top-heading">Enrollment No</span> <span class="top-colon">:</span> <span class="top-name"><?= $Students_temps['Enrollment_No'] ?></span></p>

					</div>
					<div class="top-box-inline text-top-box col-5 p-0">

						<p><span class="top-heading">Program Type</span> <span class="top-colon">:</span> <span class="top-name"><?= strtoupper($Students_temps['program_Type']) ?></span></p>
						<p class="top-detail-p"><span class="top-heading">Specialization</span> <span class="top-colon">:</span> <span class="top-name"><?= strtoupper($Students_temps['course']) ?></span></p>
						<?php


						if ($_SESSION['University_ID'] == 47) {
						?>
							<p class="style-n"><span class="blue-f"><?= $durMonthYear; ?></span> <span class="top-colon">:</span> <span class="top-name"><?= $sem ?></span></p>
						<?php } else { ?>
							<p><span class="top-heading">Duration/Hours</span> <span class="top-colon">:</span> <span class="top-name"><?php echo ($Students_temps['University_ID'] == 47) ?  $sem : $Students_temps['Duration'] ?> <?= strtoupper($durMonthYear); ?></span></p>

						<?php } ?>
					</div>


					<div class="top-box-inline photo-top-box col-2">
						<div style="position: relative; right: 40px; bottom: 20px; height: 170px; width:145px;">
							<img src="<?= $_SESSION['Photo'] ?> " style="border:1px solid #000; height: 100%; width: 100%;">
						</div>
					</div>

				</div> -->
				<br><br>
				<?php if ($Students_temps['University_ID'] == 47 || $Students_temps['mode'] == "Sem") {  ?>
					<div class="row justify-content-center mt-3">
						<div class="col-lg-12">
							<div class="table-responsive">
								<table class="table table-bordered">
									<thead>
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
										</tr>
									</thead>
									<tbody>
										<?php foreach ($result_test as $marks) { ?>
											<tr class="text-center">
												<th scope="col"><?= $marks['Code'] ?></th>
												<th scope="col" style="text-align: start;"><?= $marks['Name'] ?></th>
												<th scope="col"><?= $marks['Min_Marks'] ?></th>
												<th scope="col"><?= $marks['obt_marks_int'] ?></th>
												<th scope="col"><?= $marks['Max_Marks'] ?></th>
												<th scope="col"><?= $marks['obt_marks_ext'] ?></th>
												<th scope="col"><?= $marks['Min_Marks'] + $marks['Max_Marks']; ?></th>
												<th scope="col"><?= $marks['total'] ?></th>
											</tr>
										<?php $grand_total[] =  $marks['total'];
											$max_grand_total[] = $marks['Min_Marks'] + $marks['Max_Marks'];
										}   ?>
										<tr class="border-top-0 text-center">
											<th scope="col" class="col" style="border-right:1px solid white !important;"></th>
											<th scope="col" class="col" style="border-right:1px solid white !important;"></th>
											<th scope="col" class="col" style="border-right:1px solid white !important;"></th>
											<th scope="col" class="col" style="border-right:1px solid white !important;"></th>
											<th scope="col" class="col" style="border-right:1px solid white !important;text-align:end !important;"></th>
											<th scope="col" class="col" style="border-right: 1px solid #80808059! important;text-align:start !important;">Grand Total</th>
											<th scope="col" class="col" style="border-right: 1px solid #80808059 !important;"><?php echo  array_sum($max_grand_total); ?></th>
											<th scope="col" class="col" style="border-right: 1px solid #80808059!important;"><?php echo  array_sum($grand_total); ?></th>

										</tr>
									</tbody>
								</table>
							</div>
						</div>
					</div>
					<div class="pd" style=" padding: 13px;">
						<p style="position: relative;"><span class="top-heading-u"></span></p>
						<p style="position: relative; "><span class="top-heading-u">Disclaimer :</span> </p>
						<p style="position: relative; text-align: justify;">
							The published result is provisional only. Glocal University is not responsible for any inadvertent error that may have crept in the data / results being published online.
							This is being published just for the immediate information to the examinees. The final mark sheet(s) issued by Glocal University will only be treated authentic & final in this regard.
						</p>
					</div>
				<?php } else { ?>

					<div class="table-box">
						<table width="100%">
							<tr class="text-center" style="color: #05519E; font-weight: 700;">
								<th rowspan="2">SubCode</th>
								<th style="width: 350px;">PAPER / SUBJECT</th>
								<th rowspan="2">MAX. MARKS</th>
								<th rowspan="2">MIN. MARKS</th>
								<th rowspan="2">OBTAINED MARKS</th>
								<th rowspan="2">REMARKS</th>
							</tr>
							<tr class="text-center" style="color: #05519E; font-weight: 700;">

							</tr>
							<?php
							$total_obt = 0;
							$total_max = 0;
							$query = "";
							if ($Students_temps['University_ID'] == 47) {
								$subcourse_id = $Students_temps['Sub_Course_ID'];
								$query = "SELECT * FROM Syllabi WHERE Sub_Course_ID = $subcourse_id AND Semester = $sem ";
							} else {
								$query = "SELECT * FROM Syllabi WHERE Sub_Course_ID = " . $Students_temps['Sub_Course_ID'] . " ";
							}
							$temp_subjects = $conn->query($query);
							$resultPublishDay = "";
							if ($temp_subjects->num_rows > 0) {
								while ($temp_subject = $temp_subjects->fetch_assoc()) { ?>

									<?php
									$mark_subjects = $conn->query("SELECT obt_marks_ext, obt_marks_int,obt_marks,marksheets.status,remarks,created_at FROM marksheets WHERE enrollment_no = '" . $Students_temps['Enrollment_No'] . "' AND subject_id = " . $temp_subject['ID'] . " ");

									$mark_subjects = $mark_subjects->fetch_assoc();
									$obt_marks_ext = 0;
									$obt_marks_int = 0;
									$percentage = 0;

									if ($mark_subjects != null) {
										$resultPublishDay = date("d/m/Y", strtotime($mark_subjects['created_at']));
										if ($mark_subjects['remarks'] != "Pass") {
											$passFail = "FAIL";
										}
										$obt_marks_ext = $mark_subjects['obt_marks_ext'];
										$obt_marks_int = $mark_subjects['obt_marks_int'];
										$total_obt = $total_obt + $obt_marks_ext + $obt_marks_int;
										//$total_max = $total_max + $temp_subject['Max_Marks'] + $temp_subject['Min_Marks'];
										$total_max = $total_max + $temp_subject['Max_Marks'];
										if ($total_max != 0) {
											$percentage = ($total_obt / $total_max) * 100;
										}
									}
									// }else{
									// 	echo 0;
									// }
									?>

									<tr class="text-center" style="font-weight: 700;">
										<td><?= $temp_subject['Code'] ?></td>

										<td class="text-left"><?= $temp_subject['Name'] ?></td>

										<td><?php echo $temp_subject['Max_Marks'] ?></td>
										<td><?php echo $temp_subject['Min_Marks'] ?></td>
										<td><?php echo $obt_marks_ext + $obt_marks_int; ?></td>
										<td><?php echo ($mark_subjects != null) ? $mark_subjects['remarks'] : "" ?></td>
									</tr> <?php }
									}

											?>

							<tr>
								<th colspan="3"> <span style="display: inline-block; float: left; margin-left: 10px; text-transform: capitalize">
										<span style="color: #05519E">In Words:</span> <?php
																						// $formatter = new NumberFormatter("en", NumberFormatter::SPELLOUT);
																						// $marksWords = $formatter->format($total_obt);
																						$marksWords = numberToWordFunc($total_obt);
																						echo $marksWords;
																						?> <span id="inwords" class="res"></span> </span> <span style="display: inline-block; float: right; margin-right: 10px;">

										<span style="color: #05519E">Total Obtained Marks:</span></span>
								</th>
								<th colspan="2" class="text-center"><?= $total_obt ?></th>
							</tr>
						</table>
					</div>


					<div class="text-center" style="font-size: 22px; font-weight: 900; color: #05519E; margin-top: 10px;"> AGGREGATE MARKS </div>
					<div class="table-box-bottom">
						<table class="text-center">

							<tr style="color: #05519E; font-weight: 700;">
								<th>Marks</th>
								<th>GRAND TOTAL</th>
								<th>RESULT</th>
								<th>PERCENTAGE</th>
							</tr>
							<tr>
								<th>Maximum Mark</th>
								<td><?= $total_max ?></td>
								<td rowspan="2"><?= $passFail ?></td>
								<td rowspan="2"><?= round($percentage, 2) ?>%</td>
							</tr>
							<tr>
								<th>Obtained Mark</th>
								<td><?= $total_obt ?></td>
							</tr>
						</table>
					</div>

					<p style="position: relative; top: 30px;"><span class="top-heading-u">Result Declared On</span><?= $resultPublishDay ?> <span class="top-colon"></span> <span class="top-name"></span></p>

					<div class="pd" style=" padding: 13px;">
						<p style="position: relative; top: 34px; right: 10px;"><span class="top-heading-u"></span></p>
						<p style="position: relative; top: 34px; right: 10px;"><span class="top-heading-u">Disclaimer :</span> </p>
						<p style="position: relative; top: 34px; right: 10px;    text-align: justify;">
							The published result is provisional only. Glocal University is not responsible for any inadvertent error that may have crept in the data / results being published online.
							This is being published just for the immediate information to the examinees. The final mark sheet(s) issued by Glocal University will only be treated authentic & final in this regard.
						</p>
					</div>
				<?php } ?>

			</div>
		</div>

		<div class="text-center no-print">
			<button type="button" class="btn btn-primary" id="cmd" onclick="printDiv('content')">Download as PDF/Print</button>
		</div>

	</div>
	</div>


	<!-- <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script> -->
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
		$(document).ready(function() {});
	</script>
</body>

</html>