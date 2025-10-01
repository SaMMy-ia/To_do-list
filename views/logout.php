<?php
session_start();
require_once '../models/sessaoDAO.php';
require_once '../database/DBConnection.php';

try {
    $conn = DBConnection::getInstance();
    $sessaoDAO = new SessaoDAO($conn);

    // Invalidar sessão no banco de dados
    if (isset($_SESSION['user_id'])) {
        $sessionId = session_id();
        $sessaoDAO->invalidarSessao($sessionId);
    }

    // Limpar todas as variáveis de sessão
    $_SESSION = array();

    // Destruir cookie de sessão
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // Destruir sessão
    session_destroy();
} catch (Exception $e) {
    error_log("Erro durante logout: " . $e->getMessage());
}

// Redirecionar para página inicial
header("Location: ../views/index.php");
exit();
