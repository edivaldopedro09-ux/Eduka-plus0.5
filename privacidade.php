<?php
// Página de Política de Privacidade
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Política de Privacidade - Eduka Plus Angola</title>
  <link rel="stylesheet" href="css/inicio.css">
  <link rel="stylesheet" href="css/login.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    body { background: #f7f9fb; }
    .policy-hero {
      padding: 70px 0 40px;
      background: linear-gradient(135deg, #2f80ed 0%, #1a73e8 100%);
      color: #fff;
    }
    .policy-hero h1 { font-size: 3rem; margin-bottom: 12px; }
    .policy-hero p { max-width: 760px; margin: 0 auto; font-size: 1.05rem; line-height: 1.8; color: rgba(255,255,255,0.92); }
    .policy-badge { display: inline-flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.12); padding: 10px 18px; border-radius: 999px; font-weight: 600; margin-top: 18px; }
    .policy-container { max-width: 1100px; margin: -50px auto 50px; padding: 0 20px; }
    .policy-card { background: #ffffff; border-radius: 24px; box-shadow: 0 20px 50px rgba(15, 23, 42, 0.08); overflow: hidden; }
    .policy-card .content { padding: 42px 48px; }
    .policy-card h2 { margin-top: 0; color: #102a43; font-size: 1.7rem; }
    .policy-card p { color: #475569; line-height: 1.85; font-size: 1rem; margin-bottom: 22px; }
    .policy-card ul { list-style: none; padding-left: 0; margin: 18px 0 32px; }
    .policy-card ul li { position: relative; padding-left: 32px; margin-bottom: 14px; color: #475569; }
    .policy-card ul li::before { content: '\f111'; font-family: 'Font Awesome 5 Free'; font-weight: 900; position: absolute; left: 0; top: 4px; color: #2f80ed; font-size: 10px; }
    .policy-section { margin-bottom: 34px; }
    .policy-highlight { background: #eef6ff; border-left: 4px solid #2f80ed; padding: 20px 22px; border-radius: 12px; color: #1d4ed8; }
    .policy-footer { padding: 40px 20px; text-align: center; color: #64748b; }
    .policy-footer a { color: #2f80ed; text-decoration: none; }
    @media (max-width: 768px) {
      .policy-hero { padding-top: 50px; }
      .policy-hero h1 { font-size: 2.25rem; }
      .policy-card .content { padding: 30px 24px; }
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

  <section class="policy-hero">
    <div class="policy-container">
      <div style="text-align:center; max-width: 880px; margin: 0 auto;">
        <span class="policy-badge"><i class="fas fa-user-shield"></i> Política de Privacidade</span>
        <h1>Como tratamos seus dados com transparência e segurança</h1>
        <p>Na Eduka Plus Angola, sua privacidade é prioridade. Esta política explica quais informações coletamos, como usamos e de que forma protegemos seus dados.</p>
      </div>
    </div>
  </section>

  <div class="policy-container">
    <div class="policy-card">
      <div class="content">
        <div class="policy-section">
          <h2>1. Quais dados coletamos</h2>
          <p>Coletamos informações necessárias para criar sua conta, oferecer nossos cursos e melhorar sua experiência. Entre os dados coletados estão:</p>
          <ul>
            <li>Nome completo e endereço de e-mail</li>
            <li>Dados de autenticação, como senha e token de login</li>
            <li>Informações de curso e progresso dentro da plataforma</li>
            <li>Dados opcionais fornecidos em mensagens ou suporte</li>
          </ul>
        </div>

        <div class="policy-section">
          <h2>2. Como utilizamos seus dados</h2>
          <p>Utilizamos as informações para fornecer nossos serviços, manter a plataforma funcional e oferecer suporte personalizado. As finalidades incluem:</p>
          <ul>
            <li>Gerenciar seu cadastro e autenticação</li>
            <li>Entregar cursos, certificados e notificações</li>
            <li>Melhorar conteúdos e experiência do usuário</li>
            <li>Prestar suporte e resolver solicitações</li>
          </ul>
        </div>

        <div class="policy-section">
          <h2>3. Compartilhamento de informações</h2>
          <p>Não vendemos seus dados a terceiros. Podemos compartilhar informações apenas nas seguintes situações:</p>
          <ul>
            <li>Com prestadores de serviço que suportam a plataforma</li>
            <li>Quando exigido por lei ou ordem judicial</li>
            <li>Para cumprir com termos contratuais e proteger direitos</li>
          </ul>
        </div>

        <div class="policy-section">
          <h2>4. Segurança dos dados</h2>
          <p>Adotamos medidas técnicas e administrativas para proteger seus dados contra acesso não autorizado, alteração, divulgação ou destruição. Isso inclui métodos de criptografia, controle de acesso e revisões periódicas de segurança.</p>
          <div class="policy-highlight">
            <strong>Nota:</strong> apesar dos nossos esforços, nenhum sistema é 100% à prova de falhas. Caso identifiquemos um incidente, informaremos as autoridades competentes e os usuários afetados quando necessário.</p>
          </div>
        </div>

        <div class="policy-section">
          <h2>5. Seus direitos</h2>
          <p>Você possui direitos sobre seus dados pessoais, incluindo:</p>
          <ul>
            <li>Acesso às informações que mantemos</li>
            <li>Correção de dados incompletos ou incorretos</li>
            <li>Exclusão de dados quando permitida por lei</li>
            <li>Oposição ao tratamento quando aplicável</li>
          </ul>
        </div>

        <div class="policy-section">
          <h2>6. Cookies e tecnologias semelhantes</h2>
          <p>Utilizamos cookies para melhorar a navegação, manter sessões ativas e personalizar a experiência do usuário. Você pode gerenciar ou desativar cookies nas configurações do seu navegador, mas algumas funcionalidades podem ser afetadas.</p>
        </div>

        <div class="policy-section">
          <h2>7. Alterações nesta política</h2>
          <p>Podemos atualizar esta Política de Privacidade periodicamente. Quando houver mudanças significativas, informaremos usuários por e-mail ou avisos dentro da plataforma. Consulte esta página regularmente para estar ciente da versão mais recente.</p>
        </div>

        <div class="policy-section">
          <h2>8. Contato</h2>
          <p>Se tiver dúvidas ou quiser exercer seus direitos, entre em contato conosco através da página de <a href="contacto.php">Contato</a>.</p>
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
