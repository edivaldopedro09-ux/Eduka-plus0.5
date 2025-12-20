<?php
ob_start();
session_start();
require_once("config.php");
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Eduka Plus Angola | Plataforma de Educação Online</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Plataforma de aprendizado online para alunos, professores e instituições de ensino em Angola. Cursos em diversas áreas do conhecimento.">
  
  <link rel="stylesheet" href="css/inicio.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<header>
  <div class="logo">
    <img src="imagens/logo.jpg" alt="">
    <span>Eduka Plus Angola</span>
  </div>
  <nav id="menu">
    <a href="index.php" class="active"><i class="fas fa-home"></i> Início</a>
    <a href="cursos.php"><i class="fas fa-book"></i> Cursos</a>
    <a href="verificar_certificado.php"><i class="fas fa-certificate"></i> Validar Certificado</a>
    <a href="login.php" class="btn-login"><i class="fas fa-sign-in-alt"></i> Login</a>
    <a href="register.php" class="btn-cadastro"><i class="fas fa-user-plus"></i> Cadastro</a>
  </nav>
  <div class="hamburger" onclick="toggleMenu()">
    <div></div><div></div><div></div>
  </div>
</header>

<!-- HERO -->
<section class="hero">
  <div class="hero-content">
    <h1>Transforme seu Futuro com a <span class="highlight">Eduka Plus Angola</span></h1>
    <p>A plataforma de aprendizado online feita para alunos, professores e instituições de ensino em Angola.</p>
    <div class="hero-buttons">
      <a href="register.php" class="btn btn-primary"><i class="fas fa-rocket"></i> Comece Gratuitamente</a>
      <a href="cursos.php" class="btn btn-secondary"><i class="fas fa-play-circle"></i> Ver Cursos</a>
    </div>
    <div class="hero-stats">
      <div class="stat">
        <h3>+5.000</h3>
        <p>Alunos Ativos</p>
      </div>
      <div class="stat">
        <h3>+100</h3>
        <p>Cursos</p>
      </div>
      <div class="stat">
        <h3>+50</h3>
        <p>Professores</p>
      </div>
      <div class="stat">
        <h3>98%</h3>
        <p>Satisfação</p>
      </div>
    </div>
  </div>
  <div class="hero-image">
    <img src="imagens/foto.png" alt="Estudante aprendendo online">
  </div>
</section>

<!-- DESTAQUES -->
<section id="destaques">
  <div class="section-header">
    <h2>Por que escolher a Eduka Plus?</h2>
    <p>Oferecemos uma experiência de aprendizagem completa e personalizada</p>
  </div>
  <div class="grid grid-3">
    <div class="card feature-card">
      <div class="card-icon">
        <i class="fas fa-book-open"></i>
      </div>
      <h4>+100 Cursos</h4>
      <p>Diversas áreas de conhecimento para o seu crescimento profissional e pessoal.</p>
    </div>
    <div class="card feature-card">
      <div class="card-icon">
        <i class="fas fa-chalkboard-teacher"></i>
      </div>
      <h4>Professores Experientes</h4>
      <p>Aprenda com profissionais que dominam o mercado e têm experiência prática.</p>
    </div>
    <div class="card feature-card">
      <div class="card-icon">
        <i class="fas fa-globe-africa"></i>
      </div>
      <h4>Acesso em Qualquer Lugar</h4>
      <p>Estude de onde estiver, quando quiser, com acesso vitalício aos cursos.</p>
    </div>
  </div>
</section>

<!-- CURSOS POPULARES -->
<section id="cursos">
  <div class="section-header">
    <h2>Cursos Populares</h2>
    <p>Os cursos mais procurados pelos nossos alunos</p>
    <a href="cursos.php" class="btn-link">Ver todos os cursos <i class="fas fa-arrow-right"></i></a>
  </div>
  <div class="grid grid-3">
    <?php
    $sql = "
      SELECT c.*, COUNT(i.id) AS inscritos
      FROM cursos c
      LEFT JOIN inscricoes i ON i.curso_id = c.id
      GROUP BY c.id
      ORDER BY inscritos DESC
      LIMIT 6
    ";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
      while ($curso = $result->fetch_assoc()) {
        echo "<div class='card course-card'>";
        
        // Verifica se tem imagem, se não usa a imagem padrão do uploads
        $imagem = !empty($curso['imagem']) ? "uploads/".htmlspecialchars($curso['imagem']) : "imagens/informatica.png";
        
        echo "<div class='course-image'>";
        echo "<img src='$imagem' alt='".htmlspecialchars($curso['titulo'])."'>";
        echo "<div class='course-badge'>".$curso['inscritos']." alunos</div>";
        echo "</div>";
        echo "<div class='course-content'>";
        echo "<h4>".htmlspecialchars($curso['titulo'])."</h4>";
        echo "<p class='course-desc'>".substr(htmlspecialchars($curso['descricao']),0,100)."...</p>";
        echo "<div class='course-meta'>";
        echo "<span><i class='fas fa-clock'></i> 20h</span>";
        echo "<span><i class='fas fa-star'></i> 4.8</span>";
        echo "</div>";
        echo "<a href='#' class='btn-course'>Ver Curso</a>";
        echo "</div>";
        echo "</div>";
      }
    } else {
      echo "<p style='text-align:center;color:var(--muted);'>Nenhum curso disponível no momento.</p>";
    }
    ?>
  </div>
