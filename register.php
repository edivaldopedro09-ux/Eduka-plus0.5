<?php
session_start();
require_once("config.php");

$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $tipo = $_POST['tipo']; // professor ou aluno

    // Verifica se e-mail já existe
    $sql = "SELECT id FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $erro = "❌ Este e-mail já está cadastrado!";
    } else {
        $hash = password_hash($senha, PASSWORD_DEFAULT);

        $insert = $conn->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)");
        $insert->bind_param("ssss", $nome, $email, $hash, $tipo);

        if ($insert->execute()) {
            $novo_id = $insert->insert_id;

            // Inicia sessão automaticamente
            $_SESSION['usuario_id']   = $novo_id;
            $_SESSION['usuario_nome'] = $nome;
            $_SESSION['usuario_tipo'] = $tipo;

            // Redireciona conforme o tipo
            if ($tipo === "professor") {
                header("Location: painel_professor/dashboard.php");
            } else {
                header("Location: painel_aluno/dashboard.php");
            }
            exit();
        } else {
            $erro = "❌ Erro ao cadastrar. Tente novamente.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cadastrar - Eduka Plus Angola</title>
  <style>
    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
     background: linear-gradient(-45deg,#0d47a1,#1565c0,#1e3a8a,#0f172a);
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
    input, select {
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
    a { color: #38bdf8; text-decoration: none; }
  </style>
</head>
<body>
  <div class="container">
    <h2>Criar Conta</h2>

    <?php if ($erro): ?>
      <p class="msg erro"><?= $erro ?></p>
    <?php endif; ?>

    <form action="register.php" method="POST">
      <input type="text" name="nome" placeholder="Seu nome completo" required>
      <input type="email" name="email" placeholder="Seu e-mail" required>
      <input type="password" name="senha" placeholder="Crie uma senha" required>
      <select name="tipo" required>
        <option value="" disabled selected>Selecione o tipo de usuário</option>
        <option value="aluno">Aluno</option>
        <option value="professor">Professor</option>
      </select>
      <button type="submit">Cadastrar</button>
    </form>

    <p>Já tem conta? <a href="login.php">Fazer login</a></p>

     <!-- 🚀 FOOTER -->
  <footer style="
  margin-top:20px;
  padding:10px;
  text-align:center;
  font-size:14px;
  color:var(--muted);
  border-top:1px solid var(--border);
 
  border-radius:10px;
">
  <div style="margin-bottom:8px;">
    <a href="ajuda.php" style="color:#38bdf8; text-decoration:none; margin:0 10px;">Ajuda</a> |
    <a href="privacidade.php" style="color:#38bdf8; text-decoration:none; margin:0 10px;">Privacidade</a> |
    <a href="termos.php" style="color:#38bdf8; text-decoration:none; margin:0 10px;">Termos</a>
  </div>
   <p>Eduka Plus Angola Versão 3.0.0</p>
  <div>
    © <?php echo date("Y"); ?> Eduka Plus Angola — Todos os direitos reservados.
  </div>
</footer>
  </div>
</body>
</html>
