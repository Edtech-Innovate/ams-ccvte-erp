<?php

require '../../includes/db-config.php';
session_start();

if (isset($_REQUEST['center_id']) && isset($_REQUEST['type']) && $_REQUEST['type'] == 'checkCenterDocketID') {
    $center_id = mysqli_real_escape_string($conn, $_REQUEST['center_id']);
    checkCenterForDocketIdGenerateAndNotDispatched($center_id);
} elseif (isset($_REQUEST['center_id']) && isset($_REQUEST['docket_id'])) {
    $center_id = mysqli_real_escape_string($conn, $_REQUEST['center_id']);
    $docket_id = mysqli_real_escape_string($conn,$_REQUEST['docket_id']);
    insertDocketId($docket_id,$center_id,'not_insert');
} elseif (isset($_REQUEST['center_id'])) {
    $center_id = mysqli_real_escape_string($conn, $_REQUEST['center_id']);    
    $docket_id = generateUniqueDocketId();
    insertDocketId($docket_id,$center_id);
} else {
    $center_ids_list = [];
    foreach ($_REQUEST as $value) {
        $center_ids_list[] = mysqli_real_escape_string($conn,$value);
    }
    $center_id = implode(',',$center_ids_list);
    $docket_id = generateUniqueDocketId();
    insertDocketId($docket_id,$center_id);
}

function insertDocketId($docket_id,$center_id,$type=null) {
    global $conn;
    $update = $conn->query("UPDATE certificate_entry SET Docket_Id = '$docket_id' WHERE Added_For IN ($center_id) AND Docket_Id IS NULL AND Dispatch_status = '1'");
    if($update) {
        if(is_null($type)) {
            $insert_docketId = $conn->query("INSERT INTO `dispatch_certificate`(`dockect_id`) VALUES ('$docket_id')");
        }
        echo json_encode(['status' => 200 , 'message' => 'Docket Id Updated']);
    } else {
        echo json_encode(['status' => 400 , 'message' => 'Something went wrong']);
    }
}

function generateUniqueDocketId() {
    $docket_id = '';
    while(true) {
        $docket_id =  "CD".rand(1111,55555);
        if(!checkDocketIdForDuplicacy($docket_id)) {
            continue;
        } else {
            break;
        }
    }
    return $docket_id;
}

function checkDocketIdForDuplicacy($docket_id) {
    global $conn;
    $checkDocket_id = $conn->query("SELECT * FROM `dispatch_certificate` WHERE dockect_id = '$docket_id'");
    if($checkDocket_id->num_rows > 0) {
        return false;     
    } else {
        return true;
    }
}

function checkCenterForDocketIdGenerateAndNotDispatched($center_id) {

    global $conn;
    $checkCenter = $conn->query("SELECT  COUNT(id) as `numOfMarksheet` , Docket_Id  FROM certificate_entry WHERE Added_For = '$center_id' AND Docket_Id IS NOT NULL AND Dispatch_status = '1'");
    $checkCenter = mysqli_fetch_assoc($checkCenter);
    if($checkCenter['numOfMarksheet'] > 0) {
        echo json_encode(['status' => 400 , 'title' => 'Want to assign same docket_id ?' , 'text' => 'Docket_id already genrated for this center and certificate not dispatched yet' , 'docket_id' => $checkCenter['Docket_Id']]);
    } else {
        echo json_encode(['status' => 200 , 'message' => 'Docked id not genrated']);
    }
}
?>