<?php
session_start();
require_once '../dbconnect.php';

// SESSION TIMEOUT (1 hour)
$session_lifetime = 3600;
if (!isset($_SESSION['user_id']) || (time() - $_SESSION['last_activity'] > $session_lifetime)) {
    session_unset();
    session_destroy();
    header("Location: ../accounts/login.php");
    exit;
}
$_SESSION['last_activity'] = time();

// FETCH SESSION DATA
$user_name = htmlspecialchars($_SESSION['user_name']);
$is_admin = isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : 0;

// RESTRICT ACCESS
if ($is_admin != 1) {
    header("Location: user_dashboard.php");
    exit;
}

// FETCH LOGBOOK ENTRIES
try {
    $database = new Database();
    $conn = $database->getConnect();

    $query = "
        SELECT al.*, u.user_name
        FROM activity_log al
        LEFT JOIN users_table u ON al.user_id = u.user_id
        ORDER BY al.created_at DESC
    ";
    $stmt = $conn->query($query);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FOUND-IT | Admin Logbook</title>
    <?php include '../imports.php'; ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        div.dataTables_wrapper div.dataTables_filter {
            margin-bottom: 15px;
        }

        div.dataTables_wrapper div.dataTables_paginate {
            margin-top: 15px;
        }

        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .table-claim {
            background-color: #d0ebff;
        }

        .table-found {
            background-color: #d3f9d8;
        }

        .table-lost {
            background-color: #fff3bf;
        }
        
    </style>
</head>

<body class="bg-light">

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-danger shadow-sm fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="admin_dashboard.php">FOUND-IT Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item mx-2">
                        <a class="nav-link text-white fw-semibold" href="admin_dashboard.php">
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

    <div class="container py-5 mt-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="fw-bold text-danger mb-0"><i class="bi bi-journal-text"></i> Admin Logbook</h3>
            <a href="admin_dashboard.php" class="btn btn-outline-danger fw-semibold">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>

        <div class="mb-3">
            <div class="btn-group" role="group" aria-label="Category Filters">
                <button type="button" class="btn btn-outline-danger btn-sm category-btn active" data-category="">All</button>
                <button type="button" class="btn btn-outline-danger btn-sm category-btn" data-category="claim_request">Claims</button>
                <button type="button" class="btn btn-outline-danger btn-sm category-btn" data-category="found_report">Found Items</button>
                <button type="button" class="btn btn-outline-danger btn-sm category-btn" data-category="lost_report">Lost Items</button>
            </div>
        </div>


        <div class="card shadow border-0">
            <div class="card-header bg-danger text-white fw-semibold" style="padding: 1rem; ;"> <i class="bi bi-list-ul"></i> Activity Log
            </div>
            <div class="card-body">
                <?php if (empty($logs)): ?>
                    <div class="alert alert-info text-center">No log entries found.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table id="logbookTable" class="table table-striped table-bordered align-middle" style="table-layout: fixed; width: 100%;">
                            <thead class="table-white">
                                <tr>
                                    <th style="width: 14%;">ID</th>
                                    <th style="width: 14%;">User</th>
                                    <th style="width: 14%;">Action</th>
                                    <th style="width: 14%;">Table</th>
                                    <th style="width: 14%;">Record ID</th>
                                    <th style="width: 14%;">Details</th>
                                    <th style="width: 14%;">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log):
                                    $rowClass = '';
                                    if ($log['table_name'] === 'claim_request') $rowClass = 'table-claim';
                                    if ($log['table_name'] === 'found_report') $rowClass = 'table-found';
                                    if ($log['table_name'] === 'lost_report') $rowClass = 'table-lost';
                                ?>
                                    <tr class="<?= $rowClass ?>">
                                        <td><?= $log['log_id'] ?></td>
                                        <td><?= htmlspecialchars($log['user_name'] ?: 'System') ?></td>
                                        <td><?= htmlspecialchars(strtoupper($log['action'])) ?></td>
                                        <td><?= htmlspecialchars($log['table_name']) ?></td>
                                        <td><?= htmlspecialchars($log['record_id'] ?? '-') ?></td>
                                        <td>
                                            <pre class="mb-0"><?= htmlspecialchars($log['details'] ?? '-') ?></pre>
                                        </td>
                                        <td><?= date("M d, Y h:i A", strtotime($log['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            var table = $('#logbookTable').DataTable({
                pageLength: 15,
                order: [
                    [6, 'desc']
                ],
                responsive: true,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search logs..."
                }
            });

            // Category button filter
            $('.category-btn').on('click', function() {
                var category = $(this).data('category');
                if (category === '') {
                    table.column(3).search('').draw(); // Show all
                } else {
                    table.column(3).search('^' + category + '$', true, false).draw();
                }
            });
        });
    </script>
</body>

</html>