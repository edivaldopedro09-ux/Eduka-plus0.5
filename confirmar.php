<?php
require_once("config.php");

$msg = "";
$sucesso = false;

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Procurar o usuário com esse token
    $sql = "SELECT id, confirmado FROM usuarios WHERE confirm_token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $user = $res->fetch_assoc();

        if ($user['confirmado'] == 1) {
            $msg = "✅ Sua conta já foi confirmada. Você já pode fazer login.";
            $sucesso = true;
        } else {
            // Ativa a conta
            $upd = $conn->prepare("UPDATE usuarios SET confirmado=1, confirm_token=NULL WHERE id=?");
            $upd->bind_param("i", $user['id']);
            if ($upd->execute()) {
                $msg = "🎉 Conta confirmada com sucesso! Agora você já pode acessar.";
                $sucesso = true;
            } else {
                $msg = "❌ Ocorreu um erro ao confirmar sua conta. Tente novamente.";
            }
        }
    } else {
        $msg = "❌ Token inválido ou expirado.";
    }
} else {
    $msg = "❌ Nenhum token informado.";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link rel="stylesheet" href="css/confirmar.css">

  <title>Confirmação de Conta - Eduka Plus Angola</title>
  
</head>
<body>
  <div class="container">
    <h2>Confirmação de Conta</h2>
    <p class="msg <?= $sucesso ? 'sucesso' : 'erro' ?>"><?= $msg ?></p>

    <?php if ($sucesso): ?>
      <a href="index.php">Ir para Login</a>
    <?php else: ?>
      <a href="reenviar_confirmacao.php">Reenviar Confirmação</a>
    <?php endif; ?>
  </div>
</body>
</html>
