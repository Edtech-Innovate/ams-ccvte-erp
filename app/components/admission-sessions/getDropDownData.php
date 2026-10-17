<?php
require '../../../includes/db-config.php';

$data_field = file_get_contents('php://input'); // by this we get raw data
$data_field = json_decode($data_field,true);

$response = '';

if(isset($data_field['method']) && $data_field['method'] == "getExamSession") {

    $funcationName = mysqli_real_escape_string($conn,$data_field['method']);
    $year = mysqli_real_escape_string($conn,$data_field['year']);
    $month = mysqli_real_escape_string($conn,$data_field['month']);
    $university_id = intval($data_field['university_id']);
    $response = call_user_func($funcationName,$month,$year,$university_id);
}

echo json_encode($response);

function getExamSession($month,$year,$university_id) : array {
    global $conn;
    try {
        $possibleExamSession = $presentExamSession = [];
        // if($month == "Jul") {
        //     $examMonth = ["Dec","Jun"];
        //     $yearInc = "odd";
        // } else {
        //     $examMonth = ["Jun","Dec"];
        //     $yearInc = "even";
        // }
        $possibleExamSession = possibleExamSession($month,$year);
        $possibleExamSession = array_map(function($examSession) {
            return "'" . $examSession . "'";
        } , $possibleExamSession);
        
        $examSession_query = "SELECT `ID` , `Name`, `serialNum` , `serialNum_range` , `dateOfIssue` , `certificate_serialNum` FROM Exam_Sessions WHERE `Name` IN (" . implode(",",$possibleExamSession) . ") AND University_ID = '$university_id' ";
       
        $examSession = $conn->query($examSession_query);
        if($examSession->num_rows > 0) {
            $examSession = mysqli_fetch_all($examSession,MYSQLI_ASSOC);
            $sessionNameArr = array_column($examSession,'Name');
            
            // $sessionInSortOrder = sort($sessionNameArr);
            $i = 0;
            // print_r($sessionNameArr);die;
            foreach ($sessionNameArr as $value) {
                $key = array_search($value,$sessionNameArr);
                // print_r($value );
                $presentExamSession[$i]['id'] = $examSession[$key]['ID'];
                $presentExamSession[$i]['name'] = $examSession[$key]['Name'];
                $presentExamSession[$i]['serialNum'] = $examSession[$key]['serialNum'];
                $presentExamSession[$i]['range'] = $examSession[$key]['serialNum_range'];
                $presentExamSession[$i]['dateOfIssue'] = $examSession[$key]['dateOfIssue'];
                $presentExamSession[$i]['certificate_serialNum'] = $examSession[$key]['certificate_serialNum'];
                $presentExamSession[$i]['semester'] = $i+1;
                $i++;
            }
        }
        // print_r($presentExamSession);
        // die;
        return ['status' => 200 , 'message' => json_encode($presentExamSession)];
    } catch (Exception $e) {
       return ['status' => 400 , 'message' => "Exception : " . $e->getMessage()];
    }
}


function possibleExamSession($examMonth,$year) {
    // $possibleExamSession = [];$i = 0;
    // $startMonth = "Jan";
    // $startYear  = 2025;

    // Starting date create
    $startDate = DateTime::createFromFormat("M Y", $examMonth . " " . $year);

    $result = [];
    $startDate->modify("+1 month");
    for ($i = 0; $i < 12; $i++) {
        $result[] = $startDate->format("M-y");
        $startDate->modify("+1 month");
    }
    // print_r($result);die;
    return $result;
}

function sortSessionName($sessionNameArr) : array {
    $year = array_reduce( $sessionNameArr, function($carry ,$session) {
        $yearVal = (explode('-', $session ))[1];
        $carry[$yearVal] = array_key_exists($yearVal,$carry) ? ++$carry[$yearVal] : 1;
        return $carry;
    }, []);
    ksort($year);
    $sessionNameArrInSort = [];
    foreach ($year as $key => $value) {
        if($value == 2) {
            $sessionNameArrInSort[] = 'Jun-' . $key;
            $sessionNameArrInSort[] = 'Dec-' . $key;
        }
        if($value == 1) {
            $sessionNameArrInSort[] = in_array('Jun-'.$key,$sessionNameArr) ? 'Jun-' . $key : 'Dec-' . $key;
        }
    }
    return $sessionNameArrInSort;
}

?>