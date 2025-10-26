<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../dbconnect.php';
$database = new Database();
$conn = $database->getConnect();

if (!$conn) {
    die("Database connection failed.");
}

// SESSION TIMEOUT: 1 hour (3600 seconds)
$session_lifetime = 3600;

// If already logged in and not expired → redirect to dashboard
  if (
      isset($_SESSION['user_id']) && 
      isset($_SESSION['last_activity']) && 
      (time() - $_SESSION['last_activity'] < $session_lifetime)
  ) {
      header("Location: ../dashboard/user_dashboard.php");
      exit;
  }


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        try {
            $stmt = $conn->prepare("SELECT user_id, user_name, email, password, is_admin FROM users_table WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Verify plain or hashed passwords
                if ($password === $user['password'] || password_verify($password, $user['password'])) {
                    // SET SESSION DATA
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['user_name'] = $user['user_name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['is_admin'] = $user['is_admin'];
                    $_SESSION['last_activity'] = time(); // track last activity time

                    // REDIRECT TO CORRECT DASHBOARD
                    header("Location: " . ($user['is_admin'] ? "../dashboard/admin_dashboard.php" : "../dashboard/user_dashboard.php"));
                    exit;
                } else {
                    $error = "Incorrect password. Please try again.";
                }
            } else {
                $error = "No account found with that email.";
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
  <title>FOUND-IT | Login</title>
  <?php include '../imports.php'; ?>
</head>
<body class="bg-light d-flex flex-column align-items-center justify-content-center vh-100">

  <div class="card shadow-lg border-0" style="max-width: 400px; width: 100%;">
    <div class="card-header bg-danger text-white text-center py-3">
      <h4 class="mb-0 fw-bold">FOUND-IT Login</h4>
    </div>

    <div class="card-body p-4">
      <?php if (!empty($error)): ?>
        <div class="alert alert-danger text-center"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="mb-3">
          <label for="email" class="form-label fw-semibold">Email address</label>
          <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
        </div>

        <div class="mb-3">
          <label for="password" class="form-label fw-semibold">Password</label>
          <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
          <!-- <div class="form-check">
            <input class="form-check-input" type="checkbox" id="remember">
            <label class="form-check-label" for="remember">Remember me</label>
          </div> -->
          
          <!-- <a href="#" class="text-danger small text-decoration-none">Forgot password?</a> -->
        </div>

        <button type="submit" class="btn btn-danger w-100 fw-semibold">Login</button>
      </form>
    </div>

    <div class="card-footer text-center bg-white py-3">
      <a href="register.php" class="btn btn-outline-danger fw-semibold w-100">
        Register
      </a>
    </div>
  </div>

  <div class="mt-4 text-center">
    <a href="../index.php" class="btn btn-outline-secondary fw-semibold">
      <i class="bi bi-house-door"></i> Back to Home
    </a>
  </div>

</body>
</html>
