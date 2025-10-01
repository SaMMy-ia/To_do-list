<?php
session_start();
require_once '../database/DBConnection.php';
require_once '../models/SessaoDAO.php';

if (!isset($_SESSION['token_sessao'])) {
    echo json_encode(['success' => false, 'message' => 'Sessão expirada.']);
    exit;
}

$id = $_POST['id'] ?? null;

$data = [
    'acao' => 'deletar',
    'token_sessao' => $_SESSION['token_sessao'],
    'id' => $id
];

$ch = curl_init('http://localhost/TO-DO LIST/api/tasks.php');
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
