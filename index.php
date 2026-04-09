<?php
ob_start();
session_start();
require_once("config.php");
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Eduka Plus Angola | Excelência em Educação Online</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Plataforma de aprendizado online premium para alunos, professores e instituições de ensino em Angola.">
  
  <link rel="stylesheet" href="css/inicio.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
<header>
  <div class="logo">
    <img src="imagens/logo.jpg" alt="">
    <span>Eduka Plus</span>
    <span class="logo-sub">Angola</span>
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
    <div class="hero-badge">
      <span><i class="fas fa-star"></i> Plataforma Premium</span>
    </div>
    <h1>Elevando a <span class="highlight">Educação</span> em Angola ao Próximo Nível</h1>
    <p>Uma experiência de aprendizado transformadora com conteúdo exclusivo, mentoria personalizada e certificação reconhecida.</p>
    <div class="hero-buttons">
      <a href="register.php" class="btn btn-primary">
        <i class="fas fa-rocket"></i> Começar Agora - Gratuito
      </a>
      <a href="#cursos" class="btn btn-outline">
        <i class="fas fa-play-circle"></i> Ver Demonstração
      </a>
    </div>
    <div class="hero-stats">
      <div class="stat">
        <div class="stat-icon">
          <i class="fas fa-users"></i>
        </div>
        <div>
          <h3>+5.247</h3>
          <p>Alunos Ativos</p>
        </div>
      </div>
      <div class="stat">
        <div class="stat-icon">
          <i class="fas fa-book"></i>
        </div>
        <div>
          <h3>127</h3>
          <p>Cursos Premium</p>
        </div>
      </div>
      <div class="stat">
        <div class="stat-icon">
          <i class="fas fa-chalkboard-teacher"></i>
        </div>
        <div>
          <h3>68</h3>
          <p>Especialistas</p>
        </div>
      </div>
    </div>
  </div>
  <div class="hero-image">
    <div class="image-container">
      <img src="imagens/foto.png" alt="Estudante aprendendo online" onerror="this.src='https://images.unsplash.com/photo-1523050854058-8df90110c9f1?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'">
      <div class="floating-card card-1">
        <i class="fas fa-trophy"></i>
        <p>Certificação Reconhecida</p>
      </div>
      <div class="floating-card card-2">
        <i class="fas fa-headset"></i>
        <p>Suporte 24/7</p>
      </div>
    </div>
  </div>
</section>


<!-- BENEFÍCIOS -->
<section id="beneficios">
  <div class="section-header">
    <div class="section-subtitle">Por que somos diferentes</div>
    <h2>Vantagens <span class="highlight">Exclusivas</span></h2>
    <p>Descubra o que torna nossa plataforma única no mercado educacional angolano</p>
  </div>
  <div class="grid grid-2">
    <div class="beneficio-card">
      <div class="beneficio-icon">
        <i class="fas fa-laptop-code"></i>
      </div>
      <div class="beneficio-content">
        <h4>Aprendizado Prático</h4>
        <p>Projetos reais, estudos de caso e exercícios práticos que preparam você para o mercado de trabalho.</p>
        <ul class="beneficio-list">
          <li><i class="fas fa-check-circle"></i> Laboratórios virtuais</li>
          <li><i class="fas fa-check-circle"></i> Simulações interativas</li>
          <li><i class="fas fa-check-circle"></i> Projetos em equipe</li>
        </ul>
      </div>
    </div>
    
    <div class="beneficio-card">
      <div class="beneficio-icon">
        <i class="fas fa-user-tie"></i>
      </div>
      <div class="beneficio-content">
        <h4>Mentoria Personalizada</h4>
        <p>Receba orientação individual de especialistas com experiência comprovada no mercado angolano.</p>
        <ul class="beneficio-list">
          <li><i class="fas fa-check-circle"></i> Sessões one-on-one</li>
          <li><i class="fas fa-check-circle"></i> Feedback detalhado</li>
          <li><i class="fas fa-check-circle"></i> Planos de carreira</li>
        </ul>
      </div>
    </div>
    
    <div class="beneficio-card">
      <div class="beneficio-icon">
        <i class="fas fa-mobile-alt"></i>
      </div>
      <div class="beneficio-content">
        <h4>Plataforma Multi-dispositivo</h4>
        <p>Acesse seus cursos de qualquer lugar, a qualquer hora, com experiência otimizada para todos os dispositivos.</p>
        <ul class="beneficio-list">
          <li><i class="fas fa-check-circle"></i> App móvel dedicado</li>
          <li><i class="fas fa-check-circle"></i> Offline disponível</li>
          <li><i class="fas fa-check-circle"></i> Sincronização em nuvem</li>
        </ul>
      </div>
    </div>
    
    <div class="beneficio-card">
      <div class="beneficio-icon">
        <i class="fas fa-handshake"></i>
      </div>
      <div class="beneficio-content">
        <h4>Conexões Profissionais</h4>
        <p>Rede exclusiva de contatos com empresas parceiras e oportunidades de emprego diretas na plataforma.</p>
        <ul class="beneficio-list">
          <li><i class="fas fa-check-circle"></i> Feiras de emprego virtuais</li>
          <li><i class="fas fa-check-circle"></i> Networking com ex-alunos</li>
          <li><i class="fas fa-check-circle"></i> Programas de estágio</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<!-- CURSOS POPULARES -->
