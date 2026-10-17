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

// =====================================================
// PATHS
// =====================================================

$base_dir = dirname(dirname(dirname(__FILE__)));

$pdf_dir = $base_dir . '/uploads/certificates/';
$tempDir = $base_dir . '/uploads/qr/';

$certi_template_path = dirname(__FILE__) . '/CCVTE.pdf';

// =====================================================
// CREATE DIRECTORIES
// =====================================================

if (!is_dir($pdf_dir)) {
    mkdir($pdf_dir, 0777, true);
}

if (!is_dir($tempDir)) {
    mkdir($tempDir, 0777, true);
}

// =====================================================
// BUILD SQL FILTER
// =====================================================

$sqlQuery = '';

if (isset($_POST['course_type_id']) && !empty($_POST['course_type_id'])) {
    $course_id = $_POST['course_type_id'];
    $sqlQuery .= " AND Students.Course_ID = '$course_id'";
}

if (isset($_POST['course_id']) && !empty($_POST['course_id'])) {
    $sub_course_id = $_POST['course_id'];
    $sqlQuery .= " AND Students.Sub_Course_ID = '$sub_course_id'";
}

if (isset($_POST['student_id']) && !empty($_POST['student_id'])) {
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

// =====================================================
// GET STUDENTS
// =====================================================

$students_sql = $conn->query("
    SELECT 
        Students.*,
        Sub_Courses.Min_Duration AS total_duration,
        Modes.Name AS mode,
        Sub_Courses.Name AS course,
        Courses.Name AS program_Type
    FROM Students
    LEFT JOIN Sub_Courses 
        ON Sub_Courses.ID = Students.Sub_Course_ID
    LEFT JOIN Modes 
        ON Students.University_ID = Modes.University_ID
    LEFT JOIN Courses 
        ON Students.Course_ID = Courses.ID
    WHERE 1=1 $sqlQuery
");

// =====================================================
// CHECK STUDENTS
// =====================================================

if ($students_sql->num_rows > 0) {

    $generated_files = [];
    $staticCertificateNumber = 0;

    // =================================================
    // LOOP STUDENTS
    // =================================================

    while ($row = $students_sql->fetch_assoc()) {

        $staticCertificateNumber++;

        // =============================================
        // GET COMPLETE STUDENT DATA
        // =============================================

        $students_result = $conn->query("
            SELECT 
                Students.*,
                Admission_Sessions.Name AS Admission_Session,
                Sub_Courses.Min_Duration AS total_duration,
                Modes.Name AS mode,
                Sub_Courses.Name AS course,
                Courses.Name AS program_Type
            FROM Students
            LEFT JOIN Sub_Courses 
                ON Sub_Courses.ID = Students.Sub_Course_ID
            LEFT JOIN Modes 
                ON Students.University_ID = Modes.University_ID
            LEFT JOIN Courses 
                ON Students.Course_ID = Courses.ID
            LEFT JOIN Admission_Sessions 
                ON Students.Admission_Session_ID = Admission_Sessions.ID
            WHERE Students.Unique_ID = '" . trim($row['Unique_ID']) . "'
        ");

        if (!$students_result || $students_result->num_rows <= 0) {
            continue;
        }

        $students_temps = $students_result->fetch_assoc();
        $student_id = $students_temps['ID'];

        // =============================================
        // QR CODE
        // =============================================

        $qrFile = $tempDir . $student_id . '.png';
        $encodedId = base64_encode($student_id);
        $qrText = "https://ams.ccvte.org/student/certificate_verify?id=" . $encodedId;

        if (!file_exists($qrFile)) {
            QRcode::png(
                $qrText,
                $qrFile,
                QR_ECLEVEL_L,
                5
            );
        }

        // =============================================
        // STUDENT PHOTO
        // =============================================

        $photo_result = $conn->query("
            SELECT Location 
            FROM Student_Documents
            WHERE Student_ID = '$student_id'
            AND Type = 'Photo'
        ");

        $photo_data = $photo_result ? $photo_result->fetch_assoc() : [];
        $photo_path = $photo_data['Location'] ?? '';

        // =============================================
        // MARKS
        // =============================================

        $marks_result = $conn->query("
            SELECT 
                m.*,
                s.Max_Marks
            FROM marksheets m
            LEFT JOIN Syllabi s 
                ON m.subject_id = s.ID
            WHERE m.enrollment_no = '" . $students_temps['Unique_ID'] . "'
        ");

        $overall_total_max = 0;
        $overall_total_obt = 0;

        $certificate_number = "";
        $certificate_date = "";
        $exam_date = "";

        if ($marks_result && $marks_result->num_rows > 0) {
            while ($mark = $marks_result->fetch_assoc()) {
                $overall_total_obt +=
                    $mark['obt_marks_ext'] +
                    $mark['obt_marks_int'];

                $overall_total_max += $mark['Max_Marks'];

                $certificate_number = $mark['certificate_number'] ?? '';
                $certificate_date = $mark['certification_date'] ?? '';
                $exam_date = $mark['exam_date'] ?? '';
            }
        }

        // =============================================
        // CERTIFICATE NUMBER
        // =============================================

        if ($certificate_number == "") {
            $certificate_number = $staticCertificateNumber;
        }

        // =============================================
        // CERTIFICATE DATE
        // =============================================

        if ($certificate_date == "") {
            $certificate_date = "2024-07-26 00:00:00";
        }

        // =============================================
        // PERCENTAGE
        // =============================================

        $overall_percentage = ($overall_total_max > 0)
            ? ($overall_total_obt / $overall_total_max) * 100
            : 0;

        // =============================================
        // GRADE
        // =============================================

        $overall_grade =
            ($overall_percentage > 80)
                ? "A+"
                : (($overall_percentage > 60)
                    ? "A"
                    : (($overall_percentage > 50)
                        ? "B"
                        : (($overall_percentage > 40)
                            ? "C"
                            : "F")));

        // =============================================
        // EXAM DATE
        // =============================================

        $examDay = '';
        $examMonth = '';
        $examYear = '';

        if (!empty($exam_date)) {
            $timestamp = strtotime($exam_date);
            $examDay = date("d", $timestamp);
            $examMonth = date("M", $timestamp);
            $examYear = date("Y", $timestamp);
        }

        // =============================================
        // COUNCIL DATE
        // =============================================

        $councilDate = date(
            "d M Y",
            strtotime($certificate_date)
        );

        // =============================================
        // DURATION TYPE
        // =============================================

        $durMonthYear =
            $students_temps['program_Type'] == "Skill Program"
                ? "Months"
                : "Year";

        // =============================================
        // TOTAL DURATION
        // =============================================

        $total_duration = 0;

        if ($students_temps['University_ID'] == 47) {
            $val = str_replace(
                '"',
                '',
                $students_temps['total_duration']
            );
            $total_duration = intval($val);
        } else {
            if (str_contains(
                $students_temps['Duration'],
                '/'
            )) {
                $a = explode(
                    "/",
                    $students_temps['Duration']
                );
                $total_duration = intval($a[0]);
            } else {
                $total_duration =
                    intval($students_temps['Duration']);
            }
        }

        if ($total_duration == "12") {
            $total_duration = "1";
        }

        if ($total_duration == "24") {
            $total_duration = "2";
        }

        // =============================================
        // STUDENT NAME
        // =============================================

        $name = trim(
            $students_temps['First_Name'] . ' ' .
            $students_temps['Middle_Name'] . ' ' .
            $students_temps['Last_Name']
        );

        $name .= ' (' . $students_temps['Unique_ID'] . ')';

        // =============================================
        // COURSE NAME
        // =============================================

        $courseName = strtoupper(
            trim(
                strip_tags(
                    $students_temps['course']
                )
            )
        );

        // =============================================
        // CENTER NAME
        // =============================================

        $center_result = $conn->query("
            SELECT Users.Name AS centerName
            FROM Users
            WHERE Users.ID = '" .
            $students_temps['Added_For'] . "'
        ");

        $center_data = $center_result
            ? $center_result->fetch_assoc()
            : [];

        $centerName = $center_data['centerName'] ?? '';

        // =================================================
        // CREATE PDF
        // =================================================

        require_once('../../extras/TCPDF/tcpdf.php');
        require_once('../../extras/vendor/autoload.php');

        $pdf = new Fpdi(
            'P',
            'mm',
            [210, 299]
        );

        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(false);
        $pdf->SetAutoPageBreak(false, 0);

        // =============================================
        // LOAD TEMPLATE
        // =============================================

        if (!file_exists($certi_template_path)) {
            die(
                "Template file not found: " .
                $certi_template_path
            );
        }

        $pdf->setSourceFile(
            $certi_template_path
        );

        $tplIdx = $pdf->importPage(1);

        $pdf->AddPage();

        $pdf->useTemplate(
            $tplIdx,
            0,
            0,
            210,
            299
        );

        // =============================================
        // ADD FONTS
        // =============================================

        $pdf->AddFont(
            'montserratb',
            '',
            'montserratb.php'
        );

        $pdf->AddFont(
            'worksanssemib',
            '',
            'worksanssemib.php'
        );

        $pdf->SetTextColor(0, 0, 0);

        // =================================================
        // NAME CONFIGURATION & WRAPPING ($name <= 20 vs > 20)
        // =================================================

        $pdf->SetFont('montserratb', '', 12);
        $maxLen = 25;
        $firstmMaxLen = 20;

        if (mb_strlen($name) <= $maxLen) {
          
            // Short Names (20 characters or fewer)
            $wrapped = wordwrap($name, $firstmMaxLen, "\n");
            $lines = explode("\n", $wrapped);

            $pdf->writeHTMLCell(
                170, 0, 145.5, 106,
                ($lines[0] ?? ''),
                0, 0, false, false, 'L'
            );

            $pdf->writeHTMLCell(
                170, 0, 20, 118,
                ($lines[1] ?? ''),
                0, 0, false, false, 'L'
            );
        } else {
            // Long Names (Greater than 20 characters)
            $wrapped = wordwrap($name, $firstmMaxLen, "\n");
            $lines = explode("\n", $wrapped);

            $pdf->writeHTMLCell(
                170, 0, 137.5, 106,
                ($lines[0] ?? ''),
                0, 0, false, false, 'L'
            );

            $pdf->writeHTMLCell(
                170, 0, 20, 118,
                ($lines[1] ?? ''),
                0, 0, false, false, 'L'
            );
        }

        // =================================================
        // COURSE NAME CONFIGURATION & WRAPPING ($courseName <= 30 vs > 30)
        // =================================================

        $pdf->SetFont('worksanssemib', '', 12);

        if (mb_strlen($courseName) <= 40) {
            
          
          $maxFirstLineChars = 15;
            $firstPart = substr($courseName, 0, $maxFirstLineChars);
            $breakPos = strrpos($firstPart, ' ');

            if ($breakPos !== false) {
                $cline1 = trim(substr($courseName, 0, $breakPos));
                $cline2 = trim(substr($courseName, $breakPos));
            } else {
                $cline1 = $firstPart;
                $cline2 = trim(substr($courseName, $maxFirstLineChars));
            }

            // Course Line 1
            $pdf->writeHTMLCell(
                110, 0, 154, 118,
                htmlspecialchars($cline1),
                0, 0, false, false, 'L'
            );

            // Course Line 2
            $pdf->writeHTMLCell(
                175, 0, 20, 130,
                htmlspecialchars($cline2),
                0, 0, false, false, 'L'
            );
          
        } else {
            // Long Course Names (Greater than 30 characters) - 2 line split setup
            $maxFirstLineChars = 20;
            $firstPart = substr($courseName, 0, $maxFirstLineChars);
            $breakPos = strrpos($firstPart, ' ');

            if ($breakPos !== false) {
                $cline1 = trim(substr($courseName, 0, $breakPos));
                $cline2 = trim(substr($courseName, $breakPos));
            } else {
                $cline1 = $firstPart;
                $cline2 = trim(substr($courseName, $maxFirstLineChars));
            }

            // Course Line 1
            $pdf->writeHTMLCell(
                110, 0, 156, 118,
                htmlspecialchars($cline1),
                0, 0, false, false, 'L'
            );

            // Course Line 2
            $pdf->writeHTMLCell(
                175, 0, 20, 130,
                htmlspecialchars($cline2),
                0, 0, false, false, 'L'
            );
        }

        // =================================================
        // CENTER NAME CONFIGURATION ($centerName < 25 vs >= 25)
        // =================================================

        $pdf->SetFont('worksanssemib', '', 12);

        if (mb_strlen($centerName) < 25) {
            // Short Center Name (< 25 characters)
            $pdf->writeHTMLCell(
                170,
                0,
                80,
                142,
                strtoupper($centerName),
                0,
                0,
                false,
                true,
                'L'
            );
        } else {
            // Long Center Name (25 characters or more)
            $pdf->writeHTMLCell(
                170,
                0,
                41,
                142,
                strtoupper($centerName),
                0,
                0,
                false,
                true,
                'L'
            );
        }

        // =================================================
        // DURATION
        // =================================================

        $pdf->SetFont(
            'montserratb',
            '',
            12
        );

        $pdf->writeHTMLCell(
            100,
            7,
            160,
            130,
            $total_duration . ' ' . $durMonthYear,
            0,
            0,
            false,
            true,
            'L'
        );

        // =================================================
        // GRADE
        // =================================================

        $pdf->writeHTMLCell(
            50,
            0,
            50,
            154,
            "<b>$overall_grade</b>",
            0,
            1,
            false,
            true,
            'L'
        );

        // =================================================
        // EXAM DATE
        // =================================================

        $pdf->writeHTMLCell(
            50,
            0,
            160,
            154,
            $examDay,
            0,
            0,
            false,
            true,
            'L'
        );

        $pdf->writeHTMLCell(
            0,
            0,
            46,
            167,
            $examMonth,
            0,
            0,
            false,
            true,
            'L'
        );

        $pdf->writeHTMLCell(
            0,
            0,
            100,
            167,
            $examYear,
            0,
            0,
            false,
            true,
            'L'
        );

        // =================================================
        // COUNCIL DATE
        // =================================================

        $pdf->writeHTMLCell(
            100,
            7,
            100,
            178,
            $councilDate,
            0,
            0,
            false,
            true,
            'L'
        );

        // =================================================
        // STUDENT PHOTO
        // =================================================

        if (!empty($photo_path)) {
            $full_photo_path = $base_dir . $photo_path;

            if (file_exists($full_photo_path)) {
                $pdf->Image(
                    $full_photo_path,
                    163,
                    33,
                    34,
                    40
                );
            }
        }

        // =================================================
        // QR CODE
        // =================================================

        if (file_exists($qrFile)) {
            $pdf->Image(
                $qrFile,
                12,
                252,
                25,
                25,
                'PNG'
            );
        }

        // =================================================
        // CERTIFICATE NUMBER
        // =================================================

        $pdf->SetFont(
            'worksanssemib',
            '',
            12
        );

        $pdf->writeHTMLCell(
            0,
            0,
            30,
            16.5,
            $certificate_number,
            0,
            0,
            false,
            true,
            'L'
        );

        // =================================================
        // SAVE PDF
        // =================================================

        $filename = $students_temps['Unique_ID'] . ".pdf";
        $full_pdf_path = $pdf_dir . $filename;

        $pdf->Output(
            $full_pdf_path,
            "F"
        );

        $generated_files[] = $full_pdf_path;

        // =================================================
        // INSERT CERTIFICATE RECORD
        // =================================================

        $student_id_val = $students_temps['ID'];
        $enrollment_no = $students_temps['Enrollment_No'];
        $file_path = "uploads/certificates/" . $filename;
        $file_type = "pdf";
        $status = 1;
        $created_by = $_SESSION['ID'] ?? 0;
        $created_at = date("Y-m-d H:i:s");
        $issue_date = date(
            "Y-m-d",
            strtotime($certificate_date)
        );

        $conn->query("
            INSERT INTO certificates
            (
                student_id,
                enrollment_no,
                file_path,
                file_type,
                status,
                created_by,
                created_at,
                issue_date
            )
            VALUES
            (
                '$student_id_val',
                '$enrollment_no',
                '$file_path',
                '$file_type',
                '$status',
                '$created_by',
                '$created_at',
                '$issue_date'
            )
        ");
    }

    // =====================================================
    // CREATE ZIP AND DOWNLOAD ALL GENERATED PDFs
    // =====================================================

    if (!empty($generated_files)) {

        while (ob_get_level()) {
            ob_end_clean();
        }

        $zip = new ZipArchive();

        $zipFilename = 'certificates_' . date('Y-m-d_H-i-s') . '.zip';
        $zipPath = $pdf_dir . $zipFilename;

        if ($zip->open(
            $zipPath,
            ZipArchive::CREATE | ZipArchive::OVERWRITE
        ) !== true) {

            http_response_code(500);
            echo 'Unable to create ZIP file.';
            exit;
        }

        foreach ($generated_files as $pdfFile) {

            if (file_exists($pdfFile)) {

                $zip->addFile(
                    $pdfFile,
                    basename($pdfFile)
                );
            }
        }

        $zip->close();

        // =============================================
        // CHECK ZIP
        // =============================================

        if (!file_exists($zipPath)) {
            http_response_code(500);
            echo 'ZIP file was not created.';
            exit;
        }

        // =============================================
        // CLEANUP TEMPORARY QR CODE IMAGES
        // =============================================

        $qr_files = glob($tempDir . '*.png');

        if ($qr_files !== false) {

            foreach ($qr_files as $qr_file) {

                if (file_exists($qr_file)) {
                    unlink($qr_file);
                }
            }
        }

        // =============================================
        // DOWNLOAD ZIP
        // =============================================

        header('Content-Type: application/zip');

        header(
            'Content-Disposition: attachment; filename="' .
            basename($zipPath) .
            '"'
        );

        header(
            'Content-Length: ' .
            filesize($zipPath)
        );

        header(
            'Cache-Control: no-cache, no-store, must-revalidate'
        );

        header('Pragma: no-cache');
        header('Expires: 0');

        readfile($zipPath);

        // =============================================
        // DELETE ZIP AFTER DOWNLOAD
        // =============================================

        unlink($zipPath);

        exit;

    } else {

        http_response_code(404);
        echo 'No certificates were generated.';
    }

} else {

    http_response_code(404);
    echo "No records found!";
}

?>
