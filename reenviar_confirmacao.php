<?php
session_start();
require_once("config.php");

// PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'libs/PHPMailer/src/Exception.php';
require 'libs/PHPMailer/src/PHPMailer.php';
require 'libs/PHPMailer/src/SMTP.php';

$erro = "";
$sucesso = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);

    // Procurar usuário
    $sql = "SELECT id, nome, confirmado FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $user = $res->fetch_assoc();

        if ($user['confirmado'] == 1) {
            $erro = "✅ Sua conta já está confirmada!";
        } else {
            // Gerar novo token
            $token = bin2hex(random_bytes(32));
            $upd = $conn->prepare("UPDATE usuarios SET confirm_token=? WHERE id=?");
            $upd->bind_param("si", $token, $user['id']);
            $upd->execute();

            $link = "http://edukaplus.free.nf/confirmar.php?token=" . $token;

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.seudominio.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'reenviar_confirmacao.php';
                $mail->Password   = '0842';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = 465;

                $mail->setFrom('reenviar_confirmacao.php', 'Eduka Plus Angola');
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = "Reenvio de confirmação - Eduka Plus Angola";
                $mail->Body    = "
                    <h2>Olá, {$user['nome']}!</h2>
                    <p>Segue seu novo link para confirmar a conta:</p>
                    <p><a href='$link'>$link</a></p>
                ";
                $mail->AltBody = "Clique para confirmar sua conta: $link";

                $mail->send();
                $sucesso = "📩 Um novo e-mail de confirmação foi enviado para $email.";
            } catch (Exception $e) {
                $erro = "❌ Não foi possível enviar o e-mail. Tente novamente.";
            }
        }
    } else {
        $erro = "❌ E-mail não encontrado!";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reenviar Confirmação - Eduka Plus Angola</title>
  <style>
    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #0d1b2a, #1b263b);
      font-family: 'Segoe UI', Tahoma, sans-serif;
      color: #e0e6ed;
    }
    .container {
      background: #1e293b;
      padding: 2rem;
      border-radius: 12px;
      width: 100%;
      max-width: 420px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.6);
      text-align: center;
    }
    h2 { margin-bottom: 1.5rem; }
    input {
      width: 100%;
      padding: 0.9rem;
      margin-bottom: 1rem;
      border: 1px solid #334155;
      border-radius: 8px;
      background: #0f172a;
      color: #e0e6ed;
    }
    input::placeholder { color: #94a3b8; }
    button {
      width: 100%;
      padding: 0.9rem;
      border: none;
      border-radius: 8px;
      background: #3b82f6;
      color: #fff;
      font-size: 1rem;
      font-weight: bold;
      cursor: pointer;
    }
    button:hover { background: #2563eb; }
    .msg { margin-bottom: 1rem; font-weight: bold; }
    .erro { color: #f87171; }
    .sucesso { color: #4ade80; }
    a { color: #38bdf8; text-decoration: none; }
  </style>
</head>
<body>
  <div class="container">
    <h2>Reenviar Confirmação</h2>

    <?php if ($erro): ?>
      <p class="msg erro"><?= $erro ?></p>
    <?php endif; ?>
    <?php if ($sucesso): ?>
      <p class="msg sucesso"><?= $sucesso ?></p>
    <?php endif; ?>

    <form action="reenviar_confirmacao.php" method="POST">
      <input type="email" name="email" placeholder="Digite seu e-mail" required>
      <button type="submit">Reenviar</button>
    </form>

    <p><a href="index.php">Voltar ao login</a></p>
  </div>
</body>
</html>
