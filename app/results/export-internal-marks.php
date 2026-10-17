<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
include '../../includes/db-config.php';
include '../../includes/ClassHelper.php';
session_start();

$userQuery = '';
if ($_SESSION['Role'] == "Center" || $_SESSION['Role'] == "Sub-Center") {
    $userQuery = " AND Added_For = " . $_SESSION['ID'];
}

$filterBySubCourse = isset($_SESSION['filterBySubCourses']) ? $_SESSION['filterBySubCourses'] : "";
$filterByDuration  = isset($_SESSION['filterByDuration']) ? $_SESSION['filterByDuration'] : "";
$filterByVerticalType = isset($_SESSION['filterByVerticalType']) ? $_SESSION['filterByVerticalType'] : "";

$search_value = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : "";

$searchQuery = " ";
if ($search_value != '') {

    if (!empty(strpos($search_value, "="))) {

        $search = explode("=", $search_value);
        $searchBy = trim($search[0]);
        $values = array_key_exists(1, $search) && !empty($search[1]) ? explode(" ", $search[1]) : [];
        $values = array_filter($values);

        if (!empty($values)) {

            $student_id_column = $_SESSION['student_id'] == 1 ?
                'Students.Unique_ID' :
                "RIGHT(CONCAT('000000', Students.ID), 6)";

            if (strcasecmp($searchBy, 'student id') == 0) {
                $column = $student_id_column;
            } elseif (strcasecmp($searchBy, 'enrollment') == 0) {
                $column = 'Students.Enrollment_No';
            } elseif (strcasecmp($searchBy, 'oa number') == 0) {
                $column = 'OA_Number';
            } else {
                $column = '';
            }

            if (!empty($column)) {
                $values = "'" . implode("','", $values) . "'";
                $searchQuery = " AND $column IN ($values)";
            }
        }
    } elseif (strcasecmp($search_value, 'completed') == 0) {
        $searchQuery = " AND Step = 4 ";
    } else {

        $searchQuery = " AND (
            Students.ID LIKE '%$search_value%' OR
            Students.Unique_ID LIKE '%$search_value%' OR
            Students.Enrollment_No LIKE '%$search_value%' OR
            Students.First_Name LIKE '%$search_value%' OR
            Students.Middle_Name LIKE '%$search_value%' OR
            Students.Last_Name LIKE '%$search_value%' OR
            Students.Step LIKE '%$search_value%' OR
            Students.Father_Name LIKE '%$search_value%' OR
            Courses.Name LIKE '%$search_value%' OR
            Sub_Courses.Name LIKE '%$search_value%' OR
            Sub_Courses.Short_Name LIKE '%$search_value%'
        )";
    }
}

$examTypeSql = '';
if (isset($_SESSION['university_id']) && $_SESSION['university_id'] == 48) {
    $examTypeSql = "";
}

$filterByUniversity = " AND Students.University_ID = " . intval($_SESSION['university_id']) . " ";

$role_query = str_replace('{{ table }}', 'Students', $_SESSION['RoleQuery'] ?? '');
$role_query = str_replace('{{ column }}', 'Added_For', $role_query);

$conditionsQr =
    $userQuery .
    $role_query .
    $filterByUniversity .
    $examTypeSql .
    $filterBySubCourse .
    $searchQuery .
    $filterByDuration .
    $filterByVerticalType;


// -----------------------------------------------------------------------------
// ✔ FINAL SQL WITH Course_Types AND CORRECT MARKS COLUMNS
// -----------------------------------------------------------------------------
// echo "
// SELECT 
//     marksheets.max_marks_ext AS Max_Marks,
//     marksheets.max_marks_int AS Min_Marks,
//     marksheets.obt_marks_ext,
//     marksheets.obt_marks_int,
//     Syllabi.Code AS subject_code,
//     Syllabi.Name AS subject_name,
//     Students.University_ID,
//     Course_Types.Name AS Course_Category,
//     Students.Duration,
//     Students.Enrollment_No,
//     CONCAT(TRIM(CONCAT(Students.First_Name, ' ', Students.Middle_Name, ' ', Students.Last_Name))) AS full_name,
//     Sub_Courses.Name AS sub_course_name,
//     Courses.Short_Name AS course_short_name,
//     Users.Code,
//     Users.Name AS user_name,
//     Users.Code AS user_code
// FROM marksheets

