<?php
header("Content-Type: application/json");
require_once '../database/DBConnection.php';
$conn = DBConnection::getInstance();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Listar logs de sessão
        $stmt = $conn->query("SELECT s.id, u.username, u.email, s.login_time, s.logout_time 
                              FROM session_logs s 
                              JOIN users u ON s.user_id = u.id 
                              ORDER BY s.login_time DESC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;
}
