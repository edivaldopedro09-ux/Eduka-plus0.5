<?php
session_start();
require_once("config.php");

$erro = "";
$sucesso = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    
    // Verificar se o email existe
    $sql = "SELECT id, nome FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $usuario = $result->fetch_assoc();
        
        // Gerar token único
        $token = bin2hex(random_bytes(32));
        $expiracao = date("Y-m-d H:i:s", strtotime("+1 hour"));
        
        // Salvar token no banco
        $update = $conn->prepare("UPDATE usuarios SET reset_token = ?, reset_expira = ? WHERE id = ?");
        $update->bind_param("ssi", $token, $expiracao, $usuario['id']);
        
        if ($update->execute()) {
            // Aqui você enviaria o email na aplicação real
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/redefinir_senha.php?token=" . $token;
            
            // Para demonstração, mostramos o link
            $sucesso = "Enviamos um link de recuperação para seu e-mail!";
            $sucesso .= "<br><br><small><strong>Para desenvolvimento:</strong> <a href='$reset_link' style='color:#38bdf8;'>$reset_link</a></small>";
            
            // Em produção, você usaria algo como:
            // $to = $email;
            // $subject = "Redefinição de Senha - Eduka Plus";
            // $message = "Clique no link para redefinir sua senha: $reset_link";
            // mail($to, $subject, $message);
        } else {
            $erro = "❌ Erro ao processar solicitação. Tente novamente.";
        }
    } else {
        $erro = "❌ E-mail não encontrado em nosso sistema.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recuperar Senha - Eduka Plus Angola</title>
  <link rel="stylesheet" href="css/inicio.css">
  <link rel="stylesheet" href="css/recuperar_senha.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
  <!-- Header consistente com o site principal -->
  <header>
    <div class="logo">
      <img src="imagens/logo.jpg" alt="">
      <span>Eduka Plus</span>
      <span class="logo-sub">Angola</span>
    </div>
    <nav id="menu">
      <a href="index.php"><i class="fas fa-home"></i> Início</a>
      <a href="cursos.php"><i class="fas fa-book"></i> Cursos</a>
      <a href="verificar_certificado.php"><i class="fas fa-certificate"></i> Validar Certificado</a>
      <a href="login.php" class="btn-login"><i class="fas fa-sign-in-alt"></i> Login</a>
      <a href="register.php" class="btn-cadastro"><i class="fas fa-user-plus"></i> Cadastro</a>
    </nav>
    <div class="hamburger" onclick="toggleMenu()">
      <div></div><div></div><div></div>
    </div>
  </header>

  <main class="recovery-page">
    <div class="recovery-container">
      <div class="recovery-header">
        <div class="recovery-logo">
          <i class="fas fa-key"></i>
          <h1>Recuperar Senha</h1>
        </div>
        <h2>Esqueceu sua senha?</h2>
        <p>Não se preocupe! Vamos ajudá-lo a recuperar o acesso à sua conta.</p>
      </div>

      <?php if (!empty($erro)): ?>
        <div class="erro-alert">
          <i class="fas fa-exclamation-circle"></i>
          <span><?php echo $erro; ?></span>
        </div>
      <?php endif; ?>

      <?php if (!empty($sucesso)): ?>
        <div class="sucesso-alert">
          <i class="fas fa-check-circle"></i>
          <div>
            <strong>Email enviado com sucesso!</strong>
            <span><?php echo $sucesso; ?></span>
          </div>
        </div>
      <?php endif; ?>

      <?php if (empty($sucesso)): ?>
        <form action="recuperar_senha.php" method="POST" class="recovery-form">
          <div class="form-group">
            <label for="email">
              <i class="fas fa-envelope"></i> Digite seu e-mail cadastrado
            </label>
            <input 
              type="email" 
              id="email" 
              name="email" 
              placeholder="seu.email@exemplo.com" 
              required
              value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
            >
            <small class="form-hint">Enviaremos um link de recuperação para este e-mail</small>
          </div>

          <button type="submit" class="btn-recovery-submit">
            <i class="fas fa-paper-plane"></i> Enviar Link de Recuperação
          </button>
        </form>

        <div class="recovery-instructions">
          <h3><i class="fas fa-info-circle"></i> Como funciona:</h3>
          <div class="instruction-steps">
            <div class="step">
              <div class="step-number">1</div>
              <div class="step-content">
                <h4>Digite seu e-mail</h4>
                <p>Informe o e-mail associado à sua conta</p>
              </div>
            </div>
            <div class="step">
              <div class="step-number">2</div>
              <div class="step-content">
                <h4>Receba o link</h4>
                <p>Enviaremos um link seguro para seu e-mail</p>
              </div>
            </div>
            <div class="step">
              <div class="step-number">3</div>
              <div class="step-content">
                <h4>Redefina sua senha</h4>
                <p>Crie uma nova senha pelo link recebido</p>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <div class="recovery-footer">
        <p>
          Lembrou sua senha? 
          <a href="login.php" class="login-link">
            <i class="fas fa-sign-in-alt"></i> Faça login aqui
          </a>
        </p>
        <p>
          Não tem uma conta? 
          <a href="register.php" class="register-link">
            <i class="fas fa-user-plus"></i> Cadastre-se agora
          </a>
        </p>
      </div>

      <div class="security-note">
        <i class="fas fa-shield-alt"></i>
        <div>
          <h4>Segurança Garantida</h4>
          <p>Seu link de recuperação expira em 1 hora e pode ser usado apenas uma vez.</p>
        </div>
      </div>
    </div>

    <div class="recovery-side">
      <div class="side-content">
        <h3>Sua Segurança é Nossa Prioridade</h3>
        <p>Utilizamos os mais altos padrões de segurança para proteger sua conta.</p>
        
        <div class="security-features">
          <div class="feature">
            <i class="fas fa-lock"></i>
            <div>
              <h4>Criptografia SSL</h4>
              <p>Todas as comunicações são criptografadas</p>
            </div>
          </div>
          <div class="feature">
            <i class="fas fa-clock"></i>
            <div>
              <h4>Link Temporário</h4>
              <p>Links expiram automaticamente em 1 hora</p>
            </div>
          </div>
          <div class="feature">
            <i class="fas fa-history"></i>
            <div>
              <h4>Logs de Atividade</h4>
              <p>Monitoramos todas as atividades na sua conta</p>
            </div>
          </div>
          <div class="feature">
            <i class="fas fa-user-shield"></i>
            <div>
              <h4>Autenticação Segura</h4>
              <p>Proteção contra acessos não autorizados</p>
            </div>
          </div>
        </div>

        <div class="contact-support">
          <h4>Precisa de ajuda?</h4>
          <p>Entre em contato com nosso suporte técnico</p>
          <a href="contacto.php" class="btn-support">
            <i class="fas fa-headset"></i> Contatar Suporte
          </a>
        </div>
      </div>
    </div>
  </main>

  <footer class="recovery-page-footer">
    <div class="footer-content">
      <div class="footer-section">
        <h4><img src="imagens/logo.jpg" alt=""></i> Eduka Plus Angola</h4>
        <p>Comprometidos com sua segurança e aprendizado</p>
      </div>
      <div class="footer-section">
        <h4>Links Úteis</h4>
        <a href="index.php"><i class="fas fa-home"></i> Início</a>
        <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
        <a href="register.php"><i class="fas fa-user-plus"></i> Cadastro</a>
      </div>
      <div class="footer-section">
        <h4>Suporte</h4>
        <a href="faq.php"><i class="fas fa-question-circle"></i> FAQ</a>
        <a href="contacto.php"><i class="fas fa-envelope"></i> Contato</a>
        <a href="ajuda.php"><i class="fas fa-life-ring"></i> Ajuda</a>
      </div>
      <div class="footer-section">
        <h4>Legal</h4>
        <a href="privacidade.php"><i class="fas fa-shield-alt"></i> Privacidade</a>
        <a href="termos.php"><i class="fas fa-file-contract"></i> Termos</a>
        <a href="cookies.php"><i class="fas fa-cookie-bite"></i> Cookies</a>
      </div>
    </div>
    
    <div class="footer-bottom">
      <p>© <?php echo date("Y"); ?> Eduka Plus Angola — Versão 3.0.0</p>
      <p class="footer-copy">Todos os direitos reservados</p>
    </div>
  </footer>

  <script>
  function toggleMenu() {
    document.getElementById('menu').classList.toggle('active');
  }

  // Fechar menu ao clicar em um link no mobile
  document.querySelectorAll('#menu a').forEach(link => {
    link.addEventListener('click', () => {
      if(window.innerWidth <= 768) {
        document.getElementById('menu').classList.remove('active');
      }
    });
  });

  // Animações
  document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.recovery-form');
    if (form) {
      form.style.opacity = '0';
      form.style.transform = 'translateY(20px)';
      
      setTimeout(() => {
        form.style.transition = 'all 0.6s ease';
        form.style.opacity = '1';
        form.style.transform = 'translateY(0)';
      }, 300);
    }
  });
  </script>
</body>
</html>