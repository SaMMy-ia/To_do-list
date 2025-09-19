<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../views/index.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>User Dashboard</title>
</head>
<body>
    <h1>Welcome to User Dashboard</h1>
    <p>Hello, user!</p>
    <a href="../logout.php">Logout</a>
</body>
</html>