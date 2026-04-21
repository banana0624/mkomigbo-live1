<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= $title ?? 'Admin' ?></title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            margin: 0;
        }

        .navbar {
            background: #222;
            color: #fff;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
        }

        .navbar a {
            color: #fff;
            margin-left: 15px;
            text-decoration: none;
        }

        .container {
            padding: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }

        th {
            background: #333;
            color: #fff;
        }

        th, td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }

        tr:hover {
            background: #f1f1f1;
        }

        .btn {
            padding: 5px 10px;
            border: none;
            cursor: pointer;
            margin-right: 5px;
        }

        .btn-approve {
            background: #28a745;
            color: #fff;
        }

        .btn-reject {
            background: #dc3545;
            color: #fff;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            color: #fff;
            font-size: 12px;
        }

        .pending { background: #6c757d; }
        .approved { background: #28a745; }
        .rejected { background: #dc3545; }

        .flash {
            padding: 10px;
            margin-bottom: 15px;
        }

        .flash-success { background: #d4edda; }
        .flash-error { background: #f8d7da; }
        
        button {
            border-radius: 4px;
            margin-right: 5px;
        }
        
        .btn-approve:hover { opacity: 0.9; }
        .btn-reject:hover { opacity: 0.9; }
        
    </style>
</head>

<body>

<div class="navbar">
    <div><strong>Admin Panel</strong></div>
    <div>
        <a href="dashboard.php">Dashboard</a>
        <a href="submissions.php">Submissions</a>
        <a href="moderation_logs.php">Logs</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="container">