// LEFT JOIN Students 
//     ON marksheets.enrollment_no = Students.Unique_ID

// LEFT JOIN Users 
//     ON Students.Added_For = Users.ID

// LEFT JOIN Syllabi 
//     ON marksheets.subject_id = Syllabi.ID

// LEFT JOIN Courses 
//     ON Syllabi.Course_ID = Courses.ID

// LEFT JOIN Course_Types
//     ON Courses.Course_Type_ID = Course_Types.ID

// LEFT JOIN Sub_Courses 
//     ON Syllabi.Sub_Course_ID = Sub_Courses.ID

// WHERE 1=1 
//     $conditionsQr

// ORDER BY Students.ID DESC
// ";
// die();


// -----------------------------------------------------------------------------
// EXECUTE DOWNLOAD QUERY
// -----------------------------------------------------------------------------
$result_record = "
SELECT 
    marksheets.max_marks_ext AS Max_Marks,
    marksheets.max_marks_int AS Min_Marks,
    marksheets.obt_marks_ext,
    marksheets.obt_marks_int,
    Syllabi.Code AS subject_code,
    Syllabi.Name AS subject_name,
    Students.University_ID,
    Course_Types.Name AS Course_Category,
    Students.Duration,
    Students.Enrollment_No,
    CONCAT(TRIM(CONCAT(Students.First_Name, ' ', Students.Middle_Name, ' ', Students.Last_Name))) AS full_name,
    Sub_Courses.Name AS sub_course_name,
    Courses.Short_Name AS course_short_name,
    Users.Code,
    Users.Name AS user_name,
    Users.Code AS user_code
FROM marksheets

LEFT JOIN Students 
    ON marksheets.enrollment_no = Students.Unique_ID

LEFT JOIN Users 
    ON Students.Added_For = Users.ID

LEFT JOIN Syllabi 
    ON marksheets.subject_id = Syllabi.ID

LEFT JOIN Courses 
    ON Syllabi.Course_ID = Courses.ID

LEFT JOIN Course_Types
    ON Courses.Course_Type_ID = Course_Types.ID

LEFT JOIN Sub_Courses 
    ON Syllabi.Sub_Course_ID = Sub_Courses.ID

WHERE 1=1 
    $conditionsQr

ORDER BY Students.ID DESC
";

$sqldata = mysqli_query($conn, $result_record);

if ($sqldata->num_rows > 0) {

    $randomNumber = rand(1, 10000000000);
    $filename = "Internal-Marks" . $randomNumber . ".csv";

    header("Content-Disposition: attachment; filename=" . $filename);
    header("Content-Type: text/csv");
    header("Pragma: no-cache");
    header("Expires: 0");

    $fp = fopen('php://output', 'w');

    $h = [
        "Enrollment No.",
        "Student Name",
        "Duration",
        "Subject Name",
        "Subject Code",
        "Max Marks",
        "Min Marks",
        "Internal Marks",
        "Sub-Course Name",
        "Centre Name"
    ];

    fputcsv($fp, $h);

    $durationObj = new ClassHelper();

    while ($row = $sqldata->fetch_assoc()) {

        if ($row['University_ID'] == 48) {
            $max_marks = $row['Max_Marks'];
            $min_marks = $row['Max_Marks'] * 40 / 100;
        } else {
            $max_marks = $row['Min_Marks'];
            $min_marks = $row['Min_Marks'] * 40 / 100;
        }

        $data = [
            $row['Enrollment_No'],
            $row['full_name'],
            $durationObj->getDurationFunc($row['Duration'], $row['Course_Category'], $row['University_ID']),
            $row['subject_name'],
            $row['subject_code'],
            round($max_marks),
            round($min_marks),
            ($_SESSION['university_id'] == 48) ? $row['obt_marks_ext'] : $row['obt_marks_int'],
            $row['sub_course_name'],
            $row['user_name'] . " (" . $row['Code'] . ")"
        ];

        fputcsv($fp, $data);
    }

    fclose($fp);
} else {
    echo "No data found";
}
