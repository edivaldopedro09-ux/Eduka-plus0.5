<?php
session_start();
require_once("../config.php");

// Segurança: só professores
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'professor') {
    header("Location: ../index.php");
    exit();
}

$professor_id = $_SESSION['usuario_id'];

// Total de cursos
$sql = "SELECT COUNT(*) AS total_cursos FROM cursos WHERE professor_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_cursos = $row ? $row['total_cursos'] : 0;

// Total de alunos matriculados
$sql = "SELECT COUNT(*) AS total_alunos FROM inscricoes i 
        JOIN cursos c ON i.curso_id = c.id 
        WHERE c.professor_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_alunos = $row ? $row['total_alunos'] : 0;

// Cursos do professor
$sql = "SELECT c.id AS curso_id, c.titulo AS curso_nome, c.imagem,
               (SELECT COUNT(*) FROM inscricoes i WHERE i.curso_id=c.id) AS total_alunos
        FROM cursos c
        WHERE c.professor_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$result = $stmt->get_result();
$cursos = $result->fetch_all(MYSQLI_ASSOC);

// Notificações não lidas
$sql = "SELECT COUNT(*) AS nao_lidas FROM notificacoes WHERE destinatario_id=? AND lida=0";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$result = $stmt->get_result();
$contador = $result->fetch_assoc()['nao_lidas'] ?? 0;
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Painel Professor — Eduka Plus Angola</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --primary: #2563eb;
  --primary-dark: #1d4ed8;
  --primary-light: #3b82f6;
  --secondary: #0ea5e9;
  --secondary-dark: #0284c7;
  --background: #ffffff;
  --surface: #f8fafc;
  --surface-light: #ffffff;
  --surface-dark: #f1f5f9;
  --text-primary: #1e293b;
  --text-secondary: #64748b;
  --text-muted: #94a3b8;
  --border: #e2e8f0;
  --border-light: #f1f5f9;
  --success: #10b981;
  --warning: #f59e0b;
  --danger: #ef4444;
  --info: #0ea5e9;
  --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  --radius: 12px;
  --sidebar-width: 280px;
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  background: var(--background);
  color: var(--text-primary);
  display: flex;
  min-height: 100vh;
  overflow-x: hidden;
  line-height: 1.6;
}

/* Sidebar elegante */
#sidebar {
  width: var(--sidebar-width);
  background: linear-gradient(180deg, var(--surface-light) 0%, #ffffff 100%);
  border-right: 1px solid var(--border-light);
  padding: 2rem 1.5rem;
  display: flex;
  flex-direction: column;
  position: fixed;
  height: 100vh;
  z-index: 100;
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: var(--shadow-md);
}

#sidebar.hidden {
  transform: translateX(-100%);
}

.brand {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 2.5rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid var(--border-light);
}

.logo {
  width: 42px;
  height: 42px;
  border-radius: var(--radius);
  background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 700;
  font-size: 1.2rem;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}

.brand-text {
  font-weight: 700;
  font-size: 1.4rem;
  color: var(--text-primary);
}

.brand-subtitle {
  font-size: 0.85rem;
  color: var(--text-secondary);
  font-weight: 500;
  margin-top: 2px;
}

.sidebar-nav {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  flex: 1;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.875rem 1.25rem;
  color: var(--text-secondary);
  text-decoration: none;
  border-radius: var(--radius);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
  font-weight: 500;
}

.nav-item:hover {
  background: rgba(37, 99, 235, 0.05);
  color: var(--primary);
  transform: translateX(4px);
}

.nav-item.active {
  background: rgba(37, 99, 235, 0.1);
  color: var(--primary);
  font-weight: 600;
}

.nav-item.active::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  height: 100%;
  width: 4px;
  background: var(--primary);
  border-radius: 0 2px 2px 0;
}

.nav-icon {
  width: 20px;
  text-align: center;
  font-size: 1.1rem;
}

.sidebar-footer {
  margin-top: auto;
  padding-top: 1.5rem;
  border-top: 1px solid var(--border-light);
}

