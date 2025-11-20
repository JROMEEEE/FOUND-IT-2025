<?php
session_start();
include '../dbconnect.php';
include '../apikeys.php';

$database = new Database();
$conn = $database->getConnect();

$error = '';
$success = '';
$showModal = false;

// Step 1: Send OTP
if (isset($_POST['submit_form'])) {
    $user_name = trim($_POST['user_name']);
    $email = trim($_POST['email']);
    $contact_no = trim($_POST['contact_no']);
    $sr_code = trim($_POST['sr_code']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Basic validation
    if (empty($user_name) || empty($email) || empty($contact_no) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (!preg_match('/^09[0-9]{9}$/', $contact_no)) {
        $error = "Please enter a valid Philippine mobile number (must start with 09 and be 11 digits).";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Send OTP
        $data = [
            'api_token' => IPROG_API_TOKEN,
            'phone_number' => $contact_no
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
            $success = "OTP sent to $contact_no. Please enter it to complete registration.";
            $_SESSION['otp_data'] = [
                'user_name' => $user_name,
                'email' => $email,
                'contact_no' => $contact_no,
                'sr_code' => $sr_code,
                'password' => $password
            ];
            $showModal = true; // trigger modal
        } else {
            $error = $res['message'] ?? 'Failed to send OTP.';
        }
    }
}

// Step 2: Verify OTP & Register
if (isset($_POST['verify_otp'])) {
    $otp = trim($_POST['otp']);

    if (!isset($_SESSION['otp_data'])) {
        $error = "OTP session expired. Please try again.";
    } else {
        $data = [
            'api_token' => IPROG_API_TOKEN,
            'phone_number' => $_SESSION['otp_data']['contact_no'],
            'otp' => $otp
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
            try {
                $otpData = $_SESSION['otp_data'];

                $checkQuery = "SELECT * FROM users_table WHERE email = ? OR (sr_code IS NOT NULL AND sr_code = ?) LIMIT 1";
                $checkStmt = $conn->prepare($checkQuery);
                $checkStmt->execute([$otpData['email'], $otpData['sr_code']]);

                if ($checkStmt->fetch()) {
                    $error = "Email or SR Code already exists.";
                    $showModal = true;
                } else {
                    $hashed_password = password_hash($otpData['password'], PASSWORD_DEFAULT);

                    $stmt = $conn->prepare("
                        INSERT INTO users_table (user_name, contact_no, date_registered, is_admin, sr_code, email, password)
                        VALUES (?, ?, NOW(), 0, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $otpData['user_name'],
                        $otpData['contact_no'],
                        !empty($otpData['sr_code']) ? $otpData['sr_code'] : null,
                        $otpData['email'],
                        $hashed_password
                    ]);

                    $success = "Account created successfully! You can now log in.";
                    unset($_SESSION['otp_data']);
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
                $showModal = true;
            }
        } else {
            $error = $res['message'] ?? 'OTP verification failed.';
            $showModal = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>FOUND-IT | Register with OTP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100 p-3">

<div class="container">
<div class="row justify-content-center">
<div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
<div class="card shadow border-0">
<div class="card-header bg-danger text-white text-center py-3">
<h4 class="mb-0 fw-bold">FOUND-IT Registration</h4>
</div>
<div class="card-body p-4">

<?php if (!empty($error)): ?>
<div class="alert alert-danger text-center"><?php echo htmlspecialchars($error); ?></div>
<?php elseif (!empty($success)): ?>
<div class="alert alert-success text-center"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<form method="POST" action="">
  <div class="mb-3">
    <label for="user_name" class="form-label fw-semibold">Full Name</label>
    <input type="text" class="form-control" id="user_name" name="user_name" placeholder="Enter your full name" required
           value="<?php echo isset($_POST['user_name']) ? htmlspecialchars($_POST['user_name']) : ''; ?>">
  </div>

  <div class="mb-3">
    <label for="sr_code" class="form-label fw-semibold">SR Code</label>
    <input type="text" class="form-control" id="sr_code" name="sr_code" placeholder="Enter your SR Code (if student, ignore if otherwise)"
           value="<?php echo isset($_POST['sr_code']) ? htmlspecialchars($_POST['sr_code']) : ''; ?>">
  </div>

  <div class="mb-3">
    <label for="email" class="form-label fw-semibold">Email</label>
    <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required
           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
  </div>

  <div class="mb-3">
    <label for="contact_no" class="form-label fw-semibold">Contact Number</label>
    <input type="text" class="form-control" id="contact_no" name="contact_no" placeholder="e.g. 09171234567" required
           value="<?php echo isset($_POST['contact_no']) ? htmlspecialchars($_POST['contact_no']) : ''; ?>">
  </div>

  <div class="mb-3">
    <label for="password" class="form-label fw-semibold">Password</label>
    <input type="password" class="form-control" id="password" name="password" placeholder="Create a password" required>
  </div>

  <div class="mb-3">
    <label for="confirm_password" class="form-label fw-semibold">Confirm Password</label>
    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
  </div>

  <button type="submit" name="submit_form" class="btn btn-warning w-100 fw-semibold">Send OTP & Continue</button>
</form>

</div>
<div class="card-footer text-center bg-white py-3">
<a href="login.php" class="btn btn-outline-danger fw-semibold w-100">
<i class="bi bi-arrow-left"></i> Back to Login
</a>
</div>
</div>

<div class="text-center mt-4">
<a href="../index.php" class="btn btn-outline-secondary fw-semibold">
<i class="bi bi-house-door"></i> Back to Home
</a>
</div>
</div>
</div>
</div>

<!-- OTP Modal -->
<div class="modal fade" id="otpModal" tabindex="-1" aria-labelledby="otpModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="otpModalLabel">Enter OTP (DO NOT CLOSE THIS MODAL)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="otp" class="form-label fw-semibold">OTP</label>
          <input type="text" class="form-control" id="otp" name="otp" placeholder="Enter OTP" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="verify_otp" class="btn btn-danger w-100 fw-semibold">Verify & Register</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if (!empty($showModal)): ?>
<script>
var otpModal = new bootstrap.Modal(document.getElementById('otpModal'));
otpModal.show();
</script>
<?php endif; ?>
</body>
</html>