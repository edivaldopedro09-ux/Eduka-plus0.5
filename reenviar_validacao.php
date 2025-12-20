<?php
session_start();
require_once("config.php");
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

$mensagem = "";

if($_SERVER['REQUEST_METHOD']==='POST'){
    $email = trim($_POST['email']);
    $stmt = $conn->prepare("SELECT id,nome,email_validado FROM usuarios WHERE email=?");
    $stmt->bind_param("s",$email);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->num_rows===1){
        $user=$res->fetch_assoc();
        if($user['email_validado']==1) $mensagem="✅ E-mail já validado.";
        else{
            $token_email = bin2hex(random_bytes(32));
            $update = $conn->prepare("UPDATE usuarios SET token_email=? WHERE id=?");
            $update->bind_param("si",$token_email,$user['id']);
            $update->execute();
            $link = "https://edukaplus.free.nf/validar_email.php?token=$token_email";

            $mail = new PHPMailer(true);
            try{
                $mail->isSMTP();
                $mail->Host='smtp.gmail.com';
                $mail->SMTPAuth=true;
                $mail->Username='wenytechnology@gmail.com';
                $mail->Password='edukaplus';
                $mail->SMTPSecure='tls';
                $mail->Port=587;
                $mail->setFrom('wenytechnology@gmail.com','Eduka Plus Angola');
                $mail->addAddress($email,$user['nome']);
                $mail->isHTML(true);
                $mail->Subject='Confirme seu e-mail - Eduka Plus Angola';
                $mail->Body="Olá {$user['nome']},<br>Clique no link para validar seu e-mail:<br><a href='$link'>$link</a>";
                $mail->send();
                $mensagem="✅ E-mail de validação reenviado!";
            }catch(Exception $e){$mensagem="❌ Erro ao enviar: {$mail->ErrorInfo}";}
        }
    }else{$mensagem="❌ E-mail não encontrado.";}
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reenviar validação de e-mail</title>
<style>
body { display:flex; justify-content:center; align-items:center; min-height:100vh; background:#0f172a; font-family:sans-serif; color:#e0e6ed; }
.container { background:#1e293b; padding:2rem; border-radius:12px; width:100%; max-width:400px; text-align:center; }
input,button { width:100%; padding:0.8rem; margin:0.5rem 0; border-radius:8px; border:none; font-size:1rem; }
input { background:#0f172a; color:#e0e6ed; border:1px solid #334155; }
button { background:#3b82f6; color:#fff; cursor:pointer; }
button:hover { background:#2563eb; }
.msg { margin-top:1rem; font-weight:bold; }
</style>
</head>
<body>
<div class="container">
<h2>Reenviar validação de e-mail</h2>
<form method="POST">
    <input type="email" name="email" placeholder="Digite seu e-mail" required>
    <button type="submit">Reenviar e-mail</button>
</form>
<?php if(!empty($mensagem)) echo "<div class='msg'>{$mensagem}</div>"; ?>
<p><a href="login.php" style="color:#3b82f6;">Voltar ao login</a></p>
</div>
</body>
</html>
