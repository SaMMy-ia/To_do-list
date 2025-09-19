<?php
header("Content-Type: application/json");
require_once '../database/DBConnection.php';
$conn = DBConnection::getInstance();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Lista tarefas (todas ou por categoria)
        if (isset($_GET['category_id'])) {
            $stmt = $conn->prepare("SELECT * FROM tasks WHERE category_id=?");
            $stmt->execute([$_GET['category_id']]);
        } else {
            $stmt = $conn->query("SELECT * FROM tasks");
        }
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'POST':
        // Criar tarefa
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $conn->prepare("INSERT INTO tasks (category_id, title, description, status, due_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$data['category_id'], $data['title'], $data['description'], $data['status'] ?? 'pendente', $data['due_date'] ?? null]);
        echo json_encode(["message" => "Tarefa criada"]);
        break;

    case 'PUT':
        // Atualizar tarefa
        $data = json_decode(file_get_contents("php://input"), true);
        $stmt = $conn->prepare("UPDATE tasks SET title=?, description=?, status=?, due_date=? WHERE id=?");
        $stmt->execute([$data['title'], $data['description'], $data['status'], $data['due_date'], $data['id']]);
        echo json_encode(["message" => "Tarefa atualizada"]);
        break;

    case 'DELETE':
        // Deletar tarefa
        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM tasks WHERE id=?");
            $stmt->execute([$id]);
            echo json_encode(["message" => "Tarefa removida"]);
        } else {
            echo json_encode(["error" => "ID não informado"]);
        }
        break;
}