.user-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  background: var(--surface-dark);
  border-radius: var(--radius);
}

.user-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 600;
  font-size: 1rem;
}

.user-details h4 {
  font-size: 0.95rem;
  font-weight: 600;
  color: var(--text-primary);
}

.user-details p {
  font-size: 0.8rem;
  color: var(--text-secondary);
  margin-top: 2px;
}

/* Botão menu mobile */
#btnToggleMenu {
  display: none;
  position: fixed;
  top: 1.5rem;
  left: 1.5rem;
  z-index: 101;
  background: var(--primary);
  border: none;
  color: white;
  width: 48px;
  height: 48px;
  border-radius: var(--radius);
  cursor: pointer;
  font-size: 1.25rem;
  box-shadow: var(--shadow-lg);
  transition: all 0.3s ease;
}

#btnToggleMenu:hover {
  background: var(--primary-dark);
  transform: translateY(-2px);
}

/* Conteúdo principal */
.main-content {
  flex: 1;
  margin-left: var(--sidebar-width);
  padding: 2rem 2.5rem;
  transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  background: var(--surface);
  min-height: 100vh;
}

@media (max-width: 1024px) {
  .main-content {
    margin-left: 0;
    padding: 1.5rem;
    padding-top: 5rem;
  }
  
  #sidebar {
    transform: translateX(-100%);
  }
  
  #sidebar.show {
    transform: translateX(0);
  }
  
  #btnToggleMenu {
    display: block;
  }
}

/* Header */
.main-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2.5rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid var(--border-light);
}

.welcome-section h1 {
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
  color: var(--text-primary);
}

.welcome-section p {
  color: var(--text-secondary);
  font-size: 1rem;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.notification-btn {
  position: relative;
  background: var(--surface-light);
  border: 1px solid var(--border);
  width: 48px;
  height: 48px;
  border-radius: var(--radius);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-secondary);
  text-decoration: none;
  transition: all 0.3s ease;
}

.notification-btn:hover {
  background: var(--surface-dark);
  color: var(--primary);
  transform: translateY(-2px);
}

.notification-badge {
  position: absolute;
  top: -6px;
  right: -6px;
  background: var(--danger);
  color: white;
  font-size: 0.7rem;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
}

.logout-btn {
  background: var(--surface-light);
  color: var(--danger);
  border: 1px solid var(--border);
  padding: 0.75rem 1.5rem;
  border-radius: var(--radius);
  text-decoration: none;
  font-weight: 600;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.logout-btn:hover {
  background: var(--danger);
  color: white;
  transform: translateY(-2px);
  border-color: var(--danger);
}

/* Cartões de estatísticas */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2.5rem;
}

.stat-card {
  background: var(--surface-light);
  border-radius: var(--radius);
  padding: 1.75rem;
  border: 1px solid var(--border);
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
  opacity: 0;
  transform: translateY(20px);
  box-shadow: var(--shadow-sm);
}

.stat-card.show {
  opacity: 1;
  transform: translateY(0);
}

.stat-card:hover {
  transform: translateY(-6px);
  box-shadow: var(--shadow-lg);
  border-color: rgba(37, 99, 235, 0.2);
}

.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 4px;
  background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
}

.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1.25rem;
  font-size: 1.5rem;
  background: rgba(37, 99, 235, 0.1);
  color: var(--primary);
}

.stat-card:nth-child(2) .stat-icon {
  background: rgba(16, 185, 129, 0.1);
  color: var(--success);
}

.stat-card:nth-child(3) .stat-icon {
  background: rgba(245, 158, 11, 0.1);
  color: var(--warning);
}

