<?php
session_start();
require_once("../config.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$admin_id = $_SESSION['usuario_id'];
$nome_admin = $_SESSION['usuario_nome'] ?? "Administrador";

// Buscar usuários
$sql = "SELECT * FROM usuarios ORDER BY id DESC";
$result = $conn->query($sql);

// Estatísticas
$sql_stats = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN tipo = 'aluno' THEN 1 ELSE 0 END) as alunos,
    SUM(CASE WHEN tipo = 'professor' THEN 1 ELSE 0 END) as professores,
    SUM(CASE WHEN tipo = 'admin' THEN 1 ELSE 0 END) as admins
    FROM usuarios";
$stats_result = $conn->query($sql_stats);
$stats = $stats_result->fetch_assoc();

// Notificações
$stmt = $conn->prepare("SELECT COUNT(*) FROM notificacoes WHERE destinatario_id = ? OR destinatario_tipo = 'admin' OR destinatario_tipo = 'todos'");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$stmt->bind_result($total_notificacoes);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestão de Usuários — Painel Admin Eduka Plus</title>
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
}

.btn-primary:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
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

/* Seção de Usuários */
.users-section {
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

/* Tabela */
.users-table-container {
    overflow-x: auto;
    border-radius: var(--radius);
    border: 1px solid var(--border);
    background: var(--surface-light);
}

.users-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 800px;
}

.users-table thead {
    background: var(--surface-dark);
}

.users-table th {
    padding: 1rem 1.25rem;
    text-align: left;
    font-weight: 600;
    color: var(--text-secondary);
    font-size: 0.85rem;
    text-transform: uppercase;
    border-bottom: 1px solid var(--border);
}

.users-table td {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--border-light);
    vertical-align: middle;
}

.users-table tbody tr:hover {
    background: rgba(37, 99, 235, 0.03);
}

/* Células */
.user-cell {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.user-avatar-small {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1rem;
    flex-shrink: 0;
    overflow: hidden;
}

.user-avatar-small img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.user-info h4 {
    font-size: 0.95rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.user-info p {
    font-size: 0.85rem;
    color: var(--text-secondary);
}

/* Badges */
.type-badge {
    padding: 0.4rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    display: inline-block;
}

.type-admin {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger);
}

.type-professor {
    background: rgba(37, 99, 235, 0.1);
    color: var(--primary);
}

.type-aluno {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success);
}

.status-badge {
    padding: 0.35rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.status-active {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success);
}

.status-inactive {
    background: rgba(100, 116, 139, 0.1);
    color: var(--text-secondary);
}

/* Ações */
.actions-cell {
    display: flex;
    gap: 0.5rem;
}

.action-btn {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.3s ease;
    border: 1px solid var(--border);
    background: var(--surface);
    color: var(--text-secondary);
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}

.action-view:hover {
    background: rgba(37, 99, 235, 0.1);
    color: var(--primary);
}

.action-edit:hover {
    background: rgba(245, 158, 11, 0.1);
    color: var(--warning);
}

.action-delete:hover {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger);
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

