<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../../includes/db-config.php';
require '../../extras/vendor/autoload.php';
require 'phpqrcode/qrlib.php';
use setasign\Fpdi\Tcpdf\Fpdi;

session_start();
ini_set('max_execution_time', 120); 

// Get absolute paths
$base_dir = dirname(dirname(dirname(__FILE__))); // Go up to project root
$pdf_dir = $base_dir . '/uploads/certificates/';
$tempDir = $base_dir . '/uploads/qr/';
$certi_template_path = dirname(__FILE__) . '/certiifcate.pdf'; // Template file same directory mein hai

// Create directories if they don't exist
if(!is_dir($pdf_dir)){
    mkdir($pdf_dir, 0777, true);
}
if(!is_dir($tempDir)){
    mkdir($tempDir, 0777, true);
}

// Build SQL query based on filters
$sqlQuery = '';
if (isset($_POST['course_type_id']) && !empty($_POST['course_type_id'])) {
    $course_id = $_POST['course_type_id'];
    $sqlQuery .= "AND Students.Course_ID = '$course_id'";
}

if (isset($_POST['course_id']) && !empty($_POST['course_id'])) {
    $sub_course_id = $_POST['course_id'];
    $sqlQuery .= " AND Students.Sub_Course_ID = '$sub_course_id'";
}

if(isset($_POST['student_id'])){
    $student_id_array = [];
    $student_id_array = explode(",", $_POST['student_id']);
    foreach ($student_id_array as &$en_no) {
        $en_no = "'" . trim($en_no) . "'";
    }
    unset($en_no);
    $student_id = implode(",", $student_id_array);
    $sqlQuery .= " AND Students.Unique_ID IN ($student_id)";
}

if (isset($_POST['category']) && !empty($_POST['category'])) {
    $category = $_POST['category'];
    $sqlQuery .= " AND Students.Duration = '$category'";
}

