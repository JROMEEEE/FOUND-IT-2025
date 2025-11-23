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
  <title>Your Profile | FOUND-IT</title>
  <?php include '../imports.php'; ?>

  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>

  <style>
    body {
      padding-top: 80px;
    }
  </style>

</head>

<body class="bg-light">

  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-danger shadow-sm fixed-top">
    <div class="container">
      <a class="navbar-brand fw-bold" href="user_dashboard.php">FOUND-IT</a>
      <div class="d-flex gap-2">
        <a href="user_dashboard.php" class="btn btn-outline-light btn-sm fw-semibold">
          <i class="bi bi-house-door"></i> Dashboard
        </a>
        <a href="../accounts/logout.php" class="btn btn-light btn-sm text-danger fw-semibold">
          <i class="bi bi-box-arrow-right"></i> Logout
        </a>
      </div>
    </div>
  </nav>


  <div class="container py-5">
    <!-- USER INFO -->

    <div class="card shadow-sm mb-5 mx-auto" style="max-width: 600px; border-radius:0;">
      <div class="card-body">
        <h4 class="fw-bold text-danger text-center">
          <i class="bi bi-person-circle"></i> <?= htmlspecialchars($user['user_name']); ?>
        </h4>
        <hr>
        <form method="POST" action="">

          <label for="user_name" class="form-label fw-semibold text-black ">Username</label>
          <input type="text" class="form-control mb-3" name="user_name" value="<?= htmlspecialchars($user['user_name']) ?>" required>

          <label for="contact_no" class="form-label fw-semibold text-black">Contact Number</label>
          <input type="text" class="form-control mb-3" name="contact_no" value="<?= htmlspecialchars($user['contact_no']) ?>" required>

          <label for="user_name" class="form-label fw-semibold text-black">Email</label>
          <input type="email" class="form-control mb-3" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

          <label for="user_name" class="form-label fw-semibold text-black">Sr-Code</label>
          <input type="text" class="form-control mb-3" name="sr_code" value="<?= htmlspecialchars($user['sr_code']) ?>">
<div class="d-flex justify-content-start mt-3 gap-2">
    <button type="submit" name="update" class="btn btn-outline-danger btn-sm ">Save Changes</button>
    <a href="profile.php" class="btn btn-danger btn-sm" > Back</a>
</div>
        </form>

       
        </a>
      </div>
    </div>


  </div>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>