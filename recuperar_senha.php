<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recuperar Senha - Eduka Plus Angola</title>
  <link rel="stylesheet" href="css/recuperar_senha.css">
  
</head>
<body>
  <div class="container">
    <h2>Recuperar Senha</h2>
    <p>Digite seu e-mail e enviaremos um link para redefinir sua senha.</p>
    <form action="processa_recuperacao.php" method="POST">
      <input type="email" name="email" placeholder="Seu e-mail" required>
      <button type="submit">Enviar link</button>
    </form>
    <p><a href="index.php">Voltar ao login</a></p>
  </div>
</body>
</html>
