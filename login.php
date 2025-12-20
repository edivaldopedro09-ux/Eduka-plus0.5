<?php
ob_start();
session_start();
require_once("config.php");

$erro = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    $sql = "SELECT * FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($senha, $user['senha'])) {
            $_SESSION['usuario_id']   = $user['id'];
            $_SESSION['usuario_nome'] = $user['nome'];
            $_SESSION['usuario_tipo'] = strtolower($user['tipo']);

            if ($_SESSION['usuario_tipo'] === 'admin') {
                header("Location: painel_admin/dashboard.php");
                exit();
            } elseif ($_SESSION['usuario_tipo'] === 'professor') {
                header("Location: painel_professor/dashboard.php");
                exit();
            } elseif ($_SESSION['usuario_tipo'] === 'aluno') {
                header("Location: painel_aluno/dashboard.php");
                exit();
            } else {
                $erro = "⚠ Tipo de usuário inválido no banco!";
            }
        } else {
            $erro = "❌ Senha incorreta!";
        }
    } else {
        $erro = "❌ Email não encontrado!";
    }
}
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Eduka Plus - Login</title>
  <link rel="stylesheet" href="css/login.css">
</head>
<body>
  <div class="login-container">
    <!-- Logo -->
 
    <h2>Eduka Plus Angola</h2>

    <?php if (!empty($erro)): ?>
      <div class="erro"><?php echo $erro; ?></div>
    <?php endif; ?>

    <form action="login.php" method="POST">
      <div class="form-group">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" placeholder="Digite seu e-mail" required>
      </div>
      <div class="form-group">
        <label for="senha">Senha</label>
        <input type="password" id="senha" name="senha" placeholder="Digite sua senha" required>
      </div>
      <button type="submit" class="btn-login">Entrar</button>
    </form>

    <div class="extra-links">
      <p><a href="recuperar_senha.php">Esqueci minha senha</a></p>
      <p><a href="register.php">Criar conta</a></p>
   
    </div>
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
   <p>Eduka Plus Angola Versão  3.0.0</p>
  <div>
    © <?php echo date("Y"); ?> Eduka Plus Angola — Todos os direitos reservados.
  </div>
</footer>
  </div>
</body>
</html>
