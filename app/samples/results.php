<?php
  include($_SERVER['DOCUMENT_ROOT'] . '/includes/header-top.php');
  require ('../../extras/vendor/shuchkin/simplexlsxgen/src/SimpleXLSXGen.php');

  if($_SESSION['university_id']=='48'){ 
    $header[] = array('Course', 'Sub-Course', 'Enrollment Number', 'Subject Code', 'Obtained External Marks', 'Obtained Internal Marks', 'Semester', 'Exam Month', 'Exam Year', 'Paper Code',"Certificate Date","Exam Daete");
  }else{
    $header[] = array('Course', 'Sub-Course', 'Enrollment Number', 'Subject Code', 'Obtained External Marks', 'Obtained Internal Marks', 'Semester', 'Exam Month', 'Exam Year', 'Paper Code',"Certificate Date","Exam Daete");
  }

  $xlsx = SimpleXLSXGen::fromArray( $header )->downloadAs('Results Sample.xlsx');
