<?php

require '../../includes/db-config.php';
session_start();

if (isset($_REQUEST['dispatch_by']) && isset($_REQUEST['dispatch_date']) && isset($_REQUEST['dispatch_mode']) && isset($_REQUEST['docket_id'])) {
    $dispatch_by = mysqli_real_escape_string($conn,$_REQUEST['dispatch_by']);
    $dispatch_mode = mysqli_real_escape_string($conn,$_REQUEST['dispatch_mode']);
    $docket_id = mysqli_real_escape_string($conn,$_REQUEST['docket_id']);
    $dispatch_date = date_format(date_create(mysqli_real_escape_string($conn,$_REQUEST['dispatch_date'])),'Y-m-d');
    $courier_by = mysqli_real_escape_string($conn,$_REQUEST['courier_by']);
    $consignment_no = '';
    if(isset($_REQUEST['consignment_no'])) {
        $consignment_no = mysqli_real_escape_string($conn,$_REQUEST['consignment_no']);
    }

    $filepath = '';
    if(isset($_FILES['file']) && !empty($_FILES['file']['name'])) {
        $filepath = checkAndUploadfile($docket_id);
       
        if(!$filepath) {
            echo json_encode(['status'=>400,'message'=>'Upload file must be pdf or image']);
            die;
        } 
    }

    if (empty($filepath)) {
        $check_details = $conn->query("SELECT scan_copy FROM `dispatch_certificate` WHERE dockect_id = '$docket_id'");
        $check_details = mysqli_fetch_column($check_details);
        if(!is_null($check_details)) {
            $filepath = $check_details;
        }
    }

    $updateDispatch = $conn->query("UPDATE dispatch_certificate SET consignment_no = '$consignment_no' , dispatch_by = '$dispatch_by'  , dispatch_date = '$dispatch_date' , scan_copy = '$filepath' , mode = '$dispatch_mode' , courier_by = '$courier_by' WHERE dockect_id = '$docket_id'");

    if ($updateDispatch) {
        $updateDispatchStatus = $conn->query("UPDATE certificate_entry SET Dispatch_status = '2' WHERE Docket_Id = '$docket_id'");
        if($updateDispatchStatus) {
            showResponse($updateDispatchStatus,"updated");
        }
    } else {
        showResponse(false);
    }
}

  function checkAndUploadfile($docket_id) : bool|string {
    
    $mimes = [
        'image/jpeg',
        'image/jpg',
        "image/jpg","image/png","image/gif",
        'application/pdf',
        'application/doc',
        'application/docx',
    ]; 
    
    if (in_array($_FILES["file"]["type"], $mimes)) {
        $extension = substr($_FILES["file"]["name"],strlen($_FILES["file"]["name"])-4,strlen($_FILES["file"]["name"]));
        move_uploaded_file($_FILES['file']['tmp_name'], '../../uploads/certificate_dispatch/certificate_dispatch_bill/' . $docket_id . $extension);
        return '../../uploads/certificate_dispatch/certificate_dispatch_bill/' . $docket_id . $extension;
    } else {
        return false;
    }
}
  function showResponse($response, $message = 'Something went wrong!') {
    if ($response) {
        echo json_encode(['status' => 200, 'message' => "Dispatch details $message successfully!"]);
    } else {
        echo json_encode(['status' => 400, 'message' => $message]);
    }
}
?>