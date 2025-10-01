<?php
session_start();
require_once '../../database/DBConnection.php';
require_once '../../models/SessaoDAO.php';

// Verificar se é admin
if ($_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $email = $_POST['email'] ?? '';
    $role = $_POST['role'] ?? 'user';
    $status = $_POST['status'] ?? 'active';
    
    try {
        $conn = DBConnection::getInstance();
        
        if (empty($id)) {
            // Novo usuário
            $password = password_hash('senha123', PASSWORD_DEFAULT); // Senha padrão
            $stmt = $conn->prepare("INSERT INTO users (email, password, role, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$email, $password, $role, $status]);
        } else {
            // Atualizar usuário
            $stmt = $conn->prepare("UPDATE users SET email = ?, role = ?, status = ? WHERE id = ?");
            $stmt->execute([$email, $role, $status, $id]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Usuário salvo com sucesso!']);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro: '.$e->getMessage()]);
    }
}
?>