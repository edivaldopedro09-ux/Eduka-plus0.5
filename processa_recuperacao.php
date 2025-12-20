<?php
session_start();
require_once("config.php");

// PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'libs/PHPMailer/src/Exception.php';
require 'libs/PHPMailer/src/PHPMailer.php';
require 'libs/PHPMailer/src/SMTP.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);

    $sql = "SELECT id FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        $token = bin2hex(random_bytes(32));
        $expira = date("Y-m-d H:i:s", strtotime("+1 hour"));

        $update = $conn->prepare("UPDATE usuarios SET reset_token=?, reset_expira=? WHERE id=?");
        $update->bind_param("ssi", $token, $expira, $user['id']);
        $update->execute();

        $link = "http://localhost/edukaplus/redefinir_senha.php?token=" . $token;

        $mail = new PHPMailer(true);
        try {
            //$mail->SMTPDebug = 2; // Debug opcional
            $mail->isSMTP();
            $mail->Host       = 'smtp.seudominio.com'; 
            $mail->SMTPAuth   = true;
            $mail->Username   = 'no-reply@edukaplus.com'; 
            $mail->Password   = 'SUA_SENHA_FORTE'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;

            $mail->setFrom('no-reply@edukaplus.com', 'Eduka Plus Angola');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = "Recuperação de Senha - Eduka Plus Angola";
            $mail->Body    = "
                <h2>Olá!</h2>
                <p>Você solicitou a recuperação da sua senha.</p>
                <p>Clique no link abaixo para redefinir:</p>
                <p><a href='$link'>$link</a></p>
                <p><small>Este link expira em 1 hora.</small></p>
            ";
            $mail->AltBody = "Copie e cole este link no navegador: $link";

            $mail->send();
            echo "<p style='color:green;text-align:center;'>📧 Um e-mail foi enviado com instruções de recuperação!</p>";
            echo "<p style='text-align:center;'><a href='login.php'>Voltar ao login</a></p>";
        } catch (Exception $e) {
            echo "<p style='color:red;text-align:center;'>❌ Erro ao enviar e-mail: {$mail->ErrorInfo}</p>";
        }
    } else {
        echo "<p style='color:red;text-align:center;'>❌ E-mail não encontrado!</p>";
        echo "<p style='text-align:center;'><a href='recuperar_senha.php'>Tentar novamente</a></p>";
    }
}