</section>

<!-- DEPOIMENTOS -->
<section id="depoimentos">
  <div class="section-header">
    <h2>O que nossos alunos dizem</h2>
    <p>Histórias de sucesso e transformação</p>
  </div>
  <div class="grid grid-3">
    <div class="card testimonial-card">
      <div class="testimonial-header">
        <img src="imagens/perfil.png" alt="Ana" class="testimonial-avatar">
        <div>
          <h5>Ana Silva</h5>
          <p>Estudante de Design</p>
        </div>
        <i class="fas fa-quote-right"></i>
      </div>
      <p class="testimonial-text">"A Eduka Plus mudou minha forma de aprender. Consegui meu primeiro emprego na área após concluir o curso de UI/UX Design."</p>
      <div class="stars">
        <i class="fas fa-star"></i>
        <i class="fas fa-star"></i>
        <i class="fas fa-star"></i>
        <i class="fas fa-star"></i>
        <i class="fas fa-star"></i>
      </div>
    </div>
    
    <div class="card testimonial-card">
      <div class="testimonial-header">
        <img src="imagens/perfil.png" alt="Carlos" class="testimonial-avatar">
        <div>
          <h5>Carlos Mendes</h5>
          <p>Professor</p>
        </div>

        <i class="fas fa-quote-right"></i>
      </div>
      <p class="testimonial-text">"Como professor, a plataforma me permite alcançar muito mais alunos. As ferramentas são intuitivas e eficientes."</p>
      <div class="stars">
        <i class="fas fa-star"></i>
        <i class="fas fa-star"></i>
        <i class="fas fa-star"></i>
        <i class="fas fa-star"></i>
        <i class="fas fa-star-half-alt"></i>
      </div>
    </div>
    
    <div class="card testimonial-card">
      <div class="testimonial-header">
        <img src="imagens/perfil.png" alt="Marta" class="testimonial-avatar">
        <div>
          <h5>Marta Costa</h5>
          <p>Gestora Educacional</p>
        </div>
        <i class="fas fa-quote-right"></i>
      </div>
      <p class="testimonial-text">"Implementamos a Eduka Plus em nossa instituição e os resultados foram excelentes. Uma plataforma completa que realmente faz a diferença."</p>
      <div class="stars">
        <i class="fas fa-star"></i>
        <i class="fas fa-star"></i>
        <i class="fas fa-star"></i>
        <i class="fas fa-star"></i>
        <i class="fas fa-star"></i>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section id="cta">
  <div class="cta-container">
    <h2>Pronto para transformar seu futuro?</h2>
    <p>Junte-se a milhares de alunos que já estão construindo suas carreiras com a Eduka Plus.</p>
    <div class="cta-buttons">
      <a href="register.php" class="btn btn-primary btn-large"><i class="fas fa-user-plus"></i> Cadastrar-se Gratuitamente</a>
      <a href="cursos.php" class="btn btn-outline"><i class="fas fa-book"></i> Explorar Cursos</a>
    </div>
    <p class="cta-note"><i class="fas fa-shield-alt"></i> 7 dias de garantia ou seu dinheiro de volta</p>
  </div>
</section>

<footer>
  <div class="footer-content">
    <div class="footer-section">
      <h3><i class="fas fa-graduation-cap"></i> Eduka Plus Angola</h3>
      <p>Plataforma de educação online dedicada ao desenvolvimento de Angola através do conhecimento.</p>
      <div class="social-icons">
        <a href="#"><i class="fab fa-facebook"></i></a>
        <a href="#"><i class="fab fa-instagram"></i></a>
        <a href="#"><i class="fab fa-linkedin"></i></a>
        <a href="#"><i class="fab fa-youtube"></i></a>
      </div>
    </div>
    <div class="footer-section">
      <h4>Links Rápidos</h4>
      <a href="index.php">Início</a>
      <a href="cursos.php">Cursos</a>
      <a href="verificar_certificado.php">Validar Certificado</a>
      <a href="login.php">Login</a>
    </div>
    <div class="footer-section">
      <h4>Suporte</h4>
      <a href="ajuda.php">Central de Ajuda</a>
      <a href="faq.php">Perguntas Frequentes</a>
      <a href="contacto.php">Contacte-nos</a>
    </div>
    <div class="footer-section">
      <h4>Legal</h4>
      <a href="privacidade.php">Política de Privacidade</a>
      <a href="termos.php">Termos de Uso</a>
      <a href="cookies.php">Política de Cookies</a>
    </div>
  </div>
  <div class="footer-bottom">
    <p>© <?php echo date("Y"); ?> Eduka Plus Angola — Todos os direitos reservados.</p>
  </div>
</footer>

<!-- Botão WhatsApp Fixo -->
<a href="https://wa.me/244958922590" target="_blank" class="whatsapp-btn">
  <i class="fab fa-whatsapp"></i>
</a>

<script src="js/inicio.js"></script>

</body>
</html>