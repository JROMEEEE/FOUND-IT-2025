<?php
session_start();
include '../dbconnect.php';

$database = new Database();
$conn = $database->getConnect(); //gets the connection so that i can run sql queries

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $user_name = trim($_POST["user_name"]);
  $email = trim($_POST["email"]);
  $contact_no = trim($_POST["contact_no"]);
  $sr_code = trim($_POST["sr_code"]);


  // VALIDATION
  if (empty($user_name) || empty($email) || empty($contact_no)) {
    $error = "Please fill in all required fields."; // requires the field to be filled
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { //correct email
    $error = "Please enter a valid email address.";
  } elseif (!preg_match('/^09[0-9]{9}$/', $contact_no)) { //making sure that phone number is 11 digits
    $error = "Please enter a valid Philippine mobile number (must start with 09 and be 11 digits).";
  } else {
    try {
      // CHECK IF EMAIL OR SR CODE EXISTS
      $checkQuery = "SELECT * FROM users_table WHERE email = ? OR (sr_code IS NOT NULL AND sr_code = ?) LIMIT 1";
      $checkStmt = $conn->prepare($checkQuery);
      $checkStmt->execute([$email, $sr_code]);

      if ($checkStmt->fetch()) {
        $error = "Email or SR Code already exists.";
      } else {

        // STORE VALUES IN SESSION → MOVE TO PASSWORD PAGE
                $_SESSION["reg_user_name"] = $user_name;
                $_SESSION["reg_email"] = $email;
                $_SESSION["reg_contactno"] = $contact_no;
                $_SESSION["reg_srcode"] = $sr_code;

                header("Location: password.php");
                exit();

      }
    } catch (PDOException $e) {
      $error = "Database error: " . $e->getMessage();
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>FOUND-IT | Register</title>
  <?php include '../imports.php'; ?>

</head>

<body class="register-page">

  <div class="register-wrapper">
    <!-- Left Side -->
    <div class="left-panel">
    </div>

    <!-- Right Side -->
    <div class="right-panel">
      <h2>Create your account </h2>
      <p class="text-muted mb-4">Register to your account</p>

      <!--  <div class="container">
    <div class="row justify-content-center">
      <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
        <div class="card shadow border-0">
          <div class="card-header bg-danger text-white text-center py-3">
            <h4 class="mb-0 fw-bold">FOUND-IT Registration</h4>
          </div> -->

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger text-center"><?php echo htmlspecialchars($error); ?></div>
      <?php elseif (!empty($success)): ?>
        <div class="alert alert-success text-center"><?php echo htmlspecialchars($success); ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="mb-3">
          <label for="user_name" class="form-label fw-semibold">Full Name</label>
          <input type="text" class="form-control" id="user_name" name="user_name" placeholder="Enter your full name" required>
        </div>

        <div class="mb-3">
          <label for="sr_code" class="form-label fw-semibold">SR Code</label>
          <input type="text" class="form-control" id="sr_code" name="sr_code" placeholder="Enter your SR Code (if student, ignore if otherwise)">
        </div>

        <div class="mb-3">
          <label for="email" class="form-label fw-semibold">Email</label>
          <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
        </div>

        <div class="mb-3">
          <label for="contact_no" class="form-label fw-semibold">Contact Number</label>
          <input type="text" class="form-control" id="contact_no" name="contact_no" placeholder="e.g. 09171234567" required>
        </div>


<button type="submit" class="btn btn-danger w-100 fw-semibold">Next</button>


      </form>


      <div class="text-center text-muted mt-4">
        Already have an account?
        <a href="login.php" class="fw-semibold text-danger text-decoration-none">Login here</a>
      </div>

      <div class="mt-4 text-center">
        <a href="../index.php" class="text-secondary small text-decoration-none">
          <i class="bi bi-house-door"></i> Dashboard
        </a>
      </div>


      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>