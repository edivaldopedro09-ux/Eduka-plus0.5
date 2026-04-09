<?php
session_start();
require_once("../config.php");

// ✅ Só admin pode acessar
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$admin_id = $_SESSION['usuario_id'];
$nome_admin = $_SESSION['usuario_nome'] ?? "Administrador";

// Buscar cursos no banco
$sql = "SELECT c.id, c.titulo, c.descricao, c.imagem, 
               u.nome AS professor_nome, 
               (SELECT COUNT(*) FROM inscricoes i WHERE i.curso_id = c.id) as total_inscritos,
               (SELECT COUNT(*) FROM aulas a WHERE a.curso_id = c.id) as total_aulas
        FROM cursos c
        LEFT JOIN usuarios u ON c.professor_id = u.id
        ORDER BY c.id DESC";
$result = $conn->query($sql);

// Estatísticas
$stats_sql = "SELECT 
    COUNT(*) as total_cursos,
    (SELECT COUNT(DISTINCT professor_id) FROM cursos WHERE professor_id IS NOT NULL) as total_professores
    FROM cursos";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Total de inscrições
$inscricoes_sql = "SELECT COUNT(*) as total FROM inscricoes";
$inscricoes_result = $conn->query($inscricoes_sql);
$total_inscricoes = $inscricoes_result->fetch_assoc()['total'] ?? 0;

// Total de alunos
$alunos_sql = "SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'aluno'";
$alunos_result = $conn->query($alunos_sql);
$total_alunos = $alunos_result->fetch_assoc()['total'] ?? 0;

// Notificações
$stmt = $conn->prepare("SELECT COUNT(*) FROM notificacoes WHERE destinatario_id = ? OR destinatario_tipo = 'admin' OR destinatario_tipo = 'todos'");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$stmt->bind_result($total_notificacoes);
$stmt->fetch();
$stmt->close();

// Mensagem de sessão
$msg = "";
if (isset($_SESSION['msg'])) {
    $msg = $_SESSION['msg'];
    unset($_SESSION['msg']);
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestão de Cursos — Painel Admin Eduka Plus</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
/* Variáveis CSS - Mesmas do dashboard */
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
    --radius: 12px;
    --sidebar-width: 280px;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', sans-serif;
    background: var(--background);
    color: var(--text-primary);
    display: flex;
    min-height: 100vh;
    line-height: 1.6;
}

/* Sidebar */
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
    box-shadow: var(--shadow-md);
}

.brand {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 2.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--border-light);
}

.logo-container {
    width: 42px;
    height: 42px;
    border-radius: var(--radius);
    overflow: hidden;
}

