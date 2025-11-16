<?php
session_start();
require_once '../dbconnect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../accounts/login.php");
    exit;
}

$database = new Database();
$conn = $database->getConnect();

// SESSION TIMEOUT (1 hour)
$session_lifetime = 3600;

// CHECK LOGIN + SESSION TIME
if (!isset($_SESSION['user_id']) || (time() - $_SESSION['last_activity'] > $session_lifetime)) {
    session_unset();
    session_destroy();
    header("Location: ../accounts/login.php");
    exit;
}
$_SESSION['last_activity'] = time();

// FETCH USER INFO
$user_id = $_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['user_name']);
$email = htmlspecialchars($_SESSION['email']);
$is_admin = isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : 0;

// ADMINS ONLY
if ($is_admin != 1) {
    header("Location: user_dashboard.php");
    exit;
}

// GET CATEGORY & LOCATIONS
$categories = $conn->query("SELECT category_id, category_name FROM item_category")->fetchAll(PDO::FETCH_ASSOC);
$locations = $conn->query("SELECT location_id, location_name FROM location_table")->fetchAll(PDO::FETCH_ASSOC);

// FORM SUBMISSION HANDLING PROCESS
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fnd_name = trim($_POST['fnd_name']);
    $fnd_desc = trim($_POST['fnd_desc']);
    $location_id = $_POST['location_id'];
    $category_id = $_POST['category_id'];
    $user_id = $_SESSION['user_id'];
    $fnd_datetime = date('Y-m-d H:i:s');
    $fnd_status = 'unclaimed';

    $uploadDir = '../uploads/found_items/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $image_path = null;

    // HANDLE CAMERA CAPTURE / FILE UPL
    if (!empty($_POST['fnd_image_data'])) {
        // CAM CAPTURE
        $img = str_replace('data:image/png;base64,', '', $_POST['fnd_image_data']); // GET ONLY BASE64 STR
        $img = base64_decode($img); // CONVERT BASE64 INTO BINARY IMG DATA SO IT CAN BE SAVED AS FILE
        $fileName = 'found_' . time() . '.png'; // TIMESTAMP ON FILENAME
        $filePath = $uploadDir . $fileName; // CREATE PATH NAME
        file_put_contents($filePath, $img); // STORE RELATIVE PATH FOR DB INSERTION
        $image_path = 'uploads/found_items/' . $fileName; // PATH NAMING
    } elseif (!empty($_FILES['fnd_image']['name'])) { // CHECK IF FILE WAS UPLOADED
        $fileName = basename($_FILES['fnd_image']['name']); // GET FILE NAME
        $targetFile = $uploadDir . time() . '_' . $fileName; // CREATE PATH NAME
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION)); // EXTRACT FILE EXT THEN LOWERCASE
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif']; // ALL ALLOWED FILE TYPES
        if (in_array($imageFileType, $allowedTypes)) { // CHECK IF FILE TYPE IS ALLOWED
            if (move_uploaded_file($_FILES['fnd_image']['tmp_name'], $targetFile)) { // CHECK IF FILE WAS MOVED
                $image_path = 'uploads/found_items/' . time() . '_' . $fileName; // PATH
            } else $error = "Failed to upload image."; // ERROR IF FAILED
        } else $error = "Invalid image type. Only JPG, PNG, GIF allowed."; // ERROR IF INVALID FILE TYPE
    }

    // INSERT INTO found_report
    $sql = "INSERT INTO found_report 
            (fnd_name, fnd_desc, location_id, fnd_datetime, user_id, image_path, category_id, fnd_status)
            VALUES (:fnd_name, :fnd_desc, :location_id, :fnd_datetime, :user_id, :image_path, :category_id, :fnd_status)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':fnd_name', $fnd_name);
    $stmt->bindParam(':fnd_desc', $fnd_desc);
    $stmt->bindParam(':location_id', $location_id);
    $stmt->bindParam(':fnd_datetime', $fnd_datetime);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':image_path', $image_path);
    $stmt->bindParam(':category_id', $category_id);
    $stmt->bindParam(':fnd_status', $fnd_status);

    if (empty($error)) {
        if ($stmt->execute()) $success = "Found item successfully reported!";
        else $error = "Error submitting report. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Report Found Item | FOUND-IT</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
video { border:1px solid #ccc; width:320px; height:240px; display:block; margin-bottom:5px; }
canvas { display:block; margin-top:10px; border:1px solid #ccc; }
</style>
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="card shadow border-0">
        <div class="card-header bg-danger text-white text-center fw-bold">Report Found Item</div>
        <div class="card-body">
            <div class="alert alert-warning small">
                <strong>Disclaimer:</strong> Lorem ipsum dolor sit amet, consectetur adipisicing elit.
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php elseif (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Item Name</label>
                    <input type="text" name="fnd_name" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Item Description</label>
                    <textarea name="fnd_desc" class="form-control" rows="3" required></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Location Found</label>
                        <select name="location_id" class="form-select" required>
                            <option value="">Select Location</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?= $loc['location_id'] ?>"><?= htmlspecialchars($loc['location_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Item Category</label>
                        <select name="category_id" class="form-select" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Capture Image from Camera (optional)</label>
                    <!-- LIVE CAM, NO AUDIO, SUPPORT MOBILE DEVICES -->
                    <video id="video" autoplay playsinline muted></video> 
                    <button type="button" id="snap" class="btn btn-secondary btn-sm mt-2">Take Photo</button>
                    <canvas id="canvas" width="320" height="240"></canvas>
                    <input type="hidden" name="fnd_image_data" id="fnd_image_data">
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4">
                    <a href="admin_dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
                    <button type="submit" class="btn btn-danger fw-semibold">Submit Report</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const video = document.getElementById('video');
const canvas = document.getElementById('canvas'); // SURFACE
const context = canvas.getContext('2d'); // MANIPULATE PIXELS ON CANVAS
const hiddenInput = document.getElementById('fnd_image_data');

// GET PERMS AND START CAM
async function startCamera() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } }); // NAVIGATOR ACCESS CAM
        video.srcObject = stream; // LIVE FEED
    } catch(e) {
        alert('Cannot access camera: ' + e.message); // ERROR HNDLING
    }
}
startCamera();

// CAPTURE PHOTO
document.getElementById('snap').addEventListener('click', () => { // WHEN BUTTON CLICKED
    context.drawImage(video, 0, 0, canvas.width, canvas.height); // CAPTURE IMAGE
    const dataURL = canvas.toDataURL('image/png'); // FRAME TO BASE64
    hiddenInput.value = dataURL; // STORED IN HIDDEN <INPUT> SO IT CAN BE SUBMITTED IN FORM
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
