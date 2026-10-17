<?php
  if(isset($_GET['id'])){
    require '../../includes/db-config.php';
    session_start();
    $id = intval($_GET['id']);

    $sub_course = $conn->query("SELECT Scheme_ID, Min_Duration,Mode_ID FROM Sub_Courses WHERE ID = $id");
    $sub_course = $sub_course->fetch_assoc();
    $sub_course['Min_Duration'] = $_SESSION['Role']=='Student' ? $_SESSION['Duration'] : $sub_course['Min_Duration'];
    // print_r($sub_course['Mode_ID']);die;
    echo '<option value="">Choose</option>';
    if($sub_course['Mode_ID']==1){
        // print_r($sub_course['Min_Duration']);die;
        for($i=1; $i<=$sub_course['Min_Duration']; $i++){
          echo '<option value="'.$sub_course['Scheme_ID'].'|'.$i.'">'.$i.'</option>';
        }   
    }else{
        echo '<option value="'.$sub_course['Scheme_ID'].'|'.$sub_course['Min_Duration'].'">'.$sub_course['Min_Duration'].'</option>';
  
        }
    }