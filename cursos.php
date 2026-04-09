<?php
session_start();
require_once("config.php");

// Buscar cursos no banco
$sql = "SELECT 
          c.id, 
          c.titulo, 
          c.descricao, 
          c.imagem,
          c.professor_id,
          u.nome as professor_nome,
          COUNT(i.id) as total_inscritos,
          c.criado_em
        FROM cursos c
        LEFT JOIN usuarios u ON c.professor_id = u.id
        LEFT JOIN inscricoes i ON i.curso_id = c.id
        GROUP BY c.id
        ORDER BY c.criado_em DESC
        LIMIT 12";
$result = $conn->query($sql);
$cursos = $result->fetch_all(MYSQLI_ASSOC);

// Buscar estatísticas
$stats_sql = "SELECT 
                COUNT(*) as total_cursos,
                COUNT(DISTINCT professor_id) as total_professores,
                (SELECT COUNT(DISTINCT id) FROM usuarios WHERE tipo = 'aluno') as total_alunos
              FROM cursos";
$stats_result = $conn->query($stats_sql);
$estatisticas = $stats_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cursos - Eduka Plus Angola</title>
  <link rel="stylesheet" href="css/inicio.css">
  <link rel="stylesheet" href="css/cursos.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
  <!-- Header -->
  <header>
    <div class="logo">
      <img src="imagens/logo.jpg" alt="">
      <span>Eduka Plus</span>
      <span class="logo-sub">Angola</span>
    </div>
    <nav id="menu">
      <a href="index.php"><i class="fas fa-home"></i> Início</a>
      <a href="cursos.php" class="active"><i class="fas fa-book"></i> Cursos</a>
      <a href="verificar_certificado.php"><i class="fas fa-certificate"></i> Validar Certificado</a>
      <?php if (isset($_SESSION['usuario_id'])): ?>
        <?php if ($_SESSION['usuario_tipo'] === 'admin'): ?>
          <a href="painel_admin/dashboard.php" class="btn-login">
            <i class="fas fa-user-cog"></i> Admin
          </a>
        <?php elseif ($_SESSION['usuario_tipo'] === 'professor'): ?>
          <a href="painel_professor/dashboard.php" class="btn-login">
            <i class="fas fa-chalkboard-teacher"></i> Professor
          </a>
        <?php else: ?>
          <a href="painel_aluno/dashboard.php" class="btn-login">
            <i class="fas fa-user"></i> Aluno
          </a>
        <?php endif; ?>
      <?php else: ?>
        <a href="login.php" class="btn-login"><i class="fas fa-sign-in-alt"></i> Login</a>
        <a href="register.php" class="btn-cadastro"><i class="fas fa-user-plus"></i> Cadastro</a>
      <?php endif; ?>
    </nav>
    <div class="hamburger" onclick="toggleMenu()">
      <div></div><div></div><div></div>
    </div>
  </header>

  <!-- Hero Section -->
  <section class="cursos-hero">
    <div class="hero-content">
      <h1>Descubra Seu Próximo Curso</h1>
      <p>Explore nossa coleção de cursos ministrados por especialistas e encontre o ideal para sua carreira</p>
      
      <form method="GET" action="cursos.php" class="search-form">
        <div class="search-container">
          <i class="fas fa-search"></i>
          <input 
            type="text" 
            name="busca" 
            placeholder="Buscar cursos por título, descrição ou professor..." 
            value="<?php echo isset($_GET['busca']) ? htmlspecialchars($_GET['busca']) : ''; ?>"
          >
          <button type="submit" class="btn-search">
            <i class="fas fa-arrow-right"></i>
          </button>
        </div>
      </form>

      <div class="hero-stats">
        <div class="stat">
          <h3><?php echo $estatisticas['total_cursos'] ?? '0'; ?></h3>
          <p>Cursos Disponíveis</p>
        </div>
        <div class="stat">
          <h3><?php echo $estatisticas['total_professores'] ?? '0'; ?></h3>
          <p>Professores</p>
        </div>
        <div class="stat">
          <h3><?php echo $estatisticas['total_alunos'] ?? '0'; ?></h3>
          <p>Alunos Ativos</p>
        </div>
        <div class="stat">
          <h3>100%</h3>
          <p>Online</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Lista de Cursos -->
  <section class="cursos-section">
    <div class="container">
      <div class="section-header">
        <h2>Nossos Cursos</h2>
        <p><?php echo count($cursos); ?> cursos disponíveis para você</p>
      </div>

      <?php if (count($cursos) > 0): ?>
        <div class="cursos-grid">
          <?php foreach ($cursos as $curso): ?>
            <div class="curso-card">
              <div class="curso-header">
                <div class="curso-categoria">
                  <i class="fas fa-book"></i>
                  Curso <?php echo date('Y', strtotime($curso['criado_em'])); ?>
                </div>
                <div class="curso-badge">
                  <i class="fas fa-users"></i> <?php echo $curso['total_inscritos'] ?? '0'; ?> alunos
                </div>
              </div>

              <div class="curso-image">
                <?php 
                if (!empty($curso['imagem'])) {
                  $imagem = "uploads/" . htmlspecialchars($curso['imagem']);
                  // Verificar se o arquivo existe
                  if (!file_exists($imagem)) {
                    $imagem = "uploads/default.jpg";
                  }
                } else {
                  $imagem = "uploads/default.jpg";
                }
                
                // Se não existir default.jpg, usar imagem do Unsplash
                if (!file_exists("uploads/default.jpg")) {
                  $imagem = "https://images.unsplash.com/photo-1516321318423-f06f85e504b3?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80";
                }
                ?>
                <img src="<?php echo $imagem; ?>" alt="<?php echo htmlspecialchars($curso['titulo']); ?>" 
                     onerror="this.src='https://images.unsplash.com/photo-1516321318423-f06f85e504b3?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80'">
                <div class="curso-overlay">
                  <a href="curso_detalhes.php?id=<?php echo $curso['id']; ?>" class="btn-preview">
                    <i class="fas fa-play-circle"></i> Ver Detalhes
                  </a>
                </div>
              </div>

              <div class="curso-content">
                <div class="curso-title">
                  <h3><?php echo htmlspecialchars($curso['titulo']); ?></h3>
                  <div class="curso-rating">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star-half-alt"></i>
                    <span>(4.5)</span>
                  </div>
                </div>

                <p class="curso-desc">
                  <?php 
                  $descricao = htmlspecialchars($curso['descricao'] ?? 'Descrição não disponível.');
                  echo strlen($descricao) > 100 ? substr($descricao, 0, 100) . '...' : $descricao;
                  ?>
                </p>

                <div class="curso-meta">
                  <div class="meta-item">
                    <i class="far fa-clock"></i>
                    <span>20 horas</span>
                  </div>
                  <div class="meta-item">
                    <i class="fas fa-user-graduate"></i>
                    <span><?php echo $curso['total_inscritos'] ?? '0'; ?> alunos</span>
                  </div>
                  <div class="meta-item">
                    <i class="fas fa-signal"></i>
                    <span>Nível Variado</span>
                  </div>
                </div>

                <div class="curso-instrutor">
                  <div class="instrutor-avatar">
                    <?php
                    // Verificar se o professor tem foto
                    $professor_foto = "uploads/foto_" . $curso['professor_id'] . ".jpg";
                    if (file_exists($professor_foto)) {
                      echo '<img src="' . $professor_foto . '" alt="' . htmlspecialchars($curso['professor_nome']) . '">';
                    } else {
                      echo '<div class="avatar-placeholder">' . substr($curso['professor_nome'], 0, 1) . '</div>';
                    }
                    ?>
                  </div>
                  <div>
                    <h4>Prof. <?php echo htmlspecialchars($curso['professor_nome']); ?></h4>
                    <p>Instrutor Certificado</p>
                  </div>
                </div>

                <div class="curso-footer">
                  <?php if (isset($_SESSION['usuario_id'])): ?>
                    <a href="curso_detalhes.php?id=<?php echo $curso['id']; ?>" class="btn-curso">
                      <i class="fas fa-arrow-right"></i> Ver Curso
                    </a>
                  <?php else: ?>
                    <a href="login.php" class="btn-curso">
                      <i class="fas fa-sign-in-alt"></i> Login para Acessar
                    </a>
                  <?php endif; ?>
                  <div class="curso-actions">
                    <button class="action-btn" title="Adicionar aos favoritos" onclick="toggleFavorite(<?php echo $curso['id']; ?>)">
                      <i class="far fa-heart" id="heart-<?php echo $curso['id']; ?>"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Ver mais cursos -->
        <?php if (count($cursos) >= 12): ?>
          <div class="ver-mais">
            <a href="cursos.php?todos=true" class="btn-primary">
              <i class="fas fa-book-open"></i> Ver Mais Cursos
            </a>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <div class="no-cursos">
          <i class="fas fa-book-open fa-3x"></i>
          <h3>Nenhum curso disponível no momento</h3>
          <p>Estamos trabalhando para adicionar novos cursos em breve.</p>
          <div class="no-cursos-actions">
            <a href="index.php" class="btn-primary">
              <i class="fas fa-home"></i> Voltar ao Início
            </a>
            <?php if (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'professor'): ?>
              <a href="painel_professor/criar_curso.php" class="btn-outline">
                <i class="fas fa-plus-circle"></i> Criar Novo Curso
              </a>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- CTA Section -->
  <section class="cursos-cta">
    <div class="container">
      <div class="cta-content">
        <h2>Pronto para Transformar sua Carreira?</h2>
        <p>Junte-se a nossa comunidade de aprendizes e dê o próximo passo na sua jornada profissional</p>
        <div class="cta-buttons">
          <?php if (isset($_SESSION['usuario_id'])): ?>
            <?php if ($_SESSION['usuario_tipo'] === 'professor'): ?>
              <a href="painel_professor/criar_curso.php" class="btn-primary">
                <i class="fas fa-plus-circle"></i> Criar Novo Curso
              </a>
            <?php else: ?>
              <a href="cursos.php" class="btn-primary">
                <i class="fas fa-search"></i> Explorar Mais Cursos
              </a>
            <?php endif; ?>
          <?php else: ?>
            <a href="register.php" class="btn-primary">
              <i class="fas fa-user-plus"></i> Começar Gratuitamente
            </a>
            <a href="cursos.php" class="btn-outline">
              <i class="fas fa-book"></i> Ver Cursos Disponíveis
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
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
        <p>Excelência em educação online. Transformando vidas através do conhecimento.</p>
        <div class="social-icons">
          <a href="#"><i class="fab fa-facebook-f"></i></a>
          <a href="#"><i class="fab fa-linkedin-in"></i></a>
          <a href="#"><i class="fab fa-instagram"></i></a>
          <a href="#"><i class="fab fa-youtube"></i></a>
        </div>
      </div>
      
      <div class="footer-section">
        <h4>Links Rápidos</h4>
        <a href="index.php"><i class="fas fa-home"></i> Início</a>
        <a href="cursos.php"><i class="fas fa-book"></i> Cursos</a>
        <a href="verificar_certificado.php"><i class="fas fa-certificate"></i> Validar Certificado</a>
        <a href="ajuda.php"><i class="fas fa-question-circle"></i> Ajuda</a>
      </div>
      
      <div class="footer-section">
        <h4>Para Alunos</h4>
        <a href="login.php"><i class="fas fa-sign-in-alt"></i> Área do Aluno</a>
        <a href="verificar_certificado.php"><i class="fas fa-certificate"></i> Meus Certificados</a>
        <a href="ajuda.php"><i class="fas fa-question-circle"></i> FAQ</a>
      </div>
      
      <div class="footer-section">
        <h4>Contato</h4>
        <p><i class="fas fa-envelope"></i> info@edukaplus.co.ao</p>
        <p><i class="fas fa-phone"></i> +244 923 456 789</p>
        <p><i class="fas fa-map-marker-alt"></i> Luanda, Angola</p>
      </div>
    </div>
    
    <div class="footer-bottom">
      <p>© <?php echo date("Y"); ?> Eduka Plus Angola — Todos os direitos reservados.</p>
      <p class="footer-copy">Versão 3.0.0</p>
    </div>
  </footer>

  <!-- Botão WhatsApp Fixo -->
  <a href="https://wa.me/244958922590" target="_blank" class="whatsapp-btn">
    <i class="fab fa-whatsapp"></i>
    <span class="whatsapp-tooltip">Precisa de ajuda?</span>
  </a>

  <script>
  function toggleMenu() {
    document.getElementById('menu').classList.toggle('active');
  }

  function toggleFavorite(courseId) {
    const heart = document.getElementById('heart-' + courseId);
    if (heart.classList.contains('far')) {
      heart.classList.remove('far');
      heart.classList.add('fas');
      heart.style.color = '#ef4444';
      // Aqui você pode adicionar uma chamada AJAX para salvar nos favoritos
      alert('Curso adicionado aos favoritos!');
    } else {
      heart.classList.remove('fas');
      heart.classList.add('far');
      heart.style.color = '';
      // Aqui você pode adicionar uma chamada AJAX para remover dos favoritos
      alert('Curso removido dos favoritos!');
    }
  }

  // Animações
  document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.curso-card');
    cards.forEach((card, index) => {
      card.style.opacity = '0';
      card.style.transform = 'translateY(20px)';
      
      setTimeout(() => {
        card.style.transition = 'all 0.6s ease';
        card.style.opacity = '1';
        card.style.transform = 'translateY(0)';
      }, index * 100);
    });
  });

  // Fechar menu ao clicar em um link no mobile
  document.querySelectorAll('#menu a').forEach(link => {
    link.addEventListener('click', () => {
      if(window.innerWidth <= 768) {
        document.getElementById('menu').classList.remove('active');
      }
    });
  });

  // Melhorar a experiência do formulário de busca
  const searchForm = document.querySelector('.search-form');
  const searchInput = searchForm.querySelector('input[name="busca"]');
  
  searchForm.addEventListener('submit', function(e) {
    if (searchInput.value.trim() === '') {
      e.preventDefault();
      searchInput.focus();
      searchInput.style.border = '2px solid #ef4444';
      setTimeout(() => {
        searchInput.style.border = '';
      }, 1000);
    }
  });

  // Adicionar funcionalidade de favoritos com localStorage
  document.querySelectorAll('.action-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const courseId = this.getAttribute('onclick').match(/\d+/)[0];
      let favorites = JSON.parse(localStorage.getItem('favorites')) || [];
      
      if (favorites.includes(courseId)) {
        favorites = favorites.filter(id => id !== courseId);
        localStorage.setItem('favorites', JSON.stringify(favorites));
      } else {
        favorites.push(courseId);
        localStorage.setItem('favorites', JSON.stringify(favorites));
      }
    });
  });

  // Carregar estado dos favoritos
  document.addEventListener('DOMContentLoaded', function() {
    let favorites = JSON.parse(localStorage.getItem('favorites')) || [];
    favorites.forEach(courseId => {
      const heart = document.getElementById('heart-' + courseId);
      if (heart) {
        heart.classList.remove('far');
        heart.classList.add('fas');
        heart.style.color = '#ef4444';
      }
    });
  });
  </script>
</body>
</html>