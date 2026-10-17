<?php
  if(isset($_POST['name']) && isset($_POST['id'])){
    require '../../includes/db-config.php';
    session_start();

    $id = intval($_POST['id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $centerIds = explode(',', $name);
  
    // $ids = [];
    // foreach ($centerIds as $code){
    //   $center = $conn->query("SELECT ID FROM Users WHERE Code = '$code' AND Role = 'Center' ");
    //   if($center->num_rows > 0){
    //     $centerArr = $center->fetch_assoc();
    //     $ids[] = $centerArr['ID'];
    //   }
    // }

    // if(empty($ids)){
    //   echo json_encode(['status'=>400, 'message'=>'Invalid center code!']);
    //   die();
    // }
    $centerIds = array_map('trim', $centerIds);
    $centerIds = json_encode($centerIds, true);
    $update = $conn->query("UPDATE `Syllabi` SET `User_ID` = '$centerIds' WHERE ID = $id");
    if($update){
      echo json_encode(['status'=>200, 'message'=>'Center Alloted successlly!']);
    }else{
      echo json_encode(['status'=>400, 'message'=>'Something went wrong!']);
    }
  }
?>
