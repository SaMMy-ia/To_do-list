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

$data = [
    'acao' => 'listar',
    'token_sessao' => session_id(),
    'user_id' => $user_id
];

$ch = curl_init('http://localhost/TO-DO LIST/api/categories.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}
curl_close($ch);

echo $response;
exit;
?>