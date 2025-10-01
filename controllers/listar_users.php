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
    $stmt = $conn->prepare("SELECT * FROM users ORDER BY created_at DESC");
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($usuarios)) {
        echo '<p>Nenhum usuário encontrado.</p>';
        exit;
    }

    echo '<table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Tipo</th>
                    <th>Status</th>
                    <th>Data Registro</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($usuarios as $usuario) {
        echo '<tr>
                <td>' . $usuario['id'] . '</td>
                <td>' . htmlspecialchars($usuario['email']) . '</td>
                <td><span class="badge ' . ($usuario['role'] === 'admin' ? 'badge-primary' : 'badge-info') . '">' . $usuario['role'] . '</span></td>
                <td><span class="badge ' . ($usuario['status'] === 'active' ? 'badge-success' : 'badge-danger') . '">' . $usuario['status'] . '</span></td>
                <td>' . date('d/m/Y H:i', strtotime($usuario['created_at'])) . '</td>
                <td>
                    <button class="btn btn-primary btn-sm" onclick="openUserModal(' . $usuario['id'] . ')">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="deleteUser(' . $usuario['id'] . ')">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
              </tr>';
    }

    echo '</tbody></table>';
} catch (PDOException $e) {
    echo '<p>Erro ao carregar usuários: ' . $e->getMessage() . '</p>';
}
