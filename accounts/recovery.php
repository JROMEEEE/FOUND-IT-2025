<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include '../dbconnect.php';
include '../apikeys.php';
$database = new Database();
$conn = $database->getConnect();

if (!$conn) {
    die("Database connection failed.");
}

// SESSION TIMEOUT: 1 hour
$session_lifetime = 3600;

// Redirect if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] < $session_lifetime)) {
    header("Location: ../dashboard/user_dashboard.php");
    exit;
}

$error = '';
$success = '';
$showOtpModal = false;
$showEmailOtpModal = false;
$censoredNumber = '';
$censoredEmail = '';
$recoveryMethod = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // SEND OTP VIA SMS
    if (isset($_POST['send_otp_sms'])) {
        $identifier = trim($_POST["email"]);

        if (empty($identifier)) {
            $error = "Please fill in your email or SR code.";
        } else {
            try {
                $stmt = $conn->prepare("SELECT user_id, user_name, email, sr_code, contact_no, is_admin, is_approved 
                                        FROM users_table 
                                        WHERE (email = ? OR sr_code = ?) AND is_approved = 1 
                                        LIMIT 1");
                $stmt->execute([$identifier, $identifier]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    $contact = $user['contact_no'];

                    if (empty($contact)) {
                        $error = "No phone number found. Please use email recovery instead.";
                    } else {
                        // Censor phone number
                        $len = strlen($contact);
                        if ($len > 6) {
                            $censoredNumber = substr($contact, 0, 4) . str_repeat('*', $len - 6) . substr($contact, -2);
                        } else {
                            $censoredNumber = str_repeat('*', $len);
                        }

                        // Send OTP via iProgSMS
                        $data = [
                            'api_token' => IPROG_API_TOKEN,
                            'phone_number' => $contact
                        ];

                        $ch = curl_init('https://sms.iprogtech.com/api/v1/otp/send_otp');
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
                        $response = curl_exec($ch);
                        curl_close($ch);

                        $res = json_decode($response, true);
                        if ($res && isset($res['status']) && $res['status'] === 'success') {
                            $_SESSION['otp_user_id'] = $user['user_id'];
                            $_SESSION['otp_contact_no'] = $contact;
                            $_SESSION['recovery_method'] = 'sms';
                            $_SESSION['censored_number'] = $censoredNumber;
                            
                            $showOtpModal = true;
                            $recoveryMethod = 'sms';
                            $success = "OTP sent to your phone number ending with " . substr($contact, -2);
                        } else {
                            $error = $res['message'] ?? 'Failed to send OTP.';
                        }
                    }
                } else {
                    $error = "No approved account found with that email or SR code.";
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }

    // SEND OTP VIA EMAIL
    if (isset($_POST['send_otp_email'])) {
        $identifier = trim($_POST["email"]);

        if (empty($identifier)) {
            $error = "Please fill in your email or SR code.";
        } else {
            try {
                $stmt = $conn->prepare("SELECT user_id, user_name, email, sr_code, contact_no, is_admin, is_approved 
                                        FROM users_table 
                                        WHERE (email = ? OR sr_code = ?) AND is_approved = 1 
                                        LIMIT 1");
                $stmt->execute([$identifier, $identifier]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    $userEmail = $user['email'];

                    if (empty($userEmail)) {
                        $error = "No email found. Please contact support.";
                    } else {
                        // GENERATE 6 DIGIT OTP
                        $otp = sprintf("%06d", mt_rand(1, 999999));
                        
                        // STORE OTP IN SESSION 10m EXP
                        $_SESSION['email_otp'] = $otp;
                        $_SESSION['email_otp_expiry'] = time() + 600;
                        $_SESSION['otp_user_id'] = $user['user_id'];
                        $_SESSION['otp_email'] = $userEmail;
                        $_SESSION['recovery_method'] = 'email';

                        $emailParts = explode('@', $userEmail);
                        $username = $emailParts[0];
                        $domain = $emailParts[1];
                        if (strlen($username) > 3) {
                            $censoredEmail = substr($username, 0, 2) . str_repeat('*', strlen($username) - 3) . substr($username, -1) . '@' . $domain;
                        } else {
                            $censoredEmail = str_repeat('*', strlen($username)) . '@' . $domain;
                        }
                        $_SESSION['censored_email'] = $censoredEmail;

                        // PHPMailer
                        $phpmailer_loaded = false;
                        
                        if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
                            require_once __DIR__ . '/../vendor/autoload.php';
                            $phpmailer_loaded = true;
                        } elseif (file_exists(__DIR__ . '/../src/PHPMailer.php')) {
                            require_once __DIR__ . '/../src/Exception.php';
                            require_once __DIR__ . '/../src/PHPMailer.php';
                            require_once __DIR__ . '/../src/SMTP.php';
                            $phpmailer_loaded = true;
                        }
                        
                        if (!$phpmailer_loaded) {
                            $error = "PHPMailer not installed. Please install via Composer or download PHPMailer manually.";
                        } else {
                            $mail = new PHPMailer(true);

                            try {
                                $mail->isSMTP();
                                $mail->Host = 'smtp.gmail.com';
                                $mail->SMTPAuth = true;
                                $mail->Username = 'founditsys@gmail.com';
                                $mail->Password = APP_PASSWORD;
                                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                                $mail->Port = 587;

                                $mail->setFrom('founditsys@gmail.com', 'FOUND-IT System');
                                $mail->addAddress($userEmail, $user['user_name']);

                                $mail->isHTML(true);
                                $mail->Subject = 'Password Reset OTP - FOUND-IT';
                                $mail->Body = '
                                    <html>
                                    <head>
                                        <style>
                                            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                                            .container { max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9; }
                                            .content { background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                                            .header { text-align: center; margin-bottom: 30px; }
                                            .otp-box { background-color: #dc3545; color: white; font-size: 32px; font-weight: bold; text-align: center; padding: 20px; border-radius: 8px; letter-spacing: 8px; margin: 20px 0; }
                                            .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
                                            .warning { background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
                                        </style>
                                    </head>
                                    <body>
                                        <div class="container">
                                            <div class="content">
                                                <div class="header">
                                                    <h1 style="color: #dc3545; margin: 0;">FOUND-IT</h1>
                                                    <p style="color: #666; margin: 5px 0;">Password Reset Request</p>
                                                </div>
                                                
                                                <p>Hello <strong>' . htmlspecialchars($user['user_name']) . '</strong>,</p>
                                                
                                                <p>We received a request to reset your password. Use the OTP code below to proceed:</p>
                                                
                                                <div class="otp-box">' . $otp . '</div>
                                                
                                                <div class="warning">
                                                    <strong>⚠️ Important:</strong><br>
                                                    • This OTP will expire in 10 minutes<br>
                                                    • Do not share this code with anyone<br>
                                                    • If you did not request this, please ignore this email
                                                </div>
                                                
                                                <p>Request Details:</p>
                                                <ul>
                                                    <li><strong>Time:</strong> ' . date('F j, Y g:i A') . '</li>
                                                    <li><strong>IP Address:</strong> ' . $_SERVER['REMOTE_ADDR'] . '</li>
                                                </ul>
                                                
                                                <div class="footer">
                                                    <p>This is an automated email from FOUND-IT System. Please do not reply.</p>
                                                    <p>&copy; ' . date('Y') . ' FOUND-IT. All rights reserved.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </body>
                                    </html>
                                ';
                                $mail->AltBody = "Your FOUND-IT password reset OTP is: $otp\n\nThis code will expire in 10 minutes.\nDo not share this code with anyone.";

                                $mail->send();
                                
                                $showEmailOtpModal = true;
                                $recoveryMethod = 'email';
                                $success = "OTP sent to your email: " . $censoredEmail;

                            } catch (Exception $e) {
                                $error = "Failed to send email. Error: {$mail->ErrorInfo}";
                            }
                        }
                    }
                } else {
                    $error = "No approved account found with that email or SR code.";
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }

    // VERIFY SMS OTP & CHANGE PASSWORD
    if (isset($_POST['verify_otp_sms'])) {
        $inputOtp = trim($_POST['otp']);
        $newPassword = trim($_POST['new_password']);
        $confirmPassword = trim($_POST['confirm_password']);

        if (!isset($_SESSION['otp_user_id']) || !isset($_SESSION['otp_contact_no'])) {
            $error = "OTP session expired. Please request a new OTP.";
        } elseif (empty($newPassword) || empty($confirmPassword)) {
            $error = "Please fill in all password fields.";
            $showOtpModal = true;
            $recoveryMethod = 'sms';
            $censoredNumber = $_SESSION['censored_number'] ?? '';
        } elseif ($newPassword !== $confirmPassword) {
            $error = "Passwords do not match.";
            $showOtpModal = true;
            $recoveryMethod = 'sms';
            $censoredNumber = $_SESSION['censored_number'] ?? '';
        } elseif (strlen($newPassword) < 6) {
            $error = "Password must be at least 6 characters long.";
            $showOtpModal = true;
            $recoveryMethod = 'sms';
            $censoredNumber = $_SESSION['censored_number'] ?? '';
        } else {
            // SMS OTP VERIFICATION
            $data = [
                'api_token' => IPROG_API_TOKEN,
                'phone_number' => $_SESSION['otp_contact_no'],
                'otp' => $inputOtp
            ];

            $ch = curl_init('https://sms.iprogtech.com/api/v1/otp/verify_otp');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
            $response = curl_exec($ch);
            curl_close($ch);

            $res = json_decode($response, true);

            if ($res && isset($res['status']) && $res['status'] === 'success') {
              // UPDATE PASSWORD
                $userId = $_SESSION['otp_user_id'];
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("UPDATE users_table SET password = ? WHERE user_id = ?");
                $stmt->execute([$hashedPassword, $userId]);

                // CLEAR OTP SESSION
                unset($_SESSION['otp_user_id'], $_SESSION['otp_contact_no'], $_SESSION['recovery_method'], $_SESSION['censored_number']);

                $success = "Password changed successfully! You can now login.";
            } else {
                $error = $res['message'] ?? 'OTP verification failed.';
                $showOtpModal = true;
                $recoveryMethod = 'sms';
                $censoredNumber = $_SESSION['censored_number'] ?? '';
            }
        }
    }

    // VERIFY EMAIL OTP & CHANGE PASSWORD
    if (isset($_POST['verify_otp_email'])) {
        $inputOtp = trim($_POST['otp']);
        $newPassword = trim($_POST['new_password']);
        $confirmPassword = trim($_POST['confirm_password']);

        if (!isset($_SESSION['otp_user_id']) || !isset($_SESSION['email_otp'])) {
            $error = "OTP session expired. Please request a new OTP.";
        } elseif (time() > $_SESSION['email_otp_expiry']) {
            $error = "OTP has expired. Please request a new one.";
            unset($_SESSION['email_otp'], $_SESSION['email_otp_expiry']);
        } elseif (empty($newPassword) || empty($confirmPassword)) {
            $error = "Please fill in all password fields.";
            $showEmailOtpModal = true;
            $recoveryMethod = 'email';
            $censoredEmail = $_SESSION['censored_email'] ?? '';
        } elseif ($newPassword !== $confirmPassword) {
            $error = "Passwords do not match.";
            $showEmailOtpModal = true;
            $recoveryMethod = 'email';
            $censoredEmail = $_SESSION['censored_email'] ?? '';
        } elseif (strlen($newPassword) < 6) {
            $error = "Password must be at least 6 characters long.";
            $showEmailOtpModal = true;
            $recoveryMethod = 'email';
            $censoredEmail = $_SESSION['censored_email'] ?? '';
        } elseif ($inputOtp !== $_SESSION['email_otp']) {
            $error = "Invalid OTP. Please try again.";
            $showEmailOtpModal = true;
            $recoveryMethod = 'email';
            $censoredEmail = $_SESSION['censored_email'] ?? '';
        } else {
            $userId = $_SESSION['otp_user_id'];
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("UPDATE users_table SET password = ? WHERE user_id = ?");
            $stmt->execute([$hashedPassword, $userId]);

            unset($_SESSION['otp_user_id'], $_SESSION['otp_email'], $_SESSION['email_otp'], $_SESSION['email_otp_expiry'], $_SESSION['recovery_method'], $_SESSION['censored_email']);

            $success = "Password changed successfully! You can now login.";
        }
    }
}

if (!$showOtpModal && !$showEmailOtpModal && isset($_SESSION['recovery_method'])) {
    $recoveryMethod = $_SESSION['recovery_method'];
    if ($recoveryMethod === 'sms' && isset($_SESSION['censored_number'])) {
        $showOtpModal = true;
        $censoredNumber = $_SESSION['censored_number'];
    } elseif ($recoveryMethod === 'email' && isset($_SESSION['censored_email'])) {
        $showEmailOtpModal = true;
        $censoredEmail = $_SESSION['censored_email'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>FOUND-IT | Forgot Password</title>
  <?php include '../imports.php'; ?>
  <link rel="stylesheet" href="../css/account.css">
</head>
<body class="login-page"> 

<div class="login-wrapper">
  <div class="left-panel"></div>

  <div class="right-panel">
    <h2>Forgot Password</h2>
    <p class="text-muted mb-4">Choose your recovery method</p>

    <?php if (!empty($error) && !$showOtpModal && !$showEmailOtpModal): ?>
        <div class="alert alert-danger text-center"><?php echo htmlspecialchars($error); ?></div>
    <?php elseif (!empty($success)): ?>
        <div class="alert alert-success text-center"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?> 

    <form method="POST" action="">
      <div class="mb-3">
        <label for="email" class="form-label fw-semibold">Email address or SR Code</label>
        <input type="text" class="form-control" id="email" name="email" placeholder="Enter your email or SR code" required>
      </div>

      <div class="row g-2">
        <div class="col-md-6">
          <button type="submit" name="send_otp_sms" class="btn btn-danger w-100 fw-semibold">
            <i class="bi bi-phone"></i> Send OTP via SMS
          </button>
        </div>
        <div class="col-md-6">
          <button type="submit" name="send_otp_email" class="btn btn-outline-danger w-100 fw-semibold">
            <i class="bi bi-envelope"></i> Send OTP via Email
          </button>
        </div>
      </div>

      <div class="text-center text-muted mt-4">
        Don't have an account? <a href="register.php">Register now</a>
      </div>

      <div class="mt-3 text-center">
        <a href="login.php" class="text-secondary small text-decoration-none">
          <i class="bi bi-arrow-left"></i> Back to Login
        </a>
      </div>
    </form>
  </div>
</div>

<!-- SMS OTP Modal -->
<div class="modal fade" id="otpModalSms" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-phone"></i> SMS OTP Verification</h5>
      </div>
      <form method="POST">
        <div class="modal-body">
          <?php if (!empty($error) && $showOtpModal): ?>
              <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
          <?php endif; ?>
          
          <?php if (!empty($censoredNumber)): ?>
              <p class="text-muted text-center">OTP sent to: <strong><?php echo htmlspecialchars($censoredNumber); ?></strong></p>
          <?php endif; ?>
          
          <div class="mb-3">
            <label for="otp_sms" class="form-label">Enter OTP</label>
            <input type="text" class="form-control" id="otp_sms" name="otp" placeholder="6-digit code" maxlength="6" required>
          </div>
          <div class="mb-3">
            <label for="new_password_sms" class="form-label">New Password</label>
            <input type="password" class="form-control" id="new_password_sms" name="new_password" minlength="6" required>
            <small class="text-muted">Minimum 6 characters</small>
          </div>
          <div class="mb-3">
            <label for="confirm_password_sms" class="form-label">Confirm Password</label>
            <input type="password" class="form-control" id="confirm_password_sms" name="confirm_password" minlength="6" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="verify_otp_sms" class="btn btn-danger w-100 fw-semibold">Verify & Change Password</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Email OTP Modal -->
<div class="modal fade" id="otpModalEmail" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-envelope"></i> Email OTP Verification</h5>
      </div>
      <form method="POST">
        <div class="modal-body">
          <?php if (!empty($error) && $showEmailOtpModal): ?>
              <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
          <?php endif; ?>
          
          <?php if (!empty($censoredEmail)): ?>
              <p class="text-muted text-center">OTP sent to: <strong><?php echo htmlspecialchars($censoredEmail); ?></strong></p>
              <div class="alert alert-info small">
                <i class="bi bi-info-circle"></i> Check your email inbox (and spam folder). OTP expires in 10 minutes.
              </div>
          <?php endif; ?>
          
          <div class="mb-3">
            <label for="otp_email" class="form-label">Enter OTP</label>
            <input type="text" class="form-control" id="otp_email" name="otp" placeholder="6-digit code" maxlength="6" required>
          </div>
          <div class="mb-3">
            <label for="new_password_email" class="form-label">New Password</label>
            <input type="password" class="form-control" id="new_password_email" name="new_password" minlength="6" required>
            <small class="text-muted">Minimum 6 characters</small>
          </div>
          <div class="mb-3">
            <label for="confirm_password_email" class="form-label">Confirm Password</label>
            <input type="password" class="form-control" id="confirm_password_email" name="confirm_password" minlength="6" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="verify_otp_email" class="btn btn-danger w-100 fw-semibold">Verify & Change Password</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    <?php if ($showOtpModal && $recoveryMethod === 'sms'): ?>
      var otpModalSms = new bootstrap.Modal(document.getElementById('otpModalSms'), {
        backdrop: 'static',
        keyboard: false
      });
      otpModalSms.show();
    <?php endif; ?>
    
    <?php if ($showEmailOtpModal && $recoveryMethod === 'email'): ?>
      var otpModalEmail = new bootstrap.Modal(document.getElementById('otpModalEmail'), {
        backdrop: 'static',
        keyboard: false
      });
      otpModalEmail.show();
    <?php endif; ?>
  });
</script>

</body>
</html>