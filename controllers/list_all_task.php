<?php
session_start();
require_once '../../database/DBConnection.php';
require_once '../../models/SessaoDAO.php';

// Verificar se é admin
if ($_SESSION['role'] !== 'admin') {
    die('Acesso negado.');
}

try {
    $conn = DBConnection::getInstance();
    $stmt = $conn->prepare("
        SELECT t.*, u.email as usuario_email, c.name as categoria_nome 
        FROM tasks t 
        LEFT JOIN users u ON t.user_id = u.id 
        LEFT JOIN categories c ON t.category_id = c.id 
        ORDER BY t.created_at DESC
    ");
    $stmt->execute();
    $tarefas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($tarefas)) {
        echo '<p>Nenhuma tarefa encontrada.</p>';
        exit;
    }

    echo '<table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Usuário</th>
                    <th>Categoria</th>
                    <th>Status</th>
                    <th>Prioridade</th>
                    <th>Data Venc.</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($tarefas as $tarefa) {
        echo '<tr>
                <td>' . $tarefa['id'] . '</td>
                <td>' . htmlspecialchars($tarefa['title']) . '</td>
                <td>' . htmlspecialchars($tarefa['usuario_email']) . '</td>
                <td>' . htmlspecialchars($tarefa['categoria_nome']) . '</td>
                <td><span class="badge ' . ($tarefa['status'] === 'concluida' ? 'badge-success' : 'badge-warning') . '">' . $tarefa['status'] . '</span></td>
                <td><span class="badge ' . ($tarefa['priority'] === 'high' ? 'badge-danger' : ($tarefa['priority'] === 'medium' ? 'badge-warning' : 'badge-info')) . '">' . $tarefa['priority'] . '</span></td>
                <td>' . ($tarefa['due_date'] ? date('d/m/Y', strtotime($tarefa['due_date'])) : '-') . '</td>
                <td>
                    <button class="btn btn-danger btn-sm" onclick="deleteTask(' . $tarefa['id'] . ')">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
              </tr>';
    }

    echo '</tbody></table>';
} catch (PDOException $e) {
    echo '<p>Erro ao carregar tarefas: ' . $e->getMessage() . '</p>';
}
