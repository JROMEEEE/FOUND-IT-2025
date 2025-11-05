<?php
session_start();
include '../dbconnect.php';

// SESSION TIMEOUT (1 hour)
$session_lifetime = 3600;

// CHECK LOGIN + SESSION TIME
if (!isset($_SESSION['user_id']) || (time() - $_SESSION['last_activity'] > $session_lifetime)) {
    session_unset();
    session_destroy();
    header("Location: ../accounts/login.php");
    exit;
}
$_SESSION['last_activity'] = time(); // Refresh session time

$user_id = $_SESSION['user_id'];
$database = new Database();
$conn = $database->getConnect();

if (isset($_POST['update'])) {
    $user_name = $_POST['user_name'];
    $email = $_POST['email'];
    $contact_no = $_POST['contact_no'];
    $sr_code = $_POST['sr_code'];

    $stmt = $conn->prepare("UPDATE users_table 
                            SET user_name = :user_name, email = :email, contact_no = :contact_no, sr_code= :sr_code
                            WHERE user_id = :user_id");
    $stmt->bindParam(':user_name', $user_name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':contact_no', $contact_no);
    $stmt->bindParam(':sr_code', $sr_code);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

    $stmt->execute();
   
}

$stmt = $conn->prepare("SELECT user_name, contact_no, sr_code, email FROM users_table WHERE user_id = :user_id");
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "User not found.";
    exit;
}


?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
      <?php include '../imports.php'; ?>
</head>
<body>
    
  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-danger shadow-sm">
    <div class="container">
      <a class="navbar-brand fw-bold" href="../index.php">FOUND-IT</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
        <ul class="navbar-nav align-items-center">
          <li class="nav-item mx-2">
            <a class="nav-link text-white fw-semibold" href="user_dashboard.php">
              <i class="bi bi-house-door"></i> Dashboard
            </a>
          </li>
         
          <li class="nav-item mx-2">
            <a class="btn btn-light btn-sm fw-semibold text-danger" href="../accounts/logout.php">
              <i class="bi bi-box-arrow-right"></i> Logout
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container py-5">

<form method="POST" action="">

              <label for="user_name" class="form-label fw-semibold">Username</label>
    <input type="text" class="form-control" name="user_name" value="<?= htmlspecialchars($user['user_name']) ?>"required>

              <label for="contact_no" class="form-label fw-semibold">Contact Number</label>
    <input type="text" class="form-control" name="contact_no" value="<?= htmlspecialchars($user['contact_no']) ?>"required>

              <label for="user_name" class="form-label fw-semibold">Email</label>
    <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user['email']) ?>"required>

              <label for="user_name" class="form-label fw-semibold">Sr-Code</label>
    <input type="text" class="form-control" name="sr_code" value="<?= htmlspecialchars($user['sr_code']) ?>">
    <button type="submit" name="update" class="btn btn-primary mt-3">Save Changes</button>
</form>

 <!-- BUTTON HOME -->
    <div class="text-center mt-5">
      <a href="../index.php" class="btn btn-outline-secondary fw-semibold">
        <i class="bi bi-house-door"></i> Back to Home
      </a>
    </div>
  </div>


</body>
</html>