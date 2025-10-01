<?php
session_start();
require_once '../database/DBConnection.php';
require_once '../middleware/helpers.php'; // importa funções de encriptação
require_once '../models/SessaoDAO.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require './PHPMailer/src/Exception.php';
require './PHPMailer/src/PHPMailer.php';
require './PHPMailer/src/SMTP.php';

// Se já estiver logado, redireciona
if (isset($_SESSION['user_id'])) {
    try {
        $conn = DBConnection::getInstance();
        $sessaoDAO = new SessaoDAO($conn);

        $sessionId = session_id();
        $sessaoAtiva = $sessaoDAO->selecionarSessao($sessionId);

        if ($sessaoAtiva) {
            // Redirecionar baseado no role
            switch ($_SESSION['role'] ?? 'user') {
                case 'admin':
                    header("Location: admin/dashboardAdmin.php");
                    break;
                case 'user':
                default:
                    header("Location: public/dashboard.php");
                    break;
            }
            exit();
        }
    } catch (Exception $e) {
        // Em caso de erro, continuar com o login normal
        error_log("Erro ao verificar sessão ativa: " . $e->getMessage());
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validações básicas
    if (empty($email) || empty($password)) {
        echo "<script>alert('Por favor, preencha todos os campos.'); window.location.href = 'index.php';</script>";
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Por favor, insira um email válido.'); window.location.href = 'index.php';</script>";
        exit();
    }

    // Guardar email em sessão (apenas para exibição no OTP)
    $_SESSION['email'] = $email;

    try {
        // 🔐 Encriptar email para comparar na BD
        $encryptedEmail = encryptData($email);

        // Conexão PDO
        $conn = DBConnection::getInstance();

        // Buscar o usuário pelo email encriptado
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $encryptedEmail);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data && password_verify($password, $data['password'])) {
            // Verificar se o usuário está ativo
            if (isset($data['status']) && $data['status'] === 'inactive') {
                echo "<script>alert('Sua conta está inativa. Entre em contato com o suporte.'); window.location.href = 'index.php';</script>";
                exit();
            }

            // Gerar OTP
            $otp = rand(100000, 999999);
            $otp_expiry = date("Y-m-d H:i:s", strtotime("+3 minute"));

            // Configuração do e-mail
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.sendgrid.net';
                $mail->SMTPAuth = true;

                $mail->Port = 587;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Timeout = 10; // Timeout de 10 segundos

                $mail->isHTML(true);
                $mail->setFrom('devzitc@gmail.com', 'ITC DEVZ');
                $mail->addAddress($email);
                $mail->Subject = "Seu Codigo de Verificacao - ITC DEVZ";
                $mail->Body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                        <h2 style='color: #4a6cf7;'>Código de Verificação</h2>
                        <p>Olá,</p>
                        <p>Use o código abaixo para completar seu login:</p>
                        <div style='background: #f8f9ff; border: 2px solid #4a6cf7; border-radius: 8px; padding: 15px; text-align: center; font-size: 24px; font-weight: bold; color: #4a6cf7; margin: 20px 0;'>
                            $otp
                        </div>
                        <p>Este código expira em 3 minutos.</p>
                        <p><strong>Não compartilhe este código com ninguém.</strong></p>
                        <hr style='border: none; border-top: 1px solid #e0e0e0; margin: 20px 0;'>
                        <p style='color: #666; font-size: 12px;'>Se você não solicitou este código, por favor ignore este email.</p>
                    </div>
                ";

                // Email em texto simples para clientes que não suportam HTML
                $mail->AltBody = "Seu código de verificação é: $otp\nEste código expira em 3 minutos.\n\nNão compartilhe este código com ninguém.";

                if ($mail->send()) {
                    // Atualizar OTP na BD
                    $stmt = $conn->prepare("UPDATE users SET otp = :otp, otp_expiry = :otp_expiry WHERE id = :id");
                    $stmt->bindParam(':otp', $otp);
                    $stmt->bindParam(':otp_expiry', $otp_expiry);
                    $stmt->bindParam(':id', $data['id']);

                    if ($stmt->execute()) {
                        // Guardar dados temporários
                        $_SESSION['temp_user'] = [
                            'id' => $data['id'],
                            'otp' => $otp,
                            'role' => $data['role'] ?? 'user'
                        ];

                        // Limpar tentativas de login se existirem
                        if (isset($_SESSION['login_attempts'])) {
                            unset($_SESSION['login_attempts']);
                        }

                        header("Location: ../public/otp_verification.php");
                        exit();
                    } else {
                        throw new Exception("Erro ao atualizar OTP no banco de dados.");
                    }
                } else {
                    throw new Exception("Falha ao enviar email.");
                }
            } catch (Exception $e) {
                error_log("Erro PHPMailer: " . $e->getMessage());
                echo "<script>alert('Erro ao enviar código de verificação. Tente novamente.'); window.location.href = 'index.php';</script>";
                exit();
            }
        } else {
            // Incrementar tentativas de login
            $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
            $attempts = $_SESSION['login_attempts'];

            if ($attempts >= 5) {
                echo "<script>alert('Muitas tentativas de login. Tente novamente em 15 minutos.'); window.location.href = 'index.php';</script>";
                // Aqui você poderia implementar um bloqueio temporário
                exit();
            }

            echo "<script>alert('Email ou senha inválidos. Tentativa $attempts de 5.'); window.location.href = 'index.php';</script>";
            exit();
        }
    } catch (PDOException $e) {
        error_log("Erro de banco de dados: " . $e->getMessage());
        echo "<script>alert('Erro interno do servidor. Tente novamente.'); window.location.href = 'index.php';</script>";
        exit();
    } catch (Exception $e) {
        error_log("Erro geral: " . $e->getMessage());
        echo "<script>alert('Erro interno do servidor. Tente novamente.'); window.location.href = 'index.php';</script>";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Sistema To-Do List</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style type="text/css">
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        #container {
            background-color: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 440px;
            padding: 40px 35px;
            text-align: center;
        }

        .logo {
            margin-bottom: 30px;
        }

        .logo i {
            font-size: 48px;
            color: #4a6cf7;
            margin-bottom: 10px;
        }

        .logo h1 {
            color: #2d3748;
            font-size: 28px;
            font-weight: 700;
        }

        .logo p {
            color: #718096;
            font-size: 16px;
            margin-top: 5px;
        }

        form {
            text-align: left;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            font-size: 16px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
            display: block;
        }

        .input-container {
            position: relative;
        }

        .input-container i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            font-size: 18px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: #4a6cf7;
            outline: none;
            box-shadow: 0 0 0 3px rgba(74, 108, 247, 0.2);
        }

        .btn-login {
            background: linear-gradient(135deg, #4a6cf7 0%, #3a56d4 100%);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 15px 25px;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(74, 108, 247, 0.3);
            margin: 10px 0 20px;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #3a56d4 0%, #2a46c4 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(74, 108, 247, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .register-link {
            text-align: center;
            font-size: 15px;
            color: #718096;
        }

        .register-link a {
            color: #4a6cf7;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .register-link a:hover {
            color: #3a56d4;
            text-decoration: underline;
        }

        .security-notice {
            background: #f8f9ff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
            margin-top: 20px;
            font-size: 12px;
            color: #718096;
            text-align: center;
        }

        .security-notice i {
            color: #4a6cf7;
            margin-right: 5px;
        }

        @media (max-width: 480px) {
            #container {
                padding: 30px 25px;
            }

            input[type="text"],
            input[type="password"] {
                padding: 12px 12px 12px 40px;
                font-size: 16px;
            }
        }
    </style>
