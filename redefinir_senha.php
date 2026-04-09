<?php
session_start();
require_once("config.php");

$erro = "";
$sucesso = "";

// Verificar token
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
    $erro = "Link inválido ou expirado! <a href='recuperar_senha.php' style='color:#38bdf8; text-decoration:none;'>Solicite um novo link</a>";
} else {
    $user = $result->fetch_assoc();
    $user_id = $user['id'];

    // Processar redefinição
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $nova_senha = $_POST['nova_senha'];
        $confirma_senha = $_POST['confirma_senha'];
        
        if (empty($nova_senha) || empty($confirma_senha)) {
            $erro = "❌ Todos os campos são obrigatórios!";
        } elseif ($nova_senha !== $confirma_senha) {
            $erro = "❌ As senhas não coincidem!";
        } elseif (strlen($nova_senha) < 6) {
            $erro = "❌ A senha deve ter no mínimo 6 caracteres!";
        } else {
            $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            
            // Atualizar senha e limpar token
            $update = $conn->prepare("UPDATE usuarios SET senha=?, reset_token=NULL, reset_expira=NULL WHERE id=?");
            $update->bind_param("si", $hash, $user_id);
            
            if ($update->execute()) {
                $sucesso = "✅ Senha redefinida com sucesso! Você será redirecionado para o login...";
                header("refresh:3;url=login.php");
            } else {
                $erro = "❌ Erro ao redefinir senha. Tente novamente.";
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
  <title>Redefinir Senha - Eduka Plus Angola</title>
  <link rel="stylesheet" href="css/inicio.css">
  <link rel="stylesheet" href="css/redefinir_senha.css">
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

  <main class="reset-page">
    <div class="reset-container">
      <div class="reset-header">
        <div class="reset-logo">
          <i class="fas fa-lock"></i>
          <h1>Redefinir Senha</h1>
        </div>
        <h2>Crie uma nova senha segura</h2>
        <p>Escolha uma senha forte para proteger sua conta</p>
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
            <strong>Senha Redefinida com Sucesso!</strong>
            <span><?php echo $sucesso; ?></span>
          </div>
        </div>
      <?php elseif ($result->num_rows === 1): ?>
        <form action="redefinir_senha.php?token=<?php echo htmlspecialchars($token); ?>" method="POST" class="reset-form" id="resetForm">
          <div class="form-group">
            <label for="nova_senha">
              <i class="fas fa-lock"></i> Nova Senha
            </label>
            <div class="password-wrapper">
              <input 
                type="password" 
                id="nova_senha" 
                name="nova_senha" 
                placeholder="Digite sua nova senha" 
                required
                minlength="6"
                autocomplete="new-password"
              >
              <button type="button" class="toggle-password" onclick="togglePassword('nova_senha')">
                <i class="fas fa-eye"></i>
              </button>
            </div>
            <small class="form-hint">Mínimo de 6 caracteres</small>
          </div>

          <div class="form-group">
            <label for="confirma_senha">
              <i class="fas fa-lock"></i> Confirmar Senha
            </label>
            <div class="password-wrapper">
              <input 
                type="password" 
                id="confirma_senha" 
                name="confirma_senha" 
                placeholder="Digite a senha novamente" 
                required
                minlength="6"
                autocomplete="new-password"
              >
              <button type="button" class="toggle-password" onclick="togglePassword('confirma_senha')">
                <i class="fas fa-eye"></i>
              </button>
            </div>
            <small class="form-hint" id="passwordMatch"></small>
          </div>

          <div class="password-strength">
            <div class="strength-bar"></div>
            <span class="strength-text">Força da senha: <span id="strengthText">Fraca</span></span>
          </div>

          <div class="password-requirements">
            <h4><i class="fas fa-shield-alt"></i> Requisitos de segurança:</h4>
            <ul>
              <li id="req-length"><i class="fas fa-circle"></i> Mínimo 6 caracteres</li>
              <li id="req-number"><i class="fas fa-circle"></i> Pelo menos 1 número</li>
              <li id="req-upper"><i class="fas fa-circle"></i> Pelo menos 1 letra maiúscula</li>
            </ul>
          </div>

          <button type="submit" class="btn-reset-submit" id="submitBtn">
            <i class="fas fa-key"></i> Redefinir Senha
          </button>

          <div class="reset-note">
            <i class="fas fa-info-circle"></i>
            <p>Após redefinir sua senha, você será redirecionado para a página de login.</p>
          </div>
        </form>

        <div class="security-tips">
          <h3><i class="fas fa-lightbulb"></i> Dicas para uma senha segura:</h3>
          <div class="tips-grid">
            <div class="tip">
              <i class="fas fa-ruler-combined"></i>
              <h4>Use 8+ caracteres</h4>
              <p>Quanto mais longa, mais segura</p>
            </div>
            <div class="tip">
              <i class="fas fa-random"></i>
              <h4>Misture caracteres</h4>
              <p>Letras, números e símbolos</p>
            </div>
            <div class="tip">
              <i class="fas fa-ban"></i>
              <h4>Evite dados pessoais</h4>
              <p>Não use nome ou data de nascimento</p>
            </div>
            <div class="tip">
              <i class="fas fa-sync-alt"></i>
              <h4>Altere regularmente</h4>
              <p>Troque sua senha periodicamente</p>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <div class="reset-footer">
        <p>
          Lembrou sua senha? 
          <a href="login.php" class="login-link">
            <i class="fas fa-sign-in-alt"></i> Faça login aqui
          </a>
        </p>
        <p>
          Precisa de ajuda? 
          <a href="contacto.php" class="help-link">
            <i class="fas fa-headset"></i> Contate nosso suporte
          </a>
        </p>
      </div>
    </div>

    <div class="reset-side">
      <div class="side-content">
        <h3>Sua Segurança em Primeiro Lugar</h3>
        <p>Implementamos medidas de segurança avançadas para proteger sua conta.</p>
        
        <div class="security-features">
          <div class="feature">
            <div class="feature-icon">
              <i class="fas fa-user-shield"></i>
            </div>
            <div class="feature-content">
              <h4>Proteção Multi-fator</h4>
              <p>Camadas extras de segurança para sua conta</p>
            </div>
          </div>
          
          <div class="feature">
            <div class="feature-icon">
              <i class="fas fa-history"></i>
            </div>
            <div class="feature-content">
              <h4>Logs de Atividade</h4>
              <p>Monitoramento contínuo de acessos</p>
            </div>
          </div>
          
          <div class="feature">
            <div class="feature-icon">
              <i class="fas fa-bell"></i>
            </div>
            <div class="feature-content">
              <h4>Notificações Imediatas</h4>
              <p>Alertas para atividades suspeitas</p>
            </div>
          </div>
          
          <div class="feature">
            <div class="feature-icon">
              <i class="fas fa-database"></i>
            </div>
            <div class="feature-content">
              <h4>Backups Seguros</h4>
              <p>Seus dados são copiados regularmente</p>
            </div>
          </div>
        </div>

        <div class="quick-links">
          <h4>Links Rápidos</h4>
          <a href="ajuda.php"><i class="fas fa-question-circle"></i> Central de Ajuda</a>
          <a href="faq.php"><i class="fas fa-comments"></i> Perguntas Frequentes</a>
          <a href="privacidade.php"><i class="fas fa-shield-alt"></i> Política de Privacidade</a>
        </div>
      </div>
    </div>
  </main>

  <footer class="reset-page-footer">
    <div class="footer-content">
      <div class="footer-section">
        <h4><img src="imagens/logo.jpg" alt=""> Eduka Plus Angola</h4>
        <p>Educação e segurança caminhando juntas</p>
      </div>
      <div class="footer-section">
        <h4>Recursos</h4>
        <a href="index.php"><i class="fas fa-home"></i> Início</a>
        <a href="cursos.php"><i class="fas fa-book"></i> Cursos</a>
        <a href="recuperar_senha.php"><i class="fas fa-key"></i> Recuperar Senha</a>
      </div>
      <div class="footer-section">
        <h4>Suporte</h4>
        <a href="contacto.php"><i class="fas fa-envelope"></i> Contato</a>
        <a href="faq.php"><i class="fas fa-question-circle"></i> FAQ</a>
        <a href="ajuda.php"><i class="fas fa-life-ring"></i> Ajuda</a>
      </div>
      <div class="footer-section">
        <h4>Legal</h4>
        <a href="termos.php"><i class="fas fa-file-contract"></i> Termos de Uso</a>
        <a href="privacidade.php"><i class="fas fa-shield-alt"></i> Privacidade</a>
        <a href="cookies.php"><i class="fas fa-cookie-bite"></i> Cookies</a>
      </div>
    </div>
    
    <div class="footer-bottom">
      <p>© <?php echo date("Y"); ?> Eduka Plus Angola — Versão 3.0.0</p>
      <p class="footer-copy">Sistema de recuperação de senha seguro</p>
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

  // Validação de senha em tempo real
  const novaSenhaInput = document.getElementById('nova_senha');
  const confirmaSenhaInput = document.getElementById('confirma_senha');
  const strengthBar = document.querySelector('.strength-bar');
  const strengthText = document.getElementById('strengthText');
  const passwordMatch = document.getElementById('passwordMatch');
  const submitBtn = document.getElementById('submitBtn');
  
  // Elementos dos requisitos
  const reqLength = document.getElementById('req-length');
  const reqNumber = document.getElementById('req-number');
  const reqUpper = document.getElementById('req-upper');

  function updatePasswordRequirements(password) {
    // Verificar comprimento
    if (password.length >= 6) {
      reqLength.innerHTML = '<i class="fas fa-check-circle" style="color:#10b981"></i> Mínimo 6 caracteres';
    } else {
      reqLength.innerHTML = '<i class="fas fa-circle" style="color:#ef4444"></i> Mínimo 6 caracteres';
    }
    
    // Verificar número
    if (/\d/.test(password)) {
      reqNumber.innerHTML = '<i class="fas fa-check-circle" style="color:#10b981"></i> Pelo menos 1 número';
    } else {
      reqNumber.innerHTML = '<i class="fas fa-circle" style="color:#ef4444"></i> Pelo menos 1 número';
    }
    
    // Verificar letra maiúscula
    if (/[A-Z]/.test(password)) {
      reqUpper.innerHTML = '<i class="fas fa-check-circle" style="color:#10b981"></i> Pelo menos 1 letra maiúscula';
    } else {
      reqUpper.innerHTML = '<i class="fas fa-circle" style="color:#ef4444"></i> Pelo menos 1 letra maiúscula';
    }
  }

  if (novaSenhaInput) {
    novaSenhaInput.addEventListener('input', function() {
      const senha = this.value;
      
      // Atualizar requisitos
      updatePasswordRequirements(senha);
      
      // Calcular força
      let strength = 0;
      if (senha.length >= 6) strength += 1;
      if (senha.length >= 8) strength += 2;
      if (/[A-Z]/.test(senha)) strength += 1;
      if (/[0-9]/.test(senha)) strength += 1;
      if (/[^A-Za-z0-9]/.test(senha)) strength += 1;
      
      const width = Math.min((strength / 6) * 100, 100);
      
      if (strengthBar) {
        strengthBar.style.width = width + '%';
        
        let text = 'Muito Fraca';
        let color = '#ef4444';
        
        if (strength >= 2) {
          text = 'Fraca';
          color = '#f97316';
        }
        if (strength >= 3) {
          text = 'Média';
          color = '#f59e0b';
        }
        if (strength >= 4) {
          text = 'Forte';
          color = '#10b981';
        }
        if (strength >= 5) {
          text = 'Muito Forte';
          color = '#059669';
        }
        
        strengthText.textContent = text;
        strengthBar.style.backgroundColor = color;
      }
      
      // Verificar se senhas coincidem
      if (confirmaSenhaInput && confirmaSenhaInput.value !== '') {
        checkPasswordMatch();
      }
    });
  }

  function checkPasswordMatch() {
    const senha = novaSenhaInput.value;
    const confirmar = confirmaSenhaInput.value;
    
    if (confirmar === '') {
      passwordMatch.textContent = '';
      passwordMatch.style.color = '';
      submitBtn.disabled = true;
      submitBtn.style.opacity = '0.5';
    } else if (senha === confirmar) {
      passwordMatch.textContent = '✓ As senhas coincidem';
      passwordMatch.style.color = '#10b981';
      submitBtn.disabled = false;
      submitBtn.style.opacity = '1';
    } else {
      passwordMatch.textContent = '✗ As senhas não coincidem';
      passwordMatch.style.color = '#ef4444';
      submitBtn.disabled = true;
      submitBtn.style.opacity = '0.5';
    }
  }

  if (confirmaSenhaInput && novaSenhaInput) {
    confirmaSenhaInput.addEventListener('input', checkPasswordMatch);
  }

  // Validação do formulário
  document.getElementById('resetForm').addEventListener('submit', function(e) {
    const senha = novaSenhaInput.value;
    const confirmar = confirmaSenhaInput.value;
    
    if (senha !== confirmar) {
      e.preventDefault();
      passwordMatch.textContent = '✗ As senhas não coincidem';
      passwordMatch.style.color = '#ef4444';
      confirmaSenhaInput.focus();
      return false;
    }
    
    if (senha.length < 6) {
      e.preventDefault();
      alert('A senha deve ter no mínimo 6 caracteres');
      novaSenhaInput.focus();
      return false;
    }
    
    // Feedback visual ao enviar
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Redefinindo...';
    submitBtn.disabled = true;
  });

  // Animações
  document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.reset-form');
    if (form) {
      form.style.opacity = '0';
      form.style.transform = 'translateY(20px)';
      
      setTimeout(() => {
        form.style.transition = 'all 0.6s ease';
        form.style.opacity = '1';
        form.style.transform = 'translateY(0)';
      }, 300);
    }
    
    // Desabilitar botão inicialmente
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.style.opacity = '0.5';
    }
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