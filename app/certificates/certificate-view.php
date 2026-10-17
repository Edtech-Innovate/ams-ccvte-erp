<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require '../../includes/db-config.php';
require 'phpqrcode/qrlib.php';
session_start();

use setasign\Fpdi\Tcpdf\Fpdi;
$id = intval($_REQUEST['student_id']);
// ----------------------------------------
// VALIDATION
// ----------------------------------------
if (!isset($_REQUEST['student_id']) || empty($_REQUEST['student_id'])) {
    echo json_encode(['status'=>400,'message'=>"Invalid student ID"]);
    exit;
}
$certife_date='';
// $id = intval($_REQUEST['student_id']);
// $id= '1215';
$tempDir = '../../uploads/qr/';
if(!is_dir($tempDir)){
    mkdir($tempDir, 0777, true);
}




// $id=1251;


$qrFile = $tempDir . $id . '.png';       // FIXED EXTENSION
$qrFileUrl = '/uploads/qr/'.$id.'.png';  // For browser response
$encodedId = base64_encode($id);
// echo('<pre>');print_r("https://ams.ccvte.org/student/certificate_verify?id=".$encodedId);die;
$qrText = "https://ams.ccvte.org/student/certificate_verify?id=".$encodedId;
if(!file_exists($qrFile)){
    QRcode::png($qrText, $qrFile, QR_ECLEVEL_L, 5);
}


// echo($id);

