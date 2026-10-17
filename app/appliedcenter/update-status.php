if ($status === 1) {
    // Fetch all required details from applications_form
    $centerResult = $conn->query("SELECT * FROM applications_form WHERE ID = $id");
    $centerData = $centerResult->fetch_assoc();

    if (!$centerData) {
        echo json_encode(['status' => 404, 'message' => 'Center data not found for creating user.']);
        exit;
    }

    // Prepare required fields
    $name = mysqli_real_escape_string($conn, $centerData['Institution_Name']);
    $short_name = mysqli_real_escape_string($conn, $centerData['Short_Name'] ?? $name);
    $contact_person_name = mysqli_real_escape_string($conn, $centerData['Director_Name']);
    $email = mysqli_real_escape_string($conn, $centerData['Institution_Email']);
    $contact = mysqli_real_escape_string($conn, $centerData['Institution_Mobile']);
    $address = mysqli_real_escape_string($conn, $centerData['Address'] ?? '');
    $pincode = mysqli_real_escape_string($conn, $centerData['Pincode'] ?? '');
    $city = mysqli_real_escape_string($conn, $centerData['City'] ?? '');
    $district = mysqli_real_escape_string($conn, $centerData['District'] ?? '');
    $state = mysqli_real_escape_string($conn, $centerData['State'] ?? '');
    $alternate_contact = ''; // Default if not available
    $photo = '/assets/img/default-user.png';
    $vertical_type = 'Vocational'; // Example default; change if stored elsewhere
    $code_prefix = 'CC'; // Example prefix (you can use university-based logic)

    // Generate Unique Code
    $checkCode = $conn->query("SELECT Code FROM Users WHERE Role = 'Center' ORDER BY ID DESC LIMIT 1");
    if ($checkCode->num_rows > 0) {
        $last = mysqli_fetch_assoc($checkCode);
        $lastNum = intval(preg_replace('/\D/', '', $last['Code']));
        $code = $code_prefix . str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $code = $code_prefix . '0001';
    }

    // Check duplicates
    $checkEmail = $conn->query("SELECT ID FROM Users WHERE Email = '$email'");
    if ($checkEmail->num_rows > 0) {
        echo json_encode(['status' => 409, 'message' => 'Center already exists in Users table.']);
        exit;
    }

    // Insert into Users
    $insert = $conn->query("INSERT INTO `Users` (`Name`, `Short_Name`, `Contact_Name`, `Code`, `Email`, `Mobile`, `Alternate_Mobile`, `Address`, `Pincode`, `City`, `District`, `State`, `Password`, `Photo`, `Role`, `Designation`, `Is_Unique`, `Created_By`, `B2B_Partner`, `Vertical_type`)
    VALUES (
        '$name', '$short_name', '$contact_person_name', '$code', '$email', '$contact', '$alternate_contact', '$address',
        '$pincode', '$city', '$district', '$state',
        AES_ENCRYPT('12345','60ZpqkOnqn0UQQ2MYTlJ'), '$photo',
        'Center', 'Center', 0, 1, 1, '$vertical_type')");

    if (!$insert) {
        echo json_encode(['status' => 500, 'message' => 'User creation failed after approval.']);
        exit;
    }
}
