
<title>Result</title>
  <style>
  @import url('https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap');
  body {
	font-family: 'Roboto', sans-serif;
  }

  @media print
{    
    .no-print, .no-print *
    {
        display: none !important;
    }
}
  
  .top-box-inline {
	height: 115px;
  }
  
  
  .table-box-bottom table {
	border-collapse: collapse;border: 1.5px solid #05519E;width: 100%;
  }
  
  .table-box-bottom th {
	border: 1.5px solid #05519E;
  }
  
  .table-box-bottom td {
	border: 1.5px solid #05519E;
  }
  

  
  @page {
	margin: 0;
  }
  
  body {
	margin: 0.7cm;
  }
  </style>
<?php 
ini_set('display_errors', 1);
require '../../includes/db-config.php';
require '../../includes/helpers.php';

session_start();
$username = $_GET['user_id'];
$password  = $_GET['password'];
$url = "https://erpglocal.iitseducation.org/";
$passFail = "PASS";
$student = $conn->query("SELECT Students.*, Courses.Name as program_Type, Sub_Courses.Name as course,Modes.Name as mode, Course_Types.Name as Course_Type, Admission_Sessions.Name as Admission_Session, Admission_Types.Name as Admission_Type, CONCAT(Courses.Short_Name, ' (',Sub_Courses.Name,')') as Course_Sub_Course, TIMESTAMPDIFF(YEAR, DOB, CURDATE()) AS Age FROM Students LEFT JOIN Modes on Students.University_ID=Modes.University_ID LEFT JOIN Courses ON Students.Course_ID = Courses.ID LEFT JOIN Course_Types ON Courses.Course_Type_ID = Course_Types.ID LEFT JOIN Sub_Courses ON Students.Sub_Course_ID = Sub_Courses.ID LEFT JOIN Admission_Sessions ON Students.Admission_Session_ID = Admission_Sessions.ID LEFT JOIN Admission_Types ON Students.Admission_Type_ID = Admission_Types.ID WHERE Students.Unique_ID LIKE '$username' AND Students.Unique_ID = '$password' AND Students.Step = 4 AND Students.Status = 1");
// $student = $conn->query("SELECT Students.*,Sub_Courses.Min_Duration as total_duration,Modes.Name as mode,Sub_Courses.Name as course,Courses.Name as program_Type FROM Students left join Sub_Courses on Sub_Courses.ID=Students.Sub_Course_ID left join Modes on Students.University_ID=Modes.University_ID left join Courses on Students.Course_ID=Courses.ID WHERE Students.Unique_ID LIKE '$username' AND Students.Unique_ID = '$password' AND Students.Step = 4 AND Students.Status = 1");    //AND Duration = $sem 
if ($student->num_rows > 0) {
    $Students_temps = $student->fetch_assoc();
} else {
    echo '<div class="mt-5 mb-4 text-center" style="margin-top:220px;"><h5>Invalid credentials!</h5></div>';
    die;
}
if(isset($Students_temps['University_ID']) && $Students_temps['University_ID'] == 47) {
    echo '<div class="mt-5 mb-4 text-center" style="margin-top:220px;"><h5>Result Not Published Yet.</h5></div>';
    die;
}
$photo = $conn->query("SELECT Location FROM Student_Documents WHERE Student_ID = " . $Students_temps['ID'] . " AND Type = 'Photo'");
if ($photo->num_rows > 0) {
  $photo = $photo->fetch_assoc();
  $Students_temps['Photo'] = $photo['Location'];
}

