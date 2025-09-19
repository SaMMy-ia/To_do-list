<?php
session_start();
require_once '../database/DBConnection.php';

if (!isset($_SESSION['temp_user'])) {
    header("Location: ../views/index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_otp = $_POST['otp'];
    $stored_otp = $_SESSION['temp_user']['otp'];
    $user_id = $_SESSION['temp_user']['id'];

    // Conexão PDO
    $conn = DBConnection::getInstance();

    // Buscar user pelo id e otp
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id AND otp = :otp LIMIT 1");
    $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':otp', $user_otp, PDO::PARAM_STR);
    $stmt->execute();
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        $otp_expiry = strtotime($data['otp_expiry']);
        if ($otp_expiry >= time()) {
            // Login válido
            $_SESSION['user_id'] = $data['id'];
            $_SESSION['role'] = $data['role']; // Armazena o papel do usuário na sessão
            unset($_SESSION['temp_user']);

            // Redireciona com base no tipo de usuário
            switch ($data['role']) {
                case 'admin':
                    header("Location: ../views/admin/dashboardAdmin.php");
                    break;
                case 'user':
                    header("Location: ../views/public/dashboard.php");
                    break;
                default:
                    header("Location: dashboard.php");
                    break;
            }
            exit();
        } else {
?>
            <script>
                alert("OTP has expired. Please try again.");
                window.location.href = '../views/index.php';
            </script>
        <?php
        }
    } else {
        ?>
        <script>
            alert("Invalid OTP. Please try again.");
            window.location.href = '../views/index.php';
        </script>
<?php
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Two-Step Verification</title>
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
            max-width: 450px;
            padding: 35px 30px;
            text-align: center;
        }

        .icon-container {
            background-color: #f0f7ff;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .icon-container i {
            font-size: 36px;
            color: #4a6cf7;
        }

        h1 {
            color: #2d3748;
            margin-bottom: 15px;
            font-size: 28px;
        }

        p {
            color: #718096;
            margin-bottom: 25px;
            line-height: 1.5;
            font-size: 16px;
        }

        .email-display {
            color: #4a6cf7;
            font-weight: 600;
            word-break: break-all;
            margin: 10px 0 25px;
            padding: 10px;
            background-color: #f8f9ff;
            border-radius: 8px;
            border-left: 4px solid #4a6cf7;
        }

        form {
            text-align: left;
        }

        label {
            font-weight: 600;
            color: #2d3748;
            font-size: 16px;
            margin-bottom: 8px;
            display: block;
        }

        .input-container {
            position: relative;
            margin-bottom: 25px;
        }

        input[type="number"] {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 18px;
            transition: all 0.3s ease;
        }

        input[type="number"]:focus {
            border-color: #4a6cf7;
            outline: none;
            box-shadow: 0 0 0 3px rgba(74, 108, 247, 0.2);
        }

        .input-container i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            font-size: 20px;
        }

        button {
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
        }

        button:hover {
            background: linear-gradient(135deg, #3a56d4 0%, #2a46c4 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(74, 108, 247, 0.4);
        }

        button:active {
            transform: translateY(0);
        }

        .resend-container {
            margin-top: 25px;
            text-align: center;
            font-size: 15px;
            color: #718096;
        }

        .resend-container a {
            color: #4a6cf7;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .resend-container a:hover {
            color: #3a56d4;
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            #container {
                padding: 25px 20px;
            }

            h1 {
                font-size: 24px;
            }

            input[type="number"] {
                padding: 12px 12px 12px 40px;
                font-size: 16px;
            }
        }
    </style>
</head>

<body>
    <div id="container">
        <div class="icon-container">
            <i class="fas fa-shield-alt"></i>
        </div>

        <h1>Verificação em Duas Etapas</h1>

        <p>Enviamos um código de verificação de 6 dígitos para o seu endereço de e-mail:</p>

        <div class="email-display"><?php echo htmlspecialchars($_SESSION['email']); ?></div>

        <form method="post" action="otp_verification.php">
            <label for="otp">Código de Verificação:</label>
            <div class="input-container">
                <i class="fas fa-key"></i>
                <input type="number" id="otp" name="otp" inputmode="numeric" pattern="[0-9]{6}" placeholder="Digite o código de 6 dígitos" required>
            </div>

            <button type="submit">Verificar Código</button>
        </form>

        <div class="resend-container">
            Não recebeu o código? <a href="#">Reenviar código</a>
        </div>
    </div>

    <script>
        // Simulação de contagem regressiva para reenvio
        document.addEventListener('DOMContentLoaded', function() {
            const resendLink = document.querySelector('.resend-container a');
            let countdown = 30;

            resendLink.addEventListener('click', function(e) {
                e.preventDefault();

                // Desabilitar o link temporariamente
                this.style.pointerEvents = 'none';
                this.style.color = '#a0aec0';

                const originalText = this.textContent;
                let countdownInterval = setInterval(function() {
                    if (countdown > 0) {
                        resendLink.textContent = `Reenviar (${countdown}s)`;
                        countdown--;
                    } else {
                        clearInterval(countdownInterval);
                        resendLink.textContent = originalText;
                        resendLink.style.pointerEvents = 'auto';
                        resendLink.style.color = '#4a6cf7';
                        countdown = 30;
                    }
                }, 1000);

                // Simular reenvio do código
                alert('Código reenviado com sucesso! Verifique seu e-mail.');
            });
        });
    </script>
</body>

</html>