.stat-content h3 {
  font-size: 0.95rem;
  font-weight: 600;
  color: var(--text-secondary);
  margin-bottom: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stat-value {
  font-size: 2.5rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
  color: var(--text-primary);
  line-height: 1;
}

.stat-description {
  font-size: 0.9rem;
  color: var(--text-muted);
}

.stat-trend {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  margin-top: 0.75rem;
  color: var(--success);
  font-weight: 500;
}

/* Container principal - Gráfico + Cursos */
.main-dashboard-container {
  display: flex;
  gap: 2rem;
  margin-bottom: 2.5rem;
  flex-wrap: wrap;
}

.chart-container {
  flex: 1;
  min-width: 350px;
  background: var(--surface-light);
  border-radius: var(--radius);
  padding: 1.75rem;
  border: 1px solid var(--border);
  box-shadow: var(--shadow-sm);
}

.chart-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.chart-header h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-primary);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.chart-actions {
  display: flex;
  gap: 0.5rem;
}

.chart-btn {
  background: var(--surface-dark);
  border: 1px solid var(--border);
  color: var(--text-secondary);
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
}

.chart-btn:hover {
  background: var(--primary);
  color: white;
  border-color: var(--primary);
}

.courses-section {
  flex: 2;
  min-width: 300px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.section-header h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-primary);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.new-course-btn {
  background: var(--primary);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: var(--radius);
  font-weight: 600;
  text-decoration: none;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}

.new-course-btn:hover {
  background: var(--primary-dark);
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(37, 99, 235, 0.3);
}

.courses-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}

/* Cartões de cursos */
.course-card {
  background: var(--surface-light);
  border-radius: var(--radius);
  overflow: hidden;
  border: 1px solid var(--border);
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  opacity: 0;
  transform: translateY(20px);
  box-shadow: var(--shadow-sm);
}

.course-card.show {
  opacity: 1;
  transform: translateY(0);
}

.course-card:hover {
  transform: translateY(-8px);
  box-shadow: var(--shadow-lg);
  border-color: rgba(37, 99, 235, 0.2);
}

.course-image-container {
  position: relative;
  height: 180px;
  overflow: hidden;
}

.course-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.6s ease;
}

.course-card:hover .course-image {
  transform: scale(1.08);
}

.course-badge {
  position: absolute;
  top: 1rem;
  right: 1rem;
  background: rgba(255, 255, 255, 0.95);
  color: var(--primary);
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  box-shadow: var(--shadow-sm);
}

.course-content {
  padding: 1.5rem;
}

.course-title {
  font-size: 1.1rem;
  font-weight: 600;
  margin-bottom: 1rem;
  color: var(--text-primary);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.course-stats {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.25rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--border-light);
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--text-secondary);
  font-size: 0.9rem;
}

.stat-item i {
  color: var(--primary);
}

.course-actions {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.5rem;
}

.action-btn {
  padding: 0.6rem;
  border-radius: 8px;
  border: 1px solid var(--border);
  background: var(--surface-dark);
  color: var(--text-secondary);
  text-decoration: none;
  font-size: 0.85rem;
  text-align: center;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.25rem;
  font-weight: 500;
}

.action-btn:hover {
  background: rgba(37, 99, 235, 0.1);
  color: var(--primary);
  border-color: rgba(37, 99, 235, 0.3);
  transform: translateY(-2px);
}

.action-btn.chat-btn {
  background: rgba(16, 185, 129, 0.1);
  border-color: rgba(16, 185, 129, 0.2);
  color: var(--success);
}

.action-btn.chat-btn:hover {
  background: rgba(16, 185, 129, 0.2);
  color: var(--success);
  border-color: var(--success);
}

/* Footer */
.main-footer {
  margin-top: 3rem;
  padding-top: 2rem;
  border-top: 1px solid var(--border-light);
  text-align: center;
  color: var(--text-secondary);
}

.footer-links {
  display: flex;
  justify-content: center;
  gap: 2rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}

.footer-links a {
  color: var(--text-secondary);
  text-decoration: none;
  transition: color 0.3s ease;
  font-size: 0.95rem;
  font-weight: 500;
}

.footer-links a:hover {
  color: var(--primary);
}

.copyright {
  font-size: 0.9rem;
  margin-top: 0.5rem;
}