<section id="cursos">
  <div class="section-header">
    
    <h2>Cursos em <span class="highlight">Destaque</span></h2>
    <p>Selecione entre nossas formações mais procuradas pelo mercado</p>
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
        $categoria_class = strtolower(substr($curso['categoria'] ?? 'geral', 0, 10));
        echo "<div class='course-card'>";
        echo "<div class='course-category $categoria_class'>".($curso['categoria'] ?? 'Geral')."</div>";
        
        $imagem = !empty($curso['imagem']) ? "uploads/".htmlspecialchars($curso['imagem']) : "https://images.unsplash.com/photo-1516321318423-f06f85e504b3?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80";
        
        echo "<div class='course-image'>";
        echo "<img src='$imagem' alt='".htmlspecialchars($curso['titulo'])."'>";
        echo "<div class='course-overlay'>";
        echo "<a href='#' class='btn-preview'><i class='fas fa-play'></i> Prévia</a>";
        echo "</div>";
        echo "</div>";
        echo "<div class='course-content'>";
        echo "<div class='course-header'>";
        echo "<h4>".htmlspecialchars($curso['titulo'])."</h4>";
        echo "<div class='course-rating'>";
        echo "<i class='fas fa-star'></i><span>4.8</span>";
        echo "</div>";
        echo "</div>";
        echo "<p class='course-desc'>".substr(htmlspecialchars($curso['descricao']),0,120)."...</p>";
        echo "<div class='course-meta'>";
        echo "<span><i class='far fa-clock'></i> ".($curso['duracao'] ?? '20h')."</span>";
        echo "<span><i class='fas fa-user-graduate'></i> ".$curso['inscritos']." alunos</span>";
        echo "<span><i class='fas fa-signal'></i> ".($curso['nivel'] ?? 'Intermediário')."</span>";
        echo "</div>";
        echo "<div class='course-footer'>";
        echo "<div class='course-price'>";
        if (($curso['preco'] ?? 0) > 0) {
          echo "<span class='price-old'>".number_format($curso['preco'], 2, ',', '.')." Kz</span>";
          echo "<span class='price-new'>".number_format($curso['preco'] * 0.7, 2, ',', '.')." Kz</span>";
        } else {
          echo "<span class='price-free'>Gratuito</span>";
        }
        echo "</div>";
        echo "<a href='#' class='btn-course'><i class='fas fa-arrow-right'></i></a>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
      }
    } else {
      echo "<div class='no-courses'>";
      echo "<i class='fas fa-book-open fa-3x'></i>";
      echo "<h4>Em breve novos cursos</h4>";
      echo "<p>Estamos preparando conteúdo exclusivo para você</p>";
      echo "</div>";
    }
    ?>
  </div>
  <div class="section-cta">
    <a href="cursos.php" class="btn btn-secondary">Explorar Todos os Cursos <i class="fas fa-arrow-right"></i></a>
  </div>
</section>

<!-- PROCESSO -->
<section id="processo">
  <div class="section-header">
    <div class="section-subtitle">Simples e Eficaz</div>
    <h2>Como <span class="highlight">Funciona</span></h2>
  </div>
  <div class="processo-steps">
    <div class="step">
      <div class="step-number">01</div>
      <div class="step-icon">
        <i class="fas fa-user-plus"></i>
      </div>
      <h4>Crie sua Conta</h4>
      <p>Registro gratuito em menos de 2 minutos</p>
    </div>
    <div class="step-line"></div>
    <div class="step">
      <div class="step-number">02</div>
      <div class="step-icon">
        <i class="fas fa-book-open"></i>
      </div>
      <h4>Escolha seu Curso</h4>
      <p>Selecione entre +100 formações disponíveis</p>
    </div>
    <div class="step-line"></div>
    <div class="step">
      <div class="step-number">03</div>
      <div class="step-icon">
        <i class="fas fa-laptop"></i>
      </div>
      <h4>Aprenda na Prática</h4>
      <p>Acesso imediato às aulas e materiais</p>
    </div>
    <div class="step-line"></div>
    <div class="step">
      <div class="step-number">04</div>
      <div class="step-icon">
        <i class="fas fa-trophy"></i>
      </div>
      <h4>Obtenha sua Certificação</h4>
      <p>Certificado reconhecido pelo mercado</p>
    </div>
  </div>
