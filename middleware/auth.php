<?php
require_once '../models/SessaoDAO.php';
require_once '../database/DBConnection.php';

class AuthMiddleware
{

    public function verificarAutenticacao()
    {
        $autenticado = false;

        if (isset($_COOKIE["token_sessao"])) {
            $sessionId = $_COOKIE["token_sessao"];
            $sessaoDAO = new SessaoDAO();

            // Selecionar sessão ativa
            $sessao = $sessaoDAO->selecionarSessao($sessionId);

            if ($sessao) {
                // Salvar ID do usuário logado na sessão
                $_SESSION['user_id'] = $sessao->user_id;

                // Buscar o perfil (role) do usuário
                $conn = DBConnection::getInstance();
                $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
                $stmt->execute([$sessao->user_id]);
                $role = $stmt->fetchColumn();

                $_SESSION['perfil'] = $role; // admin, usuario, gestor
                $_SESSION['token'] = $sessionId;
                $autenticado = true;
            }
        }

        return $autenticado;
    }
}
