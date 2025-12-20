<?php
session_start();
require_once("config.php");

// Buscar cursos no banco (6 cursos mais recentes/inscritos, pode adaptar)
$sql = "SELECT id, titulo, descricao, imagem FROM cursos ORDER BY criado_em DESC LIMIT 12";
$result = $conn->query($sql);
$cursos = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cursos - Eduka Plus Angola</title>
  <link rel="stylesheet" href="css/cursos.css">
</head>
<body>

  <!-- Navbar -->
  <header>
    <div class="logo">Eduka Plus Angola</div>
    <nav id="menu">
      <a href="index.php">Início</a>
      <a href="cursos.php">Cursos</a>
      <a href="verificar_certificado.php">Validar Certificado</a>
      <a href="login.php" class="btn-login">Login</a>
      <a href="cadastro.php" class="btn-cadastro">Cadastro</a>
    </nav>
    <div class="hamburger" onclick="document.getElementById('menu').classList.toggle('active')">
      <div></div><div></div><div></div>
    </div>
  </header>

  <!-- Lista de Cursos -->
  <section class="container">
    <div class="titulo">
      <h1>Nossos Cursos</h1>
      <p>Escolha o curso ideal para você e comece a aprender agora mesmo!</p>
    </div>

    <div class="grid">
      <?php if (count($cursos) > 0): ?>
        <?php foreach ($cursos as $curso): ?>
          <div class="curso">
            <?php if (!empty($curso['imagem'])): ?>
              <img src="uploads/<?php echo htmlspecialchars($curso['imagem']); ?>" alt="Imagem do curso">
            <?php else: ?>
              <img src="uploads/default.jpg" alt="Imagem padrão do curso">
            <?php endif; ?>
            <h3><?php echo htmlspecialchars($curso['titulo']); ?></h3>
            <p><?php echo nl2br(htmlspecialchars(substr($curso['descricao'], 0, 100))); ?>...</p>
            <a href="curso_detalhes.php?id=<?php echo $curso['id']; ?>">Saiba Mais</a>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="text-align:center; color: var(--muted);">Nenhum curso disponível no momento.</p>
      <?php endif; ?>
    </div>
  </section>

  <!-- Footer -->
  <footer>
    <div>
      <a href="ajuda.php">Ajuda</a> |
      <a href="privacidade.php">Privacidade</a> |
      <a href="termos.php">Termos</a>
    </div>
    <p>© <?php echo date("Y"); ?> Eduka Plus Angola — Todos os direitos reservados.</p>
  </footer>
<!-- Botão WhatsApp Fixo -->
<a href="https://wa.me/244958922590" target="_blank" class="whatsapp-btn">
  <img src="uploads/what.png" alt="WhatsApp" />
</a>


</body>
</html>
