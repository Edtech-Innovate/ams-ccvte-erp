<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../includes/db-config.php';
require '../../vendor/autoload.php';


header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $email = $_POST['email'] ?? '';
    $name = $_POST['name'] ?? '';
    $password = $_POST['password'] ?? '';
    $code = $_POST['code'] ?? '';


    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 400, 'message' => 'Invalid email address.']);
        exit;
    }

    // ✅ Your actual login URL
    $loginUrl = "https://ams.ccvte.org/";

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'noreply@ccvte.org';
        $mail->Password   = 'cgem gcsz sjcg piye';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('noreply@ccvte.org', 'CCVTE Team');
        $mail->addAddress($email, $name);
        $mail->addReplyTo('support@ccvte.org', 'CCVTE Support');

        $mail->isHTML(true);
        $mail->Subject = "Welcome to CCVTE - Login Details Inside";

        $mail->Body = '
        <div style="font-family: Arial, sans-serif; padding: 20px; background: #f9f9f9;">
            <div style="max-width: 600px; margin: auto; background: #fff; border-radius: 8px; padding: 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <h2 style="color: #333;">Hi ' . ($name) . ', 👋</h2>
                <p>Welcome to CCVTE! Below are your login credentials:</p>
                <ul style="line-height: 1.8; color: #555;">
                    <li><strong>Email:</strong> ' . ($email) . '</li>
                    <li><strong>Username:</strong> ' . ($code) . '</li>
                    <li><strong>Password:</strong> ' . ($password) . '</li>
                </ul>
                <p style="margin-top: 20px;">Click the button below to login:</p>
                <a href="' . $loginUrl . '" style="display: inline-block; background-color: #28a745; color: #fff; padding: 12px 24px; border-radius: 5px; text-decoration: none; font-weight: bold;">
                    Login Now
                </a>
                <br><br>
                <p>Regards,<br><strong>CCVTE Team</strong></p>
            </div>
        </div>';

        $mail->AltBody = "Hi $name,\nWelcome to CCVTE!\nLogin Details:\nEmail: $email\nPassword: $password\nLogin here: $loginUrl";

        $mail->send();
        echo json_encode(['status' => 200, 'message' => "Welcome email sent to $email."]);
    } catch (Exception $e) {
        echo json_encode(['status' => 500, 'message' => 'Mailer Error: ' . $mail->ErrorInfo]);
    }
} else {
    echo json_encode(['status' => 405, 'message' => 'Invalid request method.']);
}
