<?php
// Página de Termos de Uso
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Termos de Uso - Eduka Plus Angola</title>
  <link rel="stylesheet" href="css/inicio.css">
  <link rel="stylesheet" href="css/login.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    body { background: #f7f9fb; }
    .terms-hero {
      padding: 70px 0 40px;
      background: linear-gradient(135deg, #2f80ed 0%, #1a73e8 100%);
      color: #fff;
    }
    .terms-hero h1 { font-size: 3rem; margin-bottom: 12px; }
    .terms-hero p { max-width: 760px; margin: 0 auto; font-size: 1.05rem; line-height: 1.8; color: rgba(255,255,255,0.92); }
    .terms-badge { display: inline-flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.12); padding: 10px 18px; border-radius: 999px; font-weight: 600; margin-top: 18px; }
    .terms-container { max-width: 1100px; margin: -50px auto 50px; padding: 0 20px; }
    .terms-card { background: #ffffff; border-radius: 24px; box-shadow: 0 20px 50px rgba(15, 23, 42, 0.08); overflow: hidden; }
    .terms-card .content { padding: 42px 48px; }
    .terms-card h2 { margin-top: 0; color: #102a43; font-size: 1.7rem; }
    .terms-card p { color: #475569; line-height: 1.85; font-size: 1rem; margin-bottom: 22px; }
    .terms-card ul { list-style: none; padding-left: 0; margin: 18px 0 32px; }
    .terms-card ul li { position: relative; padding-left: 32px; margin-bottom: 14px; color: #475569; }
    .terms-card ul li::before { content: '\f058'; font-family: 'Font Awesome 5 Free'; font-weight: 900; position: absolute; left: 0; top: 0; color: #2f80ed; }
    .terms-section { margin-bottom: 34px; }
    .terms-section:last-child { margin-bottom: 0; }
    .terms-highlight { background: #eef6ff; border-left: 4px solid #2f80ed; padding: 20px 22px; border-radius: 12px; color: #1d4ed8; }
    .terms-footer { padding: 40px 20px; text-align: center; color: #64748b; }
    .terms-footer a { color: #2f80ed; text-decoration: none; }
    @media (max-width: 768px) {
      .terms-hero { padding-top: 50px; }
      .terms-hero h1 { font-size: 2.25rem; }
      .terms-card .content { padding: 30px 24px; }
    }
  </style>
</head>
<body>
  <header>
    <div class="logo">
      <img src="imagens/logo.jpg" alt="Eduka Plus Angola logo">
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

  <section class="terms-hero">
    <div class="terms-container">
      <div style="text-align:center; max-width: 880px; margin: 0 auto;">
        <span class="terms-badge"><i class="fas fa-shield-alt"></i> Termos de Uso Oficiais</span>
        <h1>Termos de Uso da Plataforma Eduka Plus Angola</h1>
        <p>Estes termos estabelecem os direitos, deveres e responsabilidades entre você e a Eduka Plus Angola. Nosso objetivo é garantir uma experiência segura, transparente e justa para todos os alunos, professores e parceiros.</p>
      </div>
    </div>
  </section>

  <div class="terms-container">
    <div class="terms-card">
      <div class="content">
        <div class="terms-section">
          <h2>1. Âmbito e Aceitação</h2>
          <p>Ao acessar e utilizar a plataforma Eduka Plus Angola, você aceita integralmente estes Termos de Uso e as políticas relacionadas, incluindo nossa Política de Privacidade. Caso não concorde com algum item, recomendamos não utilizar a plataforma.</p>
        </div>

        <div class="terms-section">
          <h2>2. Cadastro de Usuário</h2>
          <p>Para utilizar recursos exclusivos, é necessário criar uma conta válida. O usuário se compromete a fornecer informações verdadeiras, completas e atualizadas. É responsabilidade do usuário manter sigilo sobre suas credenciais de acesso e notificar imediatamente a plataforma em caso de uso indevido.</p>
        </div>

        <div class="terms-section">
          <h2>3. Acesso e Uso da Plataforma</h2>
          <p>A plataforma é destinada a uso pessoal e legítimo. É proibido executar atividades que comprometam a segurança, a disponibilidade ou a integridade do serviço, tais como:</p>
          <ul>
            <li>Distribuir conteúdo ilegal, fraudulento ou discriminatório</li>
            <li>Realizar qualquer tipo de ataque, intrusão ou abuso</li>
            <li>Compartilhar credenciais de acesso com terceiros</li>
          </ul>
        </div>

        <div class="terms-section">
          <h2>4. Direitos de Propriedade Intelectual</h2>
          <p>Todo conteúdo disponível na Eduka Plus Angola, incluindo cursos, materiais, imagens, vídeos, marcas e textos, é protegido por direitos autorais e propriedade intelectual. Sua reprodução, distribuição ou modificação sem autorização prévia é proibida.</p>
        </div>

        <div class="terms-section">
          <h2>5. Responsabilidades do Usuário</h2>
          <p>O usuário deve utilizar a plataforma com responsabilidade e respeito. Qualquer comportamento que infrinja leis, direitos de terceiros, ou estas regras poderá resultar na suspensão ou exclusão da conta.</p>
        </div>

        <div class="terms-section">
          <h2>6. Segurança e Privacidade</h2>
          <p>A Eduka Plus Angola implementa medidas de segurança para proteger seus dados. Ainda assim, o usuário deve adotar práticas seguras, como o uso de senhas fortes e a proteção de seus dispositivos.</p>
          <div class="terms-highlight">
            <strong>Importante:</strong> para obter detalhes sobre coleta e uso de dados, consulte nossa <a href="privacidade.php">Política de Privacidade</a>.
          </div>
        </div>

        <div class="terms-section">
          <h2>7. Limitação de Responsabilidade</h2>
          <p>A Eduka Plus Angola não se responsabiliza por danos indiretos, perdas financeiras, perda de dados ou interrupções de serviço, exceto quando exigido por lei. Nosso compromisso é manter o serviço estável, mas não garantimos disponibilidade ininterrupta.</p>
        </div>

        <div class="terms-section">
          <h2>8. Atualizações destes Termos</h2>
          <p>Podemos revisar estes Termos de Uso periodicamente para melhorar a experiência da plataforma. As mudanças serão divulgadas nesta página. Recomendamos que você verifique a versão atual antes de usar o serviço.</p>
        </div>

        <div class="terms-section">
          <h2>9. Contato</h2>
          <p>Se você tiver dúvidas ou sugestões sobre estes Termos, entre em contato conosco por meio da nossa página de <a href="contacto.php">Contato</a> ou pela Central de Ajuda.</p>
        </div>
      </div>
    </div>
  </div>

  <footer class="login-page-footer">
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
  </script>
</body>
</html>