// ----------------------------------------
// FETCH STUDENT DATA
// ----------------------------------------
$students_temps_result = $conn->query("
    SELECT Students.*, Student_Documents.Location, Users.Name AS centerName,
           Sub_Courses.Min_Duration as total_duration,
           Modes.Name as mode, Sub_Courses.Name as course,
           Courses.Name as program_Type 
    FROM Students 
    LEFT JOIN Sub_Courses ON Sub_Courses.ID = Students.Sub_Course_ID 
    LEFT JOIN Modes ON Students.University_ID = Modes.University_ID 
    LEFT JOIN Courses ON Students.Course_ID = Courses.ID 
    LEFT JOIN Admission_Sessions ON Students.Admission_Session_ID = Admission_Sessions.ID 
    LEFT JOIN Users ON Students.Added_For = Users.ID 
    LEFT JOIN Student_Documents ON Students.ID = Student_Documents.Student_ID 
    WHERE Student_Documents.Type='Photo' 
    AND Students.ID = '$id'
");

if ($students_temps_result->num_rows == 0) {
    echo json_encode(['status'=>404,'message'=>"Student not found"]);
    exit;
}

$students_temps = $students_temps_result->fetch_assoc();
// echo('<pre>');print_r($students_temps);die;

// ----------------------------------------
// DURATION LOGIC
// ----------------------------------------
$durMonthYear = ($students_temps['program_Type']=="Skill Program") ? "Months" :"Year" ;

$total_duration = 0;

if ($students_temps['University_ID']==47) {
    $val = str_replace('"','',$students_temps['total_duration']);
    $total_duration = intval($val);
} else {
    if (str_contains($students_temps['Duration'], '/')) {
        $a = explode("/", $students_temps['Duration']);
        $total_duration = intval($a[0]);
    } else {
        $total_duration = intval($students_temps['Duration']);
    }
}


// ----------------------------------------
// DATE WITH SUFFIX
// ----------------------------------------
function addOrdinalSuffix($number){
    if (!in_array(($number % 100), [11, 12, 13])) {
        return $number . ([1=>'st',2=>'nd',3=>'rd'][$number%10] ?? 'th');
    }
    return $number.'th';
}



// ----------------------------------------
// MARKS & GRADE
// ----------------------------------------
$marks_result = $conn->query("
    SELECT m.*, s.Max_Marks 
    FROM marksheets m 
    LEFT JOIN Syllabi s ON m.subject_id = s.ID 
    WHERE m.enrollment_no = '".$students_temps['Unique_ID']."'
");
if ($marks_result->num_rows == 0) {
    echo json_encode(['status'=>404,'message'=>"Student Result marks not uploaded"]);
    exit;
}
$overall_total_max = 0;
$overall_total_obt = 0;
$certife_number = "";
while ($row = $marks_result->fetch_assoc()) {
    $overall_total_obt += $row['obt_marks_ext'] + $row['obt_marks_int'];
    $overall_total_max += $row['Max_Marks'];
    $certife_number = $row['certificate_number'];
    $certife_date = $row['certification_date'];
}

// print_r($certife_date);die;
$timestamp = strtotime($certife_date);
$examDay   = addOrdinalSuffix(date("d",$timestamp));
$examMonth = date("M",$timestamp);
$examYear  = date("Y",$timestamp);
$overall_percentage = ($overall_total_max > 0)
                        ? ($overall_total_obt / $overall_total_max) * 100
                        : 0;

$overall_grade =
    ($overall_percentage > 80) ? "A+" :
    (($overall_percentage > 60) ? "A" :
    (($overall_percentage > 50) ? "B" :
    (($overall_percentage > 40) ? "C" : "F")));


// ----------------------------------------
// BASIC VARIABLES
// ----------------------------------------
$name     = trim($students_temps['First_Name']." ".$students_temps['Middle_Name']." ".$students_temps['Last_Name']);
$courseName = strtoupper($students_temps['course']);
$councilDate = date("d M Y");

require_once('../../extras/TCPDF/tcpdf.php');
require_once('../../extras/vendor/autoload.php');


// ----------------------------------------
// PDF START
// ----------------------------------------
$pdf = new Fpdi('L','mm',[299,210]);
$pdf->SetPrintHeader(false);
$pdf->SetPrintFooter(false);
$pdf->SetAutoPageBreak(false, 0);
// Template
$pdf->setSourceFile('certiifcate.pdf');
$tplIdx = $pdf->importPage(1);
$pdf->AddPage();
$pdf->useTemplate($tplIdx, 0, 0, 299, 210);

// Fonts
$pdf->AddFont('montserratb','','montserratb.php');
$pdf->AddFont('worksanssemib','','worksanssemib.php');
$pdf->SetTextColor(0,0,0);
// print_r($_SERVER['DOCUMENT_ROOT'].$qrFile);die;

// ----------------------------------------
// NAME WRAP
// ----------------------------------------
$maxLen = 24;
$wrapped = wordwrap($name, $maxLen, "\n");
$lines = explode("\n", $wrapped);

$pdf->SetFont('montserratb','',14);
$pdf->writeHTMLCell(80,0,202,71.2,($lines[0] ?? ''),0,0,false,false,'L');
$pdf->writeHTMLCell(100,0,22,82.2,($lines[1] ?? ''),0,0,false,false,'L');


// ----------------------------------------
// COURSE WRAP
// ----------------------------------------
$pdf->SetFont('worksanssemib','',14);
$maxChars = 30;
$words = explode(" ", $courseName);
$cline1 = $cline2 = "";

foreach ($words as $w) {
    if (strlen($cline1.' '.$w) <= $maxChars) $cline1 .= ($cline1?' ':'').$w;
    else $cline2 .= ($cline2?' ':'').$w;
}

$pdf->writeHTMLCell(90,0,176,81.6,$cline1,0,0,false,true,'L');
$pdf->writeHTMLCell(186,0,22,91.6,$cline2,0,0,false,true,'L');
// echo("<pre>");print_r($students_temps);

// ----------------------------------------
// OTHER FIELDS
// ----------------------------------------
$pdf->SetFont('worksanssemib','',14);
$pdf->writeHTMLCell(0,0,22,101,$students_temps['centerName'],0,0,false,true,'L');

$pdf->SetFont('montserratb','',14);
$pdf->writeHTMLCell(100,7,213,92,$total_duration.' '.$durMonthYear,0,0,false,true,'L');

$pdf->writeHTMLCell(50,0,260,102,"<b>$overall_grade</b>",0,1,false,true,'L');

$pdf->writeHTMLCell(0,0,130,112,$examDay,0,0,false,true,'L');
$pdf->writeHTMLCell(0,0,206,112,$examMonth,0,0,false,true,'L');
$pdf->writeHTMLCell(0,0,243,112,$examYear,0,0,false,true,'L');

$pdf->writeHTMLCell(100,7,212,123,$councilDate,0,0,false,true,'L');
// print_r($_SERVER['DOCUMENT_ROOT'].$qrFile);die;
$pdf->Image($_SERVER['DOCUMENT_ROOT'].$students_temps['Location'],234.2,15.2,35,45);
$pdf->Image($qrFile, 15.5, 175.2, 25, 25, 'PNG');
$pdf->SetFont('worksanssemib','',14);
$pdf->writeHTMLCell(0,0,40,14.5,$certife_number,0,0,false,true,'L');

// $pdf->Output('certificate', "I");
// ----------------------------------------
// SAVE PDF (NO OUTPUT TO BROWSER)
// ----------------------------------------
$filename = $students_temps['Unique_ID']."_".time().".pdf";
$savePath = $_SERVER['DOCUMENT_ROOT']."/uploads/certificates/".$filename;

$pdf->Output($savePath, "F");


// // ----------------------------------------
// // INSERT DB RECORD
// // ----------------------------------------
$student_id    = $id;
$enrollment_no = $students_temps['Enrollment_No'];
$file_path     = "uploads/certificates/".$filename;
$file_type     = "pdf";
$status        = 1;
$created_by    = $_SESSION['ID'];
$created_at    = date("Y-m-d H:i:s");
$issueDate = $certife_number;

$conn->query("
    INSERT INTO certificates 
    (student_id, enrollment_no, file_path, file_type, status, created_by, created_at,issue_date)
    VALUES ('$student_id','$enrollment_no','$file_path','$file_type','$status','$created_by','$created_at','$certife_date')
");


// // ----------------------------------------
// // JSON RESPONSE FOR AJAX
// // ----------------------------------------
echo json_encode([
    'status'  => 200,
    'message' => "Certificate generated successfully!",
    'file'    => "/uploads/certificates/".$filename
]);
exit;

?>