// Get all students based on filters
$students_sql = $conn->query("
    SELECT Students.*, Sub_Courses.Min_Duration as total_duration, 
           Modes.Name as mode, Sub_Courses.Name as course, 
           Courses.Name as program_Type 
    FROM Students 
    LEFT JOIN Sub_Courses ON Sub_Courses.ID = Students.Sub_Course_ID 
    LEFT JOIN Modes ON Students.University_ID = Modes.University_ID 
    LEFT JOIN Courses ON Students.Course_ID = Courses.ID  
    WHERE 1=1 $sqlQuery
");
    // print_r($sqlQuery);die;
if ($students_sql->num_rows > 0) {
    $generated_files = [];
    $staticCertificateNumber = 0;
    while ($row = $students_sql->fetch_assoc()) {
        $staticCertificateNumber += 1;
        // Get complete student data with admission session
        $students_result = $conn->query("
            SELECT Students.*, Admission_Sessions.Name as Admission_Session, 
                   Sub_Courses.Min_Duration as total_duration, 
                   Modes.Name as mode, Sub_Courses.Name as course, 
                   Courses.Name as program_Type 
            FROM Students 
            LEFT JOIN Sub_Courses ON Sub_Courses.ID = Students.Sub_Course_ID 
            LEFT JOIN Modes ON Students.University_ID = Modes.University_ID 
            LEFT JOIN Courses ON Students.Course_ID = Courses.ID   
            LEFT JOIN Admission_Sessions ON Students.Admission_Session_ID = Admission_Sessions.ID  
            WHERE Students.Unique_ID = '" . trim($row['Unique_ID']) . "'
        ");

        if ($students_result && $students_result->num_rows > 0) {
            $students_temps = $students_result->fetch_assoc();
            $student_id = $students_temps['ID'];
            
            // Generate QR code
            $qrFile = $tempDir . $student_id . '.png';
            $encodedId = base64_encode($student_id);
            $qrText = "https://ams.ccvte.org/student/certificate_verify?id=" . $encodedId;
            if(!file_exists($qrFile)){
                QRcode::png($qrText, $qrFile, QR_ECLEVEL_L, 5);
            }
            
            // Get student photo
            $photo_result = $conn->query("
                SELECT Location FROM Student_Documents 
                WHERE Student_ID = '$student_id' AND Type = 'Photo'
            ");
            $photo_data = $photo_result->fetch_assoc();
            $photo_path = $photo_data['Location'] ?? '';
            
            // Calculate overall marks and get certificate data
            $marks_result = $conn->query("
                SELECT m.*, s.Max_Marks 
                FROM marksheets m 
                LEFT JOIN Syllabi s ON m.subject_id = s.ID 
                WHERE m.enrollment_no = '" . $students_temps['Unique_ID'] . "'
            ");
            
            $overall_total_max = 0;
            $overall_total_obt = 0;
            $certificate_number = "";
            $certificate_date = "";
            $exam_date = "";
            if ($marks_result->num_rows > 0) {
                while ($mark = $marks_result->fetch_assoc()) {
                    // print_r($mark['obt_marks_int']);
                    $overall_total_obt += $mark['obt_marks_ext'] + $mark['obt_marks_int'];
                    
                    $overall_total_max += $mark['Max_Marks'];
                    $certificate_number = $mark['certificate_number'] ?? '';
                    $certificate_date = $mark['certification_date'] ?? '';
                    $exam_date = $mark['exam_date'] ?? '';
                }
            }
            // $overall_total_obt = 7;
            // $overall_total_max = 10;
            if($certificate_number==""){
                $certificate_number = $staticCertificateNumber;
            }
            if($certificate_date==""){
                $certificate_date = "2024-07-26 00:00:00";
            }
            // Calculate percentage and grade
            $overall_percentage = ($overall_total_max > 0) 
                ? ($overall_total_obt / $overall_total_max) * 100 
                : 0;
            // echo('<pre>');print_r($overall_total_obt);die;
            $overall_grade = ($overall_percentage > 80) ? "A+" :
                            (($overall_percentage > 60) ? "A" :
                            (($overall_percentage > 50) ? "B" :
                            (($overall_percentage > 40) ? "C" : "F")));
            
            // Format certificate date
            $examDay = $examMonth = $examYear = '';
            // print_r($certificate_date);die;
            if (!empty($exam_date)) {
                $timestamp = strtotime($exam_date);
                $examDay = date("d", $timestamp);
                $examMonth = date("M", $timestamp);
                $examYear = date("Y", $timestamp);
            }
            
            // Current date for council
            $councilDate = date("d M Y", strtotime($certificate_date));
            
            // Determine duration and course info
            
            
            $durMonthYear = $students_temps['program_Type'] == "Skill Program" ? "Months" : "Year";
           
            
            $total_duration = 0;
            if ($students_temps['University_ID'] == 47) {
                $val = str_replace('"', '', $students_temps['total_duration']);
                $total_duration = intval($val);
            } else {
                if (str_contains($students_temps['Duration'], '/')) {
                    $a = explode("/", $students_temps['Duration']);
                    $total_duration = intval($a[0]);
                } else {
                    $total_duration = intval($students_temps['Duration']);
                }
            }
            
              if($total_duration == "12") {
                $total_duration = "1";
            }
            if($total_duration == "24") {
                $total_duration = "2";
            }
            
            // Student name
            // $name = trim($students_temps['First_Name'] . " " . 
            //           $students_temps['Middle_Name'] . " " . 
            //           $students_temps['Last_Name'] . " (". $students_temps['Unique_ID'] .")");
                       
           $name = '<span style="white-space: nowrap;">' .
                 trim(
                     $students_temps['First_Name'] . ' ' .
                     $students_temps['Middle_Name'] . ' ' .
                     $students_temps['Last_Name']
                 ) .
                 ' (' . $students_temps['Unique_ID'] . ')' .
                 '</span>';
                    //   echo $name;die;
            
            // Course name
            $courseName = strtoupper($students_temps['course']);
            
            // Get center name
            $center_result = $conn->query("
                SELECT Users.Name AS centerName 
                FROM Users 
                WHERE Users.ID = '" . $students_temps['Added_For'] . "'
            ");
            $center_data = $center_result->fetch_assoc();
            $centerName = $center_data['centerName'] ?? '';
            
            // Create PDF
            require_once('../../extras/TCPDF/tcpdf.php');
            require_once('../../extras/vendor/autoload.php');
            
            $pdf = new Fpdi('L', 'mm', [299, 210]);
            $pdf->SetPrintHeader(false);
            $pdf->SetPrintFooter(false);
            $pdf->SetAutoPageBreak(false, 0);
            
            // Use template with background - use absolute path
            if (!file_exists($certi_template_path)) {
                die("Template file not found: " . $certi_template_path);
            }
            $pdf->setSourceFile($certi_template_path);
            $tplIdx = $pdf->importPage(1);
            $pdf->AddPage();
            $pdf->useTemplate($tplIdx, 0, 0, 299, 210);
            
            // Add fonts
            $pdf->AddFont('montserratb', '', 'montserratb.php');
            $pdf->AddFont('worksanssemib', '', 'worksanssemib.php');
            $pdf->SetTextColor(0, 0, 0);
            
            // NAME WRAPPING
            $maxLen = 70;
            $wrapped = wordwrap($name, $maxLen, "\n");
            $lines = explode("\n", $wrapped);
            
            $pdf->SetFont('montserratb', '', 12);
            // First part of name
            $pdf->writeHTMLCell(90, 0, 186, 71.2, ($lines[0] ?? ''), 0, 0, false, false, 'L');
            // Second part of name (if exists)
            $pdf->writeHTMLCell(80, 0, 22, 82.2, ($lines[1] ?? ''), 0, 0, false, false, 'L');
            
            // COURSE WRAPPING
            $pdf->SetFont('worksanssemib', '', 14);
            $maxChars = 15;
            // $words = explode(" ", $courseName);
            // // echo('<pre>');print_r($words);die;
            // $cline1 = $cline2 = "";
            
            // foreach ($words as $w) {
            //     if (strlen($cline1 . ' ' . $w) <= $maxChars) {
            //         $cline1 .= ($cline1 ? ' ' : '') . $w;
            //     } else {
            //         $cline2 .= ($cline2 ? ' ' : '') . $w;
            //     }
            // }
            // //   echo('<pre>');print_r($cline1);die;
            // // First line of course
            // $pdf->writeHTMLCell(90, 0, 210, 81.6, $cline1, 0, 0, false, true, 'L');
            // // Second line of course
            // $pdf->writeHTMLCell(186, 0, 22, 91.6, $cline2, 0, 0, false, true, 'L');
 $words = explode(" ", strip_tags($courseName));
$cline1 = $cline2 = "";
$line1Done = false;
$cellWidth = 70;
$buffer = 8; // <-- yeh value badhao/ghatao jab tak sahi split na mile

foreach ($words as $w) {
    $testLine = $cline1 . ($cline1 ? ' ' : '') . $w;
    $testWidth = $pdf->GetStringWidth($testLine);

    if (!$line1Done && $testWidth <= ($cellWidth - $buffer)) {
        $cline1 = $testLine;
    } else {
        $line1Done = true;
        $cline2 .= ($cline2 ? ' ' : '') . $w;
    }
}

$pdf->writeHTMLCell($cellWidth, 0, 210, 81.6, $cline1, 0, 0, false, true, 'L');
$pdf->writeHTMLCell(200, 0, 22, 91.6, $cline2, 0, 0, false, true, 'L');
            
            // CENTER NAME
            $pdf->SetFont('worksanssemib', '', 14);
            //$pdf->writeHTMLCell(0, 0, 22, 101, strtoupper($centerName), 0, 0, false, true, 'L');
            $pdf->writeHTMLCell(200, 0, 22, 101, strtoupper($centerName), 0, 0, false, true, 'L');
            
            // DURATION
            $pdf->SetFont('montserratb', '', 14);
            $pdf->writeHTMLCell(100, 7, 213, 92, $total_duration . ' ' . $durMonthYear, 0, 0, false, true, 'L');
            
            // GRADE
            $pdf->writeHTMLCell(50, 0, 260, 102, "<b>$overall_grade</b>", 0, 1, false, true, 'L');
            
            // EXAM DATE
            $pdf->writeHTMLCell(0, 0, 142, 112, $examDay, 0, 0, false, true, 'L');
            $pdf->writeHTMLCell(0, 0, 196, 112, $examMonth, 0, 0, false, true, 'L');
            $pdf->writeHTMLCell(0, 0, 243, 112, $examYear, 0, 0, false, true, 'L');
            
            // COUNCIL DATE
            $pdf->writeHTMLCell(100, 7, 212, 123, $councilDate, 0, 0, false, true, 'L');
            // $pdf->writeHTMLCell(100, 7, 212, 123, '15/Feb/2026', 0, 0, false, true, 'L');
            
            // STUDENT PHOTO (if exists)
            if (!empty($photo_path)) {
                $full_photo_path = $base_dir . $photo_path;
                if (file_exists($full_photo_path)) {
                    $pdf->Image($full_photo_path, 233, 15.8, 38, 45);
                }
            }
            
            // QR CODE
            $pdf->Image($qrFile, 15.5, 175.2, 25, 25, 'PNG');
            
            // CERTIFICATE NUMBER
            $pdf->SetFont('worksanssemib', '', 14);
            $pdf->writeHTMLCell(0, 0, 40, 14.5, $certificate_number, 0, 0, false, true, 'L');
            
            // Save the PDF
            $filename = $students_temps['Unique_ID'] . ".pdf";
            $full_pdf_path = $pdf_dir . $filename;
            
            // Use absolute path for Output
            $pdf->Output($full_pdf_path, "F");
            
            // Store for later ZIP creation
            $generated_files[] = $full_pdf_path;
            
            // Insert into certificates table
            $student_id_val = $students_temps['ID'];
            $enrollment_no = $students_temps['Enrollment_No'];
            $file_path = "uploads/certificates/" . $filename;
            $file_type = "pdf";
            $status = 1;
            $created_by = $_SESSION['ID'] ?? 0;
            $created_at = date("Y-m-d H:i:s");
            $certificate_date = date("Y-m-d",strtotime($certificate_date));
            $conn->query("
                INSERT INTO certificates 
                (student_id, enrollment_no, file_path, file_type, status, created_by, created_at, issue_date)
                VALUES ('$student_id_val', '$enrollment_no', '$file_path', '$file_type', '$status', '$created_by', '$created_at', '$certificate_date')
            ");
            
            // REMOVE ALL echo STATEMENTS - they corrupt the ZIP file
            // echo "Certificate generated for: " . $name . "<br>";
        }
    }
    
    // Create ZIP file of all certificates
    $zip = new ZipArchive();
    $zip_filename = 'Certificates_' . time() . '.zip';
    $zip_file = $pdf_dir . $zip_filename;
    
    if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        foreach ($generated_files as $file) {
            if (file_exists($file)) {
                $zip->addFile($file, basename($file));
            }
        }
        $zip->close();
        
        // Clean up individual PDF files
        foreach ($generated_files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        
        // Clean up QR files
        $qr_files = glob($tempDir . '*.png');
        foreach ($qr_files as $qr_file) {
            if (file_exists($qr_file)) {
                unlink($qr_file);
            }
        }
        
        // Set headers for ZIP download
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
        header('Content-Length: ' . filesize($zip_file));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Clear any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Read and output the ZIP file
        readfile($zip_file);
        
        // Delete the ZIP file after download
        unlink($zip_file);
        exit;
        
    } else {
        // Agar ZIP create nahi ho paya, toh error message
        http_response_code(500);
        echo 'Failed to create zip file.';
    }
} else {
    // Agar koi student nahi mila
    http_response_code(404);
    echo "No records found!";
}
?>