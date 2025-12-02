<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
$censoredNumber = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // SEND OTP
    if (isset($_POST['send_otp'])) {
        $identifier = trim($_POST["email"]); // email or SR code

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

                    // Censor phone number (show first 4 digits + **** + last 2 digits)
                    $len = strlen($contact);
                    if ($len > 6) {
                        $censoredNumber = substr($contact, 0, 4) . str_repeat('*', $len - 6) . substr($contact, -2);
                    } else {
                        $censoredNumber = str_repeat('*', $len);
                    }

                    // Prepare OTP request (iProgSMS OTP API)
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
                        $showOtpModal = true;
                        $_SESSION['otp_user_id'] = $user['user_id'];
                        $_SESSION['otp_contact_no'] = $contact;
                        $success = "OTP sent to your phone number ending with " . substr($contact, -2);
                    } else {
                        $error = $res['message'] ?? 'Failed to send OTP.';
                    }

                } else {
                    $error = "No approved account found with that email or SR code.";
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }

    // VERIFY OTP & CHANGE PASSWORD
    if (isset($_POST['verify_otp'])) {
        $inputOtp = trim($_POST['otp']);
        $newPassword = trim($_POST['new_password']);
        $confirmPassword = trim($_POST['confirm_password']);

        if (!isset($_SESSION['otp_user_id']) || !isset($_SESSION['otp_contact_no'])) {
            $error = "OTP session expired. Please request a new OTP.";
        } elseif ($newPassword !== $confirmPassword) {
            $error = "Passwords do not match.";
            $showOtpModal = true;
        } else {
            // Verify OTP with iProgSMS
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
                // Update password
                $userId = $_SESSION['otp_user_id'];
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users_table SET password = ? WHERE user_id = ?");
                $stmt->execute([$hashedPassword, $userId]);

                // Clear OTP session
                unset($_SESSION['otp_user_id'], $_SESSION['otp_contact_no']);

                $success = "Password changed successfully! You can now login.";
            } else {
                $error = $res['message'] ?? 'OTP verification failed.';
                $showOtpModal = true;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>FOUND-IT | Login</title>
  <?php include '../imports.php'; ?>
  <link rel="stylesheet" href="../css/account.css">
</head>
<body class="login-page"> 

<div class="login-wrapper">
  <div class="left-panel"></div>

  <div class="right-panel">
    <h2>Welcome Back</h2>
    <p class="text-muted mb-4">Login to your account</p>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger text-center"><?php echo htmlspecialchars($error); ?></div>
    <?php elseif (!empty($success)): ?>
        <div class="alert alert-success text-center"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?> 

    <form method="POST" action="">
      <div class="mb-3">
        <label for="email" class="form-label fw-semibold">Email address or SR Code</label>
        <input type="text" class="form-control" id="email" name="email" placeholder="Enter your email or SR code" required>
      </div>

      <button type="submit" name="send_otp" class="btn btn-danger w-100 fw-semibold">Send OTP</button>

      <div class="text-center text-muted mt-4">
        Don't have an account? <a href="register.php">Register now</a>
      </div>

      <div class="mt-3 text-center">
        <a href="login.php" class="text-secondary small text-decoration-none">
          <i class="bi bi-house-door"></i> Go back to Login
        </a>
      </div>
    </form>
  </div>
</div>

<!-- OTP + Password Change Modal -->
<div class="modal fade <?php echo $showOtpModal ? 'show' : ''; ?>" id="otpModal" tabindex="-1" aria-hidden="true" <?php echo $showOtpModal ? 'style="display:block;"' : ''; ?>>
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">OTP Verification & Password Reset (DO NOT CLOSE)</h5>
      </div>
      <form method="POST">
        <div class="modal-body">
          <?php if (!empty($censoredNumber)): ?>
              <p class="text-muted text-center">OTP sent to: <?php echo htmlspecialchars($censoredNumber); ?></p>
          <?php endif; ?>
          <div class="mb-3">
            <label for="otp" class="form-label">Enter OTP</label>
            <input type="text" class="form-control" id="otp" name="otp" required>
          </div>
          <div class="mb-3">
            <label for="new_password" class="form-label">New Password</label>
            <input type="password" class="form-control" id="new_password" name="new_password" required>
          </div>
          <div class="mb-3">
            <label for="confirm_password" class="form-label">Confirm Password</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="verify_otp" class="btn btn-danger w-100 fw-semibold">Verify & Change Password</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  // Show modal if needed
  <?php if ($showOtpModal): ?>
    var otpModal = new bootstrap.Modal(document.getElementById('otpModal'));
    otpModal.show();
  <?php endif; ?>
</script>

</body>
</html>