.logo-container img {
    width: 100%;
    height: 100%;
    object-fit: contain;
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

.nav-icon {
    width: 20px;
    text-align: center;
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
}

/* Conteúdo principal */
.main-content {
    flex: 1;
    margin-left: var(--sidebar-width);
    padding: 2rem 2.5rem;
    background: var(--surface);
    min-height: 100vh;
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
}

.welcome-section p {
    color: var(--text-secondary);
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

.btn-primary {
    background: var(--primary);
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: var(--radius);
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    cursor: pointer;
}

.btn-primary:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.btn-success {
    background: var(--success);
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: var(--radius);
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.btn-success:hover {
    background: #0da271;
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.btn-outline {
    background: transparent;
    color: var(--text-primary);
    border: 2px solid var(--border);
    padding: 0.75rem 1.5rem;
    border-radius: var(--radius);
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.btn-outline:hover {
    border-color: var(--primary);
    color: var(--primary);
    transform: translateY(-2px);
}

.logout-btn {
    background: var(--surface-light);
    color: var(--danger);
    border: 1px solid var(--border);
    padding: 0.75rem 1.5rem;
    border-radius: var(--radius);
    text-decoration: none;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.logout-btn:hover {
    background: var(--danger);
    color: white;
    transform: translateY(-2px);
}

/* Estatísticas */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2.5rem;
}

.stat-card {
    background: var(--surface-light);
    border-radius: var(--radius);
    padding: 1.5rem;
    border: 1px solid var(--border);
    transition: all 0.4s ease;
    position: relative;
    overflow: hidden;
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
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
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

.stat-card:nth-child(4) .stat-icon {
    background: rgba(139, 92, 246, 0.1);
    color: #8b5cf6;
}

.stat-content h3 {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--text-secondary);
    margin-bottom: 0.75rem;
    text-transform: uppercase;
}

.stat-value {
    font-size: 2.25rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.stat-description {
    font-size: 0.875rem;
    color: var(--text-muted);
}

/* Mensagem de alerta */
.alert-message {
    background: rgba(37, 99, 235, 0.1);
    border-left: 4px solid var(--primary);
    padding: 1rem 1.5rem;
    border-radius: var(--radius);
    margin-bottom: 2rem;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.alert-message i {
    color: var(--primary);
    font-size: 1.25rem;
}

/* Seção de Cursos */
.courses-section {
    background: var(--surface-light);
    border-radius: var(--radius);
    padding: 1.5rem;
    border: 1px solid var(--border);
    box-shadow: var(--shadow-sm);
    margin-bottom: 2.5rem;
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
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Barra de busca */
.search-bar {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.search-box {
    flex: 1;
    min-width: 300px;
    position: relative;
}

.search-box input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.5rem;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    font-family: 'Inter', sans-serif;
    font-size: 0.95rem;
    color: var(--text-primary);
    background: var(--surface-light);
}

.search-box input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.search-box i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
}

.filter-select {
    padding: 0.75rem 1rem;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    font-family: 'Inter', sans-serif;
    font-size: 0.95rem;
    color: var(--text-primary);
    background: var(--surface-light);
    min-width: 180px;
    cursor: pointer;
}

/* Grid de Cursos */
.courses-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

.course-card {
    background: var(--surface-light);
    border-radius: var(--radius);
    border: 1px solid var(--border);
    overflow: hidden;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
}

.course-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
    border-color: rgba(37, 99, 235, 0.2);
}

.course-image {
    height: 180px;
    overflow: hidden;
    position: relative;
}

.course-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.course-card:hover .course-image img {
    transform: scale(1.05);
}

.course-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 0.35rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.course-content {
    padding: 1.5rem;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.course-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
    color: var(--text-primary);
    line-height: 1.4;
}

.course-description {
    font-size: 0.9rem;
    color: var(--text-secondary);
    margin-bottom: 1rem;
    flex: 1;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.course-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    font-size: 0.85rem;
    color: var(--text-secondary);
}

.course-meta span {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.course-stats {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-light);
}

.stat-item {
    text-align: center;
    flex: 1;
}

.stat-number {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--primary);
    display: block;
}

.stat-label {
    font-size: 0.75rem;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.course-actions {
    display: flex;
    gap: 0.75rem;
    margin-top: auto;
}

.action-btn {
    flex: 1;
    padding: 0.5rem 1rem;
    border-radius: var(--radius);
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 600;
    text-align: center;
    transition: all 0.3s ease;
    border: 1px solid var(--border);
}

.action-edit {
    background: rgba(37, 99, 235, 0.1);
    color: var(--primary);
}

.action-edit:hover {
    background: var(--primary);
    color: white;
    transform: translateY(-2px);
}

.action-view {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success);
}

.action-view:hover {
    background: var(--success);
    color: white;
    transform: translateY(-2px);
}

.action-delete {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger);
}

.action-delete:hover {
    background: var(--danger);
    color: white;
    transform: translateY(-2px);
}

/* Empty State */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 2rem;
    text-align: center;
    color: var(--text-secondary);
    background: var(--surface-light);
    border-radius: var(--radius);
    border: 2px dashed var(--border);
    grid-column: 1 / -1;
}

.empty-icon {
    font-size: 2.5rem;
    color: var(--text-muted);
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--text-primary);
}

.empty-state p {
    margin-bottom: 1.5rem;
    max-width: 400px;
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
}

/* Menu mobile toggle */
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
}

#btnToggleMenu:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
}

