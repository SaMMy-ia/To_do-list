<?php
session_start();
require_once '../database/DBConnection.php';
require_once '../middleware/helpers.php'; // importa funções de encriptação

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require './PHPMailer/src/Exception.php';
require './PHPMailer/src/PHPMailer.php';
require './PHPMailer/src/SMTP.php';

// Se já estiver logado, redireciona
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Guardar email em sessão (ainda em texto plano, só para envio de OTP)
    $_SESSION['email'] = $email;

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
        // Gerar OTP
        $otp = rand(100000, 999999);
        $otp_expiry = date("Y-m-d H:i:s", strtotime("+3 minute"));

        // Configuração do e-mail
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.sendgrid.net';
            $mail->SMTPAuth = true;
             // tua chave SendGrid
            $mail->Port = 587;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

            $mail->isHTML(true);
            $mail->setFrom('devzitc@gmail.com', 'ITC DEVZ');
            $mail->addAddress($email); // aqui usamos o email original que o user digitou
            $mail->Subject = "Your OTP for Login";
            $mail->Body = "Your OTP is: $otp";

            $mail->send();

            // Atualizar OTP na BD
            $stmt = $conn->prepare("UPDATE users SET otp = :otp, otp_expiry = :otp_expiry WHERE id = :id");
            $stmt->bindParam(':otp', $otp);
            $stmt->bindParam(':otp_expiry', $otp_expiry);
            $stmt->bindParam(':id', $data['id']);
            $stmt->execute();

            // Guardar dados temporários
            $_SESSION['temp_user'] = [
                'id' => $data['id'],
                'otp' => $otp
            ];

            header("Location: ../public/otp_verification.php");
            exit();
        } catch (Exception $e) {
            echo "Erro ao enviar e-mail: {$mail->ErrorInfo}";
        }
    } else {
?>
        <script>
            alert("Invalid Email or Password. Please try again.");
            window.location.href = 'index.php';
        </script>
<?php
    }
}
?>


<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title></title>
    <style type="text/css">
        #container {
            margin: 40px auto;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            width: 440px;
            padding: 30px;
            background: #ffffff;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        input[type=text],
        input[type=password] {
            width: 300px;
            height: 25px;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
            margin-top: 5px;
        }

        input[type=text]:focus,
        input[type=password]:focus {
            border-color: #4a6cf7;
            outline: none;
            box-shadow: 0 0 0 3px rgba(74, 108, 247, 0.1);
        }

        label {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            display: block;
        }

        form {
            margin-left: 40px;
        }

        a {
            text-decoration: none;
            font-weight: 600;
            font-size: 18px;
            color: #4a6cf7;
            transition: color 0.3s ease;
        }

        a:hover {
            cursor: pointer;
            color: #3a56d4;
            text-decoration: underline;
        }

        input[type=submit] {
            width: 120px;
            background: linear-gradient(135deg, #4a6cf7, #3a56d4);
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            padding: 12px;
            margin-left: 100px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(74, 108, 247, 0.3);
        }

        input[type=submit]:hover {
            background: linear-gradient(135deg, #3a56d4, #2a46c4);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(74, 108, 247, 0.4);
        }

        input[type=submit]:active {
            transform: translateY(0);
        }

        /* Adicionando espaçamento consistente */
        br {
            margin-bottom: 5px;
        }

        /* Melhorando a responsividade */
        @media (max-width: 480px) {
            #container {
                width: 90%;
                margin: 20px auto;
                padding: 20px;
            }

            form {
                margin-left: 20px;
            }

            input[type=text],
            input[type=password] {
                width: 90%;
            }

            input[type=submit] {
                margin-left: 50px;
            }
        }
    </style>
</head>

<body>
    <div id="container">
        <form method="post" action="index.php">
            <label for="email">Email</label><br>
            <input type="text" name="email" placeholder="Enter Your Email" required><br><br>
            <label for="password">Password:</label><br>
            <input type="password" name="password" placeholder="Enter Your Password" required><br><br>
            <input type="submit" name="login" value="Login"><br><br>
            <label>Don't have an account? </label><a href="../public/registration.php">Sign Up</a>
        </form>
    </div>

</body>

</html>