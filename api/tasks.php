<?php
header("Content-Type: application/json");
require_once '../database/DBConnection.php';
require_once '../models/SessaoDAO.php';

session_start();

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['acao'])) {
    echo json_encode(["success" => false, "message" => "Ação não informada"]);
    exit;
}

// Verificar sessão para ações que requerem autenticação
if (in_array($data['acao'], ['listar', 'criar', 'atualizar', 'remover'])) {
    if (!isset($_SESSION['user_id']) && !isset($data['token_sessao'])) {
        echo json_encode(["success" => false, "message" => "Não autenticado"]);
        exit;
    }
}

$conn = DBConnection::getInstance();

switch ($data['acao']) {
    case 'listar':
        $user_id = $_SESSION['user_id'] ?? $data['user_id'] ?? null;
        
        if (!$user_id) {
            echo json_encode(["success" => false, "message" => "ID do usuário não informado"]);
            exit;
        }

        if (isset($data['category_id'])) {
            $stmt = $conn->prepare("SELECT * FROM tasks WHERE user_id = ? AND category_id = ? ORDER BY created_at DESC");
            $stmt->execute([$user_id, $data['category_id']]);
        } else {
            $stmt = $conn->prepare("SELECT * FROM tasks WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$user_id]);
        }
        
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["success" => true, "data" => $tasks]);
        break;

    case 'criar':
        $user_id = $_SESSION['user_id'] ?? $data['user_id'] ?? null;
        
        if (!$user_id) {
            echo json_encode(["success" => false, "message" => "ID do usuário não informado"]);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO tasks (user_id, category_id, title, description, status, priority, due_date) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $user_id,
            $data['category_id'],
            $data['title'],
            $data['description'] ?? '',
            $data['status'] ?? 'pendente',
            $data['priority'] ?? 'medium',
            $data['due_date'] ?? null
        ]);
        echo json_encode(["success" => true, "message" => "Tarefa criada"]);
        break;

    case 'atualizar':
        $stmt = $conn->prepare("UPDATE tasks SET title=?, description=?, status=?, priority=?, due_date=? WHERE id=? AND user_id=?");
        $stmt->execute([
            $data['title'],
            $data['description'] ?? '',
            $data['status'],
            $data['priority'] ?? 'medium',
            $data['due_date'] ?? null,
            $data['id'],
            $_SESSION['user_id'] ?? $data['user_id']
        ]);
        echo json_encode(["success" => true, "message" => "Tarefa atualizada"]);
        break;

    case 'remover':
        $stmt = $conn->prepare("DELETE FROM tasks WHERE id=? AND user_id=?");
        $stmt->execute([
            $data['id'],
            $_SESSION['user_id'] ?? $data['user_id']
        ]);
        echo json_encode(["success" => true, "message" => "Tarefa removida"]);
        break;

    default:
        echo json_encode(["success" => false, "message" => "Ação inválida"]);
}