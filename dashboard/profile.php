<?php
session_start();
require_once '../dbconnect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../accounts/login.php");
    exit;
}

$database = new Database();
$conn = $database->getConnect();
$user_id = $_SESSION['user_id'];

// Fetch user info including is_admin
$stmt = $conn->prepare("SELECT user_name, sr_code, contact_no, email, is_admin FROM users_table WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// CLAIM REQUESTS (available to all users)
$stmt = $conn->prepare("
    SELECT cr.request_id, cr.ticket_code, cr.status, cr.request_date, fr.fnd_name
    FROM claim_request cr
    LEFT JOIN found_report fr ON cr.fnd_id = fr.fnd_id
    WHERE cr.user_id = ?
    ORDER BY cr.request_date DESC
");
$stmt->execute([$user_id]);
$claim_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// FOUND REPORTS (admin only)
$found_reports = [];
if ($user['is_admin']) {
    $stmt = $conn->prepare("
        SELECT fnd_id, fnd_name, fnd_datetime, image_path, fnd_status
        FROM found_report
        ORDER BY fnd_datetime DESC
    ");
    $stmt->execute();
    $found_reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
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



        div.dataTables_wrapper div.dataTables_filter {
            margin-bottom: 15px;
        }

        div.dataTables_wrapper div.dataTables_paginate {
            margin-top: 15px;
        }

        table.dataTable td,
        table.dataTable th {
            vertical-align: middle;
        }

        /*  .card {
            border-radius: 10px;
        } */
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
        <div class="card shadow-sm mb-5 mx-auto" style="max-width: 100%; border-radius:0;">
            <div class="card-body">
                <h4 class="fw-bold text-danger text-center">
                    <i class="bi bi-person-circle"></i> <?= htmlspecialchars($user['user_name']); ?>
                </h4>
                <hr>
                <p class="mb-0"><strong>Username:</strong> <?= htmlspecialchars($user['user_name']); ?></p>
                <p class="mb-0"><strong>Email:</strong> <?= htmlspecialchars($user['email']); ?></p>
                <p class="mb-0"><strong>SR Code:</strong> <?= $user['sr_code'] ? htmlspecialchars($user['sr_code']) : '<span class="text-muted">Not Provided</span>'; ?></p>
                <p class="mb-0"><strong>Contact No:</strong> <?= htmlspecialchars($user['contact_no']); ?></p>

                <a href="edit_profile.php" class="btn btn-danger btn-sm fw-semibold" style="margin-top: 20px;">
                    <i class="bi bi-pencil-square"></i> Edit Profile
                </a>
            </div>
        </div>






        <?php if ($user['is_admin']): ?>

            <div class="text-left mb-4">
                <div class="btn-group" role="group">
                    <button class="btn btn-outline-danger active" id="foundBtn" onclick="showTable('found')">Found Items</button>
                    <button class="btn btn-outline-danger" id="claimBtn" onclick="showTable('claims')">Claim Requests</button>
                </div>
            </div>

            <!-- FOUND REPORTS (Admin Only) -->
            <div id="foundTableDiv" style="display: none;">
                <div class="card shadow-sm mb-4 mx-auto border-danger" style="max-width: 100%; border-radius: 0; border-width: 0; padding: 1.0 rem;">

                    <!-- Header -->
                    <div class="card-header bg-danger text-white fw-semibold" style="border-radius: 0; padding: 1.0rem;">
                        <i class="bi bi-binoculars"></i> All Found Items
                    </div>

                    <div class="card-body">

                        <?php if (empty($found_reports)): ?>
                            <div class="text-muted fst-italic text-center py-3">
                                No found items reported yet.
                            </div>
                        <?php else: ?>
                            <!-- Table inside single card -->
                            <div class="table-responsive">
                                <table id="foundTable" class="table table-bordered table-striped mb-0">
                                    <thead class="table-white">
                                        <tr>
                                            <th>Item Name</th>
                                            <th>Status</th>
                                            <th>Date & Time</th>
                                            <th>Image</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($found_reports as $row): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['fnd_name']); ?></td>
                                                <td><span class="fw-bold text-uppercase"><?= htmlspecialchars($row['fnd_status']); ?></span></td>
                                                <td><?= date("M d, Y h:i A", strtotime($row['fnd_datetime'])); ?></td>
                                                <td class="text-center">
                                                    <?php if ($row['image_path'] && file_exists("../" . $row['image_path'])): ?>
                                                        <img src="../<?= htmlspecialchars($row['image_path']); ?>"
                                                            style="height: 70px; width: 70px; object-fit: cover; border-radius: 8px;">
                                                    <?php else: ?>
                                                        <span class="text-muted">No Image</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        <?php endif; ?>






        <!-- CLAIM REQUESTS -->
        <div id="claimTableDiv" style="display: <?= $user['is_admin'] ? 'none' : 'block' ?>;">
            <div class="card shadow-sm mb-5 mx-auto" style="max-width: 100%; border-radius:0;">
                <div class="card-header bg-danger text-white fw-semibold" style="border-radius: 0;">
                    <i class="bi bi-ticket-perforated"></i> Your Claim Requests
                </div>
                <div class="card-body">
                    <?php if (empty($claim_requests)): ?>
                        <div class="text-muted fst-italic text-center py-3">You have not submitted any claim requests.</div>
                    <?php else: ?>
                        <div class="table-responsive mt-3" style="max-width: 100%; margin: 0 auto;">
                            <table id="claimRequestsTable" class="table table-hover align-middle">

                                <thead class="table-danger">
                                    <tr>
                                        <th style="width: 35%;">Item</th>
                                        <th style="width: 15%;">Status</th>
                                        <th style="width: 25%;">Date Requested</th>
                                        <th style="width: 25%;">Action</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($claim_requests as $row): ?>
                                        <?php
                                        $badgeClass = match ($row['status']) {
                                            'approved' => 'success',
                                            'rejected' => 'danger',
                                            default => 'secondary',
                                        };
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['fnd_name']); ?></td>
                                            <td><span class="badge bg-<?= $badgeClass ?> text-uppercase"><?= htmlspecialchars($row['status']); ?></span></td>
                                            <td><?= date("M d, Y h:i A", strtotime($row['request_date'])); ?></td>
                                            <td>
                                                <?php if ($row['status'] === 'approved'): ?>
                                                    <form method="POST" action="generate_pdf.php" target="_blank">
                                                        <input type="hidden" name="request_id" value="<?= $row['request_id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            <i class="bi bi-file-earmark-pdf"></i> PDF
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            // Initialize Found Items table (admin only)
            if ($('#foundTable').length) {
                $('#foundTable').DataTable({
                    lengthMenu: [5, 10, 25, 50],
                    pageLength: 5,
                    order: [
                        [2, 'desc']
                    ],
                    columnDefs: [{
                        orderable: false,
                        targets: 3
                    }],
                    language: {
                        search: "_INPUT_",
                        searchPlaceholder: "Search found items..."
                    }
                });
            }

            // Initialize Claim Requests table (all users)
            $(document).ready(function() {
                if ($('#claimRequestsTable').length) {
                    $('#claimRequestsTable').DataTable({
                        pageLength: 5,
                        order: [
                            [2, 'desc']
                        ],
                        responsive: true,
                        language: {
                            search: "_INPUT_",
                            searchPlaceholder: "Search claims..."
                        }
                    });
                }
            });
        });

        // Toggle tables for admin
        function showTable(tab) {
            if (tab === 'found') {
                document.getElementById('foundTableDiv').style.display = 'block';
                document.getElementById('claimTableDiv').style.display = 'none';
                document.getElementById('foundBtn').classList.add('active');
                document.getElementById('claimBtn').classList.remove('active');
            } else {
                document.getElementById('foundTableDiv').style.display = 'none';
                document.getElementById('claimTableDiv').style.display = 'block';
                document.getElementById('foundBtn').classList.remove('active');
                document.getElementById('claimBtn').classList.add('active');
            }
        }

        // Show default table on page load
        window.onload = function() {
            showTable('found');
        };
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>