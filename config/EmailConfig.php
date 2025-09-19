<?php
class EmailConfig {
    public static function getConfig() {
        return [
            'host' => 'smtp.gmail.com',
            'smtp_auth' => true,
            'username' => 'devzitc@gmail.com',
            'password' => 'devzitc1234', // ⚠️ use variável de ambiente em produção
            'smtp_secure' => PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS,
            'port' => 587,
            'from_email' => 'devzitc@gmail.com',
            'from_name' => 'Sistema de Tarefas'
        ];
    }
}
