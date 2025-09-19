<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/EmailConfig.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    public function sendOTP($toEmail, $toName, $otpCode) {
        $mail = new PHPMailer(true);
        $config = EmailConfig::getConfig();

        try {
            // Configurações servidor SMTP
            $mail->isSMTP();
            $mail->Host = $config['host'];
            $mail->SMTPAuth = $config['smtp_auth'];
            $mail->Username = $config['username'];
            $mail->Password = $config['password'];
            $mail->SMTPSecure = $config['smtp_secure'];
            $mail->Port = $config['port'];
            $mail->CharSet = 'UTF-8';

            // Remetente e destinatário
            $mail->setFrom($config['from_email'], $config['from_name']);
            $mail->addAddress($toEmail, $toName);

            // Conteúdo
            $mail->isHTML(true);
            $mail->Subject = 'Seu código OTP para Login';
            $mail->Body = "
                <h2>Código de Verificação</h2>
                <p>Olá <b>{$toName}</b>,</p>
                <p>Seu código OTP é:</p>
                <h1 style='color:#6a11cb;letter-spacing:5px;'>{$otpCode}</h1>
                <p>Expira em <b>3 minutos</b>.</p>
            ";
            $mail->AltBody = "Seu código OTP é: {$otpCode}. Expira em 3 minutos.";

            return $mail->send();
        } catch (Exception $e) {
            error_log("Erro ao enviar email: " . $mail->ErrorInfo);
            return false;
        }
    }
}
