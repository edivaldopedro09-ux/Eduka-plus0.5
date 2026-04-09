<?php
session_start();
require_once("config.php");
require_once("google_oauth_config.php");

$erro = "";
$sucesso = "";

if (isset($_SESSION['google_error'])) {
    $erro = $_SESSION['google_error'];
    unset($_SESSION['google_error']);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    $tipo = $_POST['tipo'];
    $termos = isset($_POST['termos']);

    if (empty($nome) || empty($email) || empty($senha) || empty($confirmar_senha) || empty($tipo)) {
        $erro = "❌ Todos os campos são obrigatórios!";
    } elseif (!$termos) {
        $erro = "❌ Você deve aceitar os Termos de Uso para continuar.";
    } elseif (!in_array($tipo, ['aluno', 'professor'], true)) {
        $erro = "❌ Tipo de conta inválido.";
    } elseif ($senha !== $confirmar_senha) {
        $erro = "❌ As senhas não coincidem!";
    } elseif (strlen($senha) < 6) {
        $erro = "❌ A senha deve ter no mínimo 6 caracteres!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "❌ E-mail inválido!";
    } else {
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
                session_regenerate_id(true);
                $_SESSION['usuario_id']   = $novo_id;
                $_SESSION['usuario_nome'] = $nome;
                $_SESSION['usuario_tipo'] = strtolower($tipo);

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
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cadastrar - Eduka Plus Angola</title>
  <link rel="stylesheet" href="css/inicio.css">
  <link rel="stylesheet" href="css/register.css">
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
      <a href="register.php" class="btn-cadastro active"><i class="fas fa-user-plus"></i> Cadastro</a>
    </nav>
    <div class="hamburger" onclick="toggleMenu()">
      <div></div><div></div><div></div>
    </div>
  </header>

  <main class="register-page">
    <div class="register-container">
      <div class="register-header">
        <div class="register-logo">
          <i class="fas fa-user-plus"></i>
          <h1>Criar Conta</h1>
        </div>
        <h2>Junte-se à Comunidade Eduka Plus</h2>
        <p>Comece sua jornada de aprendizado em menos de 2 minutos</p>
      </div>

      <div class="register-steps">
        <div class="step active">
          <div class="step-number">1</div>
          <span>Informações</span>
        </div>
        <div class="step-line"></div>
        <div class="step">
          <div class="step-number">2</div>
          <span>Selecionar Plano</span>
        </div>
        <div class="step-line"></div>
        <div class="step">
          <div class="step-number">3</div>
          <span>Confirmar</span>
        </div>
      </div>

      <?php if (!empty($erro)): ?>
        <div class="erro-alert">
          <i class="fas fa-exclamation-circle"></i>
          <span><?php echo $erro; ?></span>
        </div>
      <?php endif; ?>

      <form action="register.php" method="POST" class="register-form" id="registerForm">
        <div class="form-grid">
          <div class="form-group">
            <label for="nome">
              <i class="fas fa-user"></i> Nome Completo
            </label>
            <input 
              type="text" 
              id="nome" 
              name="nome" 
              placeholder="Digite seu nome completo" 
              required
              value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>"
            >
            <small class="form-hint">Como você gostaria de ser chamado</small>
          </div>

          <div class="form-group">
            <label for="email">
              <i class="fas fa-envelope"></i> E-mail
            </label>
            <input 
              type="email" 
              id="email" 
              name="email" 
              placeholder="Digite seu melhor e-mail" 
              required
              value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
            >
            <small class="form-hint">Usaremos para login e notificações</small>
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
                placeholder="Crie uma senha segura" 
                required
                minlength="6"
              >
              <button type="button" class="toggle-password" onclick="togglePassword('senha')">
                <i class="fas fa-eye"></i>
              </button>
            </div>
            <div class="password-strength">
              <div class="strength-bar"></div>
              <span class="strength-text">Força da senha: <span id="strengthText">Fraca</span></span>
            </div>
          </div>

          <div class="form-group">
            <label for="confirmar_senha">
              <i class="fas fa-lock"></i> Confirmar Senha
            </label>
            <div class="password-wrapper">
              <input 
                type="password" 
                id="confirmar_senha" 
                name="confirmar_senha" 
                placeholder="Digite a senha novamente" 
                required
                minlength="6"
              >
              <button type="button" class="toggle-password" onclick="togglePassword('confirmar_senha')">
                <i class="fas fa-eye"></i>
              </button>
            </div>
            <small class="form-hint" id="passwordMatch"></small>
          </div>

          <div class="form-group full-width">
            <label for="tipo">
              <i class="fas fa-user-tag"></i> Tipo de Conta
            </label>
            <div class="account-type-selector">
              <div class="type-option">
                <input type="radio" id="aluno" name="tipo" value="aluno" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] === 'aluno') ? 'checked' : 'checked'; ?>>
                <label for="aluno" class="type-label">
                  <div class="type-icon">
                    <i class="fas fa-user-graduate"></i>
                  </div>
                  <div class="type-content">
                    <h4>Aluno</h4>
                    <p>Acesso completo aos cursos, certificados e comunidade</p>
                  </div>
                </label>
              </div>
              
              
            </div>
          </div>
        </div>

        <div class="form-options">
          <label class="checkbox-container">
            <input type="checkbox" name="termos" id="termos" required>
            <span class="checkmark"></span>
            <span>Eu concordo com os <a href="termos.php" target="_blank">Termos de Uso</a> e <a href="privacidade.php" target="_blank">Política de Privacidade</a></span>
          </label>
          
          <label class="checkbox-container">
            <input type="checkbox" name="newsletter" id="newsletter" checked>
            <span class="checkmark"></span>
            <span>Desejo receber novidades e ofertas especiais por e-mail</span>
          </label>
        </div>

        <button type="submit" class="btn-register-submit" id="submitBtn">
          <i class="fas fa-rocket"></i> Criar Minha Conta
        </button>

        <div class="divider">
          <span>ou</span>
        </div>

        <button type="button" class="btn-social btn-google" onclick="loginWithGoogle()">
          <i class="fab fa-google"></i> Cadastrar com Google
        </button>
      </form>

      <div class="register-footer">
        <p>
          Já tem uma conta? 
          <a href="login.php" class="login-link">
            Faça login aqui
          </a>
        </p>
        <p class="register-note">
          <i class="fas fa-shield-alt"></i> Suas informações estão seguras conosco
        </p>
      </div>

      <div class="register-benefits">
        <h3>O que você ganha ao se cadastrar:</h3>
        <div class="benefits-grid">
          <div class="benefit">
            <i class="fas fa-play-circle"></i>
            <h4>Acesso Gratuito</h4>
            <p>7 dias grátis em cursos selecionados</p>
          </div>
          <div class="benefit">
            <i class="fas fa-certificate"></i>
            <h4>Certificado</h4>
            <p>Certificação reconhecida ao completar cursos</p>
          </div>
          <div class="benefit">
            <i class="fas fa-users"></i>
            <h4>Comunidade</h4>
            <p>Network com outros profissionais</p>
          </div>
          <div class="benefit">
            <i class="fas fa-headset"></i>
            <h4>Suporte 24/7</h4>
            <p>Ajuda sempre que precisar</p>
          </div>
        </div>
      </div>
    </div>

    <div class="register-side">
      <div class="side-content">
        <h3>Comece sua Jornada de Sucesso</h3>
        <p>Transforme sua carreira com os melhores cursos online de Angola</p>
        
        <div class="success-story">
          <div class="story-header">
            <img src="https://images.unsplash.com/photo-1494790108755-2616b612b786?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=80" alt="História de sucesso">
            <div>
              <h4>Carlos Mendes</h4>
              <span>Ex-aluno, hoje Professor</span>
            </div>
          </div>
          <p>"Comecei como aluno, hoje sou professor na plataforma. A Eduka Plus mudou minha vida profissional completamente."</p>
        </div>

        <div class="features-list">
          <div class="feature-item">
            <i class="fas fa-check-circle"></i>
            <span>Acesso vitalício aos cursos comprados</span>
          </div>
          <div class="feature-item">
            <i class="fas fa-check-circle"></i>
            <span>Certificado digital reconhecido</span>
          </div>
          <div class="feature-item">
            <i class="fas fa-check-circle"></i>
            <span>Atualizações gratuitas de conteúdo</span>
          </div>
          <div class="feature-item">
            <i class="fas fa-check-circle"></i>
            <span>Comunidade exclusiva de alunos</span>
          </div>
          <div class="feature-item">
            <i class="fas fa-check-circle"></i>
            <span>Suporte técnico especializado</span>
          </div>
        </div>

        
      </div>
    </div>
  </main>

  <footer class="register-page-footer">
    <div class="footer-content">
      <div class="footer-section">
        <h4><img src="imagens/logo.jpg" alt=""> Eduka Plus Angola</h4>
        <p>Educação de qualidade para o futuro de Angola</p>
      </div>
      <div class="footer-section">
        <h4>Links Rápidos</h4>
        <a href="index.php"><i class="fas fa-home"></i> Início</a>
        <a href="cursos.php"><i class="fas fa-book"></i> Cursos</a>
        <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
      </div>
      <div class="footer-section">
        <h4>Ajuda</h4>
        <a href="ajuda.php"><i class="fas fa-question-circle"></i> Central de Ajuda</a>
        <a href="faq.php"><i class="fas fa-comments"></i> FAQ</a>
        <a href="contacto.php"><i class="fas fa-envelope"></i> Contato</a>
      </div>
      <div class="footer-section">
        <h4>Legal</h4>
        <a href="termos.php"><i class="fas fa-file-contract"></i> Termos de Uso</a>
        <a href="privacidade.php"><i class="fas fa-shield-alt"></i> Privacidade</a>
        <a href="cookies.php"><i class="fas fa-cookie-bite"></i> Política de Cookies</a>
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

  function togglePassword(fieldId) {
    const passwordInput = document.getElementById(fieldId);
    const toggleButton = passwordInput.parentNode.querySelector('.toggle-password i');
    
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

  // Função para login com Google
  function loginWithGoogle() {
    // Redirecionar para a URL de autenticação do Google
    window.location.href = '<?php echo getGoogleLoginUrl("register"); ?>';
  }

  // Validação de senha em tempo real
  const senhaInput = document.getElementById('senha');
  const confirmarSenhaInput = document.getElementById('confirmar_senha');
  const strengthBar = document.querySelector('.strength-bar');
  const strengthText = document.getElementById('strengthText');
  const passwordMatch = document.getElementById('passwordMatch');
  const submitBtn = document.getElementById('submitBtn');

  senhaInput.addEventListener('input', function() {
    const senha = this.value;
    let strength = 0;
    
    // Verificar força da senha
    if (senha.length >= 6) strength += 1;
    if (senha.length >= 8) strength += 1;
    if (/[A-Z]/.test(senha)) strength += 1;
    if (/[0-9]/.test(senha)) strength += 1;
    if (/[^A-Za-z0-9]/.test(senha)) strength += 1;
    
    // Atualizar barra de força
    const width = (strength / 5) * 100;
    strengthBar.style.width = width + '%';
    
    // Atualizar texto
    let text = 'Fraca';
    let color = '#ef4444';
    
    if (strength >= 3) {
      text = 'Média';
      color = '#f59e0b';
    }
    if (strength >= 4) {
      text = 'Forte';
      color = '#10b981';
    }
    
    strengthText.textContent = text;
    strengthBar.style.backgroundColor = color;
  });

  confirmarSenhaInput.addEventListener('input', function() {
    const senha = senhaInput.value;
    const confirmar = this.value;
    
    if (confirmar === '') {
      passwordMatch.textContent = '';
      passwordMatch.style.color = '';
    } else if (senha === confirmar) {
      passwordMatch.textContent = '✓ As senhas coincidem';
      passwordMatch.style.color = '#10b981';
    } else {
      passwordMatch.textContent = '✗ As senhas não coincidem';
      passwordMatch.style.color = '#ef4444';
    }
  });

  // Validação do formulário
  document.getElementById('registerForm').addEventListener('submit', function(e) {
    const senha = senhaInput.value;
    const confirmar = confirmarSenhaInput.value;
    const termos = document.getElementById('termos');
    
    if (senha !== confirmar) {
      e.preventDefault();
      passwordMatch.textContent = '✗ As senhas não coincidem';
      passwordMatch.style.color = '#ef4444';
      confirmarSenhaInput.focus();
    }
    
    if (!termos.checked) {
      e.preventDefault();
      alert('Você precisa aceitar os Termos de Uso para continuar');
      termos.focus();
    }
  });

  // Animações
  document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.register-form');
    form.style.opacity = '0';
    form.style.transform = 'translateY(20px)';
    
    setTimeout(() => {
      form.style.transition = 'all 0.6s ease';
      form.style.opacity = '1';
      form.style.transform = 'translateY(0)';
    }, 300);
  });

  // Fechar menu ao clicar em um link no mobile
  document.querySelectorAll('#menu a').forEach(link => {
    link.addEventListener('click', () => {
      if(window.innerWidth <= 768) {
        document.getElementById('menu').classList.remove('active');
      }
    });
  });
  </script>
</body>
</html>