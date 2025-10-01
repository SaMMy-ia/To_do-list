<?php
session_start();
require_once '../database/DBConnection.php';
require_once '../middleware/helpers.php'; // importa funções de encriptação


if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Encriptar username e email
    $encryptedUsername = encryptData($username);
    $encryptedEmail = encryptData($email);

    // Hash da senha
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    try {
        $conn = DBConnection::getInstance();

        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
        $stmt->bindParam(':username', $encryptedUsername);
        $stmt->bindParam(':email', $encryptedEmail);
        $stmt->bindParam(':password', $hashedPassword);

        if ($stmt->execute()) {
            echo "<script>alert('Registration Successful.'); window.location.href='../views/index.php';</script>";
        } else {
            echo "<script>alert('Registration Failed. Try Again');</script>";
        }
    } catch (PDOException $e) {
        echo "<script>alert('Database Error: " . $e->getMessage() . "');</script>";
    }
}
?>



<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title></title>
    <link rel="stylesheet" href="registration.css">
</head>

<body>
    <div id="container">
        <div class="logo">
            <i class="fas fa-tasks"></i>
            <h1>To-Do List</h1>
            <p>Crie sua conta</p>
        </div>
        <div id="container">
            <form method="post" action="registration.php">
                <label for="username">Username:</label><br>
                <input type="text" name="username" placeholder="Enter Username" required><br><br>

                <label for="email">Email:</label><br>
                <input type="text" name="email" placeholder="Enter Your Email" required><br><br>

                <label for="password">Password:</label><br>
                <input type="password" name="password" placeholder="Enter Password" required><br><br>
                <input class="btn-register" type="submit" name="register" value="Register"><br><br>
                <label>Already have an account? </label><a href="../views/index.php">Login</a>
            </form>
        </div>

</body>

</html>