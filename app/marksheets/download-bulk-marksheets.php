<?php

/*
|--------------------------------------------------------------------------
| ERROR SETTINGS
|--------------------------------------------------------------------------
*/

// Enable these only while debugging.
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require '../../includes/db-config.php';
require '../../includes/helpers.php';

session_start();

use setasign\Fpdi\Fpdi;

$url = "https://erpglocal.iitseducation.org";
$passFail = "PASS";

/*
|--------------------------------------------------------------------------
| LIBRARIES
|--------------------------------------------------------------------------
*/

ob_end_clean();

require_once('../../extras/TCPDF/tcpdf.php');
require_once('../../extras/vendor/setasign/fpdf/fpdf.php');
require_once('../../extras/vendor/setasign/fpdi/src/autoload.php');
require '../../extras/vendor/autoload.php';
require('../../extras/vendor/shuchkin/simplexlsxgen/src/SimpleXLSXGen.php');
require('../../extras/vendor/nuovo/spreadsheet-reader/SpreadsheetReader.php');
require 'phpqrcode/qrlib.php';

/*
|--------------------------------------------------------------------------
| DIRECTORIES
|--------------------------------------------------------------------------
*/

$tempDir = '../../uploads/qr/';
$pdf_dir = '../../uploads/marksheet/';

if (!is_dir($tempDir)) {
    mkdir($tempDir, 0777, true);
}

if (!is_dir($pdf_dir)) {
    mkdir($pdf_dir, 0777, true);
}

/*
|--------------------------------------------------------------------------
| REQUEST ID
|--------------------------------------------------------------------------
|
| Every request gets its own working directory.
| This prevents one request from accidentally putting another request's
| PDFs into its ZIP.
|
*/

$requestId = date('YmdHis') . '_' . bin2hex(random_bytes(4));

$workDir = rtrim($pdf_dir, '/') . '/tmp_' . $requestId . '/';

if (!is_dir($workDir)) {
    mkdir($workDir, 0777, true);
}

/*
|--------------------------------------------------------------------------
| EXPORT DATA
|--------------------------------------------------------------------------
*/

$export_data = [];

if ($_SESSION['university_id'] == 48) {
    $header = [
        'Enrollment_No',
        'Course',
        'Sub-Course',
        'Duration',
        'Remark'
    ];
} else {
    $header = [
        'Enrollment_No',
        'Course',
        'Sub-Course',
        'Semester',
        'Remark'
    ];
}

$export_data[] = $header;

/*
|--------------------------------------------------------------------------
| BUILD STUDENT FILTER
|--------------------------------------------------------------------------
*/

$where = [];
$params = [];
$types = '';

/*
|--------------------------------------------------------------------------
| COURSE TYPE
|--------------------------------------------------------------------------
*/

if (!empty($_POST['course_type_id'])) {

    $where[] = "Students.Course_ID = ?";
    $params[] = $_POST['course_type_id'];
    $types .= 'i';
}

/*
|--------------------------------------------------------------------------
| SUB COURSE
|--------------------------------------------------------------------------
*/

if (!empty($_POST['course_id'])) {

    $where[] = "Students.Sub_Course_ID = ?";
    $params[] = $_POST['course_id'];
    $types .= 'i';
}

/*
|--------------------------------------------------------------------------
| STUDENT IDS / ENROLLMENT NOS
|--------------------------------------------------------------------------
*/

if (!empty($_POST['student_id'])) {

    $studentIds = explode(',', $_POST['student_id']);

    $studentIds = array_values(
        array_filter(
            array_map('trim', $studentIds),
            function ($value) {
                return $value !== '';
            }
        )
    );

    if (!empty($studentIds)) {

        $placeholders = implode(',', array_fill(0, count($studentIds), '?'));

        /*
         * Unique_ID and Enrollment_No may contain strings,
         * therefore bind these as strings.
         */
        $where[] = "
            (
                Students.Unique_ID IN ($placeholders)
                OR
                Students.Enrollment_No IN ($placeholders)
            )
        ";

        foreach ($studentIds as $value) {
            $params[] = $value;
            $types .= 's';
        }

        foreach ($studentIds as $value) {
            $params[] = $value;
            $types .= 's';
        }
    }
}

/*
|--------------------------------------------------------------------------
| CATEGORY / DURATION
|--------------------------------------------------------------------------
*/

if (!empty($_POST['category'])) {

    $where[] = "Students.Duration = ?";
    $params[] = $_POST['category'];
    $types .= 's';
}

/*
|--------------------------------------------------------------------------
| MAIN STUDENT QUERY
|--------------------------------------------------------------------------
|
| IMPORTANT:
| The old code selected Students first and then selected the same student
| again inside the loop.
|
| This query contains all required student information.
|
*/

$whereSql = '';

if (!empty($where)) {
    $whereSql = ' AND ' . implode(' AND ', $where);
}

$studentSql = "
    SELECT
        Students.*,

        Sub_Courses.Min_Duration AS total_duration,
        Sub_Courses.Name AS course,

        Courses.Name AS program_Type,

        Users.Name AS CenterName,

        Modes.Name AS mode,

        Admission_Sessions.Name AS Admission_Session,
        Admission_Sessions.Exam_Session,

        Admission_Types.Name AS Admission_Type,

        Student_Documents.Location AS StudentPhoto

    FROM Students

    LEFT JOIN Sub_Courses
        ON Sub_Courses.ID = Students.Sub_Course_ID

    LEFT JOIN Courses
        ON Courses.ID = Students.Course_ID

    LEFT JOIN Users
        ON Users.ID = Students.Added_For

    LEFT JOIN Modes
        ON Modes.University_ID = Students.University_ID

    LEFT JOIN Admission_Sessions
        ON Admission_Sessions.ID = Students.Admission_Session_ID

    LEFT JOIN Admission_Types
        ON Admission_Types.ID = Students.Admission_Type_ID

    LEFT JOIN Student_Documents
        ON Student_Documents.Student_ID = Students.ID
        AND Student_Documents.Type = 'Photo'

    WHERE 1 = 1

    $whereSql

    GROUP BY Students.ID
";

/*
|--------------------------------------------------------------------------
| PREPARED MAIN QUERY
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare($studentSql);

if (!$stmt) {
    cleanupDirectory($workDir);

    echo json_encode([
        'status' => 'error',
        'message' => 'Unable to prepare student query: ' . $conn->error
    ]);

    exit;
}

if (!empty($params)) {
    bindDynamicParams($stmt, $types, $params);
}

if (!$stmt->execute()) {

    cleanupDirectory($workDir);

    echo json_encode([
        'status' => 'error',
        'message' => 'Unable to execute student query: ' . $stmt->error
    ]);

    exit;
}

$student = $stmt->get_result();

if (!$student || $student->num_rows <= 0) {

    cleanupDirectory($workDir);

    echo json_encode([
        'status' => 'error',
        'message' => 'No record found!'
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| REQUEST SEMESTER
|--------------------------------------------------------------------------
*/

$requestedSchemeId = null;
$requestedSemester = null;

if (!empty($_POST['semester'])) {

    $semesterParts = explode('|', $_POST['semester']);

    if (count($semesterParts) === 2) {

        $requestedSchemeId = (int)$semesterParts[0];
        $requestedSemester = trim($semesterParts[1]);
    }
}

/*
|--------------------------------------------------------------------------
| CACHE ARRAYS
|--------------------------------------------------------------------------
*/

$subjectCache = [];
$qrCache = [];

/*
|--------------------------------------------------------------------------
| GENERATED FILES
|--------------------------------------------------------------------------
*/

$generatedFiles = [];

/*
|--------------------------------------------------------------------------
| PROCESS STUDENTS
|--------------------------------------------------------------------------
*/

