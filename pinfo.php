<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require 'includes/db-config.php';
require 'includes/helpers.php';
session_start();
$rrs = $conn->query("SELECT Re_Registrations.*, IF(Payment_From='Wallet', Wallet_Payments.Transaction_ID, Payments.Transaction_ID) as Transaction_ID, IF(Payment_From='Wallet', Wallet_Payments.Added_By, Payments.Added_By) as Added_By FROM Re_Registrations LEFT JOIN Wallet_Payments ON Re_Registrations.Payment_ID = Wallet_Payments.ID LEFT JOIN Payments ON Re_Registrations.Payment_ID = Payments.ID WHERE Re_Registrations.Status = 1");
while ($rr = $rrs->fetch_assoc()) {
  generateStudentLedger($conn, $rr['Student_ID']);

  $addedBy = $rr['Added_By'];
  $user = $conn->query("SELECT Role FROM Users WHERE ID = $addedBy");
  $user = $user->fetch_assoc();
  $userRole = $user['Role'];

  $check = $conn->query("SELECT ID FROM Student_Ledgers WHERE Student_ID = " . $rr['Student_ID'] . " AND Duration = " . $rr['Duration'] . " AND Type = 3 AND Transaction_ID =  '" . $rr['Transaction_ID'] . "'");
  if ($check->num_rows == 0) {
    $conn->query("INSERT INTO Student_Ledgers (`Student_ID`, `Duration`, `Date`, `University_ID`, `Type`, `Source`, `Fee`, `Status`, `Created_At`, `Transaction_ID`) VALUES (" . $rr['Student_ID'] . ", " . $rr['Duration'] . ", '" . date("Y-m-d", strtotime($rr['Created_At'])) . "', " . $rr['University_ID'] . ", 3, '" . $rr['Payment_From'] . "', '" . json_encode(['Paid' => $rr['Amount']]) . "', 1, '" . $rr['Created_At'] . "', '" . $rr['Transaction_ID'] . "')");
  }

  $checkInInvoice = $conn->query("SELECT ID FROM Wallet_Invoices WHERE Invoice_No = '" . $rr['Transaction_ID'] . "' AND Student_ID = " . $rr['Student_ID']);
  if ($checkInInvoice->num_rows == 0) {
    $add = $conn->query("INSERT INTO Wallet_Invoices (`Invoice_No`, `User_ID`, `Student_ID`, `Duration`, `University_ID`, `Amount`) VALUES ('" . $rr['Transaction_ID'] . "', " . $rr['Added_By'] . ", " . $rr['Student_ID'] . ", " . $rr['Duration'] . ", " . $_SESSION['university_id'] . ", " . $rr['Amount'] . ")");
  }

  if ($userRole == 'Sub-Center') {
    $center = $conn->query("SELECT Center FROM Center_SubCenter WHERE Sub_Center = " . $rr['Added_By']);
    if ($center->num_rows == 0) {
      continue;
    }

    $center = $center->fetch_assoc();

    $student = $conn->query("SELECT Course_ID,Sub_Course_ID, University_ID FROM Students WHERE ID = " . $rr['Student_ID']);
    $student = $student->fetch_assoc();

    $courseId = $student['Course_ID'];
    $subCourseId = $student['Sub_Course_ID'];
    $universityId = $student['University_ID'];
    $fee = $conn->query("SELECT Fee FROM Center_Sub_Courses WHERE User_ID = " . $center['Center'] . " AND Course_ID = $courseId AND Sub_Course_ID = $subCourseId AND University_ID = $universityId");
    if ($fee->num_rows == 0) {
        echo $center['Center'] . " AND Course_ID = $courseId AND Sub_Course_ID = $subCourseId AND University_ID = $universityId<br>";
        continue;
    }
    $fee = $fee->fetch_assoc();

    // Add Credit to Center
    $creditAmount = $rr['Amount'] - $fee['Fee'];
    $check = $conn->query("SELECT ID FROM Wallets WHERE Transaction_ID = '" . $rr['Transaction_ID'] . "'");
    if ($check->num_rows == 0) {
      $add = $conn->query("INSERT INTO Wallets (`Type`, `Transaction_ID`, `Gateway_ID`, `Bank`, `Payment_Mode`, `Amount`, `Added_By`, `Source`, `Status`, `University_ID`) VALUES (1, '" . $rr['Transaction_ID'] . "', '" . $rr['Transaction_ID'] . "', 'Wallet', 'Settelment By Sub-Center', '$creditAmount', '" . $center['Center'] . "', 'Re-Reg', 1, " . $universityId . ")");
      if(!$add){
          echo mysqli_error($conn).'<br>';
      }
    }else{
        $check = $check->fetch_assoc();
        echo json_encode($check).'<br>';
    }
  }
}
