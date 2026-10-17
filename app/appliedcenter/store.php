<?php
header('Content-Type: application/json');
require '../../includes/db-config.php';

// 🔹 Debug session values
file_put_contents('store-debug.txt', "SESSION DATA:\n" . print_r($_SESSION, true), FILE_APPEND);


//  Step 1: Log the incoming POST request
file_put_contents('store-debug.txt', "==== New Request: " . date("Y-m-d H:i:s") . " ====\n", FILE_APPEND);
file_put_contents('store-debug.txt', "POST DATA:\n" . print_r($_POST, true), FILE_APPEND);

//  Step 2: Validate method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    file_put_contents('store-debug.txt', "ERROR: Invalid request method\n", FILE_APPEND);
    echo json_encode(['status' => 405, 'message' => 'Invalid request method.']);
    exit;
}

// 📥 Step 3: Get and validate fields
// $name = $_POST['name'] ?? '';

// $short_name = $_POST['short_name'] ?? $name;

// $email = $_POST['email'] ?? '';
// $contact = $_POST['contact'] ?? '';
// $contact_person_name = $_POST['contact_person_name'] ?? '';
// $address = $_POST['address'] ?? '';
// $pincode = $_POST['pincode'] ?? '';
// $city = $_POST['city'] ?? '';
// $district = $_POST['district'] ?? '';
// $state = $_POST['state'] ?? '';
$name = mysqli_real_escape_string($conn, $_POST['name'] ?? '');
$short_name = $_POST['short_name'] ?? $name;
$short_name = mysqli_real_escape_string($conn, $short_name);
$address = mysqli_real_escape_string($conn, $_POST['address'] ?? '');
$email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
$contact = mysqli_real_escape_string($conn, $_POST['contact'] ?? '');
$contact_person_name = mysqli_real_escape_string($conn, $_POST['contact_person_name'] ?? '');
$pincode = mysqli_real_escape_string($conn, $_POST['pincode'] ?? '');
$city = mysqli_real_escape_string($conn, $_POST['city'] ?? '');
$district = mysqli_real_escape_string($conn, $_POST['district'] ?? '');
$state = mysqli_real_escape_string($conn, $_POST['state'] ?? '');

$user_type = $_POST['user_type'] ?? 1;
$vertical_type = $_POST['vertical_type'] ?? 'Vocational';
$form_type = isset($_POST['status']) ? intval($_POST['status']) : 0;
$form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;




if (empty($name) || empty($email) || empty($contact)) {
    file_put_contents('store-debug.txt', "ERROR: Missing required fields\n", FILE_APPEND);
    echo json_encode(['status' => 400, 'message' => 'Missing required fields.']);
    exit;
}

// 🔢 Step 4: Generate Code
$code_prefix = 'CCVTE';
$res = $conn->query("SELECT Code FROM Users WHERE Role = 'Center' ORDER BY ID DESC LIMIT 1");
if ($res && $res->num_rows > 0) {
    $lastCode = $res->fetch_assoc()['Code'];
    $number = intval(preg_replace('/\D/', '', $lastCode)) + 1;
} else {
    $number = 1;
}
$code = $code_prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
file_put_contents('store-debug.txt', "Generated Code: $code\n", FILE_APPEND);

//  Step 5: Prepare and run insert query
$query = "
INSERT INTO Users (
    Name, Short_Name, Contact_Name, Code, Email, Mobile, Address, Pincode, City, District, State,
    Password, Photo, Role, Designation, Is_Unique, Created_By, B2B_Partner, Vertical_type,form_type,form_id
) VALUES (
    '$name', '$short_name', '$contact_person_name', '$code', '$email', '$contact', '$address', '$pincode',
    '$city', '$district', '$state', AES_ENCRYPT('12345','60ZpqkOnqn0UQQ2MYTlJ'),
    '/assets/img/default-user.png', 'Center', 'Center', 1, 1, '$user_type', '$vertical_type','$form_type', '$form_id'
)";
file_put_contents('store-debug.txt', "QUERY:\n$query\n", FILE_APPEND);

//  Step 6: Execute query and fetch result
$insert = $conn->query($query);
if ($insert) {
    file_put_contents('store-debug.txt', "SUCCESS: Inserted successfully\n", FILE_APPEND);

    // Fetch the inserted user's code and decrypted password
    $userRes = $conn->query("SELECT Code, AES_DECRYPT(Password, '60ZpqkOnqn0UQQ2MYTlJ') AS DecryptedPassword 
                             FROM Users 
                             WHERE Email = '$email' 
                             ORDER BY ID DESC LIMIT 1");

    if ($userRes && $userRes->num_rows > 0) {
        $userData = $userRes->fetch_assoc();
        $generatedCode = $userData['Code'];
        $plainPassword = $userData['DecryptedPassword'];

        file_put_contents('store-debug.txt', "Generated Code & Password Returned\n", FILE_APPEND);

        echo json_encode([
            'status' => 200,
            'message' => 'Center added successfully.',
            'code'    => $generatedCode,
            'password' => $plainPassword
        ]);
    } else {
        file_put_contents('store-debug.txt', "ERROR: Unable to fetch inserted user\n", FILE_APPEND);
        echo json_encode(['status' => 500, 'message' => 'Inserted, but failed to fetch code and password.']);
    }
} else {
    file_put_contents('store-debug.txt', "ERROR: " . $conn->error . "\n", FILE_APPEND);
    echo json_encode(['status' => 500, 'message' => 'Failed to insert center.']);
}
