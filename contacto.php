<?php
session_start();
require_once("sms_config.php");

$erro = "";
$sucesso = "";
$nome = "";
$email = "";
$telefone = "";
$assunto = "";
$mensagem = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $assunto = trim($_POST['assunto'] ?? '');
    $mensagem = trim($_POST['mensagem'] ?? '');

    if (empty($nome) || empty($email) || empty($telefone) || empty($assunto) || empty($mensagem)) {
        $erro = '❌ Todos os campos são obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = '❌ E-mail inválido.';
    } elseif (!preg_match('/^\+?[0-9]{8,15}$/', $telefone)) {
        $erro = '❌ Número de telefone inválido. Informe em formato internacional, por exemplo +244923456789.';
    } else {
        $smsBody = sprintf(
            "Olá %s, recebemos sua mensagem sobre '%s'. Em breve nossa equipe de suporte entrará em contato. Obrigado por escolher a Eduka Plus Angola.",
            $nome,
            $assunto
        );

        $resultado = sendSms($telefone, $smsBody);

        if ($resultado['success']) {
            $sucesso = '✅ SMS enviado com sucesso para ' . htmlspecialchars($telefone) . '. Você receberá a confirmação em instantes.';
            $nome = $email = $telefone = $assunto = $mensagem = '';
        } else {
            $erro = '❌ Falha ao enviar SMS: ' . htmlspecialchars($resultado['error']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contato - Eduka Plus Angola</title>
  <link rel="stylesheet" href="css/inicio.css">
  <link rel="stylesheet" href="css/login.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    body { background: #f7f9fb; }
    .contact-hero {
      padding: 70px 0 40px;
      background: linear-gradient(135deg, #1a73e8 0%, #2f80ed 100%);
      color: #fff;
    }
    .contact-hero h1 { font-size: 3rem; margin-bottom: 14px; }
    .contact-hero p { max-width: 780px; margin: 0 auto; font-size: 1.05rem; line-height: 1.75; color: rgba(255,255,255,0.92); }
    .contact-container { max-width: 1100px; margin: -50px auto 60px; padding: 0 20px; }
    .contact-grid { display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 30px; }
    .contact-card { background: #ffffff; border-radius: 24px; box-shadow: 0 20px 50px rgba(15, 23, 42, 0.08); overflow: hidden; }
    .contact-card .content { padding: 42px 42px 36px; }
    .contact-card h2 { margin-top: 0; color: #102a43; font-size: 1.95rem; }
    .contact-card p { color: #475569; line-height: 1.85; font-size: 1rem; margin-bottom: 24px; }
    .contact-card ul { list-style: none; padding: 0; margin: 0; }
    .contact-card li { display: flex; align-items: flex-start; gap: 14px; margin-bottom: 18px; color: #334155; }
    .contact-card li i { color: #2f80ed; margin-top: 4px; }
    .contact-form { display: grid; gap: 18px; }
    .contact-form label { display: block; font-weight: 600; margin-bottom: 8px; color: #102a43; }
    .contact-form input,
    .contact-form textarea { width: 100%; border: 1px solid #d1d5db; border-radius: 14px; padding: 14px 16px; font-size: 0.95rem; background: #f8fafc; color: #0f172a; }
    .contact-form textarea { min-height: 170px; resize: vertical; }
    .contact-form .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
    .contact-form .form-row .form-group:last-child { margin-bottom: 0; }
    .contact-form button { border: none; border-radius: 14px; background: #2f80ed; color: #fff; font-size: 1rem; padding: 16px 22px; cursor: pointer; transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .contact-form button:hover { transform: translateY(-1px); box-shadow: 0 20px 40px rgba(47, 128, 237, 0.18); }
    .contact-support { padding: 42px 42px 36px; }
    .contact-support h2 { margin-top: 0; font-size: 1.75rem; color: #102a43; }
    .contact-support p { color: #475569; line-height: 1.8; margin-bottom: 18px; }
    .contact-support .support-box { background: #f1f5f9; border-radius: 18px; padding: 20px; margin-bottom: 18px; }
    .contact-support .support-box strong { display: block; margin-bottom: 8px; color: #102a43; }
    .contact-support .support-link { color: #2f80ed; text-decoration: none; }
    @media (max-width: 960px) {
      .contact-grid { grid-template-columns: 1fr; }
      .contact-form .form-row { grid-template-columns: 1fr; }
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

  <section class="contact-hero">
    <div class="contact-container">
      <div style="text-align:center; max-width: 860px; margin: 0 auto;">
        <span class="policy-badge"><i class="fas fa-headset"></i> Suporte e Contato</span>
        <h1>Fale com a Eduka Plus Angola</h1>
        <p>Estamos aqui para ajudar você em cada etapa da sua jornada. Se precisar de suporte, tiver dúvidas sobre cursos ou quiser enviar uma sugestão, fale conosco.</p>
      </div>
    </div>
  </section>

  <div class="contact-container">
    <div class="contact-grid">
      <div class="contact-card">
        <div class="content">
          <h2>Estamos prontos para ajudar</h2>
          <p>Nosso time de atendimento está disponível para responder perguntas sobre matrículas, cursos, certificados e suporte técnico. Conte conosco para uma experiência rápida e transparente.</p>

          <ul>
            <li><i class="fas fa-phone-alt"></i><div><strong>Telefone</strong><br>+244 222 000 000</div></li>
            <li><i class="fas fa-envelope"></i><div><strong>E-mail</strong><br>suporte@edukaplus.ao</div></li>
            <li><i class="fas fa-map-marker-alt"></i><div><strong>Endereço</strong><br>Luanda, Angola</div></li>
            <li><i class="fas fa-clock"></i><div><strong>Horário</strong><br>Segunda a Sexta, 08:00–18:00</div></li>
          </ul>

          <div class="contact-support">
            <div class="support-box">
              <strong>Precisa de resposta imediata?</strong>
              <p>Visite nossa <a href="faq.php" class="support-link">Central de Ajuda</a> para encontrar respostas rápidas sobre cursos, pagamento e certificados.</p>
            </div>
            <div class="support-box">
              <strong>Prefere falar com um consultor?</strong>
              <p>Nosso time de atendimento pode orientar você na escolha do curso ideal e ajudar com o processo de matrícula.</p>
            </div>
          </div>
        </div>
      </div>

      <div class="contact-card">
        <div class="content">
          <h2>Envie sua mensagem</h2>
          <p>Preencha o formulário abaixo e responderemos o mais rápido possível.</p>

          <?php if (!empty($erro)): ?>
            <div class="erro-alert" style="margin-bottom: 20px; background: #fee2e2; color: #b91c1c; border-radius: 12px; padding: 16px; border: 1px solid #fecaca;">
              <i class="fas fa-exclamation-circle"></i>
              <span><?php echo $erro; ?></span>
            </div>
          <?php endif; ?>
          <?php if (!empty($sucesso)): ?>
            <div class="sucesso-alert" style="margin-bottom: 20px; background: #dcfce7; color: #166534; border-radius: 12px; padding: 16px; border: 1px solid #bbf7d0;">
              <i class="fas fa-check-circle"></i>
              <span><?php echo $sucesso; ?></span>
            </div>
          <?php endif; ?>

          <form class="contact-form" action="contacto.php" method="POST">
            <div class="form-row">
              <div class="form-group">
                <label for="nome">Nome completo</label>
                <input type="text" id="nome" name="nome" placeholder="Seu nome completo" required value="<?php echo htmlspecialchars($nome); ?>">
              </div>
              <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" placeholder="Seu melhor e-mail" required value="<?php echo htmlspecialchars($email); ?>">
              </div>
            </div>
            <div class="form-group">
              <label for="telefone">Telefone</label>
              <input type="text" id="telefone" name="telefone" placeholder="+244923456789" required value="<?php echo htmlspecialchars($telefone); ?>">
            </div>
            <div class="form-group">
              <label for="assunto">Assunto</label>
              <input type="text" id="assunto" name="assunto" placeholder="Motivo do contato" required value="<?php echo htmlspecialchars($assunto); ?>">
            </div>
            <div class="form-group">
              <label for="mensagem">Mensagem</label>
              <textarea id="mensagem" name="mensagem" placeholder="Escreva sua mensagem" required><?php echo htmlspecialchars($mensagem); ?></textarea>
            </div>
            <button type="submit">Enviar mensagem</button>
          </form>
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
        <h4>Suporte</h4>
        <a href="ajuda.php"><i class="fas fa-question-circle"></i> Ajuda</a>
        <a href="faq.php"><i class="fas fa-comments"></i> FAQ</a>
        <a href="contacto.php"><i class="fas fa-envelope"></i> Contato</a>
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
  </script>
</body>
</html>
