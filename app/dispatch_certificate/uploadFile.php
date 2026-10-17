<?php 

    require '../../includes/db-config.php';
session_start();

if (isset($_FILES['file'])) {
    $docket_id = mysqli_real_escape_string($conn,$_REQUEST['docketId']);
    $filepath = checkAndUploadfile($docket_id);

    if($filepath) {
        $update = $conn->query("UPDATE dispatch_certificate SET upload_file = '$filepath' WHERE dockect_id = '$docket_id'");
        showResponse($update,'Uploaded');
    }
}

function checkAndUploadfile($docket_id) : bool|string {
    
    $mimes = [
        'application/pdf',
        'application/doc',
        'application/docx',
        'application/msword'
    ];
    
    if (in_array($_FILES["file"]["type"], $mimes)) {
        $extension = substr($_FILES["file"]["name"],strlen($_FILES["file"]["name"])-4,strlen($_FILES["file"]["name"]));
        move_uploaded_file($_FILES['file']['tmp_name'], '../../uploads/certificate_dispatch/certificate_record/' . $docket_id . $extension);
        return '../../uploads/certificate_dispatch/certificate_record/' . $docket_id . $extension;
    } else {
        return false;
    }
}

function showResponse($response, $message) {
    if ($response) {
        echo json_encode(['status' => 200, 'message' => "File $message successfully!"]);
    } else {
        echo json_encode(['status' => 400, 'message' => 'Something went wrong!']);
    }
}

?>