<?php
session_start();
require_once '../dbconnect.php';
include '../apikeys.php';

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

// FETCH USERS
try {
    $database = new Database();
    $conn = $database->getConnect();

    $query = "SELECT * FROM users_table ORDER BY date_registered DESC";
    $stmt = $conn->query($query);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// iProgSMS function (fixed)
function sendSMS($phone, $message)
{
    $url = 'https://www.iprogsms.com/api/v1/sms_messages';
    $data = [
        'api_token' => IPROG_API_TOKEN,
        'phone_number' => $phone,
        'message' => $message
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); // form-urlencoded
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    $result = curl_exec($ch);
    curl_close($ch);

    return json_decode($result, true);
}

// Handle approve/reject/delete/make-admin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['user_id'])) {
    $action = $_POST['action'];
    $user_id = (int)$_POST['user_id'];

    // Get user contact info
    $userStmt = $conn->prepare("SELECT user_name, contact_no FROM users_table WHERE user_id = ?");
    $userStmt->execute([$user_id]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    $phone = $user['contact_no'];
    $name = $user['user_name'];

    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE users_table SET is_approved = 1 WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $_SESSION['status_msg'] = "User approved successfully.";

        // Send SMS
        $msg = "Hello $name, your account has been approved. You can now log in to FOUND-IT.";
        sendSMS($phone, $msg);
    } elseif ($action === 'reject' || $action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM users_table WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $_SESSION['status_msg'] = $action === 'reject' ? "User rejected and removed." : "User deleted successfully.";
    } elseif ($action === 'make_admin') {
        $stmt = $conn->prepare("UPDATE users_table SET is_admin = 1 WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $_SESSION['status_msg'] = "User granted admin privileges.";

        // Send SMS
        $msg = "Hello $name, you have been granted admin privileges on FOUND-IT.";
        sendSMS($phone, $msg);
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FOUND-IT | User Approval</title>
    <?php include '../imports.php'; ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        div.dataTables_wrapper div.dataTables_filter {
            margin-bottom: 15px;
        }

        div.dataTables_wrapper div.dataTables_paginate {
            margin-top: 15px;
        }

        .filter-btn.active {
            font-weight: bold;
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

    <?php if (isset($_SESSION['status_msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show m-3 mt-5" role="alert">
            <?= htmlspecialchars($_SESSION['status_msg']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php unset($_SESSION['status_msg']);
    endif; ?>

    <div class="container py-5 mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold text-danger mb-0"><i class="bi bi-person-check"></i> User Approval Management</h3>
            <a href="admin_dashboard.php" class="btn btn-outline-danger fw-semibold">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>

        <div class="card shadow border-0">
            <div class="card-header bg-danger text-white fw-semibold">
                <i class="bi bi-list-ul"></i> Registered Users
            </div>
            <div class="card-body">

                <!-- STATUS FILTER BUTTONS CENTERED -->
                <div class="text-center mb-2">
                    <div class="text-left " style="margin-top: 5px; margin-bottom: 20px;">
                        <div class="btn-group" role="group" aria-label="Status Filters">
                            <button class="btn btn-outline-dark filter-btn mx-1 active" data-status="">All</button>
                            <button class="btn btn-outline-warning filter-btn mx-1" data-status="0">Pending</button>
                            <button class="btn btn-outline-success filter-btn mx-1" data-status="1">Approved</button>
                        </div>
                    </div>
                </div>

                <?php if (empty($users)): ?>
                    <div class="alert alert-info text-center">No users found.</div>
                <?php else: ?>
                    <div class="table-responsive mt-3">
                        <table id="usersTable" class="table table-hover align-middle">
                            <thead class="table-danger">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Contact</th>
                                    <th>Date Registered</th>
                                    <th>Status</th>
                                    <th>Role</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $row): ?>
                                    <?php
                                    $status = $row['is_approved'];
                                    $statusText = ($status == 1) ? 'Approved' : 'Pending';
                                    $statusColor = ($status == 1) ? 'green' : 'orange';
                                    $isAdmin = $row['is_admin'];
                                    ?>
                                    <tr>
                                        <td><?= $row['user_id'] ?></td>
                                        <td><?= htmlspecialchars($row['user_name']) ?></td>
                                        <td><?= htmlspecialchars($row['email']) ?></td>
                                        <td><?= htmlspecialchars($row['contact_no']) ?></td>
                                        <td><?= date("M d, Y", strtotime($row['date_registered'])) ?></td>
                                        <td data-status="<?= $status ?>" style="color: <?= $statusColor ?>; font-weight: bold; text-transform: uppercase;">
                                            <?= $statusText ?>
                                        </td>
                                        <td style="color: <?= $isAdmin ? 'red' : 'black' ?>; font-weight: bold; text-transform: uppercase;">
                                            <?= $isAdmin ? 'ADMIN' : 'USER' ?>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1 flex-wrap">
                                                <?php if ($status == 0): ?>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                                                        <input type="hidden" name="action" value="approve">
                                                        <button type="submit" class="btn btn-success btn-sm">
                                                            <i class="bi bi-check-circle"></i> Approve
                                                        </button>
                                                    </form>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to reject this user?');">
                                                        <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                                                        <input type="hidden" name="action" value="reject">
                                                        <button type="submit" class="btn btn-danger btn-sm">
                                                            <i class="bi bi-x-circle"></i> Reject
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <?php if (!$isAdmin): ?>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Grant admin privileges to this user?');">
                                                        <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                                                        <input type="hidden" name="action" value="make_admin">
                                                        <button type="submit" class="btn btn-primary btn-sm">
                                                            <i class="bi bi-shield-lock"></i> Make Admin
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                    <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <button type="submit" class="btn btn-dark btn-sm">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
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

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            let table = $('#usersTable').DataTable({
                pageLength: 10,
                order: [
                    [4, 'desc']
                ],
                responsive: true,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search users..."
                }
            });

            // Custom filter using data-status attribute
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                let selectedStatus = $('.filter-btn.active').data('status');
                if (selectedStatus === "") return true; // show all

                let rowStatus = table.row(dataIndex).node().querySelector('td:nth-child(6)').getAttribute('data-status');
                return rowStatus == selectedStatus;
            });

            $('.filter-btn').on('click', function() {
                $('.filter-btn').removeClass('active btn-danger text-white');
                $(this).addClass('active btn-danger text-white');
                table.draw();
            });
        });
    </script>
</body>

</html>