.version {
  display: inline-block;
  background: var(--surface-dark);
  color: var(--text-secondary);
  padding: 0.4rem 1rem;
  border-radius: 20px;
  font-size: 0.85rem;
  margin-top: 1rem;
  font-weight: 500;
}

/* Animações */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.fade-in {
  animation: fadeInUp 0.6s ease forwards;
}

/* Responsividade adicional */
@media (max-width: 768px) {
  .main-content {
    padding: 1.25rem;
    padding-top: 4.5rem;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .main-dashboard-container {
    flex-direction: column;
  }
  
  .chart-container, .courses-section {
    width: 100%;
  }
  
  .courses-grid {
    grid-template-columns: 1fr;
  }
  
  .footer-links {
    flex-direction: column;
    gap: 1rem;
  }
  
  .header-actions {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .welcome-section h1 {
    font-size: 1.75rem;
  }
}

@media (max-width: 480px) {
  .stat-value {
    font-size: 2rem;
  }
  
  .course-actions {
    grid-template-columns: 1fr;
  }
  
  .action-btn {
    width: 100%;
  }
  
  .chart-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .chart-actions {
    width: 100%;
    justify-content: space-between;
  }
}

/* Scrollbar personalizada */
::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

::-webkit-scrollbar-track {
  background: var(--surface-dark);
  border-radius: 4px;
}

::-webkit-scrollbar-thumb {
  background: var(--primary);
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: var(--primary-dark);
}

/* Placeholder quando não há cursos */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 2rem;
  text-align: center;
  background: var(--surface-light);
  border-radius: var(--radius);
  border: 2px dashed var(--border);
}

.empty-icon {
  font-size: 3rem;
  color: var(--text-muted);
  margin-bottom: 1.5rem;
  opacity: 0.5;
}
</style>
</head>
<body>

<!-- Botão para menu mobile -->
<button id="btnToggleMenu">
  <i class="fas fa-bars"></i>
</button>

<!-- Sidebar -->
<aside id="sidebar">
  <div>
    <div class="brand">
      <img src="imagens/logo.jpg" alt="">
      <div>
        <div class="brand-text">Eduka Plus</div>
        <div class="brand-subtitle">Painel do Professor</div>
      </div>
    </div>
    
    <nav class="sidebar-nav">
      <a href="./dashboard.php" class="nav-item active">
        <span class="nav-icon"><i class="fas fa-tachometer-alt"></i></span>
        <span>Dashboard</span>
      </a>
      <a href="../perfil.php" class="nav-item">
        <span class="nav-icon"><i class="fas fa-user-circle"></i></span>
        <span>Meu Perfil</span>
      </a>
      <a href="./meus_cursos.php" class="nav-item">
        <span class="nav-icon"><i class="fas fa-graduation-cap"></i></span>
        <span>Meus Cursos</span>
      </a>
      <a href="./materiais.php" class="nav-item">
        <span class="nav-icon"><i class="fas fa-folder-open"></i></span>
        <span>Materiais</span>
      </a>
      <a href="./alunos_matriculados.php" class="nav-item">
        <span class="nav-icon"><i class="fas fa-user-friends"></i></span>
        <span>Alunos</span>
      </a>
      
    </nav>
  </div>
  
  <div class="sidebar-footer">
    <div class="user-info">
      <div class="user-avatar">
        <?php 
          $nome = $_SESSION['usuario_nome'];
          $iniciais = strtoupper(substr($nome, 0, 1) . substr($nome, strpos($nome, ' ') + 1, 1));
          echo $iniciais;
        ?>
      </div>
      <div class="user-details">
        <h4><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></h4>
        <p>Professor</p>
      </div>
    </div>
  </div>
</aside>

<!-- Conteúdo Principal -->
<main class="main-content">
  <!-- Header -->
  <header class="main-header">
    <div class="welcome-section">
      <h1>Bem-vindo, <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>!</h1>
      <p>Acompanhe o desempenho dos seus cursos e gerencie suas turmas.</p>
    </div>
    
    <div class="header-actions">
      <a href="#" class="notification-btn">
        <i class="fas fa-bell"></i>
        <?php if($contador > 0): ?>
          <span class="notification-badge"><?php echo $contador; ?></span>
        <?php endif; ?>
      </a>
      <a href="../logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i>
        <span>Sair</span>
      </a>
    </div>
  </header>

  <!-- Estatísticas -->
  <div class="stats-grid">
    <div class="stat-card fade-in" style="animation-delay: 0.1s">
      <div class="stat-icon">
        <i class="fas fa-book-open"></i>
      </div>
      <div class="stat-content">
        <h3>Total de Cursos</h3>
        <div class="stat-value" data-count="<?php echo $total_cursos; ?>">0</div>
        <p class="stat-description">Cursos criados por você</p>
        <div class="stat-trend">
          <i class="fas fa-arrow-up"></i>
          <span>+2 este mês</span>
        </div>
      </div>
    </div>
    
    <div class="stat-card fade-in" style="animation-delay: 0.2s">
      <div class="stat-icon">
        <i class="fas fa-user-graduate"></i>
      </div>
      <div class="stat-content">
        <h3>Total de Alunos</h3>
        <div class="stat-value" data-count="<?php echo $total_alunos; ?>">0</div>
        <p class="stat-description">Alunos matriculados</p>
        <div class="stat-trend">
          <i class="fas fa-arrow-up"></i>
          <span>+15 esta semana</span>
        </div>
      </div>
    </div>
    
    <div class="stat-card fade-in" style="animation-delay: 0.3s">
      <div class="stat-icon">
        <i class="fas fa-bell"></i>
      </div>
      <div class="stat-content">
        <h3>Notificações</h3>
        <div class="stat-value" data-count="<?php echo $contador; ?>">0</div>
        <p class="stat-description">Não lidas</p>
        <div class="stat-trend">
          <i class="fas fa-eye"></i>
          <span>Marcar como lidas</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Gráfico e Cursos -->
  <div class="main-dashboard-container">
    <div class="chart-container fade-in" style="animation-delay: 0.4s">
      <div class="chart-header">
        <h3><i class="fas fa-chart-bar"></i> Alunos por Curso</h3>
        <div class="chart-actions">
          <button class="chart-btn">7 dias</button>
          <button class="chart-btn">30 dias</button>
          <button class="chart-btn">Total</button>
        </div>
      </div>
      <canvas id="chartCursos" height="250"></canvas>
    </div>
    
    <div class="courses-section">
      <div class="section-header">
        <h3><i class="fas fa-graduation-cap"></i> Meus Cursos</h3>
        <a href="novo_curso.php" class="new-course-btn">
          <i class="fas fa-plus"></i>
          <span>Novo Curso</span>
        </a>
      </div>
      
      <?php if(count($cursos) > 0): ?>
        <div class="courses-grid">
          <?php foreach($cursos as $curso): ?>
            <div class="course-card fade-in">
              <div class="course-image-container">
                <?php 
                $imgCurso = !empty($curso['imagem']) 
                            ? '../uploads/'.htmlspecialchars($curso['imagem']) 
                            : 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80';
                ?>
                <img src="<?php echo $imgCurso; ?>" class="course-image" alt="Imagem do curso">
                <div class="course-badge">
                  <span class="course-student-count" data-count="<?php echo $curso['total_alunos']; ?>">0</span> alunos
                </div>
              </div>
              <div class="course-content">
                <h3 class="course-title"><?php echo htmlspecialchars($curso['curso_nome']); ?></h3>
                <div class="course-stats">
                  <div class="stat-item">
                    <i class="fas fa-user-graduate"></i>
                    <span><span class="course-student-count" data-count="<?php echo $curso['total_alunos']; ?>">0</span> alunos</span>
                  </div>
                  <div class="stat-item">
                    <i class="fas fa-star"></i>
                    <span>4.8</span>
                  </div>
                </div>
                <div class="course-actions">
                  <a href="alunos.php?curso_id=<?php echo $curso['curso_id']; ?>" class="action-btn">
                    <i class="fas fa-users"></i> Alunos
                  </a>
                  <a href="gerenciar_aulas.php?curso_id=<?php echo $curso['curso_id']; ?>" class="action-btn">
                    <i class="fas fa-video"></i> Aulas
                  </a>
                  <a href="gerenciar_materiais.php?curso_id=<?php echo $curso['curso_id']; ?>" class="action-btn">
                    <i class="fas fa-folder"></i> Materiais
                  </a>
                  <a href="chat_list.php?curso_id=<?php echo $curso['curso_id']; ?>" class="action-btn chat-btn">
                    <i class="fas fa-comments"></i> Chat
                  </a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty-state fade-in">
          <div class="empty-icon">
            <i class="fas fa-graduation-cap"></i>
          </div>
          <h3>Nenhum curso criado ainda</h3>
          <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">Comece criando seu primeiro curso para compartilhar conhecimento.</p>
          <a href="novo_curso.php" class="new-course-btn">
            <i class="fas fa-plus"></i>
            <span>Criar Primeiro Curso</span>
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Footer -->
  <footer class="main-footer">
    <div class="footer-links">
      <a href="ajuda.php">Central de Ajuda</a>
      <a href="privacidade.php">Política de Privacidade</a>
      <a href="termos.php">Termos de Uso</a>
      <a href="contato.php">Fale Conosco</a>
    </div>
    <div class="copyright">
      © <?php echo date("Y"); ?> Eduka Plus Angola — Transformando a educação angolana.
    </div>
    <div class="version">Versão 3.0.0</div>
  </footer>
</main>

<script>
// Toggle sidebar mobile
const btnMenu = document.getElementById('btnToggleMenu');
const sidebar = document.getElementById('sidebar');
const mainContent = document.querySelector('.main-content');

btnMenu.addEventListener('click', () => {
  sidebar.classList.toggle('show');
});

// Fechar sidebar ao clicar fora (mobile)
document.addEventListener('click', (e) => {
  if (window.innerWidth <= 1024) {
    if (!sidebar.contains(e.target) && !btnMenu.contains(e.target)) {
      sidebar.classList.remove('show');
    }
  }
});

// Animação de contagem suave
function animateCounter(element, duration = 1200) {
  const target = parseInt(element.getAttribute('data-count'));
  if (isNaN(target)) return;
  
  const start = 0;
  const startTime = performance.now();
  
  function updateCount(currentTime) {
    const elapsed = currentTime - startTime;
    const progress = Math.min(elapsed / duration, 1);
    
    // Easing function para animação suave
    const easeOutQuart = 1 - Math.pow(1 - progress, 4);
    const currentValue = Math.floor(easeOutQuart * target);
    
    element.textContent = currentValue.toLocaleString('pt-BR');
    
    if (progress < 1) {
      requestAnimationFrame(updateCount);
    } else {
      element.textContent = target.toLocaleString('pt-BR');
    }
  }
  
  requestAnimationFrame(updateCount);
}

// Iniciar animações de contagem
document.querySelectorAll('.stat-value').forEach(el => {
  animateCounter(el, 1500);
});

document.querySelectorAll('.course-student-count').forEach(el => {
  animateCounter(el, 1000);
});

// Mostrar elementos com animação ao rolar
const observerOptions = {
  threshold: 0.1,
  rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('show');
    }
  });
}, observerOptions);