/* Responsividade */
@media (max-width: 1200px) {
    .courses-grid {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    }
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

@media (max-width: 768px) {
    .main-content {
        padding: 1.25rem;
        padding-top: 4.5rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .header-actions {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .welcome-section h1 {
        font-size: 1.75rem;
    }
    
    .search-bar {
        flex-direction: column;
    }
    
    .search-box {
        min-width: 100%;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .courses-grid {
        grid-template-columns: 1fr;
    }
    
    .course-actions {
        flex-direction: column;
    }
}

/* Scrollbar */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: var(--surface-dark);
}

::-webkit-scrollbar-thumb {
    background: var(--primary);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: var(--primary-dark);
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
            <div class="logo-container">
                <img src="../imagens/logo.jpg" alt="Eduka Plus Logo">
            </div>
            <div>
                <div class="brand-text">Eduka Plus</div>
                <div class="brand-subtitle">Painel Administrativo</div>
            </div>
        </div>
        
        <nav class="sidebar-nav"><a href="./dashboard.php" class="nav-item">
                <span class="nav-icon"><i class="fas fa-tachometer-alt"></i></span>
                <span>Dashboard</span>
            </a>
            <a href="./usuarios.php" class="nav-item">
                <span class="nav-icon"><i class="fas fa-users"></i></span>
                <span>Usuários</span>
            </a>
           <!--  <a href="./professores.php" class="nav-item">
                <span class="nav-icon"><i class="fas fa-chalkboard-teacher"></i></span>
                <span>Professores</span>
            </a>
            <a href="./alunos.php" class="nav-item">
                <span class="nav-icon"><i class="fas fa-user-graduate"></i></span>
                <span>Alunos</span>
            </a> -->
            <a href="./cursos.php" class="nav-item active">
                <span class="nav-icon"><i class="fas fa-book"></i></span>
                <span>Cursos</span>
            </a>
            <a href="./inscricoes.php" class="nav-item">
                <span class="nav-icon"><i class="fas fa-file-signature"></i></span>
                <span>Inscrições</span>
            </a>
            <a href="./certificados.php" class="nav-item">
                <span class="nav-icon"><i class="fas fa-award"></i></span>
                <span>Certificados</span>
            </a>
            <a href="./relatorios.php" class="nav-item">
                <span class="nav-icon"><i class="fas fa-chart-pie"></i></span>
                <span>Relatórios</span>
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
                <p>Administrador</p>
            </div>
        </div>
    </div>
</aside>

<!-- Conteúdo Principal -->
<main class="main-content">
    <!-- Header -->
    <header class="main-header">
        <div class="welcome-section">
            <h1>📚 Gestão de Cursos</h1>
            <p>Gerencie todos os cursos da plataforma Eduka Plus</p>
        </div>
        
        <div class="header-actions">
            <a href="notificacoes.php" class="notification-btn">
                <i class="fas fa-bell"></i>
                <?php if($total_notificacoes > 0): ?>
                    <span class="notification-badge"><?php echo $total_notificacoes; ?></span>
                <?php endif; ?>
            </a>
            <a href="cursos_add.php" class="btn-primary">
                <i class="fas fa-plus-circle"></i>
                <span>Novo Curso</span>
            </a>
            <a href="../logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Sair</span>
            </a>
        </div>
    </header>

    <!-- Mensagem -->
    <?php if ($msg): ?>
        <div class="alert-message">
            <i class="fas fa-info-circle"></i>
            <span><?php echo htmlspecialchars($msg); ?></span>
        </div>
    <?php endif; ?>

    <!-- Estatísticas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="stat-content">
                <h3>Total de Cursos</h3>
                <div class="stat-value"><?php echo $stats['total_cursos'] ?? 0; ?></div>
                <p class="stat-description">Cursos criados na plataforma</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="stat-content">
                <h3>Total de Alunos</h3>
                <div class="stat-value"><?php echo $total_alunos; ?></div>
                <p class="stat-description">Alunos cadastrados</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="stat-content">
                <h3>Professores</h3>
                <div class="stat-value"><?php echo $stats['total_professores'] ?? 0; ?></div>
                <p class="stat-description">Instrutores ativos</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3>Total de Inscrições</h3>
                <div class="stat-value"><?php echo $total_inscricoes; ?></div>
                <p class="stat-description">Matrículas realizadas</p>
            </div>
        </div>
    </div>

    <!-- Seção de Cursos -->
    <section class="courses-section">
        <div class="section-header">
            <h3><i class="fas fa-list"></i> Todos os Cursos</h3>
            <div>
                <a href="dashboard.php" class="btn-outline">
                    <i class="fas fa-arrow-left"></i>
                    <span>Voltar ao Dashboard</span>
                </a>
            </div>
        </div>
        
        <!-- Barra de busca -->
        <div class="search-bar">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Buscar cursos por título ou professor...">
            </div>
            <select class="filter-select" id="professorFilter">
                <option value="">Todos os professores</option>
                <?php
                $prof_sql = "SELECT DISTINCT u.id, u.nome 
                            FROM cursos c 
                            JOIN usuarios u ON c.professor_id = u.id 
                            ORDER BY u.nome";
                $prof_result = $conn->query($prof_sql);
                while($prof = $prof_result->fetch_assoc()):
                ?>
                    <option value="<?php echo htmlspecialchars(strtolower($prof['nome'])); ?>">
                        <?php echo htmlspecialchars($prof['nome']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div class="courses-grid">
            <?php if($result->num_rows === 0): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3>Nenhum curso encontrado</h3>
                    <p>Não há cursos cadastrados na plataforma ainda.</p>
                    <a href="cursos_add.php" class="btn-primary">
                        <i class="fas fa-plus-circle"></i>
                        <span>Criar Primeiro Curso</span>
                    </a>
                </div>
            <?php else: ?>
                <?php while($curso = $result->fetch_assoc()): ?>
                <div class="course-card" 
                     data-title="<?php echo htmlspecialchars(strtolower($curso['titulo'])); ?>"
                     data-professor="<?php echo htmlspecialchars(strtolower($curso['professor_nome'] ?? '')); ?>">
                    
                    <div class="course-image">
                        <?php if (!empty($curso['imagem'])): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($curso['imagem']); ?>" alt="<?php echo htmlspecialchars($curso['titulo']); ?>">
                        <?php else: ?>
                            <div style="width: 100%; height: 100%; background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); display: flex; align-items: center; justify-content: center; color: white;">
                                <i class="fas fa-graduation-cap" style="font-size: 3rem;"></i>
                            </div>
                        <?php endif; ?>
                        
                        <span class="course-badge">Curso</span>
                    </div>
                    
                    <div class="course-content">
                        <h3 class="course-title"><?php echo htmlspecialchars($curso['titulo']); ?></h3>
                        
                        <div class="course-meta">
                            <span>
                                <i class="fas fa-chalkboard-teacher"></i>
                                <?php echo htmlspecialchars($curso['professor_nome'] ?? "Não definido"); ?>
                            </span>
                        </div>
                        
                        <p class="course-description">
                            <?php echo nl2br(htmlspecialchars(substr($curso['descricao'] ?? '', 0, 150))); ?>
                            <?php if(strlen($curso['descricao'] ?? '') > 150): ?>...<?php endif; ?>
                        </p>
                        
                        <div class="course-stats">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $curso['total_aulas'] ?? 0; ?></span>
                                <span class="stat-label">Aulas</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $curso['total_inscritos'] ?? 0; ?></span>
                                <span class="stat-label">Inscritos</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number">
                                    <?php 
                                    $date = date_create($curso['criado_em'] ?? '');
                                    echo $date ? date_format($date, 'd/m/Y') : '--/--/----';
                                    ?>
                                </span>
                                <span class="stat-label">Criado em</span>
                            </div>
                        </div>
                        
                        <div class="course-actions">
                            <a href="cursos_view.php?id=<?php echo $curso['id']; ?>" class="action-btn action-view">
                                <i class="fas fa-eye"></i> Ver
                            </a>
                            <a href="cursos_edit.php?id=<?php echo $curso['id']; ?>" class="action-btn action-edit">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <a href="cursos_delete.php?id=<?php echo $curso['id']; ?>" 
                               class="action-btn action-delete"
                               onclick="return confirm('Tem certeza que deseja excluir o curso \"<?php echo addslashes($curso['titulo']); ?>\"?\n\nEsta ação não poderá ser desfeita.')">
                                <i class="fas fa-trash"></i> Excluir
                            </a>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </section>

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
// Menu mobile
const btnMenu = document.getElementById('btnToggleMenu');
const sidebar = document.getElementById('sidebar');

if (btnMenu) {
    btnMenu.addEventListener('click', () => {
        sidebar.classList.toggle('show');
    });
    
    if (window.innerWidth <= 1024) {
        btnMenu.style.display = 'block';
    }
    
    window.addEventListener('resize', () => {
        if (window.innerWidth <= 1024) {
            btnMenu.style.display = 'block';
        } else {
            btnMenu.style.display = 'none';
            sidebar.classList.remove('show');
        }
    });
}

// Filtro de busca
const searchInput = document.getElementById('searchInput');
const professorFilter = document.getElementById('professorFilter');

function filterCourses() {
    const searchTerm = searchInput.value.toLowerCase();
    const selectedProfessor = professorFilter.value.toLowerCase();
    
    const courses = document.querySelectorAll('.course-card');
    let visibleCount = 0;
    
    courses.forEach(course => {
        const title = course.getAttribute('data-title');
        const professor = course.getAttribute('data-professor');
        
        const matchesSearch = searchTerm === '' || title.includes(searchTerm);
        const matchesProfessor = selectedProfessor === '' || professor.includes(selectedProfessor);
        
        if (matchesSearch && matchesProfessor) {
            course.style.display = 'flex';
            visibleCount++;
        } else {
            course.style.display = 'none';
        }
    });
    
    // Mostrar mensagem se não houver resultados
    const emptyState = document.querySelector('.empty-state');
    if (visibleCount === 0 && !emptyState) {
        const coursesGrid = document.querySelector('.courses-grid');
        const emptyHTML = `
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-search"></i>
                </div>
                <h3>Nenhum curso encontrado</h3>
                <p>Tente ajustar os filtros ou buscar por outros termos.</p>
            </div>
        `;
        coursesGrid.innerHTML = emptyHTML;
    } else if (visibleCount > 0 && emptyState && emptyState.textContent.includes('Nenhum curso encontrado')) {
        emptyState.remove();
        // Recarregar os cursos originais
        location.reload();
    }
}

if (searchInput) searchInput.addEventListener('input', filterCourses);
if (professorFilter) professorFilter.addEventListener('change', filterCourses);

// Animar entrada dos cards
document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('.course-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});

// Highlight no card ao passar o mouse
const courseCards = document.querySelectorAll('.course-card');
courseCards.forEach(card => {
    card.addEventListener('mouseenter', () => {
        card.style.boxShadow = 'var(--shadow-xl)';
    });
    
    card.addEventListener('mouseleave', () => {
        card.style.boxShadow = '';
    });
});
</script>
</body>
</html>