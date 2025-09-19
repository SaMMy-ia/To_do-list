<?php
header("Content-Type: application/json");
require_once '../database/DBConnection.php';
$conn = DBConnection::getInstance();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        // Criar sessão (login)
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $conn->prepare("SELECT * FROM users WHERE email=? AND password=?");
        $stmt->execute([$data['email'], $data['password']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Registrar log de sessão
            $stmt = $conn->prepare("INSERT INTO session_logs (user_id, login_time) VALUES (?, NOW())");
            $stmt->execute([$user['id']]);
            echo json_encode(["message" => "Login bem-sucedido", "user" => $user]);
        } else {
            echo json_encode(["error" => "Credenciais inválidas"]);
        }
        break;

    case 'PUT':
        // Encerrar sessão (logout)
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $conn->prepare("UPDATE session_logs SET logout_time=NOW() WHERE user_id=? AND logout_time IS NULL");
        $stmt->execute([$data['user_id']]);
        echo json_encode(["message" => "Logout registrado"]);
        break;
}