</head>

<body>
    <div id="container">
        <div class="logo">
            <i class="fas fa-tasks"></i>
            <h1>To-Do List</h1>
            <p>Faça login na sua conta</p>
        </div>

        <form method="post" action="index.php">
            <div class="form-group">
                <label for="email">Email</label>
                <div class="input-container">
                    <i class="fas fa-envelope"></i>
                    <input type="text" id="email" name="email" placeholder="Digite seu email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Senha</label>
                <div class="input-container">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Digite sua senha" required>
                </div>
            </div>

            <button type="submit" name="login" class="btn-login">Entrar</button>
        </form>

        <div class="register-link">
            <label>Não tem uma conta? </label>
            <a href="../public/registration.php">Cadastre-se</a>
        </div>

        <div class="security-notice">
            <i class="fas fa-shield-alt"></i>
            Seus dados estão protegidos com criptografia avançada
        </div>
    </div>

    <script>
        // Prevenir reenvio do formulário
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        // Foco no campo de email
        document.getElementById('email')?.focus();

        // Mostrar/ocultar senha (opcional)
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const lockIcon = document.querySelector('.fa-lock');

            if (passwordInput && lockIcon) {
                lockIcon.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.classList.toggle('fa-lock');
                    this.classList.toggle('fa-lock-open');
                });

                lockIcon.style.cursor = 'pointer';
                lockIcon.title = 'Mostrar/ocultar senha';
            }
        });
    </script>
</body>

</html>