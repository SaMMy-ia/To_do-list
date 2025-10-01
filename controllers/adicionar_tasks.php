<?php
session_start();
require_once '../database/DBConnection.php';
require_once '../models/SessaoDAO.php';

// Verificar sessão usando SessaoDAO
try {
    $conn = DBConnection::getInstance();
    $sessaoDAO = new SessaoDAO($conn);
    
    $sessionId = session_id();
    $sessaoAtiva = $sessaoDAO->selecionarSessao($sessionId);
    
    if (!$sessaoAtiva || !isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Sessão expirada ou inválida.']);
        exit;
    }
    
    // Verificar se o usuário da sessão corresponde ao usuário logado
    if ($sessaoAtiva->user_id != $_SESSION['user_id']) {
        $sessaoDAO->invalidarSessao($sessionId);
        session_destroy();
        echo json_encode(['success' => false, 'message' => 'Sessão inválida.']);
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    
} catch (Exception $e) {
    error_log("Erro ao verificar sessão: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor.']);
    exit;
}

// Verifica se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

$title = $_POST['title'] ?? null;
$description = $_POST['description'] ?? '';
$priority = $_POST['priority'] ?? 'medium';
$due_date = $_POST['due_date'] ?? null;
$category_id = $_POST['category_id'] ?? null;

// Validação básica
if (empty($title)) {
    echo json_encode(['success' => false, 'message' => 'Título é obrigatório.']);
    exit;
}

try {
    $conn = DBConnection::getInstance();
    
    // Se category_id for nulo, usa uma categoria padrão ou cria uma
    if (empty($category_id)) {
        // Busca ou cria uma categoria padrão "Geral"
        $stmt = $conn->prepare("SELECT id FROM categories WHERE user_id = ? AND name = 'Geral'");
        $stmt->execute([$user_id]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($category) {
            $category_id = $category['id'];
        } else {
            // Cria categoria "Geral" se não existir
            $stmt = $conn->prepare("INSERT INTO categories (user_id, name) VALUES (?, 'Geral')");
            $stmt->execute([$user_id]);
            $category_id = $conn->lastInsertId();
        }
    }
    
    // Verifica se a categoria pertence ao usuário
    $stmt = $conn->prepare("SELECT id FROM categories WHERE id = ? AND user_id = ?");
    $stmt->execute([$category_id, $user_id]);
    $categoriaValida = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$categoriaValida) {
        echo json_encode(['success' => false, 'message' => 'Categoria inválida.']);
        exit;
    }
    
    // Insere a tarefa
    $stmt = $conn->prepare("INSERT INTO tasks (user_id, category_id, title, description, status, due_date, priority) 
                           VALUES (?, ?, ?, ?, 'pendente', ?, ?)");
    
    $success = $stmt->execute([
        $user_id,
        $category_id,
        $title,
        $description,
        $due_date ?: null,
        $priority
    ]);
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Tarefa adicionada com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao adicionar tarefa.']);
    }
    
} catch (PDOException $e) {
    error_log("Erro ao adicionar tarefa: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro no servidor: ' . $e->getMessage()]);
}
?>