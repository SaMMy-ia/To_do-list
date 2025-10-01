<?php
header("Content-Type: application/json");
require_once '../database/DBConnection.php';
$conn = DBConnection::getInstance();

$data = json_decode(file_get_contents("php://input"), true);

// Verifica se veio uma ação
if (!isset($data['acao'])) {
    echo json_encode(["success" => false, "message" => "Ação não informada"]);
    exit;
}

switch ($data['acao']) {
    case 'listar':
        if (!isset($data['user_id'])) {
            echo json_encode(["success" => false, "message" => "user_id não informado"]);
            exit;
        }
        $stmt = $conn->prepare("SELECT * FROM categories WHERE user_id=?");
        $stmt->execute([$data['user_id']]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'criar':
        $stmt = $conn->prepare("INSERT INTO categories (user_id, name) VALUES (?, ?)");
        $stmt->execute([$data['user_id'], $data['name']]);
        echo json_encode(["success" => true, "message" => "Categoria criada"]);
        break;

    case 'atualizar':
        $stmt = $conn->prepare("UPDATE categories SET name=? WHERE id=?");
        $stmt->execute([$data['name'], $data['id']]);
        echo json_encode(["success" => true, "message" => "Categoria atualizada"]);
        break;

    case 'remover':
        $stmt = $conn->prepare("DELETE FROM categories WHERE id=?");
        $stmt->execute([$data['id']]);
        echo json_encode(["success" => true, "message" => "Categoria removida"]);
        break;

    default:
        echo json_encode(["success" => false, "message" => "Ação inválida"]);
}