while ($data = $student->fetch_assoc()) {

    try {

        /*
        |--------------------------------------------------------------------------
        | BASIC DATA
        |--------------------------------------------------------------------------
        */

        $id = (int)$data['ID'];

        /*
        |--------------------------------------------------------------------------
        | QR CODE
        |--------------------------------------------------------------------------
        */

        $qrFile = $tempDir . $id . '.png';

        if (!isset($qrCache[$id])) {

            if (!file_exists($qrFile)) {

                $encodedId = base64_encode($id);

                $qrText =
                    "https://ams.ccvte.org/student/certificate_verify?id=" .
                    $encodedId;

                QRcode::png(
                    $qrText,
                    $qrFile,
                    QR_ECLEVEL_L,
                    5,
                    1
                );
            }

            $qrCache[$id] = $qrFile;
        }

        /*
        |--------------------------------------------------------------------------
        | INITIAL VALUES
        |--------------------------------------------------------------------------
        */

        $typoArr = [
            0 => "th",
            1 => "st",
            2 => "nd",
            3 => "rd",
            4 => "th",
            5 => "th",
            6 => "th",
            7 => "th",
            8 => "th",
            9 => "th"
        ];

        $total_obt = 0;
        $total_max = 0;
        $min_val = 0;

        $data['marks'] = [];
        $data['allremarks'] = [];

        $obt_int = [];
        $not_empty_ext = [];

        $resultPublishDay = '';

        $semester = $requestedSemester;
        $scheme_id = $requestedSchemeId;

        $sem_sql = '';
        $durationCacheKey = '';

        /*
        |--------------------------------------------------------------------------
        | ADMISSION SESSION
        |--------------------------------------------------------------------------
        */

        $adm_session = '';

        if (!empty($data['Admission_Session'])) {

            $sessionParts = explode('-', $data['Admission_Session']);

            if (count($sessionParts) >= 2) {

                $month = $sessionParts[0];
                $year = $sessionParts[1];

                $year = (int)$year;

                if ($year > 99) {
                    $year = date('y', strtotime($year . '-01-01'));
                }

                $adm_session = '20' . $year . '-' . ($year + 1);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | DETERMINE SEMESTER
        |--------------------------------------------------------------------------
        */

        if (
            isset($_POST['semester']) &&
            !empty($_POST['semester']) &&
            $requestedSemester !== null &&
            $requestedSchemeId !== null
        ) {

            $semester = $requestedSemester;

            $data['Duration'] = $semester;

            $sem_sql = "
                AND Syllabi.Semester = ?
                AND Syllabi.Scheme_ID = ?
            ";

            $durationCacheKey =
                $data['University_ID'] .
                '|' .
                $data['Sub_Course_ID'] .
                '|' .
                $semester .
                '|' .
                $scheme_id;

        } elseif ((int)$data['University_ID'] === 47) {

            $semester = $data['Duration'];

            $sem_sql = "
                AND Syllabi.Semester = ?
            ";

            $durationCacheKey =
                $data['University_ID'] .
                '|' .
                $data['Sub_Course_ID'] .
                '|' .
                $semester;

        } elseif ((int)$data['University_ID'] === 48) {

            $category = strtolower(
                trim((string)$data['Course_Category'])
            );

            $duration = (string)$data['Duration'];

            if (
                $category === 'certified' &&
                ($duration === '6' || $duration === '11')
            ) {

                $due_val = $duration . '/certified';

            } elseif ($category === 'certification') {

                $due_val = $duration . '/certification';

            } elseif (
                $category === 'advance_diploma' ||
                $duration === '11/advance-diploma'
            ) {

                $due_val = '11/advanced';

            } elseif ($category === 'post_graduate') {

                $due_val = '24/post-graduate';

            } else {

                $due_val = $duration;
            }

            $semester = $due_val;

            $sem_sql = "
                AND Syllabi.Semester = ?
            ";

            $durationCacheKey =
                $data['University_ID'] .
                '|' .
                $data['Sub_Course_ID'] .
                '|' .
                $semester;
        }

        /*
        |--------------------------------------------------------------------------
        | EXAM SESSION / ISSUE DATE
        |--------------------------------------------------------------------------
        */

        list(
            $date_of_issue,
            $exam_month
        ) = selectExamSessionAndDateOfIssue(
            $data['Admission_Session'],
            $semester,
            'date&exam'
        );

        /*
        |--------------------------------------------------------------------------
        | UNIVERSITY 48 USES CURRENT DATE
        |--------------------------------------------------------------------------
        */

        if ((int)$data['University_ID'] === 48) {
            $date_of_issue = date('d-m-Y');
        }

        /*
        |--------------------------------------------------------------------------
        | MARKS QUERY
        |--------------------------------------------------------------------------
        |
        | Prepared query.
        |
        */

        $marksSql = "
            SELECT
                marksheets.certification_date,
                marksheets.series,
                marksheets.unique_id,
                marksheets.exam_month,
                marksheets.exam_year,

                marksheets.obt_marks_ext,
                marksheets.obt_marks_int,
                marksheets.obt_marks,

                marksheets.status,
                marksheets.remarks,
                marksheets.Created_At,

                Syllabi.Code,
                Syllabi.Name AS subject_name,
                Syllabi.Min_Marks,
                Syllabi.Max_Marks,
                Syllabi.Credit,
               Syllabi.Paper_Type

            FROM marksheets

            LEFT JOIN Syllabi
                ON marksheets.subject_id = Syllabi.ID

            WHERE marksheets.enrollment_no = ?
            AND Syllabi.Sub_Course_ID = ?

            $sem_sql

            ORDER BY Syllabi.Code ASC
        ";

        $marksStmt = $conn->prepare($marksSql);

        if (!$marksStmt) {

            makeStatus($data, "Result Not Found");

            continue;
        }

        /*
        |--------------------------------------------------------------------------
        | BIND MARK QUERY
        |--------------------------------------------------------------------------
        */

        if (
            isset($_POST['semester']) &&
            !empty($_POST['semester']) &&
            $requestedSemester !== null &&
            $requestedSchemeId !== null
        ) {

            $marksStmt->bind_param(
                'sisi',
                $data['Unique_ID'],
                $data['Sub_Course_ID'],
                $semester,
                $scheme_id
            );

        } else {

            $marksStmt->bind_param(
                'sis',
                $data['Unique_ID'],
                $data['Sub_Course_ID'],
                $semester
            );
        }

        if (!$marksStmt->execute()) {

            makeStatus($data, "Result Not Found");

            $marksStmt->close();

            continue;
        }

        $temp_subjects = $marksStmt->get_result();

        /*
        |--------------------------------------------------------------------------
        | NO MARKS
        |--------------------------------------------------------------------------
        */

        if (!$temp_subjects || $temp_subjects->num_rows <= 0) {

            makeStatus($data, "Result Not Found");

            $marksStmt->close();

            continue;
        }

        /*
        |--------------------------------------------------------------------------
        | PROCESS MARKS
        |--------------------------------------------------------------------------
        */

        while ($temp_subject = $temp_subjects->fetch_assoc()) {

            $data['unique_id'] =
                $temp_subject['unique_id'];

            $data['series'] =
                $temp_subject['series'];

            $data['exam_session'] =
                trim(
                    $temp_subject['exam_month'] .
                    '-' .
                    $temp_subject['exam_year']
                );

            $data['certification_date'] =
                $temp_subject['certification_date'];

            /*
            |--------------------------------------------------------------------------
            | RESULT PUBLISH DATE
            |--------------------------------------------------------------------------
            */

            if (!empty($temp_subject['Created_At'])) {

                $resultPublishDay =
                    date(
                        "d/m/Y",
                        strtotime($temp_subject['Created_At'])
                    );
            }

            /*
            |--------------------------------------------------------------------------
            | EXTERNAL MARK
            |--------------------------------------------------------------------------
            */

            $obt_marks_ext =
                $temp_subject['obt_marks_ext'];

            /*
            |--------------------------------------------------------------------------
            | INTERNAL MARK
            |--------------------------------------------------------------------------
            */

            $obt_marks_int =
                $temp_subject['obt_marks_int'];

            /*
            |--------------------------------------------------------------------------
            | EMPTY EXTERNAL MARK CACHE
            |--------------------------------------------------------------------------
            */

            $temp_subject['not_empty_obt_ext_marks'] = '';

            if (
                $obt_marks_ext !== null &&
                $obt_marks_ext !== ''
            ) {

                $temp_subject['not_empty_obt_ext_marks'] =
                    $obt_marks_ext;
            }

            /*
            |--------------------------------------------------------------------------
            | TOTAL OBTAINED
            |--------------------------------------------------------------------------
            */

            if (
                $obt_marks_ext === 'AB' ||
                $obt_marks_int === 'AB'
            ) {

                /*
                 * Do not add AB as a numeric mark.
                 */
                $total_obt +=
                    ($obt_marks_ext === 'AB')
                    ? 0
                    : (int)$obt_marks_ext;

                $total_obt +=
                    ($obt_marks_int === 'AB')
                    ? 0
                    : (int)$obt_marks_int;

            } else {

                $total_obt +=
                    (int)$obt_marks_ext +
                    (int)$obt_marks_int;
            }

            /*
            |--------------------------------------------------------------------------
            | DEFAULT STATUS
            |--------------------------------------------------------------------------
            */

            $temp_subject['remarks_status'] = 'Pass';

            /*
            |--------------------------------------------------------------------------
            | UNIVERSITY 48
            |--------------------------------------------------------------------------
            */

            if ((int)$data['University_ID'] === 48) {

                /*
                 * Use current subject's passing requirement.
                 *
                 * The original code compared cumulative $total_obt
                 * against $min_val, which can produce wrong results.
                 */

                $subjectMin = 0;

                if (
                    $temp_subject['Min_Marks'] !== null &&
                    $temp_subject['Min_Marks'] !== ''
                ) {

                    $subjectMin =
                        (float)$temp_subject['Min_Marks'];
                }

                if (
                    $temp_subject['Max_Marks'] == 60 &&
                    $temp_subject['Min_Marks'] == 40
                ) {

                    $subjectMin = 40;

                } elseif (
                    $temp_subject['Max_Marks'] == 100 &&
                    $temp_subject['Min_Marks'] == 40
                ) {

                    $subjectMin = 40;

                } elseif (
                    $temp_subject['Max_Marks'] == 100 &&
                    $temp_subject['Min_Marks'] == 100
                ) {

                    $subjectMin = 40;
                }

                $obtNumeric = 0;

                if ($obt_marks_ext !== 'AB') {
                    $obtNumeric += (int)$obt_marks_ext;
                }

                if ($obt_marks_int !== 'AB') {
                    $obtNumeric += (int)$obt_marks_int;
                }

                if (
                    $obtNumeric < $subjectMin ||
                    $obt_marks_ext === 0 ||
                    $obt_marks_ext === 'AB'
                ) {

                    $temp_subject['remarks_status'] = 'FAIL';
                }

                $total_max +=
                    (int)$temp_subject['Max_Marks'];

                /*
                |--------------------------------------------------------------------------
                | DISPLAY OBTAINED MARK
                |--------------------------------------------------------------------------
                */

                if (
                    $obt_marks_ext === 'AB' &&
                    $obt_marks_int === 'AB'
                ) {

                    $temp_subject['obt_marks'] = 'AB';

                } elseif (
                    $obt_marks_ext === 'AB' &&
                    $obt_marks_int !== 'AB'
                ) {

                    $temp_subject['obt_marks'] =
                        (int)$obt_marks_int;

                } elseif (
                    $obt_marks_ext !== 'AB' &&
                    $obt_marks_int !== 'AB'
                ) {

                    $temp_subject['obt_marks'] =
                        (int)$obt_marks_ext +
                        (int)$obt_marks_int;

                } else {

                    $temp_subject['obt_marks'] =
                        (int)$obt_marks_ext;
                }

            } else {

                /*
                |--------------------------------------------------------------------------
                | UNIVERSITY 47
                |--------------------------------------------------------------------------
                */

                $subjectMin =
                    (int)$temp_subject['Min_Marks'];

                $subjectMax =
                    (int)$temp_subject['Max_Marks'];

                $obtNumeric = 0;

                if ($obt_marks_ext !== 'AB') {
                    $obtNumeric += (int)$obt_marks_ext;
                }

                if ($obt_marks_int !== 'AB') {
                    $obtNumeric += (int)$obt_marks_int;
                }

                /*
                 * External/internal individually need to exist.
                 */

                if (
                    $obtNumeric <= $subjectMin ||
                    $obt_marks_ext === 0 ||
                    $obt_marks_ext === 'AB' ||
                    $obt_marks_int === 0 ||
                    $obt_marks_int === 'AB'
                ) {

                    $temp_subject['remarks_status'] =
                        'FAIL';
                }

                $total_max +=
                    $subjectMin +
                    $subjectMax;

                /*
                |--------------------------------------------------------------------------
                | DISPLAY MARK
                |--------------------------------------------------------------------------
                */

                if (
                    $obt_marks_ext === 'AB' &&
                    $obt_marks_int === 'AB'
                ) {

                    $temp_subject['obt_marks'] = 'AB';

                } elseif (
                    $obt_marks_ext === 'AB'
                ) {

                    $temp_subject['obt_marks'] =
                        (int)$obt_marks_int;

                } elseif (
                    $obt_marks_int === 'AB'
                ) {

                    $temp_subject['obt_marks'] =
                        (int)$obt_marks_ext;

                } else {

                    $temp_subject['obt_marks'] =
                        (int)$obt_marks_ext +
                        (int)$obt_marks_int;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | PRACTICAL
            |--------------------------------------------------------------------------
            */

            if (
                $temp_subject['Paper_Type'] === 'Practical' &&
                (int)$temp_subject['obt_marks_int'] === 0 &&
                (int)$temp_subject['Min_Marks'] === 0
            ) {

                $temp_subject['Min_Marks'] = '-';
                $temp_subject['obt_marks_int'] = '-';
                $temp_subject['remarks_status'] = 'Pass';
            }

            /*
            |--------------------------------------------------------------------------
            | FINAL REMARK
            |--------------------------------------------------------------------------
            */

            $temp_subject['remarks'] =
                $temp_subject['remarks_status'];

            /*
            |--------------------------------------------------------------------------
            | SAVE MARK
            |--------------------------------------------------------------------------
            */

            $data['marks'][] =
                $temp_subject;

            $data['allremarks'][] =
                $temp_subject['remarks_status'];

            $obt_int[] =
                $temp_subject['obt_marks_int'];

            $not_empty_ext[] =
                $temp_subject['not_empty_obt_ext_marks'];
        }

        $marksStmt->close();

        /*
        |--------------------------------------------------------------------------
        | RESULT
        |--------------------------------------------------------------------------
        */

        $percentage = 0;

        if (in_array('FAIL', $data['allremarks'], true)) {

            $data['remarks'] = 'Fail';

        } else {

            $data['remarks'] = 'Pass';

            if ($total_max > 0) {

                $percentage =
                    ($total_obt / $total_max) * 100;
            }
        }

        $percentage =
            round((float)$percentage, 2);

        /*
        |--------------------------------------------------------------------------
        | DIVISION
        |--------------------------------------------------------------------------
        */

        if ($percentage >= 60) {

            $division = 'Ist';

        } elseif ($percentage >= 50) {

            $division = 'IInd';

        } elseif ($percentage >= 40) {

            $division = 'IIIrd';

        } else {

            $division = 'Fail';
        }

        /*
        |--------------------------------------------------------------------------
        | UNIVERSITY 47 SUBJECT VALIDATION
        |--------------------------------------------------------------------------
        */

        if ((int)$data['University_ID'] === 47) {

            /*
             * Cache the total subject lookup because multiple students
             * may use the same course/duration.
             */

            $subjectCountCacheKey =
                $data['University_ID'] .
                '|' .
                $data['Sub_Course_ID'] .
                '|' .
                $data['Duration'];

            if (!isset($subjectCache[$subjectCountCacheKey])) {

                $total_subject =
                    totalUloadedSubjectsFunc(
                        $conn,
                        $data['University_ID'],
                        $data['ID'],
                        $data['Duration'],
                        ''
                    );

                $subjectCache[$subjectCountCacheKey] =
                    is_array($total_subject)
                    ? count($total_subject)
                    : 0;
            }

            $total_subject_count =
                $subjectCache[$subjectCountCacheKey];

            if (
                $total_subject_count !== count($obt_int) ||
                $total_subject_count !== count(
                    array_filter(
                        $not_empty_ext,
                        function ($value) {
                            return $value !== '';
                        }
                    )
                )
            ) {

                makeStatus(
                    $data,
                    'Result Not Found'
                );

                continue;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | RESULT FOUND
        |--------------------------------------------------------------------------
        */

        makeStatus(
            $data,
            'Result Found'
        );

        $data['total_max'] =
            $total_max;

        $data['total_obt'] =
            $total_obt;

        /*
        |--------------------------------------------------------------------------
        | MARKS IN WORD
        |--------------------------------------------------------------------------
        */

        $marksWords =
            ucwords(
                strtolower(
                    numberToWordFunc($total_obt)
                )
            );

        $count =
            count($data['marks']);

        /*
        |--------------------------------------------------------------------------
        | UNIVERSITY / DURATION
        |--------------------------------------------------------------------------
        */

        $hours = '';
        $total_duration = '';

        $durations = '';

        if ((int)$data['University_ID'] === 48) {

            $data['mode_type'] = 'Duration';

            $data['university_name'] =
                'Skill Education Development';

            $total_duration =
                $data['Duration'];

            $data['Durations'] =
                $data['Duration'];

            if ($total_duration == 3) {

                $durations =
                    'Certification Course';

                $hours = 160;

            } elseif ($total_duration == 6) {

                $durations =
                    'Certified Skill Diploma';

                $hours = 320;

            } elseif (
                $data['Duration'] === '11/advance-diploma'
            ) {

                $hours = 960;

                $durations =
                    'Advanced Certification Skill Diploma';

                $data['Durations'] = 11;

            } elseif (
                $data['Duration'] === '11/certified'
            ) {

                $durations =
                    'Certified Skill Diploma';

                $data['Durations'] = 11;

                $hours = 960;

            } elseif ($data['Duration'] === '12') {

                $durations =
                    'Certified Skill Diploma';

                $data['Durations'] = 1;

                $hours = 960;

            } elseif ($data['Duration'] === '24') {

                $durations =
                    'Certified Skill Diploma';

                $data['Durations'] = 2;

                $hours = 960;
            }

        } else {

            $data['university_name'] =
                'Glocal School of Vocational Studies';

            $data['mode_type'] =
                'Semester';

            $durations =
                'B. VOC';
        }

        /*
        |--------------------------------------------------------------------------
        | DURATION TEXT
        |--------------------------------------------------------------------------
        */

        $durationValue =
            isset($data['Durations'])
            ? $data['Durations']
            : $data['Duration'];

        if ($data['program_Type'] === 'Skill Program') {

            $durMonthYear =
                ((int)$durationValue === 1)
                ? ' Month'
                : ' Months';

        } else {

            $durMonthYear =
                ((int)$durationValue === 1)
                ? ' Year'
                : ' Years';
        }

        if ((int)$data['University_ID'] === 48) {

            $data['durMonthYear'] =
                $data['Durations'] .
                $durMonthYear;

        } else {

            $durationInt =
                (int)$data['Duration'];

            $suffix =
                $typoArr[$durationInt] ?? 'th';

            $data['durMonthYear'] =
                $data['Duration'] .
                $suffix;
        }

        /*
        |--------------------------------------------------------------------------
        | PHOTO
        |--------------------------------------------------------------------------
        */

        if (!empty($data['StudentPhoto'])) {

            $data['Photo'] =
                $url . $data['StudentPhoto'];

        } else {

            $data['Photo'] = '';
        }

        /*
        |--------------------------------------------------------------------------
        | UNIVERSITY 47
        |--------------------------------------------------------------------------
        */

        if ((int)$data['University_ID'] === 47) {

            generateUniversity47PDF(
                $data,
                $qrFile,
                $date_of_issue,
                $percentage,
                $division,
                $workDir,
                $generatedFiles
            );

        } else {

            /*
            |--------------------------------------------------------------------------
            | UNIVERSITY 48
            |--------------------------------------------------------------------------
            */

            generateUniversity48PDF(
                $data,
                $qrFile,
                $date_of_issue,
                $percentage,
                $division,
                $workDir,
                $generatedFiles
            );
        }

    } catch (Throwable $e) {

        /*
        |--------------------------------------------------------------------------
        | DO NOT STOP ENTIRE BULK REQUEST
        |--------------------------------------------------------------------------
        */

        makeStatus(
            $data,
            'Error: ' . $e->getMessage()
        );

        continue;
    }
}

/*
|--------------------------------------------------------------------------
| CREATE XLSX
|--------------------------------------------------------------------------
*/

$xlsxFilename =
    'marksheet_download_status_' .
    date('Ymd_His') .
    '_' .
    $requestId .
    '.xlsx';

$xlsxPath =
    $workDir . $xlsxFilename;

SimpleXLSXGen::fromArray(
    $export_data
)->saveAs($xlsxPath);

$generatedFiles[] =
    $xlsxPath;

/*
|--------------------------------------------------------------------------
| CREATE ZIP
|--------------------------------------------------------------------------
*/

$zipFile =
    $pdf_dir .
    'Marksheets_' .
    $requestId .
    '.zip';

$zip = new ZipArchive();

if (
    $zip->open(
        $zipFile,
        ZipArchive::CREATE |
        ZipArchive::OVERWRITE
    ) === true
) {

    foreach ($generatedFiles as $file) {

        if (is_file($file)) {

            $zip->addFile(
                $file,
                basename($file)
            );
        }
    }

    $zip->close();

    if (!is_file($zipFile)) {

        cleanupDirectory($workDir);

        echo json_encode([
            'status' => 'error',
            'message' => 'ZIP file was not created.'
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | SEND ZIP
    |--------------------------------------------------------------------------
    */

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: application/zip');

    header(
        'Content-Disposition: attachment; filename="' .
        basename($zipFile) .
        '"'
    );

    header(
        'Content-Length: ' .
        filesize($zipFile)
    );

    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');

    readfile($zipFile);

    /*
    |--------------------------------------------------------------------------
    | CLEANUP
    |--------------------------------------------------------------------------
    */

    cleanupDirectory($workDir);

    if (is_file($zipFile)) {
        unlink($zipFile);
    }

    exit;

} else {

    cleanupDirectory($workDir);

    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to create ZIP file.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| UNIVERSITY 48 PDF
|--------------------------------------------------------------------------
*/

function generateUniversity48PDF(
    $data,
    $qrFile,
    $date_of_issue,
    $percentage,
    $division,
    $workDir,
    &$generatedFiles
) {

    $pdf =
        new Fpdi();

    $templateFile =
        'finalMarksheet.pdf';

    $pageCount =
        $pdf->setSourceFile($templateFile);

    $tplIdx =
        $pdf->importPage(1);

    $pdf->addPage();

    $pdf->useTemplate(
        $tplIdx,
        0,
        0,
        210,
        297
    );

    /*
    |--------------------------------------------------------------------------
    | HEADER
    |--------------------------------------------------------------------------
    */

    $pdf->SetFont(
        'times',
        'B',
        10
    );

    $pdf->SetXY(
        174,
        10
    );

    $pdf->Cell(
        107,
        10,
        strtoupper($data['series'] ?? ''),
        0,
        0,
        'L',
        0
    );

    $pdf->SetXY(
        56,
        56
    );

    $pdf->Cell(
        107,
        10,
        strtoupper($data['exam_session'] ?? ''),
        0,
        0,
        'L',
        0
    );

    $fullName =
        trim(
            strtoupper(
                ($data['First_Name'] ?? '') .
                ' ' .
                ($data['Middle_Name'] ?? '') .
                ' ' .
                ($data['Last_Name'] ?? '')
            )
        );

    $pdf->SetXY(
        43,
        48
    );

    $pdf->Cell(
        107,
        10,
        $fullName,
        0,
        0,
        'L',
        0
    );

    $pdf->SetXY(
        156,
        56
    );

    $pdf->Cell(
        70,
        10.5,
        $data['Enrollment_No'] ?? '',
        0,
        0,
        'L',
        0
    );

    /*
    |--------------------------------------------------------------------------
    | UNIQUE ID
    |--------------------------------------------------------------------------
    */

    $pdf->SetXY(
        155.5,
        56.3
    );

    $pdf->Cell(
        70,
        10.5,
        $data['Unique_ID'] ?? '',
        0,
        0,
        'L',
        0
    );

    /*
    |--------------------------------------------------------------------------
    | DURATION
    |--------------------------------------------------------------------------
    */

    $pdf->SetXY(
        108,
        56
    );

    $pdf->Cell(
        70,
        10.5,
        $data['durMonthYear'] ?? '',
        0,
        0,
        'L',
        0
    );

    /*
    |--------------------------------------------------------------------------
    | COURSE
    |--------------------------------------------------------------------------
    */

    $pdf->SetXY(
        43,
        63
    );

    $pdf->Cell(
        70,
        10,
        strtoupper($data['course'] ?? ''),
        0,
        0,
        'L',
        0
    );

    /*
    |--------------------------------------------------------------------------
    | CENTER
    |--------------------------------------------------------------------------
    */

    $pdf->SetXY(
        43,
        71.5
    );

    $pdf->Cell(
        70,
        10,
        strtoupper($data['CenterName'] ?? ''),
        0,
        0,
        'L',
        0
    );

    /*
    |--------------------------------------------------------------------------
    | DIVISION
    |--------------------------------------------------------------------------
    */

    $pdf->SetFont(
        'times',
        'B',
        14
    );

    $pdf->SetXY(
        178,
        212
    );

    $pdf->Cell(
        70,
        10,
        ucwords($division),
        0,
        0,
        'L',
        0
    );

    /*
    |--------------------------------------------------------------------------
    | TABLE HEADER
    |--------------------------------------------------------------------------
    */

    $pdf->SetFont(
        'times',
        'B',
        10
    );

    $cellWidth = 30;

    $cellHeight = 10;

    $pdf->SetXY(
        16.1,
        83
    );

    $pdf->MultiCell(
        27,
        10,
        'Subject Code',
        'TLB',
        'C'
    );

    $pdf->SetXY(
        43,
        83
    );

    $pdf->MultiCell(
        70.3,
        10,
        'Subject Name ',
        'TLB',
        'C'
    );

    $pdf->SetXY(
        113.3,
        83
    );

    $pdf->MultiCell(
        18.3,
        5,
        'Obtained Marks',
        'TLB',
        'C'
    );

    $pdf->SetXY(
        131,
        83
    );

    $pdf->MultiCell(
        20,
        5,
        'Min. Marks',
        'TLB',
        'C'
    );

    $pdf->SetXY(
        151,
        83
    );

    $pdf->MultiCell(
        19.8,
        5,
        'Max. Marks',
        'TLB',
        'C'
    );

    $pdf->SetXY(
        171,
        83
    );

    $pdf->MultiCell(
        22,
        10,
        'Remarks',
        1,
        'C'
    );

    /*
    |--------------------------------------------------------------------------
    | MARKS
    |--------------------------------------------------------------------------
    */

    $pdf->SetFont(
        'times',
        '',
        10
    );

    $x_cor = 16;

    foreach ($data['marks'] as $mark) {

        $pdf->SetX($x_cor);

        $subjectName =
            cleanPdfText(
                $mark['subject_name'] ?? ''
            );

        if (strlen($subjectName) > 30) {

            $cellHeight = 20;

        } else {

            $cellHeight = 10;
        }

        if (strlen($subjectName) > 30) {

            $pdf->Cell(
                27,
                $cellHeight - 10,
                $mark['Code'] ?? '',
                'LB',
                0,
                'L'
            );

            $nameParts =
                explode(
                    "\n",
                    wordwrap(
                        trim($subjectName),
                        40
                    )
                );

            $line1 =
                $nameParts[0] ?? '';

            $line2 =
                $nameParts[1] ?? '';
                
                

            $pdf->MultiCell(
                70.3,
                5,
                $line1 .
                chr(25) .
                $line2,
                'LB',
                'L',
                0
            );

            $x =
                $pdf->GetX();

            $y =
                $pdf->GetY();

            $pdf->SetXY(
                $x + 103.3,
                $y - 10
            );

            $pdf->Cell(
                $cellWidth - 12.3,
                $cellHeight - 10,
                $mark['obt_marks'] ?? '',
                'LB',
                0,
                'C'
            );

            $pdf->Cell(
                $cellWidth - 10,
                $cellHeight - 10,
                $mark['Min_Marks'] ?? '',
                'LB',
                0,
                'C'
            );

            $pdf->Cell(
                $cellWidth - 10,
                $cellHeight - 10,
                $mark['Max_Marks'] ?? '',
                'LB',
                0,
                'C'
            );

            $pdf->Cell(
                22,
                $cellHeight - 10,
                $mark['remarks'] ?? '',
                'LBR',
                0,
                'C'
            );

        } else {

            $pdf->Cell(
                27,
                $cellHeight,
                $mark['Code'] ?? '',
                'LB',
                0,
                'L'
            );

            $pdf->Cell(
                70.3,
                $cellHeight,
                trim($subjectName),
                'LB',
                0,
                'L'
            );

            $pdf->Cell(
                $cellWidth - 2.3,
                $cellHeight,
                $mark['obt_marks'] ?? '',
                'LB',
                0,
                'C'
            );

            $pdf->Cell(
                $cellWidth,
                $cellHeight,
                $mark['Min_Marks'] ?? '',
                'LB',
                0,
                'C'
            );

            $pdf->Cell(
                $cellWidth,
                $cellHeight,
                $mark['Max_Marks'] ?? '',
                'LB',
                0,
                'C'
            );

            $pdf->Cell(
                22,
                $cellHeight,
                $mark['remarks'] ?? '',
                'LBR',
                0,
                'C'
            );
        }

        $pdf->Ln();
    }

    /*
    |--------------------------------------------------------------------------
    | QR
    |--------------------------------------------------------------------------
    */

    if (is_file($qrFile)) {

        $pdf->Image(
            $qrFile,
            167,
            232.2,
            28,
            23,
            'PNG'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TOTAL
    |--------------------------------------------------------------------------
    */

    $pdf->SetX($x_cor);

    $pdf->Cell(
        27,
        $cellHeight,
        '',
        'LB',
        0,
        'L'
    );

    $pdf->Cell(
        70.3,
        $cellHeight,
        'Total Marks',
        'B',
        0,
        'C'
    );

    $pdf->Cell(
        $cellWidth - 12.3,
        $cellHeight,
        $data['total_obt'],
        'LB',
        0,
        'C'
    );

    $pdf->Cell(
        $cellWidth - 10,
        $cellHeight,
        '',
        'LB',
        0,
        'C'
    );

    $pdf->Cell(
        $cellWidth - 10,
        $cellHeight,
        $data['total_max'],
        'LB',
        0,
        'C'
    );

    $pdf->Cell(
        22,
        $cellHeight,
        $data['remarks'],
        'LBR',
        0,
        'C'
    );

    $pdf->Ln();

    /*
    |--------------------------------------------------------------------------
    | ISSUE DATE
    |--------------------------------------------------------------------------
    */

    if (
        !empty($data['certification_date'])
    ) {

        $timestamp =
            strtotime(
                $data['certification_date']
            );

        if ($timestamp !== false) {

            $day =
                date('d', $timestamp);

            $month =
                date('m', $timestamp);

            $year =
                date('Y', $timestamp);

            $pdf->SetXY(
                28,
                241
            );

            $pdf->Cell(
                0,
                9.1,
                $day,
                0,
                1,
                'L',
                0
            );

            $pdf->SetXY(
                36,
                241
            );

            $pdf->Cell(
                0,
                9.1,
                $month,
                0,
                1,
                'L',
                0
            );

            $pdf->SetXY(
                45,
                241
            );

            $pdf->Cell(
                0,
                9.1,
                $year,
                0,
                1,
                'L',
                0
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SAVE PDF
    |--------------------------------------------------------------------------
    */

    $filename =
        sanitizeFilename(
            ($data['Unique_ID'] ?? $data['ID']) .
            '_' .
            time() .
            '_' .
            bin2hex(random_bytes(2)) .
            '.pdf'
        );

    $pdfPath =
        $workDir . $filename;

    $pdf->Output(
        $pdfPath,
        'F'
    );

    $generatedFiles[] =
        $pdfPath;
}


/*
|--------------------------------------------------------------------------
| UNIVERSITY 47 PDF
|--------------------------------------------------------------------------
*/

function generateUniversity47PDF(
    $data,
    $qrFile,
    $date_of_issue,
    $percentage,
    $division,
    $workDir,
    &$generatedFiles
) {

    $pdf =
        new Fpdi();

    $templateFile =
        'finalMarksheet.pdf';

    $pageCount =
        $pdf->setSourceFile($templateFile);

    $tplIdx =
        $pdf->importPage(1);

    $pdf->addPage();

    $pdf->useTemplate(
        $tplIdx,
        0,
        0,
        210,
        297
    );

    /*
    |--------------------------------------------------------------------------
    | GRADE OR PERCENTAGE MARKSHEET
    |--------------------------------------------------------------------------
    */

    /*
     * Calculate grade information.
     */

    $total_obt_grade_value = 0;
    $total_credit = 0;

    foreach (
        $data['marks']
        as $key => $value
    ) {

        $obtMarks =
            $value['obt_marks'] ?? 0;

        $credit =
            (int)($value['Credit'] ?? 0);

        if (
            ($value['remarks_status'] ?? '') ===
            'FAIL'
        ) {

            $data['marks'][$key]['grade'] =
                ($obtMarks === 'AB')
                ? 'S'
                : 'F';

            $data['marks'][$key]['grade_value'] =
                0;

        } else {

            $grandTotal =
                (int)$value['Min_Marks'] +
                (int)$value['Max_Marks'];

            $student_obt_mark =
                is_numeric($obtMarks)
                ? (float)$obtMarks
                : 0;

            if ($grandTotal > 0) {

                $student_obt_per =
                    round(
                        (
                            $student_obt_mark /
                            $grandTotal
                        ) * 100,
                        2
                    );

            } else {

                $student_obt_per = 0;
            }

            if ($student_obt_per > 90) {

                $grade = 'O';
                $gradeValue = 10;

            } elseif ($student_obt_per > 80) {

                $grade = 'A+';
                $gradeValue = 9;

            } elseif ($student_obt_per > 70) {

                $grade = 'A';
                $gradeValue = 8;

            } elseif ($student_obt_per > 60) {

                $grade = 'B+';
                $gradeValue = 7;

            } elseif ($student_obt_per > 55) {

                $grade = 'B';
                $gradeValue = 6;

            } elseif ($student_obt_per > 50) {

                $grade = 'C';
                $gradeValue = 5;

            } elseif ($student_obt_per >= 40) {

                $grade = 'P';
                $gradeValue = 4;

            } else {

                $grade = 'F';
                $gradeValue = 0;
            }

            $data['marks'][$key]['grade'] =
                $grade;

            $data['marks'][$key]['grade_value'] =
                $gradeValue;
        }

        $total_obt_grade_value +=
            (
                (int)$data['marks'][$key]['grade_value']
                * $credit
            );

        $total_credit +=
            $credit;
    }

    /*
    |--------------------------------------------------------------------------
    | SGPA
    |--------------------------------------------------------------------------
    */

    if ($total_credit > 0) {

        $data['SGPA'] =
            number_format(
                $total_obt_grade_value /
                $total_credit,
                2
            );

    } else {

        $data['SGPA'] = '0.00';
    }

    /*
    |--------------------------------------------------------------------------
    | TOTAL COURSE DURATION
    |--------------------------------------------------------------------------
    */

    $decoded_duration =
        json_decode(
            $data['total_duration'],
            true
        );

    if (is_array($decoded_duration)) {

        $total_course_dur =
            $decoded_duration[0] ?? null;

    } else {

        $total_course_dur =
            $decoded_duration;
    }

    $checkLastSem =
        ((string)$total_course_dur ===
         (string)$data['Duration']);

    /*
    |--------------------------------------------------------------------------
    | CGPA
    |--------------------------------------------------------------------------
    */

    $sgpa_record = [];

    if ($checkLastSem) {

        $sgpa_record =
            calculateCGPAOptimized(
                $data['Enrollment_No'],
                $data['Sub_Course_ID'],
                $data['Duration'],
                $_POST['semester'] ?? ''
            );

        $sgpa_record[] = [
            'semester' =>
                $data['Duration'],

            'sgpa' =>
                $data['SGPA'],

            'total_credit' =>
                $total_credit
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | HEADER
    |--------------------------------------------------------------------------
    */

    if (isset($_REQUEST['marksheet_in_grade'])) {

        setHeaderGrade(
            $data,
            $data['exam_session'] ?? ''
        );

        $cellHeight = 10;

        $pdf->SetXY(
            16,
            99
        );

        $pdf->SetFont(
            'times',
            'B',
            12
        );

        $pdf->MultiCell(
            35,
            $cellHeight,
            'Course Code',
            'TLB',
            'C'
        );

        $pdf->SetXY(
            51,
            99
        );

        $pdf->MultiCell(
            102,
            $cellHeight,
            'Course Title ',
            'TLB',
            'C'
        );

        $pdf->SetXY(
            153,
            99
        );

        $pdf->MultiCell(
            20,
            $cellHeight,
            'Credit',
            'TLB',
            'C'
        );

        $pdf->SetXY(
            173,
            99
        );

        $pdf->MultiCell(
            20,
            $cellHeight,
            'Grade',
            'TLRB',
            'C'
        );

        $pdf->SetFont(
            'times',
            '',
            12
        );

        makeGradeMarksheet(
            $data['marks'],
            $data['SGPA'],
            16,
            $sgpa_record,
            $data['remarks']
        );

    } else {

        setHeaderPercentage(
            $data,
            $data['exam_session'] ?? ''
        );

        /*
        |--------------------------------------------------------------------------
        | TABLE HEADER
        |--------------------------------------------------------------------------
        */

        $pdf->SetFont(
            'times',
            'B',
            10
        );

        $pdf->SetXY(
            16.1,
            115
        );

        $pdf->MultiCell(
            25,
            10,
            'Subject Code',
            'TL',
            'C'
        );

        $pdf->SetXY(
            41,
            115
        );

        $pdf->MultiCell(
            76,
            10,
            'Subject Name ',
            'TL',
            'C'
        );

        $pdf->SetXY(
            117,
            115
        );

        $pdf->MultiCell(
            25,
            10,
            'Internal',
            'TL',
            'C'
        );

        $pdf->SetXY(
            142,
            115
        );

        $pdf->MultiCell(
            25,
            10,
            'External',
            'TL',
            'C'
        );

        $pdf->SetXY(
            167,
            115
        );

        $pdf->MultiCell(
            26,
            10,
            'Total',
            'TLR',
            'C'
        );

        /*
        |--------------------------------------------------------------------------
        | SECOND HEADER
        |--------------------------------------------------------------------------
        */

        $pdf->SetXY(
            16.1,
            125
        );

        $pdf->MultiCell(
            25,
            10,
            '',
            'LB',
            'C'
        );

        $pdf->SetXY(
            41,
            125
        );

        $pdf->MultiCell(
            76,
            10,
            ' ',
            'LB',
            'C'
        );

        $pdf->SetXY(
            117,
            125
        );

        $pdf->MultiCell(
            12.6,
            10,
            'Obt',
            'TBL',
            'C'
        );

        $pdf->SetXY(
            129.8,
            125
        );

        $pdf->MultiCell(
            12,
            10,
            'Max',
            'TBL',
            'C'
        );

        $pdf->SetXY(
            142,
            125
        );

        $pdf->MultiCell(
            12.5,
            10,
            'Obt',
            'TBL',
            'C'
        );

        $pdf->SetXY(
            154.8,
            125
        );

        $pdf->MultiCell(
            12,
            10,
            'Max',
            'TBL',
            'C'
        );

        $pdf->SetXY(
            167,
            125
        );

        $pdf->MultiCell(
            14,
            10,
            'Obt',
            'TBL',
            'C'
        );

        $pdf->SetXY(
            181,
            125
        );

        $pdf->MultiCell(
            12,
            10,
            'Max',
            'TLBR',
            'C'
        );

        $pdf->SetFont(
            'times',
            '',
            10
        );

        makePercentageMarksheet(
            $data['marks'],
            16
        );

        /*
        |--------------------------------------------------------------------------
        | FOOTER
        |--------------------------------------------------------------------------
        */

        $pdf->SetXY(
            16,
            230.4
        );

        $pdf->SetFont(
            'times',
            'B',
            10
        );

        $pdf->Cell(
            0,
            0,
            'Aggregate Marks',
            0,
            0,
            'C',
            0
        );

        $pdf->SetXY(
            16,
            233.4
        );

        $pdf->Cell(
            65,
            8,
            'Marks',
            'TL',
            1,
            'C',
            0
        );

        $pdf->SetXY(
            81,
            233.4
        );

        $pdf->Cell(
            35,
            8,
            'Grand Total',
            'TL',
            1,
            'C',
            0
        );

        $pdf->SetXY(
            116,
            233.4
        );

        $pdf->Cell(
            35,
            8,
            'Result',
            'LT',
            1,
            'C',
            0
        );

        $pdf->SetXY(
            151,
            233.4
        );

        $pdf->Cell(
            42,
            8,
            'Percentage',
            'LTR',
            1,
            'C',
            0
        );

        $pdf->SetFont(
            'times',
            '',
            10
        );

        $pdf->SetXY(
            16,
            240.4
        );

        $pdf->Cell(
            65,
            8,
            ' Obtained Mark',
            'TL',
            1,
            'C',
            0
        );

        $pdf->SetXY(
            81,
            240.4
        );

        $pdf->Cell(
            35,
            8,
            $data['total_obt'],
            'TL',
            1,
            'C',
            0
        );

        $pdf->SetXY(
            116,
            240.4
        );

        $pdf->Cell(
            35,
            8,
            $data['remarks'],
            'TLR',
            1,
            'C',
            0
        );

        $pdf->SetXY(
            151,
            240.4
        );

        $pdf->Cell(
            42,
            8,
            $percentage,
            'TR',
            1,
            'C',
            0
        );

        $pdf->SetXY(
            16,
            247.3
        );

        $pdf->Cell(
            65,
            8,
            'Maximum Mark',
            'TLB',
            1,
            'C',
            0
        );

        $pdf->SetXY(
            81,
            247.3
        );

        $pdf->Cell(
            35,
            8,
            $data['total_max'],
            'LBT',
            1,
            'C',
            0
        );

        $pdf->SetXY(
            116,
            247.3
        );

        $pdf->Cell(
            35,
            8,
            '',
            'LRB',
            1,
            'C',
            0
        );

        $pdf->SetXY(
            151,
            247.3
        );

        $pdf->Cell(
            42,
            8,
            '',
            'RB',
            1,
            'C',
            0
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DATE
    |--------------------------------------------------------------------------
    */

    $pdf->SetXY(
        39,
        260
    );

    $pdf->Cell(
        0,
        9.1,
        $date_of_issue,
        0,
        1,
        'L',
        0
    );

    /*
    |--------------------------------------------------------------------------
    | SAVE
    |--------------------------------------------------------------------------
    */

    $filename =
        sanitizeFilename(
            ($data['Unique_ID'] ?? $data['ID']) .
            '_' .
            time() .
            '_' .
            bin2hex(random_bytes(2)) .
            '.pdf'
        );

    $pdfPath =
        $workDir . $filename;

    $pdf->Output(
        $pdfPath,
        'F'
    );

    $generatedFiles[] =
        $pdfPath;
}


/*
|--------------------------------------------------------------------------
| GRADE MARKSHEET
|--------------------------------------------------------------------------
*/

function makeGradeMarksheet(
    $marks_arr,
    $sgpa,
    $x_cor,
    $sgpa_record,
    $remark
) {

    global $pdf;

    $cellHeight = 10;

    foreach ($marks_arr as $mark) {

        $pdf->SetX(
            $x_cor
        );

        $subjectName =
            cleanPdfText(
                $mark['subject_name'] ?? ''
            );

        if (strlen($subjectName) > 50) {

            $pdf->Cell(
                35,
                $cellHeight,
                $mark['Code'] ?? '',
                'LB',
                0,
                'C'
            );

            $nameParts =
                explode(
                    "\n",
                    wordwrap(
                        $subjectName,
                        50
                    )
                );

            $line1 =
                $nameParts[0] ?? '';

            $line2 =
                $nameParts[1] ?? '';

            $pdf->MultiCell(
                102,
                5,
                ' ' .
                $line1 .
                chr(10) .
                ' ' .
                $line2,
                'BL',
                'L',
                0
            );

            $y =
                $pdf->GetY();

            $pdf->SetXY(
                153,
                $y - 10
            );

            $pdf->Cell(
                20,
                $cellHeight,
                $mark['Credit'] ?? '',
                'LB',
                0,
                'C'
            );

            $pdf->Cell(
                20,
                $cellHeight,
                $mark['grade'] ?? '',
                'LBR',
                0,
                'C'
            );

        } else {

            $pdf->Cell(
                35,
                $cellHeight,
                $mark['Code'] ?? '',
                'LB',
                0,
                'C'
            );

            $pdf->Cell(
                102,
                $cellHeight,
                ' ' . $subjectName,
                'LB',
                0,
                'L'
            );

            $pdf->Cell(
                20,
                $cellHeight,
                $mark['Credit'] ?? '',
                'LB',
                0,
                'C'
            );

            $pdf->Cell(
                20,
                $cellHeight,
                $mark['grade'] ?? '',
                'LBR',
                0,
                'C'
            );
        }

        $pdf->Ln();
    }

    /*
    |--------------------------------------------------------------------------
    | SGPA
    |--------------------------------------------------------------------------
    */

    $pdf->SetFont(
        'times',
        'B',
        12
    );

    $y_cor =
        $pdf->GetY();

    $pdf->SetXY(
        153,
        $y_cor
    );

    $pdf->Cell(
        20,
        $cellHeight,
        'SGPA:',
        'LB',
        0,
        'C'
    );

    $pdf->Cell(
        20,
        $cellHeight,
        $sgpa,
        'LBR',
        0,
        'C'
    );

    /*
    |--------------------------------------------------------------------------
    | CGPA
    |--------------------------------------------------------------------------
    */

    if (!empty($sgpa_record)) {

        $pdf->SetFont(
            'times',
            '',
            12
        );

        $y_cor =
            $pdf->GetY();

        $y_cor += 20;

        $pdf->SetXY(
            16,
            $y_cor
        );

        $a = 1;

        $total_sgpa_credit = 0;
        $grand_total_credit = 0;

        foreach (
            $sgpa_record
            as $sgpa_details
        ) {

            $semester =
                $sgpa_details['semester'];

            $semesterSgpa =
                (float)$sgpa_details['sgpa'];

            $semesterCredit =
                (int)$sgpa_details['total_credit'];

            $total_sgpa_credit +=
                $semesterSgpa *
                $semesterCredit;

            $grand_total_credit +=
                $semesterCredit;

            $sgpaTotal =
                number_format(
                    $semesterSgpa,
                    2,
                    '.',
                    ''
                );

            $content =
                ' SGPA of Semester ' .
                intToRoman((int)$semester) .
                ':';

            $width =
                $pdf->GetStringWidth(
                    $content
                );

            $pdf->Cell(
                $width,
                $cellHeight,
                $content,
                'LBT',
                0,
                'L'
            );

            $pdf->SetFont(
                'times',
                'B',
                12
            );

            $newWidth =
                59 - $width;

            $pdf->Cell(
                $newWidth,
                $cellHeight,
                ' ' . $sgpaTotal,
                'BTR',
                0,
                'L'
            );

            $pdf->SetFont(
                'times',
                '',
                12
            );

            if ($a == 3) {

                $pdf->Ln();

                $pdf->SetX(
                    16
                );

                $a = 0;
            }

            $a++;
        }

        /*
        |--------------------------------------------------------------------------
        | RESULT
        |--------------------------------------------------------------------------
        */

        $pdf->SetFont(
            'times',
            'B',
            12
        );

        $pdf->SetX(
            17
        );

        $pdf->Cell(
            30,
            $cellHeight,
            'RESULT: ' .
            strtoupper($remark),
            '',
            0,
            'C'
        );

        /*
        |--------------------------------------------------------------------------
        | CGPA
        |--------------------------------------------------------------------------
        */

        if ($grand_total_credit > 0) {

            $cgpa =
                round(
                    $total_sgpa_credit /
                    $grand_total_credit,
                    2
                );

        } else {

            $cgpa = '0.00';
        }

        $pdf->SetX(
            134
        );

        $pdf->Cell(
            39,
            $cellHeight,
            'CGPA: ',
            'LBRT',
            0,
            'R'
        );

        $pdf->Cell(
            20,
            $cellHeight,
            $cgpa,
            'LBRT',
            0,
            'C'
        );
    }
}


/*
|--------------------------------------------------------------------------
| ROMAN NUMBER
|--------------------------------------------------------------------------
*/

function intToRoman($num)
{

    $num = (int)$num;

    if ($num <= 0) {
        return '';
    }

    $mapping = [
        1000 => 'M',
        900 => 'CM',
        500 => 'D',
        400 => 'CD',
        100 => 'C',
        90 => 'XC',
        50 => 'L',
        40 => 'XL',
        10 => 'X',
        9 => 'IX',
        5 => 'V',
        4 => 'IV',
        1 => 'I'
    ];

    $result = '';

    foreach (
        $mapping
        as $value => $roman
    ) {

        while ($num >= $value) {

            $result .= $roman;

            $num -= $value;
        }
    }

    return $result;
}


/*
|--------------------------------------------------------------------------
| PERCENTAGE MARKSHEET
|--------------------------------------------------------------------------
*/

function makePercentageMarksheet(
    $marks_arr,
    $x_cor
) {

    global $pdf;

    foreach ($marks_arr as $mark) {

        $pdf->SetX(
            $x_cor
        );

        $subjectName =
            cleanPdfText(
                $mark['subject_name'] ?? ''
            );

        $cellHeight =
            strlen($subjectName) > 30
            ? 20
            : 10;

        if (strlen($subjectName) > 30) {

            $pdf->SetFont(
                'times',
                '',
                11
            );

            $pdf->Cell(
                25,
                $cellHeight - 10,
                $mark['Code'] ?? '',
                'BL',
                0,
                'L'
            );

            $nameParts =
                explode(
                    "\n",
                    wordwrap(
                        $subjectName,
                        30
                    )
                );

            $line1 =
                $nameParts[0] ?? '';

            $line2 =
                $nameParts[1] ?? '';

            $pdf->MultiCell(
                76,
                5,
                $line1 .
                chr(10) .
                $line2,
                'BL',
                'L',
                0
            );

            $x =
                $pdf->GetX();

            $y =
                $pdf->GetY();

            $pdf->SetXY(
                $x + 107,
                $y - 10
            );

            $pdf->Cell(
                12.8,
                10,
                $mark['obt_marks_int'] ?? '',
                'LB',
                0,
                'C'
            );

            $pdf->Cell(
                12.2,
                10,
                $mark['Min_Marks'] ?? '',
                'LB',
                0,
                'C'
            );

            $pdf->Cell(
                12.8,
                10,
                $mark['obt_marks_ext'] ?? '',
                'BL',
                0,
                'C'
            );

            $pdf->Cell(
                12.2,
                10,
                $mark['Max_Marks'] ?? '',
                'BL',
                0,
                'C'
            );

            $pdf->Cell(
                14,
                10,
                $mark['obt_marks'] ?? '',
                'BL',
                0,
                'C'
            );

            $pdf->Cell(
                12,
                10,
                (
                    (int)$mark['Min_Marks'] +
                    (int)$mark['Max_Marks']
                ),
                'BLR',
                0,
                'C'
            );

        } else {

            $pdf->Cell(
                25,
                $cellHeight,
                $mark['Code'] ?? '',
                'LB',
                0,
                'L'
            );

            $pdf->Cell(
                76,
                $cellHeight,
                $subjectName,
                'LB',
                0,
                'L'
            );

            $pdf->Cell(
                12.8,
                10,
                $mark['obt_marks_int'] ?? '',
                'LB',
                0,
                'C'
            );

            $pdf->Cell(
                12.2,
                10,
                $mark['Min_Marks'] ?? '',
                'LB',
                0,
                'C'
            );

            $pdf->Cell(
                12.8,
                10,
                $mark['obt_marks_ext'] ?? '',
                'BL',
                0,
                'C'
            );

            $pdf->Cell(
                12.2,
                10,
                $mark['Max_Marks'] ?? '',
                'BL',
                0,
                'C'
            );

            $pdf->Cell(
                14,
                10,
                $mark['obt_marks'] ?? '',
                'BL',
                0,
                'C'
            );

            $pdf->Cell(
                12,
                10,
                (
                    (int)$mark['Min_Marks'] +
                    (int)$mark['Max_Marks']
                ),
                'BLR',
                0,
                'C'
            );
        }

        $pdf->Ln();
    }
}


/*
|--------------------------------------------------------------------------
| STATUS
|--------------------------------------------------------------------------
*/

function makeStatus(
    $student_data,
    $message
) {

    global $export_data;

    if (
        (string)$student_data['University_ID'] ===
        '48'
    ) {

        $export_data[] = [
            $student_data['Enrollment_No'],
            $student_data['program_Type'],
            $student_data['course'],
            $student_data['Duration'],
            $message
        ];

    } else {

        $semester = '';

        if (!empty($_POST['semester'])) {

            $parts =
                explode(
                    '|',
                    $_POST['semester']
                );

            $semester =
                $parts[1] ?? '';
        }

        $export_data[] = [
            $student_data['Enrollment_No'],
            $student_data['program_Type'],
            $student_data['course'],
            $semester,
            $message
        ];
    }
}


/*
|--------------------------------------------------------------------------
| OPTIMIZED CGPA
|--------------------------------------------------------------------------
|
| OLD:
|
|   while semester
|       SELECT marksheets...
|
| This could mean 5-6 queries per student.
|
| NEW:
|
|   One query gets ALL previous semesters.
|
|--------------------------------------------------------------------------
*/

function calculateCGPAOptimized(
    $enrol,
    $sub_course_id,
    $total_sem_dur,
    $semesterRequest
) {

    global $conn;

    $sgpa_record = [];

    /*
    |--------------------------------------------------------------------------
    | SCHEME
    |--------------------------------------------------------------------------
    */

    $scheme_id = null;

    if (!empty($semesterRequest)) {

        $parts =
            explode(
                '|',
                $semesterRequest
            );

        if (count($parts) === 2) {

            $scheme_id =
                (int)$parts[0];
        }
    }

    $maxSemester =
        max(
            1,
            ((int)$total_sem_dur - 1)
        );

    /*
    |--------------------------------------------------------------------------
    | BUILD QUERY
    |--------------------------------------------------------------------------
    */

    if ($scheme_id !== null) {

        $sql = "
            SELECT
                Syllabi.Semester,
                Syllabi.Min_Marks,
                Syllabi.Max_Marks,
                Syllabi.Credit,

                marksheets.obt_marks_ext,
                marksheets.obt_marks_int

            FROM marksheets

            LEFT JOIN Syllabi
                ON marksheets.subject_id = Syllabi.ID

            WHERE marksheets.enrollment_no = ?
            AND marksheets.Sub_Course_ID = ?

            AND Syllabi.Semester >= 1
            AND Syllabi.Semester < ?
            AND Syllabi.Scheme_ID = ?

            ORDER BY
                Syllabi.Semester ASC,
                Syllabi.Code ASC
        ";

        $stmt =
            $conn->prepare($sql);

        if (!$stmt) {
            return [];
        }

        $stmt->bind_param(
            'siii',
            $enrol,
            $sub_course_id,
            $total_sem_dur,
            $scheme_id
        );

    } else {

        $sql = "
            SELECT
                Syllabi.Semester,
                Syllabi.Min_Marks,
                Syllabi.Max_Marks,
                Syllabi.Credit,

                marksheets.obt_marks_ext,
                marksheets.obt_marks_int

            FROM marksheets

            LEFT JOIN Syllabi
                ON marksheets.subject_id = Syllabi.ID

            WHERE marksheets.enrollment_no = ?
            AND marksheets.Sub_Course_ID = ?

            AND Syllabi.Semester >= 1
            AND Syllabi.Semester < ?

            ORDER BY
                Syllabi.Semester ASC,
                Syllabi.Code ASC
        ";

        $stmt =
            $conn->prepare($sql);

        if (!$stmt) {
            return [];
        }

        $stmt->bind_param(
            'sii',
            $enrol,
            $sub_course_id,
            $total_sem_dur
        );
    }

    if (!$stmt->execute()) {

        $stmt->close();

        return [];
    }

    $result =
        $stmt->get_result();

    /*
    |--------------------------------------------------------------------------
    | GROUP BY SEMESTER
    |--------------------------------------------------------------------------
    */

    $semesterSubjects = [];

    while (
        $row =
        $result->fetch_assoc()
    ) {

        $semesterNo =
            (int)$row['Semester'];

        if (!isset(
            $semesterSubjects[$semesterNo]
        )) {

            $semesterSubjects[$semesterNo] =
                [];
        }

        $semesterSubjects[$semesterNo][] =
            $row;
    }

    $stmt->close();

    /*
    |--------------------------------------------------------------------------
    | CALCULATE SGPA
    |--------------------------------------------------------------------------
    */

    foreach (
        $semesterSubjects
        as $semesterNo => $subjects
    ) {

        list(
            $sgpa,
            $total_credit
        ) =
            calculateSGPA(
                $subjects
            );

        $sgpa_record[] = [
            'semester' =>
                $semesterNo,

            'sgpa' =>
                $sgpa,

            'total_credit' =>
                $total_credit
        ];
    }

    return $sgpa_record;
}


/*
|--------------------------------------------------------------------------
| SGPA
|--------------------------------------------------------------------------
*/

function calculateSGPA(
    $temp_subject
) {

    $total_obt_grade_value = 0;

    $total_credit = 0;

    foreach (
        $temp_subject
        as $value
    ) {

        $obtExt =
            $value['obt_marks_ext'];

        $obtInt =
            $value['obt_marks_int'];

        /*
        |--------------------------------------------------------------------------
        | ABSENT
        |--------------------------------------------------------------------------
        */

        if (
            $obtExt === 'AB' ||
            $obtInt === 'AB'
        ) {

            $grade_value = 0;

        } else {

            $grandTotal =
                (int)$value['Min_Marks'] +
                (int)$value['Max_Marks'];

            $student_obt_mark =
                (int)$obtExt +
                (int)$obtInt;

            if ($grandTotal > 0) {

                $student_obt_per =
                    round(
                        (
                            $student_obt_mark /
                            $grandTotal
                        ) * 100,
                        2
                    );

            } else {

                $student_obt_per = 0;
            }

            if ($student_obt_per > 90) {

                $grade_value = 10;

            } elseif ($student_obt_per > 80) {

                $grade_value = 9;

            } elseif ($student_obt_per > 70) {

                $grade_value = 8;

            } elseif ($student_obt_per > 60) {

                $grade_value = 7;

            } elseif ($student_obt_per > 55) {

                $grade_value = 6;

            } elseif ($student_obt_per > 50) {

                $grade_value = 5;

            } elseif ($student_obt_per >= 40) {

                $grade_value = 4;

            } else {

                $grade_value = 0;
            }
        }

        $credit =
            (int)$value['Credit'];

        $total_obt_grade_value +=
            $grade_value *
            $credit;

        $total_credit +=
            $credit;
    }

    if ($total_credit <= 0) {

        return [
            '0.00',
            0
        ];
    }

    return [
        number_format(
            $total_obt_grade_value /
            $total_credit,
            2
        ),
        $total_credit
    ];
}


/*
|--------------------------------------------------------------------------
| HEADER - GRADE
|--------------------------------------------------------------------------
*/

function setHeaderGrade(
    $data,
    $exam_month
) {

    global $pdf;

    $pdf->SetFont(
        'times',
        '',
        12
    );

    /*
    |--------------------------------------------------------------------------
    | SERIAL
    |--------------------------------------------------------------------------
    */

    $pdf->SetXY(
        16.1,
        5
    );

    $pdf->Cell(
        0,
        10,
        $data['unique_id'] ?? '',
        0,
        0,
        'R'
    );

    /*
    |--------------------------------------------------------------------------
    | STATEMENT
    |--------------------------------------------------------------------------
    */

    $pdf->SetXY(
        15,
        55
    );

    $stat =
        isset($_REQUEST['marksheet_in_grade'])
        ? 'Statement of Grades'
        : 'Statement of Marks';

    $pdf->Cell(
        0,
        0,
        strtoupper($stat),
        0,
        0,
        'C',
        0
    );

    /*
    |--------------------------------------------------------------------------
    | COURSE
    |--------------------------------------------------------------------------
    */

    $pdf->SetFont(
        'times',
        'B',
        12
    );

    $pdf->SetXY(
        15,
        62
    );

    $course_name =
        isset($_REQUEST['marksheet_in_grade'])
        ? strtoupper($data['course'])
        : $data['course'];

    $pdf->Cell(
        0,
        0,
        ($data['duration_val'] ?? '') .
        '. IN ' .
        $course_name,
        0,
        0,
        'C',
        0
    );

    /*
    |--------------------------------------------------------------------------
    | SESSION
    |--------------------------------------------------------------------------
    */

    $pdf->SetXY(
        15,
        69
    );

    $pdf->SetFont(
        'times',
        '',
        12
    );

    $addmissionSession =
        explode(
            '-',
            $data['Admission_Session']
        );

    $addmissionYearFrom =
        2000 +
        (int)($addmissionSession[1] ?? 0);

    $addmissionYearTo =
        3 +
        $addmissionYearFrom;

    $pdf->Cell(
        0,
        0,
        strtoupper(
            'Session: ' .
            $addmissionYearFrom .
            '-' .
            $addmissionYearTo
        ),
        0,
        0,
        'C',
        0
    );

    /*
    |--------------------------------------------------------------------------
    | SCHOOL
    |--------------------------------------------------------------------------
    */

    $pdf->SetFont(
        'times',
        'B',
        12
    );

    $pdf->SetXY(
        16.1,
        85
    );

    $pdf->Cell(
        107,
        10,
        'School: ' .
        $data['university_name'],
        'LTB',
        0,
        'L',
        0
    );

    /*
    |--------------------------------------------------------------------------
    | SEMESTER
    |--------------------------------------------------------------------------
    */

    $pdf->SetXY(
        123,
        85
    );

    $semInRoman =
        intToRoman(
            (int)($data['Duration'] ?? 0)
        );

    if (empty($exam_month)) {
        $exam_month = 'Jan-25';
    }

    $examSession =
        explode(
            '-',
            $exam_month
        );

    $examMonth =
        date(
            'F',
            strtotime(
                $examSession[0] ?? 'Jan'
            )
        );

    $examYear =
        2000 +
        (int)($examSession[1] ?? 25);

    $pdf->Cell(
        70,
        10,
        'Semester: ' .
        $semInRoman .
        ' (' .
        ucwords(
            strtolower(
                $examMonth .
                ' ' .
                $examYear
            )
        ) .
        ')',
        1,
        0,
        'L',
        0
    );

    $pdf->SetFont(
        'times',
        'B',
        10
    );
}


/*
|--------------------------------------------------------------------------
| HEADER - PERCENTAGE
|--------------------------------------------------------------------------
*/

function setHeaderPercentage(
    $data,
    $exam_month
) {

    global $pdf;

    $pdf->SetFont(
        'times',
        '',
        12
    );

    $pdf->SetXY(
        15,
        60
    );

    $stat =
        isset($_REQUEST['marksheet_in_grade'])
        ? 'Statement of Grades'
        : 'Statement of Marks';

    $pdf->Cell(
        0,
        0,
        strtoupper($stat),
        0,
        0,
        'C',
        0
    );

    /*
    |--------------------------------------------------------------------------
    | COURSE
    |--------------------------------------------------------------------------
    */

    $pdf->SetXY(
        15,
        67.4
    );

    $pdf->Cell(
        0,
        0,
        ($data['duration_val'] ?? '') .
        '. IN ' .
        $data['course'],
        0,
        0,
        'C',
        0
    );

    /*
    |--------------------------------------------------------------------------
    | ADMISSION SESSION
    |--------------------------------------------------------------------------
    */

    $pdf->SetXY(
        15,
        75
    );

    $addmissionSession =
        explode(
            '-',
            $data['Admission_Session']
        );

    $addmissionYearFrom =
        2000 +
        (int)($addmissionSession[1] ?? 0);

    $addmissionYearTo =
        3 +
        $addmissionYearFrom;

    $pdf->Cell(
        0,
        0,
        strtoupper(
            'Admission Session: ' .
            $addmissionYearFrom .
            '-' .
            $addmissionYearTo
        ),
        0,
        0,
        'C',
        0
    );

    /*
    |--------------------------------------------------------------------------
    | FATHER NAME
    |--------------------------------------------------------------------------
    */

    $pdf->SetFont(
        'times',
        'B',
        12
    );

    $pdf->SetXY(
        16.1,
        82
    );

    $pdf->Cell(
        26,
        10,
        'Father Name : ',
        'LTB',
        0,
        'L',
        0
    );

    checkWarpText(
        strtoupper(
            $data['Father_Name'] ?? ''
        ),
        33,
        94
    );

    /*
    |--------------------------------------------------------------------------
    | MODE
    |--------------------------------------------------------------------------
    */

    $pdf->SetXY(
        123,
        92
    );

    $pdf->Cell(
        70,
        10,
        ($data['mode_type'] ?? '') .
        ' : ' .
        ($data['durMonthYear'] ?? ''),
        1,
        0,
        'L',
        0
    );

    /*
    |--------------------------------------------------------------------------
    | SCHOOL
    |--------------------------------------------------------------------------
    */

    $pdf->SetXY(
        16.1,
        102
    );

    $pdf->Cell(
        107,
        10,
        'School : ' .
        $data['university_name'],
        'LTB',
        0,
        'L',
        0
    );

    /*
    |--------------------------------------------------------------------------
    | EXAM SESSION
    |--------------------------------------------------------------------------
    */

    $pdf->SetXY(
        123,
        102
    );

    if (empty($exam_month)) {
        $exam_month = 'Jan-25';
    }

    $examSession =
        explode(
            '-',
            $exam_month
        );

    $examMonth =
        date(
            'F',
            strtotime(
                $examSession[0] ?? 'Jan'
            )
        );

    $examYear =
        2000 +
        (int)($examSession[1] ?? 25);

    $pdf->Cell(
        70,
        10,
        'Exam Session : ' .
        ucwords(
            strtolower(
                $examMonth .
                ' ' .
                $examYear
            )
        ),
        1,
        0,
        'L',
        0
    );

    $pdf->SetFont(
        'times',
        'B',
        10
    );
}


/*
|--------------------------------------------------------------------------
| WRAP TEXT
|--------------------------------------------------------------------------
*/

function checkWarpText(
    $content,
    $content_length,
    $width
) {

    global $pdf;

    if (
        strlen($content) >
        $content_length
    ) {

        $nameParts =
            explode(
                "\n",
                wordwrap(
                    $content,
                    $content_length
                )
            );

        $pdf->SetFont(
            'times',
            '',
            10
        );

        $pdf->MultiCell(
            $width,
            5,
            ($nameParts[0] ?? '') .
            chr(10) .
            ($nameParts[1] ?? ''),
            'TB',
            'L',
            0
        );

        $pdf->SetFont(
            'times',
            '',
            12
        );

    } else {

        $pdf->Cell(
            $width,
            10,
            $content,
            'TB',
            0,
            'L',
            0
        );
    }
}


/*
|--------------------------------------------------------------------------
| EXAM SESSION / DATE
|--------------------------------------------------------------------------
*/

function selectExamSessionAndDateOfIssue(
    $admission_session,
    $semester,
    $type
) {

    $parts =
        explode(
            '-',
            trim($admission_session)
        );

    $month =
        ucwords(
            strtolower(
                substr(
                    $parts[0] ?? '',
                    0,
                    3
                )
            )
        );

    $year =
        intval(
            $parts[1] ?? 0
        );

    if ($year > 99) {

        $year =
            date(
                'y',
                strtotime(
                    $year . '-01-01'
                )
            );
    }

    $updated_adm_session =
        $month .
        '-' .
        $year;

    /*
    |--------------------------------------------------------------------------
    | EXAM SESSION MAP
    |--------------------------------------------------------------------------
    */

    $list_details = [

        [
            'admission_session' => 'Jul-21',
            'exam_session' => 'Dec-21',
            'semester' => '1',
            'date_of_issue' => '10-02-2022'
        ],

        [
            'admission_session' => 'Jul-21',
            'exam_session' => 'Jun-22',
            'semester' => '2',
            'date_of_issue' => '08-08-2022'
        ],

        [
            'admission_session' => 'Jul-21',
            'exam_session' => 'Dec-22',
            'semester' => '3',
            'date_of_issue' => '15-02-2023'
        ],

        [
            'admission_session' => 'Jul-21',
            'exam_session' => 'Jun-23',
            'semester' => '4',
            'date_of_issue' => '22-07-2023'
        ],

        [
            'admission_session' => 'Jul-21',
            'exam_session' => 'Dec-23',
            'semester' => '5',
            'date_of_issue' => '26-02-2024'
        ],

        [
            'admission_session' => 'Jul-21',
            'exam_session' => 'Jun-24',
            'semester' => '6',
            'date_of_issue' => '19-10-2024'
        ],

        [
            'admission_session' => 'Jul-22',
            'exam_session' => 'Dec-22',
            'semester' => '1',
            'date_of_issue' => '15-02-2023'
        ],

        [
            'admission_session' => 'Jul-22',
            'exam_session' => 'Jun-23',
            'semester' => '2',
            'date_of_issue' => '22-07-2023'
        ],

        [
            'admission_session' => 'Jul-22',
            'exam_session' => 'Dec-23',
            'semester' => '3',
            'date_of_issue' => '20-02-2024'
        ],

        [
            'admission_session' => 'Jul-22',
            'exam_session' => 'Jun-24',
            'semester' => '4',
            'date_of_issue' => '19-10-2024'
        ],

        [
            'admission_session' => 'Jul-23',
            'exam_session' => 'Dec-23',
            'semester' => '1',
            'date_of_issue' => '26-02-2024'
        ],

        [
            'admission_session' => 'Jul-23',
            'exam_session' => 'Jun-24',
            'semester' => '2',
            'date_of_issue' => '19-10-2024'
        ],

        [
            'admission_session' => 'Jul-23',
            'exam_session' => 'Dec-24',
            'semester' => '3',
            'date_of_issue' => date('d-m-Y')
        ],

        [
            'admission_session' => 'Jul-23',
            'exam_session' => 'Jun-24',
            'semester' => '4',
            'date_of_issue' => date('d-m-Y')
        ],

        [
            'admission_session' => 'Jul-24',
            'exam_session' => 'Dec-24',
            'semester' => '1',
            'date_of_issue' => '15-05-2025'
        ],

        [
            'admission_session' => 'Jul-22',
            'exam_session' => 'Dec-24',
            'semester' => '5',
            'date_of_issue' => date('d-m-Y')
        ],

        [
            'admission_session' => 'Jul-22',
            'exam_session' => 'Jun-25',
            'semester' => '6',
            'date_of_issue' => date('d-m-Y')
        ],

        [
            'admission_session' => 'Jul-24',
            'exam_session' => 'Dec-24',
            'semester' => '5',
            'date_of_issue' => '15-05-2025'
        ],

        [
            'admission_session' => 'Jul-24',
            'exam_session' => 'Dec-25',
            'semester' => '5',
            'date_of_issue' => date('d-m-Y')
        ],

        [
            'admission_session' => 'Jul-21 CT',
            'exam_session' => 'Dec-24',
            'semester' => '6',
            'date_of_issue' => '10-02-2025'
        ]
    ];

    $exam_session = '';
    $date_of_issue = '';

    foreach (
        $list_details
        as $value
    ) {

        if (
            $value['admission_session'] ===
            $updated_adm_session &&

            (string)$value['semester'] ===
            (string)$semester
        ) {

            $exam_session =
                $value['exam_session'];

            $date_of_issue =
                $value['date_of_issue'];

            break;
        }
    }

    if (
        $type ===
        'date&exam'
    ) {

        return [
            $date_of_issue,
            $exam_session
        ];
    }

    return [
        $date_of_issue
    ];
}


/*
|--------------------------------------------------------------------------
| CLEAN PDF TEXT
|--------------------------------------------------------------------------
*/

function cleanPdfText($text)
{

    $text =
        (string)$text;

    /*
     * Remove NBSP.
     */
    $text =
        str_replace(
            "\xC2\xA0",
            ' ',
            $text
        );

    /*
     * Normalize whitespace.
     */
    $text =
        preg_replace(
            '/\s+/u',
            ' ',
            $text
        );

    /*
     * TCPDF/FPDF may not handle every UTF-8 character
     * depending on the selected font.
     *
     * Keep normal UTF-8 where possible.
     */
    return trim($text);
}


/*
|--------------------------------------------------------------------------
| SANITIZE FILE NAME
|--------------------------------------------------------------------------
*/

function sanitizeFilename($filename)
{

    return preg_replace(
        '/[^A-Za-z0-9._-]/',
        '_',
        $filename
    );
}


/*
|--------------------------------------------------------------------------
| CLEAN DIRECTORY
|--------------------------------------------------------------------------
*/

function cleanupDirectory($dir)
{

    if (
        !is_dir($dir)
    ) {
        return;
    }

    $items =
        scandir($dir);

    foreach (
        $items
        as $item
    ) {

        if (
            $item === '.' ||
            $item === '..'
        ) {
            continue;
        }

        $path =
            $dir .
            DIRECTORY_SEPARATOR .
            $item;

        if (is_dir($path)) {

            cleanupDirectory(
                $path
            );

            @rmdir($path);

        } else {

            @unlink($path);
        }
    }

    @rmdir($dir);
}


/*
|--------------------------------------------------------------------------
| DYNAMIC MYSQLI BIND
|--------------------------------------------------------------------------
*/

function bindDynamicParams(
    $stmt,
    $types,
    $params
) {

    $bindParams = [];

    $bindParams[] =
        $types;

    foreach (
        $params
        as $key => $value
    ) {

        $bindParams[] =
            &$params[$key];
    }

    call_user_func_array(
        [$stmt, 'bind_param'],
        $bindParams
    );
}
