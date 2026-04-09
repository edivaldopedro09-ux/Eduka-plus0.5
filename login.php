<?php
ob_start();
session_start();
require_once("config.php");
require_once("google_oauth_config.php");

$erro = "";

if (isset($_SESSION['google_error'])) {
    $erro = $_SESSION['google_error'];
    unset($_SESSION['google_error']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    if (empty($email) || empty($senha)) {
        $erro = "❌ E-mail e senha são obrigatórios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "❌ E-mail inválido.";
    } else {
        $sql = "SELECT * FROM usuarios WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($senha, $user['senha'])) {
                session_regenerate_id(true);
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
                $erro = "❌ E-mail ou senha incorretos.";
            }
        } else {
            $erro = "❌ E-mail ou senha incorretos.";
        }
    }
}
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Eduka Plus Angola | Login</title>
  <link rel="stylesheet" href="css/inicio.css">
  <link rel="stylesheet" href="css/login.css">
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
      <a href="login.php" class="btn-login active"><i class="fas fa-sign-in-alt"></i> Login</a>
      <a href="register.php" class="btn-cadastro"><i class="fas fa-user-plus"></i> Cadastro</a>
    </nav>
    <div class="hamburger" onclick="toggleMenu()">
      <div></div><div></div><div></div>
    </div>
  </header>

  <main class="login-page">
    <div class="login-container">
      <div class="login-header">
        <div class="login-logo">
          <img src="imagens/logo.jpg" alt="">
          <h1>Eduka Plus  </h1>
        </div>  
        <h2>Bem-vindo de volta</h2>
        <p>Acesse sua conta para continuar sua jornada de aprendizado</p>
      </div>
      
      <?php if (!empty($erro)): ?>
        <div class="erro-alert">
          <i class="fas fa-exclamation-circle"></i>
          <span><?php echo $erro; ?></span>
        </div>
      <?php endif; ?>

      <form action="login.php" method="POST" class="login-form">
        <div class="form-group">
          <label for="email">
            <i class="fas fa-envelope"></i> E-mail
          </label>
          <input 
            type="email" 
            id="email" 
            name="email" 
            placeholder="Digite seu e-mail" 
            required
            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
          >
        </div>
        
        <div class="form-group">
          <label for="senha">
            <i class="fas fa-lock"></i> Senha
          </label>
          <div class="password-wrapper">
            <input 
              type="password" 
              id="senha" 
              name="senha" 
              placeholder="Digite sua senha" 
              required
            >
            <button type="button" class="toggle-password" onclick="togglePassword()">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>

        <div class="form-options">
          <label class="checkbox-container">
            <input type="checkbox" name="lembrar">
            <span class="checkmark"></span>
            Lembrar-me
          </label>
          <a href="recuperar_senha.php" class="forgot-password">
            Esqueceu a senha?
          </a>
        </div>

        <button type="submit" class="btn-login-submit">
          <i class="fas fa-sign-in-alt"></i> Entrar na Conta
        </button>

        <div class="divider">
          <span>ou</span>
        </div>

        <button type="button" class="btn-social btn-google" onclick="loginWithGoogle()">
          <i class="fab fa-google"></i> Continuar com Google
        </button>
      </form>

      <div class="login-footer">
        <p>
          Novo na plataforma? 
          <a href="register.php" class="register-link">
            Crie uma conta agora
          </a>
        </p>
        <p class="login-note">
          <i class="fas fa-shield-alt"></i> Sua segurança é nossa prioridade
        </p>
      </div>

      <div class="login-features">
        <div class="feature">
          <i class="fas fa-shield-check"></i>
          <span>Autenticação segura</span>
        </div>
        <div class="feature">
          <i class="fas fa-clock"></i>
          <span>Acesso 24/7</span>
        </div>
        <div class="feature">
          <i class="fas fa-headset"></i>
          <span>Suporte dedicado</span>
        </div>
      </div>
    </div>

    <div class="login-side">
      <div class="side-content">
        <h3>Transformando Educação em Angola</h3>
        <p>Junte-se a mais de 5.000 alunos que já transformaram suas carreiras com a Eduka Plus</p>
        
        <div class="testimonial">
          <div class="testimonial-content">
            <i class="fas fa-quote-left"></i>
            <p>"A Eduka Plus mudou completamente minha trajetória profissional. Consegui uma promoção após concluir o curso de Gestão de Projetos."</p>
            <div class="testimonial-author">
              <strong>Maria Silva</strong>
              <span>Aluna desde 2025</span>
            </div>
          </div>
        </div>

        
      </div>
    </div>
  </main>

  <footer class="login-page-footer">
    <div class="footer-content">
      <div class="footer-section">
        <h4><img src="imagens/logo.jpg" alt=""> Eduka Plus Angola</h4>
        <p>Excelência em educação online para o futuro de Angola</p>
      </div>
      <div class="footer-section">
        <h4>Acesso Rápido</h4>
        <a href="index.php"><i class="fas fa-home"></i> Início</a>
        <a href="cursos.php"><i class="fas fa-book"></i> Cursos</a>
        <a href="register.php"><i class="fas fa-user-plus"></i> Cadastro</a>
      </div>
      <div class="footer-section">
        <h4>Suporte</h4>
        <a href="ajuda.php"><i class="fas fa-question-circle"></i> Ajuda</a>
        <a href="contacto.php"><i class="fas fa-envelope"></i> Contato</a>
        <a href="faq.php"><i class="fas fa-comments"></i> FAQ</a>
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

  function loginWithGoogle() {
    // Redirecionar para a URL de autenticação do Google
    window.location.href = '<?php echo getGoogleLoginUrl("login"); ?>';
  }

  function togglePassword() {
    const passwordInput = document.getElementById('senha');
    const toggleButton = document.querySelector('.toggle-password i');
    
    if (passwordInput.type === 'password') {
      passwordInput.type = 'text';
      toggleButton.classList.remove('fa-eye');
      toggleButton.classList.add('fa-eye-slash');
    } else {
      passwordInput.type = 'password';
      toggleButton.classList.remove('fa-eye-slash');
      toggleButton.classList.add('fa-eye');
    }
  }

  // Fechar menu ao clicar em um link no mobile
  document.querySelectorAll('#menu a').forEach(link => {
    link.addEventListener('click', () => {
      if(window.innerWidth <= 768) {
        document.getElementById('menu').classList.remove('active');
      }
    });
  });

  // Animação suave para o formulário
  document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.login-form');
    form.style.opacity = '0';
    form.style.transform = 'translateY(20px)';
    
    setTimeout(() => {
      form.style.transition = 'all 0.6s ease';
      form.style.opacity = '1';
      form.style.transform = 'translateY(0)';
    }, 300);
  });
  </script>
</body>
</html>