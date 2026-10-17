<?php
ini_set('display_errors', 1);
require '../../includes/db-config.php';
session_start();

$html = '';

if ($_SESSION['university_id'] == 47) {
    //  $subCourseId = intval($_POST['subCourseId']);
    $subCourseId = isset($_POST['id']) ? intval($_POST['id']) : '';
 $sql = "SELECT Name, Min_Duration FROM Sub_Courses WHERE ID = $subCourseId AND Status = 1";
    $result = $conn->query($sql);

    $html = '<option value="">Select Duration</option>';
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (is_string($row['Min_Duration']) == 1) {
            $duration = json_decode($row['Min_Duration'], true)[0];
        } else {
            $duration = intval($row['Min_Duration']);
        }
        for ($i = 1; $i <= $duration; $i++) {
            $html .= '<option value="' . $i . '" >' . $i . '</option>';
        }
    }
} elseif ($_SESSION['university_id'] == 48) {
    $html = '<option value="">Select Duration</option>';
    $optionsArr = [
        "24" => "24",
        "12" => "12",
        "6" => "6",
        "3" => "3",
    ];
    foreach ($optionsArr as $value => $label) {
        $html .= '<option value="' . $value . '" >' . strtolower($label ). '</option>';
    }
} else {
    $html = '<option value="">Select a valid university</option>';
}
echo $html;