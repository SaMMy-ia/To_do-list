<?php

require_once __DIR__ . '/../database/DBConnection.php';

class SessaoDAO {
    private $tabela = 'session_logs';
    private $conn;

    public function __construct($conn = null) {
        try {
            if ($conn === null) {
                $this->conn = DBConnection::getInstance();
            } else {
                $this->conn = $conn;
            }
            
            if ($this->conn === null) {
                throw new Exception('Falha ao obter conexão com o banco de dados');
            }
        } catch (Exception $e) {
            error_log('Erro ao inicializar conexão: ' . $e->getMessage());
            throw new Exception('Erro ao inicializar conexão com o banco de dados');
        }
    }

    // Registrar nova sessão
    public function registrarSessao($sessionId, $userId, $ipAddress, $userAgent) {
        try {
            $sql = "INSERT INTO $this->tabela (session_id, user_id, ip_address, user_agent) 
                    VALUES (:sessionId, :userId, :ipAddress, :userAgent)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':sessionId', $sessionId);
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':ipAddress', $ipAddress);
            $stmt->bindParam(':userAgent', $userAgent);
            
            if (!$stmt->execute()) {
                throw new Exception('Erro ao registrar sessão');
            }
            return true;
        } catch (Exception $e) {
            error_log('Erro ao registrar sessão: ' . $e->getMessage());
            throw new Exception('Erro ao registrar sessão');
        }
    }

    // Selecionar sessão ativa
    public function selecionarSessao($sessionId) {
        try {
            $sql = "SELECT * FROM $this->tabela 
                    WHERE session_id = :sessionId AND logout_time IS NULL";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':sessionId', $sessionId);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (Exception $e) {
            error_log('Erro ao selecionar sessão: ' . $e->getMessage());
            return null;
        }
    }

    // Invalidar sessão (logout)
    public function invalidarSessao($sessionId) {
        try {
            $sql = "UPDATE $this->tabela 
                    SET logout_time = NOW() 
                    WHERE session_id = :sessionId AND logout_time IS NULL";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':sessionId', $sessionId);
            
            if (!$stmt->execute()) {
                throw new Exception('Erro ao invalidar sessão');
            }
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log('Erro ao invalidar sessão: ' . $e->getMessage());
            throw new Exception('Erro ao invalidar sessão');
        }
    }

    // Nova função: Verificar sessões expiradas (para limpeza)
    public function limparSessoesExpiradas($tempoExpiracaoHoras = 24) {
        try {
            $sql = "UPDATE $this->tabela 
                    SET logout_time = NOW() 
                    WHERE logout_time IS NULL 
                    AND login_time < DATE_SUB(NOW(), INTERVAL :hours HOUR)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':hours', $tempoExpiracaoHoras, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log('Erro ao limpar sessões expiradas: ' . $e->getMessage());
            return false;
        }
    }

    // Nova função: Obter todas as sessões ativas de um usuário
    public function obterSessoesAtivasUsuario($userId) {
        try {
            $sql = "SELECT * FROM $this->tabela 
                    WHERE user_id = :userId AND logout_time IS NULL 
                    ORDER BY login_time DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {
            error_log('Erro ao obter sessões ativas: ' . $e->getMessage());
            return [];
        }
    }
}
?>