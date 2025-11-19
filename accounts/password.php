<?php
session_start();
include '../dbconnect.php';

$database = new Database();
$conn = $database->getConnect(); //gets the connection so that i can run sql queries

if(!isset($_SESSION["reg_email"])){
    header("Location:register.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);


   if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $conn->prepare("
                INSERT INTO users_table (user_name, contact_no, date_registered, is_admin, sr_code, email, password)
                VALUES (?, ?, NOW(), 0, ?, ?, ?)
            ");

       $stmt->execute([
                $_SESSION["reg_user_name"],
                $_SESSION["reg_contactno"],
                !empty($_SESSION["reg_srcode"]) ? $_SESSION["reg_srcode"] : null,
                $_SESSION["reg_email"],
                $hashed_password
            ]);

            // CLEAR SESSION AFTER REGISTERING
            session_unset();

            header("Location: login.php?registered=1");
            exit();

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

<div class="right-panel">
  <div class="heading-wrapper">
      <h2>Create your account</h2>
  <span class="tooltip-wrapper">
    <a href="register.php">
    <i class="bi bi-arrow-right-square"></i>
    </a>
    <span class="tooltip-text">Go back</span>
  </span>
  </div>
  <p class="text-muted mb-4">Register to your account</p>



 <!--  <div class="container">
    <div class="row justify-content-center">
      <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
        <div class="card shadow border-0">
          <div class="card-header bg-danger text-white text-center py-3">
            <h4 class="mb-0 fw-bold">FOUND-IT Registration</h4>
          </div> -->


                <form method="POST" action="">
              <div class="mb-3">
                <label for="password" class="form-label fw-semibold">Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Create a password" required>
              </div>

              <div class="mb-3">
                <label for="confirm_password" class="form-label fw-semibold">Confirm Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
              </div>

              <button type="submit" class="btn btn-danger w-100 fw-semibold">Register</button>
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
