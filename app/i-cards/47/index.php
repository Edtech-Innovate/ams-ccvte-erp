<?php

use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfReader;

if (isset($_GET['id'])) {
  require '../../../includes/db-config.php';
  session_start();

  $id = mysqli_real_escape_string($conn, $_GET['id']);
  $id = base64_decode($id);
  $id = intval(str_replace('W1Ebt1IhGN3ZOLplom9I', '', $id));

  $student = $conn->query("SELECT Students.ID, Students.Roll_No, Students.Duration, Students.First_Name, Students.Middle_Name, Students.Last_Name, Students.Father_Name, Students.Enrollment_No, Students.Contact, Students.Email, Students.Unique_ID, Students.DOB, Students.Address, Sub_Courses.Short_Name,  Sub_Courses.Name as Sub_Cour_name, Courses.Short_Name as Course, Admission_Sessions.Name as Session FROM Students LEFT JOIN Courses ON Students.Course_ID = Courses.ID LEFT JOIN Sub_Courses ON Students.Sub_Course_ID = Sub_Courses.ID LEFT JOIN Admission_Sessions ON Students.Admission_Session_ID = Admission_Sessions.ID WHERE Students.ID = $id AND Students.University_ID = " . $_SESSION['university_id'] . "");
  if ($student->num_rows == 0) {
    header('Location: /dashboard');
  }

  $student = $student->fetch_assoc();

  $file_extensions = array('.png', '.jpg', '.jpeg');

  $photo = "";
  $document = $conn->query("SELECT Location FROM Student_Documents WHERE Student_ID = " . $student['ID'] . " AND `Type` = 'Photo'");
  if ($document->num_rows > 0) {
    $photo = $document->fetch_assoc();
    $photo = "../../.." . $photo['Location'];
  }
  $student_photo = base64_encode(file_get_contents($photo));
  $i = 0;
  $end = 3;
  while ($i < $end) {
    $data1 = base64_decode($student_photo);
    $filename1 = $student['ID'] . "_Photo" . $file_extensions[$i]; //$file_extensions loops through the file extensions
    file_put_contents($filename1, $data1); //we save our new images to the path above
    $i++;
  }

  require_once('../../../extras/qrcode/qrlib.php');
  require_once('../../../extras/vendor/setasign/fpdf/fpdf.php');
  require_once('../../../extras/vendor/setasign/fpdi/src/autoload.php');

  $pdf = new Fpdi('P', 'mm', array(222, 140));

  $pdf->SetTitle('ID Card');

  $pageCount = $pdf->setSourceFile('Glocal-ID-Card.pdf');

  $pageId = $pdf->importPage(1, PdfReader\PageBoundaries::MEDIA_BOX);
  $pdf->addPage();
  $pdf->useImportedPage($pageId, 0, 0, 140);

  $pdf->SetMargins(0, 0, 0);
  $pdf->SetAutoPageBreak(true, 1);

  $pdf->AddFont('Helvetica-Bold', '', 'helveticab.php');
  $pdf->SetFont('Helvetica-Bold', '', 14);

  $student_id = empty($student['Unique_ID']) ? $student['ID'] : $student['Unique_ID'];
  $pdf->SetXY(65, 127);
  $pdf->Write(1, $student_id);

  $student_name = array($student['First_Name'], $student['Middle_Name'], $student['Last_Name']);
  $student_name = array_filter($student_name);
  $pdf->SetXY(28, 120);
  $pdf->Write(1, ucwords(strtolower(implode(" ", $student_name))));

  // Programme
  $pdf->SetXY(50, 132);
  $pdf->Write(1, $student['Course'] . ' (' . $student['Sub_Cour_name'] . ')');

  // $pdf->SetXY(65, 192.4);
  // $pdf->Write(1, ucfirst($student['Session']).'-'. ucfirst(strstr($student['Session'], '-', true)).'-'.(int)preg_replace('/\D+/', '', $student['Session'])+$student['Duration']);

  if (filetype($photo) === 'file' && file_exists($photo)) {
    try {
      $filename = $student['ID'] . "_Photo" . $file_extensions[0];
      $image = $filename;
      $pdf->Image($image, 42, 46, 55, 63);
      $photo = $image;
    } catch (Exception $e) {
      try {
        $filename = $student['ID'] . "_Photo" . $file_extensions[1];
        $image = $filename;
        $pdf->Image($image, 51, 63, 40, 45);
        $photo = $image;
      } catch (Exception $e) {
        try {
          $filename = $student['ID'] . "_Photo" . $file_extensions[2];
          $image = $filename;
          $pdf->Image($image, 51, 63, 40, 45);
          $photo = $image;
        } catch (Exception $e) {
          echo 'Message: ' . $e->getMessage();
        }
      }
    }
  }

  $pageId = $pdf->importPage(2, PdfReader\PageBoundaries::MEDIA_BOX);
  $pdf->addPage();
  $pdf->useImportedPage($pageId, 0, 0, 140);

  $pdf->SetXY(47, 25);
  $pdf->Write(1, ucwords(strtolower($student['Father_Name'])));

  $pdf->SetXY(47, 35);
  $pdf->Write(1, $student['DOB']);

  $pdf->SetXY(47, 45);
  $pdf->Write(1, $student['Contact']);

  $pdf->SetXY(47, 55);
  $pdf->Write(1, $student['Contact']);

  $pdf->SetXY(47, 65);
  $pdf->Write(1, $student['Email']);

  $address = json_decode($student['Address'], true);

  // Permanent Address
  $pdf->SetXY(30, 75);
  $pdf->Write(1, $address['present_address']);

  // City
  $pdf->SetXY(47, 85);
  $pdf->Write(1, $address['present_city']);

  // State
  $pdf->SetXY(47, 95);
  $pdf->Write(1, $address['present_state']);


  $pdf->SetXY(67, 196);
  $pdf->Write(1, strtoupper($student['Session']));

  $pdf->SetXY(73, 205.5);
  $pdf->Write(1, ":  NA");

  $i = 0;
  $end = 3;
  while ($i < $end) {
    // Delete Photos
    if (!empty($student_photo)) {
      $filename = $student['ID'] . "_Photo" . $file_extensions[$i]; //$file_extensions loops through the file extensions
      unlink($filename);
    }
    $i++;
  }

  $pdf->Output('I', 'ID Card.pdf');
}
