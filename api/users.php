<?php
header("Content-Type: application/json");
require_once '../database/DBConnection.php';
$conn = DBConnection::getInstance();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Lista todos os usuários
        $stmt = $conn->query("SELECT id, username, email, role, created_at FROM users");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'POST':
        // Criação de novo usuário
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$data['username'], $data['email'], $data['password'], $data['role'] ?? 'usuario']);
        echo json_encode(["message" => "Usuário criado com sucesso"]);
        break;

    case 'PUT':
        // Atualizar dados do usuário
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, role=? WHERE id=?");
        $stmt->execute([$data['username'], $data['email'], $data['role'], $data['id']]);
        echo json_encode(["message" => "Usuário atualizado"]);
        break;

    case 'DELETE':
        // Remover usuário
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
            $stmt->execute([$id]);
            echo json_encode(["message" => "Usuário removido"]);
        } else {
            echo json_encode(["error" => "ID não informado"]);
        }
        break;
}
