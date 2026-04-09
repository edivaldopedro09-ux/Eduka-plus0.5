<?php
session_start();
require_once("config.php");

if(!isset($_GET['token'])) die("❌ Token inválido.");

$token = $_GET['token'];
$stmt = $conn->prepare("SELECT id,nome FROM usuarios WHERE token_email=? AND email_validado=0");
$stmt->bind_param("s",$token);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows===1){
    $user = $result->fetch_assoc();
    $update = $conn->prepare("UPDATE usuarios SET email_validado=1, token_email=NULL WHERE id=?");
    $update->bind_param("i",$user['id']);
    $update->execute();
    echo "<h2>E-mail validado com sucesso!</h2>";
    echo "<p>Olá {$user['nome']}, agora você pode fazer login.</p>";
    echo "<p><a href='login.php'>Login</a></p>";
}else{ echo "❌ Token inválido ou e-mail já validado.";}
?>
