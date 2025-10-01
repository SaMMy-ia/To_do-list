<?php
require_once '../../database/DBConnection.php';
$pdo = DBConnection::getInstance();

$user_id = $_SESSION['user_id'];

// Inserir categorias padrão se não existirem
$categorias_padrao = ['Trabalho', 'Pessoal', 'Escolar', 'Saúde', 'Família', 'Finanças', 'Projetos Paralelos'];

foreach ($categorias_padrao as $nome_categoria) {
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE user_id = ? AND name = ?");
    $stmt->execute([$user_id, $nome_categoria]);

    if ($stmt->rowCount() === 0) {
        $insert = $pdo->prepare("INSERT INTO categories (user_id, name) VALUES (?, ?)");
        $insert->execute([$user_id, $nome_categoria]);
    }
}

// Buscar categorias para exibir no <select>
$stmt = $pdo->prepare("SELECT id, name FROM categories WHERE user_id = ?");
$stmt->execute([$user_id]);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>