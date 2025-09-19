<?php
header("Content-Type: application/json");
require_once '../database/DBConnection.php';
$conn = DBConnection::getInstance();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Lista categorias por usuário
        $user_id = $_GET['user_id'] ?? null;
        if ($user_id) {
            $stmt = $conn->prepare("SELECT * FROM categories WHERE user_id=?");
            $stmt->execute([$user_id]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        } else {
            echo json_encode(["error" => "user_id não informado"]);
        }
        break;

    case 'POST':
        // Criar categoria
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $conn->prepare("INSERT INTO categories (user_id, name) VALUES (?, ?)");
        $stmt->execute([$data['user_id'], $data['name']]);
        echo json_encode(["message" => "Categoria criada"]);
        break;

    case 'PUT':
        // Atualizar categoria
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $conn->prepare("UPDATE categories SET name=? WHERE id=?");
        $stmt->execute([$data['name'], $data['id']]);
        echo json_encode(["message" => "Categoria atualizada"]);
        break;

    case 'DELETE':
        // Deletar categoria
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM categories WHERE id=?");
            $stmt->execute([$id]);
            echo json_encode(["message" => "Categoria removida"]);
        } else {
            echo json_encode(["error" => "ID não informado"]);
        }
        break;
}