document.querySelectorAll('.stat-card, .course-card, .chart-container, .empty-state').forEach(el => {
  observer.observe(el);
});

// Gráfico moderno com azuis
const cursosNomes = <?php echo json_encode(array_column($cursos,'curso_nome')); ?>;
const alunosQtd = <?php echo json_encode(array_map(fn($c)=>intval($c['total_alunos']), $cursos)); ?>;

// Configurar gráfico se houver cursos
if (cursosNomes.length > 0) {
  const ctx = document.getElementById('chartCursos').getContext('2d');
  
  // Cores em gradiente azul
  const blueGradients = [];
  for (let i = 0; i < cursosNomes.length; i++) {
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    const opacity = 0.7 + (i * 0.1);
    gradient.addColorStop(0, `rgba(37, 99, 235, ${opacity})`);
    gradient.addColorStop(1, `rgba(14, 165, 233, ${opacity})`);
    blueGradients.push(gradient);
  }
  
  // Dados do gráfico
  const chartData = {
    labels: cursosNomes,
    datasets: [{
      label: 'Alunos matriculados',
      data: alunosQtd,
      backgroundColor: blueGradients,
      borderColor: 'rgba(37, 99, 235, 0.8)',
      borderWidth: 1,
      borderRadius: 6,
      borderSkipped: false,
    }]
  };
  
  // Configurações do gráfico
  const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        display: false
      },
      tooltip: {
        backgroundColor: 'rgba(255, 255, 255, 0.95)',
        titleColor: '#1e293b',
        bodyColor: '#64748b',
        borderColor: 'rgba(37, 99, 235, 0.2)',
        borderWidth: 1,
        cornerRadius: 8,
        padding: 12,
        boxPadding: 6,
        callbacks: {
          label: function(context) {
            return `Alunos: ${context.parsed.y}`;
          }
        }
      }
    },
    scales: {
      x: {
        grid: {
          color: 'rgba(226, 232, 240, 0.5)',
          drawBorder: false
        },
        ticks: {
          color: '#64748b',
          font: {
            size: 12,
            family: "'Inter', sans-serif"
          }
        }
      },
      y: {
        beginAtZero: true,
        grid: {
          color: 'rgba(226, 232, 240, 0.5)',
          drawBorder: false
        },
        ticks: {
          color: '#64748b',
          font: {
            size: 12,
            family: "'Inter', sans-serif"
          },
          callback: function(value) {
            return value;
          }
        }
      }
    },
    animation: {
      duration: 2000,
      easing: 'easeOutQuart'
    }
  };
  
  // Criar gráfico
  new Chart(ctx, {
    type: 'bar',
    data: chartData,
    options: chartOptions
  });
} else {
  // Se não houver cursos, esconder o gráfico
  document.querySelector('.chart-container').innerHTML = `
    <div class="empty-state" style="height: 250px; border: none;">
      <div class="empty-icon">
        <i class="fas fa-chart-bar"></i>
      </div>
      <h3 style="margin-bottom: 0.5rem;">Sem dados para exibir</h3>
      <p style="color: var(--text-secondary);">Crie seu primeiro curso para visualizar estatísticas.</p>
    </div>
  `;
}

// Efeito de clique nos botões do gráfico
document.querySelectorAll('.chart-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.chart-btn').forEach(b => {
      b.style.background = 'var(--surface-dark)';
      b.style.color = 'var(--text-secondary)';
      b.style.borderColor = 'var(--border)';
    });
    
    this.style.background = 'var(--primary)';
    this.style.color = 'white';
    this.style.borderColor = 'var(--primary)';
  });
});

// Ativar o primeiro botão do gráfico por padrão
if (document.querySelector('.chart-btn')) {
  document.querySelector('.chart-btn').click();
}

// Ajustar altura do sidebar
function adjustSidebarHeight() {
  if (window.innerWidth > 1024) {
    sidebar.style.height = '100vh';
  } else {
    sidebar.style.height = '100%';
  }
}

window.addEventListener('resize', adjustSidebarHeight);
adjustSidebarHeight();

// Adicionar tooltips aos ícones
document.querySelectorAll('.stat-icon, .nav-icon').forEach(icon => {
  icon.addEventListener('mouseenter', function() {
    this.style.transform = 'scale(1.1)';
  });
  
  icon.addEventListener('mouseleave', function() {
    this.style.transform = 'scale(1)';
  });
});
</script>
</body>
</html>