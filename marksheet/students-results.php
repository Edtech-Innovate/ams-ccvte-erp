<?php
error_reporting(1);

use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfReader;

require '../includes/db-config.php';
session_start();

require_once('../extras/vendor/setasign/fpdf/fpdf.php');
require_once('../extras/vendor/setasign/fpdi/src/autoload.php');
require '../extras/vendor/autoload.php';

$year = "1";
if ($_GET['year'] == 2) {
    $year = "2";
}

$reg_no = $_SESSION['Registration_No'];

$student_sql = $conn->query("SELECT * FROM tbl_results WHERE Registration_No = '$reg_no' AND CAST(Year as UNSIGNED) = $year LIMIT 1");
$students = $student_sql->fetch_assoc();
$row = [];
$student_sql = $conn->query("SELECT * FROM tbl_results WHERE Registration_No = '$reg_no' AND CAST(Year as UNSIGNED) = $year");

while ($stu_arr = $student_sql->fetch_assoc()) {
    $row[] = $stu_arr;
}

$pdf = new Fpdi();
if ($_GET['type'] == "Marksheet") {

    $pdf->SetTitle('Marksheet');
    $pageCount = $pdf->setSourceFile('ccvte_marks.pdf');
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetLeftMargin(16);

    // Page 1
    $pageId = $pdf->importPage(1, PdfReader\PageBoundaries::MEDIA_BOX);
    $pdf->addPage();
    $pdf->useImportedPage($pageId, 0, 0, 210);
    $pdf->SetFont('Arial', '', 11);
   

    $pdf->SetFont('Arial', '', 11);
    $pdf->SetXY(62, 60);
    $pdf->Write(1,substr($students['Exam_Month'], 0, 3) . "-" .  $students['Exam_Year']);

    $pdf->SetFont('Arial', '', 11);
    $pdf->SetXY(52, 69.5);
    $pdf->Write(1, $students['Registration_No']);

    $pdf->SetFont('Arial', '', 11);
    $pdf->SetXY(48, 78.3);
    $pdf->Write(1, strtoupper($students['Candidate_Name']));

    $pdf->SetFont('Arial', '', 11);
    $pdf->SetXY(30, 87);
    $pdf->Write(1, strtoupper($students['Course']));

    $pdf->SetFont('Arial', '', 11);
    $pdf->SetXY(48, 95.8);
    $pdf->Write(1, strtoupper($students['Year']));

    $pdf->SetFont('Arial', '', 10);
    $pdf->SetXY(46, 107.8);
    $pdf->Write(1, strtoupper($students['Center_Name']));


    $pdf->SetXY(16, 130);
    $pdf->MultiCell(25, 10,  ' Sub. Code', 'TLB',   'C');
    $pdf->SetXY(41, 130);
    $pdf->MultiCell(100, 10,  'Subject Name ', 'TLB',   'C');
    $pdf->SetXY(141, 130);
    $pdf->MultiCell(25.3, 10,  'Max. Marks', 'TLB',   'C');
    // $pdf->SetXY(131.3, 130);
    // $pdf->MultiCell(20, 10,  'Theory', 'TLB',  'C');
    // $pdf->SetXY(151.3, 130);
    // $pdf->MultiCell(25, 10,  'Practical', 'TLB',   'C');
    $pdf->SetXY(166.3, 130);
    $pdf->MultiCell(25, 10,  'Marks Obt.', 'TLBR',   'C');
    $y = 130;
    $total_obtained = [];
    $total_max = [];
    $pdf->SetXY(0, 130);
    $pdf->Ln();
    $pdf->SetFont('Arial', '', 10);
    $x_cor = 16;
    $pdf->SetXY($x_cor,140);
    foreach ($row as $data) {
        if (trim($data['Subject_Name']) == "VIVA") {
            $data['Obtained_Marks_Practical'] = $data['Obtained_Marks_VIVA'];
        }
        $obt_marks =  $data['Obtained_Marks_Theory'];
        $total_obtained[] = $obt_marks;
        $total_max[] = isset($data['Max_Mark']) ? $data['Max_Mark'] : 0;


        // $pdf->SetXY(10, $y);
        // $pdf->Cell(20, 10, !empty($data['Subject_Code']) ? substr($data['Subject_Code'], 0, 28) : '', 1);
        // $pdf->Cell(74, 10, !empty($data['Subject_Name']) ? $data['Subject_Name'] : '', 1);
        // $pdf->Cell(20, 10, !empty($data['Max_Mark']) ? substr($data['Max_Mark'], 0, 28) : '', 1);
        // $pdf->Cell(25, 10, !empty($data['Obtained_Marks_Theory']) ? substr($data['Obtained_Marks_Theory'], 0, 28) : '', 1);
        // if (trim($data['Subject_Name']) == "VIVA") {
        //     $pdf->Cell(28, 10, !empty($data['Obtained_Marks_VIVA']) ? substr($data['Obtained_Marks_VIVA'], 0, 28) : '', 1);
        // } else {
        //     $pdf->Cell(28, 10, !empty($data['Obtained_Marks_Practical']) ? substr($data['Obtained_Marks_Practical'], 0, 28) : '', 1);
        // }
        // $pdf->Cell(28, 10, !empty($obt_marks) ? substr($obt_marks, 0, 28) : '0', 1);
        // $pdf->Ln();

        // $y += 10;
        if (strlen($data['Subject_Name']) > 30) {
            $cellHeight = 20;
        } else {
            $cellHeight = 20;
        }
        if (strlen($data['Subject_Name']) > 30) {
            $pdf->Cell(25, $cellHeight - 10, $data['Subject_Code'], 'LB', 0, 'L');
            $nameParts = explode("\n", wordwrap($data['Subject_Name'], 30));
            $pdf->MultiCell(100, 5, $nameParts[0] . chr(10) . $nameParts[1], 'LB', 0, 0, 'L');
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            // print_r($x);
            // exit();
            $pdf->SetXY($x+125,$y-10);

            $pdf->Cell(25.3, $cellHeight - 10, $data['Max_Mark'], 'LB', 0, 'C');
            // $pdf->Cell(20, $cellHeight - 10, $data['Obtained_Marks_Theory'], 'LB', 0, 'C');
            // if (trim($data['Subject_Name']) == "VIVA") {
            //     $pdf->Cell(25, $cellHeight - 10, $data['Obtained_Marks_VIVA'], 'LB', 0, 'C');
            // } else {
            //     $pdf->Cell(25, $cellHeight - 10, $data['Obtained_Marks_Practical'], 'LB', 0, 'C');
            // }
            $pdf->Cell(25, $cellHeight - 10, $obt_marks, 'LBR', 0, 'C');
        } else {

            $pdf->Cell(25, $cellHeight - 10, $data['Subject_Code'], 'LB', 0, 'L');
            $pdf->Cell(100, $cellHeight - 10, $data['Subject_Name'], 'LB', 0, 'L');
            $pdf->Cell(25.3, $cellHeight - 10, $data['Max_Mark'], 'LB', 0, 'C');
            // $pdf->Cell(20, $cellHeight - 10, $data['Obtained_Marks_Theory'], 'LB', 0, 'C');
            // if (trim($data['Subject_Name']) == "VIVA") {
            //     $pdf->Cell(25, $cellHeight - 10, $data['Obtained_Marks_VIVA'], 'LB', 0, 'C');
            // } else {
            //     $pdf->Cell(25, $cellHeight - 10, $data['Obtained_Marks_Practical'], 'LB', 0, 'C');
            // }
            $pdf->Cell(25, $cellHeight - 10, $obt_marks, 'LBR', 0, 'C');
        }
        $pdf->Ln();
    }
    $pdf->SetXY(10, $y);
    // $pdf->Cell(96.4, 10, "Grand Total", 'LBR');
    // $pdf->Cell(20, 10, array_sum($total_max), 1);
    // $pdf->Cell(81, 10, "", 1);

    // $pdf->SetFont('Arial', '', 11);
    // $pdf->SetXY(178, 195);
    // $pdf->Write(1, array_sum($total_obtained));
    // $percentage = 0;
    // $division = "";
      $pdf->SetXY(16, 190);
    $pdf->Cell(125, 10, "Grand Total", 'LB', 0, 'C');


    $pdf->SetXY(141, 190);
    $pdf->Cell(25.3, 10, array_sum($total_max), 'LB', 0, 'C');
    

    $pdf->SetXY(166.3 ,190);
    $pdf->Cell(25, 10, array_sum($total_obtained), 'LBR', 0, 'C');
   

    
   
    $pdf->SetXY(9, 200.9);
     $disclaimer = explode("\n", wordwrap('Disclaimer: The published result is provisional only. CCVTE is not responsible for any inadvertent error that may have crept in the data / results being published online. This is being published just for the immediate information to the examinees. The final mark sheet(s) issued by CCVTE will only be treated authentic & final in this regard.', 125));
    $pdf->Cell(100, 20, $disclaimer[0]);
    $pdf->SetXY(9, 205.9);
    $pdf->Cell(100, 20, $disclaimer[1]);
    $pdf->SetXY(9, 210.9);
    $pdf->Cell(100, 20, $disclaimer[2]);

    // if ($total_max != 0) {
    //     $percentage = (array_sum($total_obtained) / array_sum($total_max)) * 100;
    // }
    $percentage = 0;
    $division = "";
    if (array_sum($total_max) != 0) {
        $percentage = (array_sum($total_obtained) / array_sum($total_max)) * 100;
    }

    if ($percentage < 40) {
        $division = "Fail";
    } elseif ($percentage >= 40 && $percentage < 50) {
        $division = "IIIrd";
    } elseif ($percentage >= 50 && $percentage < 60) {
        $division = "IInd";
    } elseif ($percentage >= 60) {
        $division = "Ist";
    } else {
        $division = "Distinction";
    }

    $pdf->SetFont('Arial', '', 14);
    $pdf->SetXY(167, 236.4);
    $pdf->Write(1, $division);

    $pdf->SetFont('Arial', '', 11);
    $pdf->SetXY(27, 267.9);
    // $pdf->Write(1, $data['Examination_Date'] . "   " . $data['Exam_Month'] . "   " . $data['Exam_Year']);

    
    $pdf->Output('I', ' Markhseet.pdf');
} else {

    $pdf = new Fpdi();
    $pdf->SetTitle('Certificate');
    $pageCount = $pdf->setSourceFile('Certificate.pdf');
    $pdf->SetFont('Arial', 'B', 11);

    $pageId = $pdf->importPage(1, PdfReader\PageBoundaries::MEDIA_BOX);
    $pdf->addPage();
    $pdf->useImportedPage($pageId, 0, 0, 210);

    $pdf->SetFont('Arial', '', 11);
    $pdf->SetXY(130, 69);
    $pdf->Write(1, $students['Candidate_Name']);

    $pdf->SetFont('Arial', '', 11);
    $pdf->SetXY(130, 75);
    $pdf->Write(1, $students['Course']);

    $pdf->SetFont('Arial', '', 11);
    $pdf->SetXY(110, 82);
    $pdf->Write(1, $students['Year']);

    $pdf->SetFont('Arial', '', 11);
    $pdf->SetXY(14, 88);
    $pdf->Write(1, $students['Center_Name']);

    foreach ($row as $data) {
        if (trim($data['Subject_Name']) == "VIVA") {
            $data['Obtained_Marks_Practical'] = $data['Obtained_Marks_VIVA'];
        }
        $obt_marks = $data['Obtained_Marks_Practical'] + $data['Obtained_Marks_Theory'];
        $total_obtained[] = $obt_marks;
        $total_max[] = isset($data['Max_Mark']) ? $data['Max_Mark'] : 0;
    }
    $percentage = 0;
    $division = "";
    if (array_sum($total_max) != 0) {
        $percentage = (array_sum($total_obtained) / array_sum($total_max)) * 100;
    }
    $pdf->SetFont('Arial', '', 11);
    $pdf->SetXY(135, 88.5);
    $pdf->Write(1, number_format($percentage, 2) . "%");

    $pdf->SetFont('Arial', '', 11);
    $pdf->SetXY(70, 96);
    $pdf->Write(1, $students['Examination_Date']);

    $pdf->SetFont('Arial', '', 11);
    $pdf->SetXY(100, 96);
    $pdf->Write(1, $students['Exam_Month']);

    $pdf->SetFont('Arial', '', 11);
    $pdf->SetXY(140, 96);
    $pdf->Write(1, $students['Exam_Year']);

    $certificate_date = strtotime($students['Certification_Date']);
    $pdf->SetXY(140, 103);
    $pdf->Write(1, date('d-F-Y', $certificate_date));
    $pdf->Output('I', ' Certificate.pdf');
}