</section>

<!-- CTA -->
<section id="cta">
  <div class="cta-container">
    <div class="cta-content">
      <h2>Comece Sua Jornada de <span class="highlight">Sucesso</span> Hoje</h2>
      <p>Junte-se a milhares de profissionais que transformaram suas carreiras com a Eduka Plus. Primeiro mês totalmente gratuito.</p>
      <div class="cta-features">
        <div class="cta-feature">
          <i class="fas fa-check-circle"></i>
          <span>Acesso a todos os cursos básicos</span>
        </div>
        <div class="cta-feature">
          <i class="fas fa-check-circle"></i>
          <span>Suporte por especialistas</span>
        </div>
        <div class="cta-feature">
          <i class="fas fa-check-circle"></i>
          <span>Certificado de conclusão</span>
        </div>
      </div>
      <div class="cta-buttons">
        <a href="register.php" class="btn btn-primary btn-large">
          <i class="fas fa-rocket"></i> Começar Gratuitamente
        </a>
        <a href="login.php" class="btn btn-outline">
          <i class="fas fa-user"></i> Já Tenho Conta
        </a>
      </div>
      <p class="cta-note"><i class="fas fa-shield-alt"></i> Segurança garantida · Sem compromisso · Cancele quando quiser</p>
    </div>
    <div class="cta-image">
      <img src="imagens/foto2.png" alt="Estudantes felizes">
    </div>
  </div>
</section>

<footer>
  <div class="footer-content">
    <div class="footer-section footer-main">
      <div class="footer-logo">
        <img src="imagens/logo.jpg" alt="">
        <div>
          <h3>Eduka Plus</h3>
          <span class="logo-sub">Angola</span>
        </div>
      </div>
      <p>Transformando vidas através da educação de qualidade. Somos a ponte entre o talento angolano e as oportunidades do futuro.</p>
      <div class="footer-contact">
        <p><i class="fas fa-envelope"></i> info@edukaplus.co.ao</p>
        <p><i class="fas fa-phone"></i> +244 923 456 789</p>
        <p><i class="fas fa-map-marker-alt"></i> Luanda, Angola</p>
      </div>
    </div>
    
    <div class="footer-section">
      <h4>Cursos</h4>
      <a href="cursos.php?categoria=tecnologia">Tecnologia & Programação</a>
      <a href="cursos.php?categoria=negocios">Negócios & Gestão</a>
      <a href="cursos.php?categoria=design">Design & Criatividade</a>
      <a href="cursos.php?categoria=marketing">Marketing Digital</a>
      <a href="cursos.php?categoria=idiomas">Línguas & Idiomas</a>
    </div>
    
    <div class="footer-section">
      <h4>Empresa</h4>
      <a href="sobre.php">Sobre Nós</a>
      <a href="carreiras.php">Carreiras</a>
      <a href="parceiros.php">Seja Parceiro</a>
      <a href="blog.php">Blog</a>
      <a href="imprensa.php">Imprensa</a>
    </div>
    
    <div class="footer-section">
      <h4>Suporte</h4>
      <a href="ajuda.php">Central de Ajuda</a>
      <a href="faq.php">FAQ</a>
      <a href="contacto.php">Contacte-nos</a>
      <a href="politica.php">Política de Privacidade</a>
      <a href="termos.php">Termos de Uso</a>
    </div>
    
    <div class="footer-section">
      <h4>Newsletter</h4>
      <p>Receba as últimas atualizações e ofertas exclusivas</p>
      <form class="newsletter-form">
        <input type="email" placeholder="Seu email" required>
        <button type="submit"><i class="fas fa-paper-plane"></i></button>
      </form>
      <div class="social-icons">
        <a href="#"><i class="fab fa-facebook-f"></i></a>
        <a href="#"><i class="fab fa-linkedin-in"></i></a>
        <a href="#"><i class="fab fa-instagram"></i></a>
        <a href="#"><i class="fab fa-youtube"></i></a>
        <a href="#"><i class="fab fa-twitter"></i></a>
      </div>
    </div>
  </div>
  
  <div class="footer-bottom">
    
    <p>© <?php echo date("Y"); ?> Eduka Plus Angola. Todos os direitos reservados.</p>
    <p class="footer-copy">Desenvolvido com <i class="fas fa-heart"></i> para Angola</p>
  </div>
</footer>

<!-- Botão WhatsApp Fixo -->
<a href="https://wa.me/244958922590" target="_blank" class="whatsapp-btn">
  <i class="fab fa-whatsapp"></i>
  <span class="whatsapp-tooltip">Precisa de ajuda?</span>
</a>

<script src="js/inicio.js"></script>

</body>
</html>