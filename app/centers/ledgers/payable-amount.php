<?php
if (isset($_POST['ids']) && isset($_POST['center'])) {
  require '../../../includes/db-config.php';
  require '../../../includes/helpers.php';
  session_start();

  $center = intval($_POST['center']);
  $ids = is_array($_POST['ids']) ? array_filter($_POST['ids']) : [];
  $by = $_POST['by'];

  if (empty($ids)) {
    exit(json_encode(['status' => false, 'message' => 'Please select student!']));
  }

  $invoice_no = strtoupper(uniqid('IN'));
  // echo "<pre>"; print_r($_SESSION); die;
  $balance = array();
  foreach ($ids as $id) {
    $duration = $conn->query("SELECT Duration, Added_By, Course_ID, Sub_Course_ID FROM Students WHERE ID = $id");
    $durationArr = $duration->fetch_assoc();
    $duration = $durationArr['Duration'];
    // get real amount of subcenter in center login which center has been assign to subcourses.
    if ($_SESSION['Role'] == "Center" && $_SESSION['university_id'] == 47) {
      $center_fee_Query = $conn->query("SELECT Fee FROM `Center_Sub_Courses` WHERE `User_ID` = " . $center . " AND `Course_ID` = " . $durationArr['Course_ID'] . "  AND `Sub_Course_ID` = " . $durationArr['Sub_Course_ID'] . " ");
      $centerArr = $center_fee_Query->fetch_assoc();
      $balance[] = $centerArr['Fee'];
    } elseif ($_SESSION['Role'] == "Center" && $_SESSION['university_id'] == 48) {
      $center_fee_Query = $conn->query("SELECT Fee FROM `Center_Sub_Courses` WHERE `User_ID` = " . $center . " AND `Course_ID` = " . $durationArr['Course_ID'] . "  AND `Sub_Course_ID` = " . $durationArr['Sub_Course_ID'] . " AND Duration ='$duration'");
      $centerArr = $center_fee_Query->fetch_assoc();
      $balance[] = $centerArr['Fee'];
    } else {
      $balance[] = balanceAmount($conn, $id, $duration);
    }
  }


  $amount = array_sum($balance);
  $amount = $amount < 0 ? (-1) * $amount : $amount;

  if ($_SESSION['Role'] == 'Center' || $_SESSION['Role'] == 'Sub-Center') {
    $walletAmounts = $conn->query("SELECT sum(Amount) as total_amt FROM Wallets WHERE Added_By = " . $_SESSION['ID'] . " AND Status = 1");
    $walletAmounts = $walletAmounts->fetch_assoc();
    $debited_amount = 0;
    $debit_amts = $conn->query("SELECT sum(Amount) as debit_amt FROM Wallet_Payments WHERE Added_By = " . $_SESSION['ID'] . " AND Type = 3");
    if ($debit_amts->num_rows > 0) {
      $debit_amt = $debit_amts->fetch_assoc();
      $debited_amount = $debit_amt['debit_amt'];
    }

    $walletAmount = $walletAmounts['total_amt'] - $debited_amount;

    if ($walletAmount < $amount && $by == "wallet") {
      exit(json_encode(['status' => false, 'message' => 'Wallet balance insufficient!']));
    }
  }

  echo json_encode(['status' => true, 'amount' => $amount]);
}
