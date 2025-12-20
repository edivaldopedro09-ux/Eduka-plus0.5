<?php
session_start();
require_once("config.php");

if (!isset($_GET['token'])) {
    die("Token inválido!");
}

$token = $_GET['token'];

$sql = "SELECT id FROM usuarios WHERE reset_token=? AND reset_expira > NOW()";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    die("Link inválido ou expirado!");
}
$user = $result->fetch_assoc();
$_SESSION['reset_user_id'] = $user['id'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Redefinir Senha - Eduka Plus Angola</title>
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
      max-width: 400px;
      text-align: center;
    }
    input {
      width: 100%;
      padding: 0.9rem;
      margin-bottom: 1rem;
      border: 1px solid #334155;
      border-radius: 8px;
      background: #0f172a;
      color: #e0e6ed;
    }
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
  </style>
</head>
<body>
  <div class="container">
    <h2>Redefinir Senha</h2>
    <form action="processa_redefinicao.php" method="POST">
      <input type="password" name="nova_senha" placeholder="Nova senha" required>
      <input type="password" name="confirma_senha" placeholder="Confirme a senha" required>
      <button type="submit">Redefinir</button>
    </form>
  </div>
</body>
</html>