$Students_temps['Enrollment_No'] = isset($Students_temps['Enrollment_No'])?$Students_temps['Enrollment_No']:'';
$result_test = $conn->query("SELECT obt_marks_ext, obt_marks_int,obt_marks,marksheets.status,remarks,created_at FROM marksheets WHERE enrollment_no = '".$Students_temps['Enrollment_No']."' ");                    
if($result_test->num_rows ==0){
    echo '<div class="mt-5 mb-4 text-center" style="margin-top:220px;"><h5>Result Not Published Yet.</h5></div>';
    die;
}
// echo "<pre>"; print_r($Students_temps);
$html = '<div id="content" class="html-content" style="background: #fff;">
            <div class="mt-5" style="background: url(\'https://erpglocal.iitseducation.org/assets/img/marksheet.jpeg\'); height: 1689px; width: 1221px; margin: 0 auto; background-position: center; background-size: contain; background-repeat: no-repeat; padding: 0px;">
                <div class="main-result-box" style="padding: 50px; height: 840px; width: 1050px; margin: 0 auto; position: relative; top: 310px; right: 0px;">
                    <p style="color: #C14635; position: absolute; top: -289px; right: 20px; font-size: 20px; font-weight: 600;"></p>
                    <p style="font-size: 22px; font-weight: 900; color: #05519E;"></p>
                    <div class="row">
                        <div class="top-box-inline text-top-box col-5" style = "height: 115px;">
                            <p class="top-detail-p" style="margin-bottom: 20px;"><span class="top-heading" style="color: #05519E;font-weight: 700;display: inline-block;width: 130px;">Student Name</span> <span class="top-colon" style="color: #05519E;font-weight: 700;display: inline-block;margin-left: 15px;margin-right: 15px">:</span> <span class="top-name" style="color: #000;font-weight: 700;">'.$Students_temps['First_Name']." ".$Students_temps['Middle_Name']." ".$Students_temps['Last_Name'].'</span></p>
                            <p><span class="top-heading" style="color: #05519E;font-weight: 700;display: inline-block;width: 130px;">Father\'s Name</span> <span class="top-colon" style="color: #05519E;font-weight: 700;display: inline-block;margin-left: 15px;margin-right: 15px">:</span> <span class="top-name" style="color: #000;font-weight: 700;">'.$Students_temps['Father_Name'].'</span></p>
                            <p><span class="top-heading" style="color: #05519E;font-weight: 700;display: inline-block;width: 130px;">Enrollment No</span> <span class="top-colon" style="color: #05519E;font-weight: 700;display: inline-block;margin-left: 15px;margin-right: 15px">:</span> <span class="top-name" style="color: #000;font-weight: 700;">'.$Students_temps['Enrollment_No'].'</span></p>
                        </div>
                        <div class="top-box-inline text-top-box col-5 p-0" style = "height: 115px;">
                        <p><span class="top-heading" style="color: #05519E;font-weight: 700;display: inline-block;width: 130px;">Program Type</span> <span class="top-colon" style="color: #05519E;font-weight: 700;display: inline-block;margin-left: 15px;margin-right: 15px">:</span> <span class="top-name" style="color: #000;font-weight: 700;">'.strtoupper($Students_temps['program_Type']).'</span></p>
                        <p class="top-detail-p" style="style="margin-bottom: 20px;""><span class="top-heading" style="color: #05519E;font-weight: 700;display: inline-block;width: 130px;">Program/Course</span> <span class="top-colon" style="color: #05519E;font-weight: 700;display: inline-block;margin-left: 15px;margin-right: 15px">:</span> <span class="top-name" style="color: #000;font-weight: 700;">'.strtoupper($Students_temps['course']).'</span></p>';
                        $durMonthYear="";
                        if($Students_temps['mode']=="Monthly"){
                            $durMonthYear=" Months";
                        }elseif($Students_temps['mode']=="Sem"){
                            $durMonthYear=" Semesters";
                        }else{
                            $durMonthYear=" Years";
                        }
            $html.= '<p><span class="top-heading" style="color: #05519E;font-weight: 700;display: inline-block;width: 130px;">Duration/Hours</span> <span class="top-colon" style="color: #05519E;font-weight: 700;display: inline-block;margin-left: 15px;margin-right: 15px">:</span> <span class="top-name" style="color: #000;font-weight: 700;">'.$Students_temps['Duration']." ".strtoupper($durMonthYear).'</span></p>
                      </div> 
                      <div class="top-box-inline photo-top-box col-2" style = "height: 50px;">
                      <div style="position: absolute; right: 40px; top: 30px; height: 170px; width:145px;">
                        <img src="'.$url.$Students_temps['Photo'].'" style="border:1px solid #000; height: 100%; width: 100%;"> 
                        </div>
                    </div><br>';
            $html.='<div class="table-box" >
                    <table width="100%" style="border-collapse: collapse;border: 1.5px solid #05519E;width: 100%;">
                    <tr class="text-center" style="color: #05519E; font-weight: 700;">
                        <th  style="width: 350px; border: 1.5px solid #05519E;">PAPER / SUBJECT</th>
                        
                        <th style="border: 1.5px solid #05519E;" rowspan="2">MAX. MARKS</th>
                        <th style="border: 1.5px solid #05519E;" rowspan="2">MIN. MARKS</th>
                        <th style="border: 1.5px solid #05519E;" rowspan="2">OBTAINED MARKS</th>
                        <th style="border: 1.5px solid #05519E;" rowspan="2" >REMARKS</th>
                    </tr>
                    <tr class="text-center" style="color: #05519E; font-weight: 700;">
                    </tr> ';
                    $total_obt = 0;
                    $total_max = 0;

                    $temp_subjects = $conn->query("SELECT * FROM Syllabi WHERE Sub_Course_ID = " . $Students_temps['Sub_Course_ID'] . " ");
                    $resultPublishDay = "";
                    if ($temp_subjects->num_rows > 0) {
                        while ($temp_subject = $temp_subjects->fetch_assoc()) {
                          
                            $mark_subjects = $conn->query("SELECT obt_marks_ext, obt_marks_int,obt_marks,marksheets.status,remarks,created_at FROM marksheets WHERE enrollment_no = '" . $Students_temps['Enrollment_No'] . "' AND subject_id = " . $temp_subject['ID'] . " ");
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
                    $html .= '<tr class="text-center" style="font-weight: 700;">
                            <td class="text-left" style="padding: 6px;border-left: 1.5px solid #05519E;border-radius: 1.5px solid #05519E;font-size: 14px;">'.$temp_subject['Name'].'</td>
                            <td style="padding: 6px;border-left: 1.5px solid #05519E;border-radius: 1.5px solid #05519E;font-size: 14px;">'.$temp_subject['Max_Marks'].'</td>
                            <td style="padding: 6px;border-left: 1.5px solid #05519E;border-radius: 1.5px solid #05519E;font-size: 14px;">'.$temp_subject['Min_Marks'].'</td>
                            <td style="padding: 6px;border-left: 1.5px solid #05519E;border-radius: 1.5px solid #05519E;font-size: 14px;">'.($obt_marks_ext + $obt_marks_int).'</td>
                            <td style="padding: 6px;border-left: 1.5px solid #05519E;border-radius: 1.5px solid #05519E;font-size: 14px;">'.(($mark_subjects != null) ? $mark_subjects['remarks'] : '').'</td>
                        </tr>';              
                            
                        }
                    }  
                    $marksWords = numberToWordFunc($total_obt);
                   

          $html.= '<tr>
                    <th colspan="3" style="border: 1.5px solid #05519E;" > <span style="display: inline-block; float: left; margin-left: 10px; text-transform: capitalize">
                        <span style="color: #05519E">In Words: </span>'; 
                        
          $html.='<span id="inwords" class="res">'.$marksWords.'</span> </span> <span style="display: inline-block; float: right; margin-right: 10px;">
                        <span style="color: #05519E">Total Obtained Marks:</span></span>
                    </th>
                    <th colspan="2" class="text-center" style="border: 1.5px solid #05519E;" >'.$total_obt.'</th>
                    </tr>
                </table>
                </div>';   
                $html.= '<div class="text-center mt-3 mb-4" style="font-size: 22px; font-weight: 900; color: #05519E;"> AGGREGATE MARKS </div>
				<div class="table-box-bottom">
					<table class="text-center" style="border-collapse: collapse;border: 1.5px solid #05519E;width: 100%;">

						<tr style="color: #05519E; font-weight: 700;">
							<th style="border: 1.5px solid #05519E;">Marks</th>
							<th style="border: 1.5px solid #05519E;">GRAND TOTAL</th>
							<th style="border: 1.5px solid #05519E;">RESULT</th>
							<th style="border: 1.5px solid #05519E;">PERCENTAGE</th>
						</tr>

						<tr>
							<th style="border: 1.5px solid #05519E;"> Maximum Mark</th>
							<td style="border: 1.5px solid #05519E;">'.$total_max.'</td>
							<td rowspan="2" style="border: 1.5px solid #05519E;">'.$passFail.'</td>
							<td rowspan="2" style="border: 1.5px solid #05519E;">'.round($percentage, 2).'%</td>
						</tr>

						<tr>
							<th style="border: 1.5px solid #05519E;">Obtained Mark</th>
							<td style="border: 1.5px solid #05519E;">'.$total_obt.'</td>
						</tr>

					</table>
				</div>
                <p style="position: relative; top: 30px;color: #05519E;font-weight: 700;display: inline-block;"><span class="top-heading-u">Result Declared On</span>'. $resultPublishDay.'<span class="top-colon" style="color: #05519E;font-weight: 700;display: inline-block;margin-left: 15px;margin-right: 15px"></span> <span class="top-name" style="color: #000;font-weight: 700;"></span></p>
				<p style="position: relative; top: 34px; right: 10px;color: #05519E;font-weight: 700;display: inline-block;"><span class="top-heading-u"></span></p>
				<p style="position: relative; top: 34px; right: 10px;color: #05519E;font-weight: 700;display: inline-block;"><span class="top-heading-u"></span>Disclaimer :</p>
				<p style="position: relative; top: 34px; right: 10px;color: #05519E;font-weight: 700;display: inline-block;"><span class="top-heading-u"></span>
					The published result is provisional only. Glocal University is not responsible for any inadvertent error that may have crept in the data / results being published online.
					This is being published just for the immediate information to the examinees. The final mark sheet(s) issued by Glocal University will only be treated authentic & final in this regard.

				</p>';     
          $html.= '</div>
                </div>
            </div>
        </div>';
        $html .= '<div class="text-center no-print mb-4">
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
	$(document).ready(function(){
	  $("#year_semester").change(function(){
      var year_sem = $("#year_semester").val();
			window.location.href = '/student/examination/results?year_sem='+year_sem;	
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