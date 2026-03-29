<?php
session_start();
include 'db.php';

// Load PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-7.0.2/src/Exception.php';
require 'PHPMailer-7.0.2/src/PHPMailer.php';
require 'PHPMailer-7.0.2/src/SMTP.php';

header('Content-Type: application/json');

if (!isset($_POST['email'])) {
    echo json_encode(['success' => false, 'message' => 'Email address is required.']);
    exit();
}

$email = trim($_POST['email']);
$phone = $conn->real_escape_string(trim($_POST['phone'] ?? ''));
$role  = $conn->real_escape_string(trim($_POST['role'] ?? ''));

// Validate email format 
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => '❌ Invalid email format. Please enter a valid email address (e.g. name@gmail.com).']);
    exit();
}

$email_safe = $conn->real_escape_string($email);

// Check if email already registered 
$exists = $conn->query("SELECT id FROM users WHERE email='$email_safe' LIMIT 1");
if ($exists && $exists->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => '❌ This email address is already registered. Please login or use a different email.']);
    exit();
}

//  Validate phone 
if (!preg_match('/^0[0-9]{9}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => '❌ Invalid phone number. Must be 10 digits starting with 0 (e.g. 0771234567).']);
    exit();
}

//  Generate OTP 
$otp_code       = strval(rand(100000, 999999));
$generated_time = date('Y-m-d H:i:s');
$expiry_time    = date('Y-m-d H:i:s', strtotime('+10 minutes'));

// Mark previous pending OTPs as expired
$conn->query("UPDATE otp_verification 
              SET verification_status='expired' 
              WHERE email='$email_safe' AND verification_status='pending'");

// Insert new OTP
$conn->query("INSERT INTO otp_verification 
                  (phone, email, otp_code, role, generated_time, expiry_time, verification_status)
              VALUES 
                  ('$phone','$email_safe','$otp_code','$role','$generated_time','$expiry_time','pending')");

//  Send OTP email via Gmail SMTP 
$mail = new PHPMailer(true);

try {
    // SMTP Settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'himashisamadhi0709@gmail.com';
    $mail->Password   = 'rzny pzpy bpex gpkl';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Sender & Recipient
    $mail->setFrom('himashisamadhi0709@gmail.com', 'ManningHub');
    $mail->addAddress($email);

    // Email content
    $mail->isHTML(true);
    $mail->Subject = 'ManningHub — Your OTP Verification Code';
    $mail->Body    = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; background: #f4f7f6; margin: 0; padding: 0; }
            .container { max-width: 500px; margin: 40px auto; background: white;
                         border-radius: 16px; overflow: hidden;
                         box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #1a252f, #27ae60);
                      padding: 30px; text-align: center; }
            .header img { height: 60px; }
            .header h1 { color: white; margin: 12px 0 0; font-size: 22px; }
            .body { padding: 35px 40px; text-align: center; }
            .body p { color: #555; font-size: 15px; line-height: 1.7; }
            .otp-box { background: #f0faf4; border: 2px dashed #27ae60;
                       border-radius: 12px; padding: 20px; margin: 25px 0; }
            .otp-code { font-size: 42px; font-weight: 900; color: #27ae60;
                        letter-spacing: 10px; }
            .otp-label { font-size: 12px; color: #888; margin-top: 6px; }
            .warning { background: #fff9e6; border-left: 4px solid #f39c12;
                       border-radius: 8px; padding: 12px 16px; margin-top: 20px;
                       font-size: 13px; color: #856404; text-align: left; }
            .footer { background: #f8f9fa; padding: 20px; text-align: center;
                      font-size: 12px; color: #aaa; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1> ManningHub</h1>
                <p style="color:rgba(255,255,255,0.8); font-size:13px; margin:4px 0 0;">
                    Smart Manning Market Management System
                </p>
            </div>
            <div class="body">
                <p>Hello! You requested an OTP to register on <strong>ManningHub</strong>.<br>
                   Use the code below to complete your registration.</p>
                <div class="otp-box">
                    <div class="otp-code">' . $otp_code . '</div>
                    <div class="otp-label">Your One-Time Password</div>
                </div>
                <div class="warning">
                    ⏰ <strong>This OTP expires in 10 minutes.</strong><br>
                    Do not share this code with anyone.<br>
                    If you did not request this, please ignore this email.
                </div>
            </div>
            <div class="footer">
                © 2026 ManningHub — Smart Manning Market Management System<br>
                This is an automated message. Please do not reply.
            </div>
        </div>
    </body>
    </html>';

    // Plain text fallback
    $mail->AltBody = "Your ManningHub OTP code is: $otp_code\nThis code expires in 10 minutes.\nDo not share this with anyone.";

    $mail->send();

    // Save to session as backup
    $_SESSION['otp_code']  = $otp_code;
    $_SESSION['otp_phone'] = $phone;
    $_SESSION['otp_email'] = $email;
    $_SESSION['otp_role']  = $role;

    // Auto-assign stall number for vendors
    $stall_number = '';
    if ($role === 'vendor') {
        $stall_number = 'ST' . rand(100, 999);
        $_SESSION['stall_number'] = $stall_number;
    }

    echo json_encode([
        'success'      => true,
        'message'      => '✅ OTP sent successfully! Please check your email inbox (and spam folder). Valid for 10 minutes.',
        'stall_number' => $stall_number
    ]);

} catch (Exception $e) {
    // Email failed — give helpful error
    echo json_encode([
        'success' => false,
        'message' => '❌ Could not send OTP email. Please check your email address and try again. (Error: ' . $mail->ErrorInfo . ')'
    ]);
}
?>
