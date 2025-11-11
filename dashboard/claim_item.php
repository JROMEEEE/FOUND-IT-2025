<?php
session_start();
require_once '../dbconnect.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../accounts/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['fnd_id'])) {
        die("Invalid request. Missing item ID or form data.");
    }

    // Retrieve and sanitize inputs
    $fnd_id = $_POST['fnd_id'];
    $claimer_name = trim($_POST['claimer_name'] ?? '');
    $claimer_id = trim($_POST['claimer_id'] ?? '');
    $claimer_email = trim($_POST['claimer_email'] ?? '');
    $proof_of_ownership = trim($_POST['claimer_proof_desc'] ?? ''); // <-- fixed name to match form
    $contact_number = ''; // optional, left blank since form doesn’t include it

    if (empty($proof_of_ownership)) {
        $error = "You must provide a proof of ownership or statement to claim this item.";
    }

    // Generate unique ticket code
    $ticket_code = 'CLAIM-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));

    try {
        $database = new Database();
        $conn = $database->getConnect();

        // CHECK IF USER HAS REQUESTED THIS ITEM WITHIN 15 MINUTES
        $checkQuery = "
            SELECT request_date 
            FROM claim_request 
            WHERE user_id = :user_id AND fnd_id = :fnd_id
            ORDER BY request_date DESC 
            LIMIT 1
        ";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->execute([
            ':user_id' => $user_id,
            ':fnd_id' => $fnd_id
        ]);
        $lastRequest = $checkStmt->fetch(PDO::FETCH_ASSOC);

        // // REQ COOLDOWN
        // if ($lastRequest) {
        //       $lastRequestTime = strtotime($lastRequest['request_date']);
        //       $currentTime = time();
        //       $timeDiff = $currentTime - $lastRequestTime;

        //       if ($timeDiff < 180) {
        //           $remaining = 180 - $timeDiff;
        //           $minutes = floor($remaining / 60);
        //           $seconds = $remaining % 60;
        //           $error = "You can only submit another claim for this item after 3 minutes.";
        //       }
        //   }


        // Proceed only if no cooldown or validation errors
        if (!isset($error)) {
            $query = "
                INSERT INTO claim_request (
                    fnd_id, user_id, ticket_code, proof_of_ownership,
                    claimer_name, claimer_id, claimer_email, status
                ) VALUES (
                    :fnd_id, :user_id, :ticket_code, :proof_of_ownership,
                    :claimer_name, :claimer_id, :claimer_email, 'pending'
                )
            ";

            $stmt = $conn->prepare($query);
            $stmt->execute([
                ':fnd_id' => $fnd_id,
                ':user_id' => $user_id,
                ':ticket_code' => $ticket_code,
                ':proof_of_ownership' => $proof_of_ownership,
                ':claimer_name' => $claimer_name,
                ':claimer_id' => $claimer_id,
                ':claimer_email' => $claimer_email
            ]);

            $success = true;
        }

    } catch (PDOException $e) {
        $error = "Error submitting claim: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Claim Item | FOUND-IT</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php include '../imports.php'; ?>
</head>

<body class="bg-light">
  <div class="container py-5">
    <div class="card shadow-lg mx-auto" style="max-width: 700px;">
      <div class="card-header bg-danger text-white text-center">
        <h4 class="mb-0 fw-bold">Claim Request</h4>
      </div>
      <div class="card-body p-4">

        <?php if (!isset($success)): ?>
          <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
          <?php endif; ?>

          <div class="alert alert-secondary text-center">
            Redirect back and fill out the form again if needed.
          </div>
          <a href="found_dashboard.php" class="btn btn-outline-danger w-100 fw-semibold mt-3">
            <i class="bi bi-arrow-left"></i> Return to Found Dashboard
          </a>

        <?php else: ?>
          <div class="alert alert-success">
            Your claim request has been submitted successfully!<br>
            Please wait for admin verification.
          </div>
          <a href="found_dashboard.php" class="btn btn-outline-danger w-100 fw-semibold mt-3">
            <i class="bi bi-arrow-left"></i> Return to Found Dashboard
          </a>
        <?php endif; ?>

      </div>
    </div>
  </div>
</body>
</html>