/* Responsividade */
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
            <a href="./usuarios.php" class="nav-item active">
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
            <a href="./cursos.php" class="nav-item">
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
            <h1>👋 Gestão de Usuários</h1>
            <p>Gerencie todos os usuários da plataforma Eduka Plus</p>
        </div>
        
        <div class="header-actions">
            <a href="notificacoes.php" class="notification-btn">
                <i class="fas fa-bell"></i>
                <?php if($total_notificacoes > 0): ?>
                    <span class="notification-badge"><?php echo $total_notificacoes; ?></span>
                <?php endif; ?>
            </a>
            <a href="usuarios_add.php" class="btn-primary">
                <i class="fas fa-user-plus"></i>
                <span>Novo Usuário</span>
            </a>
            <a href="../logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Sair</span>
            </a>
        </div>
    </header>

    <!-- Estatísticas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3>Total de Usuários</h3>
                <div class="stat-value"><?php echo $stats['total'] ?? 0; ?></div>
                <p class="stat-description">Usuários cadastrados na plataforma</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="stat-content">
                <h3>Alunos</h3>
                <div class="stat-value"><?php echo $stats['alunos'] ?? 0; ?></div>
                <p class="stat-description">Estudantes ativos</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="stat-content">
                <h3>Professores</h3>
                <div class="stat-value"><?php echo $stats['professores'] ?? 0; ?></div>
                <p class="stat-description">Instrutores da plataforma</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <div class="stat-content">
                <h3>Administradores</h3>
                <div class="stat-value"><?php echo $stats['admins'] ?? 0; ?></div>
                <p class="stat-description">Equipe administrativa</p>
            </div>
        </div>
    </div>

    <!-- Seção de Usuários -->
    <section class="users-section">
        <div class="section-header">
            <h3><i class="fas fa-users"></i> Todos os Usuários</h3>
            <a href="usuarios_add.php" class="btn-primary">
                <i class="fas fa-user-plus"></i>
                <span>Adicionar Usuário</span>
            </a>
        </div>
        
        <!-- Barra de busca -->
        <div class="search-bar">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Buscar por nome, email ou tipo...">
            </div>
            <select class="filter-select" id="typeFilter">
                <option value="">Todos os tipos</option>
                <option value="aluno">Alunos</option>
                <option value="professor">Professores</option>
                <option value="admin">Administradores</option>
            </select>
            <select class="filter-select" id="statusFilter">
                <option value="">Todos os status</option>
                <option value="ativo">Ativos</option>
                <option value="inativo">Inativos</option>
            </select>
        </div>
        
        <?php if($result->num_rows === 0): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-users-slash"></i>
                </div>
                <h3>Nenhum usuário encontrado</h3>
                <p>Não há usuários cadastrados na plataforma ainda.</p>
                <a href="usuarios_add.php" class="btn-primary">
                    <i class="fas fa-user-plus"></i>
                    <span>Adicionar Primeiro Usuário</span>
                </a>
            </div>
        <?php else: ?>
            <div class="users-table-container">
                <table class="users-table" id="usersTable">
                    <thead>
                        <tr>
                            <th>Usuário</th>
                            <th>Email</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th>Cadastro</th>
                            <th style="text-align: center;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($u = $result->fetch_assoc()): 
                            $status = $u['ativo'] ?? 1;
                            $data_cadastro = isset($u['data_cadastro']) ? date('d/m/Y', strtotime($u['data_cadastro'])) : '--/--/----';
                        ?>
                        <tr data-user-id="<?php echo $u['id']; ?>" data-type="<?php echo $u['tipo']; ?>" data-status="<?php echo $status ? 'ativo' : 'inativo'; ?>">
                            <td>
                                <div class="user-cell">
                                    <div class="user-avatar-small">
                                        <?php if(!empty($u['foto'])): ?>
                                            <img src="../uploads/<?php echo htmlspecialchars($u['foto']); ?>" alt="<?php echo htmlspecialchars($u['nome']); ?>">
                                        <?php else: ?>
                                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); color: white; font-weight: 600;">
                                                <?php 
                                                    $iniciais = strtoupper(substr($u['nome'], 0, 1) . substr($u['nome'], strpos($u['nome'], ' ') + 1, 1));
                                                    echo $iniciais;
                                                ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="user-info">
                                        <h4><?php echo htmlspecialchars($u['nome']); ?></h4>
                                        <p>ID: #<?php echo $u['id']; ?></p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 500;"><?php echo htmlspecialchars($u['email']); ?></div>
                                <?php if(!empty($u['telefone'])): ?>
                                    <div style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 4px;">
                                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($u['telefone']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="type-badge type-<?php echo $u['tipo']; ?>">
                                    <?php echo ucfirst($u['tipo']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $status ? 'active' : 'inactive'; ?>">
                                    <?php echo $status ? 'Ativo' : 'Inativo'; ?>
                                </span>
                            </td>
                            <td>
                                <div style="font-size: 0.9rem; color: var(--text-secondary);">
                                    <?php echo $data_cadastro; ?>
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <div class="actions-cell">
                                    <a href="usuarios_view.php?id=<?php echo $u['id']; ?>" class="action-btn action-view" title="Visualizar">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="usuarios_edit.php?id=<?php echo $u['id']; ?>" class="action-btn action-edit" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="usuarios_delete.php?id=<?php echo $u['id']; ?>" 
                                       class="action-btn action-delete" 
                                       title="Excluir"
                                       onclick="return confirm('Tem certeza que deseja excluir o usuário <?php echo addslashes($u['nome']); ?>?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
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
const typeFilter = document.getElementById('typeFilter');
const statusFilter = document.getElementById('statusFilter');

function filterUsers() {
    const searchTerm = searchInput.value.toLowerCase();
    const selectedType = typeFilter.value;
    const selectedStatus = statusFilter.value;
    
    const rows = document.querySelectorAll('#usersTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const type = row.getAttribute('data-type');
        const status = row.getAttribute('data-status');
        
        const matchesSearch = text.includes(searchTerm);
        const matchesType = !selectedType || type === selectedType;
        const matchesStatus = !selectedStatus || status === selectedStatus;
        
        row.style.display = matchesSearch && matchesType && matchesStatus ? '' : 'none';
    });
}

if (searchInput) searchInput.addEventListener('input', filterUsers);
if (typeFilter) typeFilter.addEventListener('change', filterUsers);
if (statusFilter) statusFilter.addEventListener('change', filterUsers);

// Animar entrada das linhas
document.addEventListener('DOMContentLoaded', () => {
    const rows = document.querySelectorAll('#usersTable tbody tr');
    rows.forEach((row, index) => {
        row.style.opacity = '0';
        row.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            row.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            row.style.opacity = '1';
            row.style.transform = 'translateY(0)';
        }, index * 50);
    });
});
</script>
</body>
</html>