<?php
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once("../config.php");

$admin_id = $_SESSION['usuario_id'];
$nome_admin = $_SESSION['usuario_nome'] ?? "Administrador";

// --- Estatísticas rápidas ---
$totalAlunos = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo='aluno'")->fetch_assoc()['total'] ?? 0;
$totalProfessores = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo='professor'")->fetch_assoc()['total'] ?? 0;
$totalCursos = $conn->query("SELECT COUNT(*) as total FROM cursos")->fetch_assoc()['total'] ?? 0;
$totalInscricoes = $conn->query("SELECT COUNT(*) as total FROM inscricoes")->fetch_assoc()['total'] ?? 0;
$totalAulas = $conn->query("SELECT COUNT(*) as total FROM aulas")->fetch_assoc()['total'] ?? 0;

// --- Cursos com número de alunos inscritos ---
$sql = "SELECT c.titulo, c.descricao, u.nome as professor, COUNT(i.aluno_id) as inscritos
        FROM cursos c
        LEFT JOIN inscricoes i ON c.id = i.curso_id
        LEFT JOIN usuarios u ON c.professor_id = u.id
        GROUP BY c.id, c.titulo, c.descricao, u.nome
        ORDER BY inscritos DESC";
$cursos = $conn->query($sql);

$cursosData = [];
while($c = $cursos->fetch_assoc()){
    $cursosData[] = $c;
}

// --- Últimos usuários registrados ---
$ultimosUsuarios = $conn->query("SELECT nome, email, tipo, DATE_FORMAT(data_cadastro, '%d/%m/%Y') as data FROM usuarios ORDER BY id DESC LIMIT 10");

// --- Cursos mais populares (top 5) ---
$cursosPopulares = $conn->query("SELECT c.titulo, COUNT(i.aluno_id) as inscritos FROM cursos c LEFT JOIN inscricoes i ON c.id = i.curso_id GROUP BY c.id ORDER BY inscritos DESC LIMIT 5");

// --- Notificações ---
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
<title>Relatórios — Painel Admin Eduka Plus</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

.btn-warning {
    background: var(--warning);
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

.btn-warning:hover {
    background: #d97706;
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

.stat-card:nth-child(5) .stat-icon {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger);
}

.stat-card:nth-child(6) .stat-icon {
    background: rgba(14, 165, 233, 0.1);
    color: var(--info);
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

/* Seção de Relatórios */
.reports-section {
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

.export-buttons {
    display: flex;
    gap: 1rem;
}

/* Container de gráficos */
.charts-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2.5rem;
}

.chart-card {
    background: var(--surface-light);
    border-radius: var(--radius);
    padding: 1.5rem;
    border: 1px solid var(--border);
    box-shadow: var(--shadow-sm);
}

.chart-card h4 {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.chart-wrapper {
    height: 300px;
    position: relative;
}

/* Tabelas */
.tables-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2.5rem;
}

.table-card {
    background: var(--surface-light);
    border-radius: var(--radius);
    padding: 1.5rem;
    border: 1px solid var(--border);
    box-shadow: var(--shadow-sm);
}

.table-card h4 {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.reports-table {
    width: 100%;
    border-collapse: collapse;
    background: var(--surface-light);
    border-radius: var(--radius);
    overflow: hidden;
}

.reports-table thead {
    background: var(--surface-dark);
}

.reports-table th {
    padding: 1rem 1.25rem;
    text-align: left;
    font-weight: 600;
    color: var(--text-secondary);
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid var(--border);
}

.reports-table td {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--border-light);
    color: var(--text-primary);
    vertical-align: middle;
}

.reports-table tbody tr {
    transition: all 0.2s ease;
}

.reports-table tbody tr:hover {
    background: rgba(37, 99, 235, 0.03);
}

.reports-table tbody tr:last-child td {
    border-bottom: none;
}

/* Badges para tipo de usuário */
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
    .charts-container,
    .tables-container {
        grid-template-columns: 1fr;
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
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .export-buttons {
        flex-direction: column;
        width: 100%;
    }
    
    .export-buttons form {
        width: 100%;
    }
    
    .export-buttons button {
        width: 100%;
    }
    
    .charts-container,
    .tables-container {
        grid-template-columns: 1fr;
    }
    
    .chart-card,
    .table-card {
        padding: 1rem;
    }
    
    .chart-wrapper {
        height: 250px;
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
            <a href="./relatorios.php" class="nav-item active">
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
            <h1>📊 Relatórios da Plataforma</h1>
            <p>Análise completa dos dados da plataforma Eduka Plus</p>
        </div>
        
        <div class="header-actions">
            <a href="notificacoes.php" class="notification-btn">
                <i class="fas fa-bell"></i>
                <?php if($total_notificacoes > 0): ?>
                    <span class="notification-badge"><?php echo $total_notificacoes; ?></span>
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
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="stat-content">
                <h3>Total de Alunos</h3>
                <div class="stat-value"><?php echo $totalAlunos; ?></div>
                <p class="stat-description">Alunos cadastrados</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="stat-content">
                <h3>Total de Professores</h3>
                <div class="stat-value"><?php echo $totalProfessores; ?></div>
                <p class="stat-description">Instrutores ativos</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="stat-content">
                <h3>Total de Cursos</h3>
                <div class="stat-value"><?php echo $totalCursos; ?></div>
                <p class="stat-description">Cursos disponíveis</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3>Total de Inscrições</h3>
                <div class="stat-value"><?php echo $totalInscricoes; ?></div>
                <p class="stat-description">Matrículas realizadas</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-play-circle"></i>
            </div>
            <div class="stat-content">
                <h3>Total de Aulas</h3>
                <div class="stat-value"><?php echo $totalAulas; ?></div>
                <p class="stat-description">Aulas disponíveis</p>
            </div>
        </div>
    </div>

    <!-- Seção de Relatórios -->
    <section class="reports-section">
        <div class="section-header">
            <h3><i class="fas fa-chart-line"></i> Análises e Gráficos</h3>
            <div class="export-buttons">
                <form action="relatorio_pdf.php" method="post">
                    <button type="submit" class="btn-warning">
                        <i class="fas fa-file-pdf"></i>
                        <span>Exportar PDF</span>
                    </button>
                </form>
                <form action="relatorio_excel.php" method="post">
                    <button type="submit" class="btn-success">
                        <i class="fas fa-file-excel"></i>
                        <span>Exportar Excel</span>
                    </button>
                </form>
                <a href="dashboard.php" class="btn-outline">
                    <i class="fas fa-arrow-left"></i>
                    <span>Voltar ao Dashboard</span>
                </a>
            </div>
        </div>
        
        <!-- Gráficos -->
        <div class="charts-container">
            <div class="chart-card">
                <h4><i class="fas fa-chart-bar"></i> Inscrições por Curso</h4>
                <div class="chart-wrapper">
                    <canvas id="graficoCursos"></canvas>
                </div>
            </div>
            
            <div class="chart-card">
                <h4><i class="fas fa-chart-pie"></i> Distribuição de Usuários</h4>
                <div class="chart-wrapper">
                    <canvas id="graficoUsuarios"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Tabelas -->
        <div class="tables-container">
            <div class="table-card">
                <h4><i class="fas fa-graduation-cap"></i> Detalhes dos Cursos</h4>
                <div class="table-responsive">
                    <table class="reports-table">
                        <thead>
                            <tr>
                                <th>Curso</th>
                                <th>Professor</th>
                                <th>Inscritos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($cursosData as $c): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($c['titulo']); ?></strong>
                                        <?php if(!empty($c['descricao'])): ?>
                                            <div style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.25rem;">
                                                <?php echo substr(htmlspecialchars($c['descricao']), 0, 60); ?>...
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="type-badge type-professor">
                                            <?php echo htmlspecialchars($c['professor'] ?? 'Não definido'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; color: var(--primary); font-size: 1.1rem;">
                                            <?php echo $c['inscritos']; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="table-card">
                <h4><i class="fas fa-users"></i> Últimos Usuários Registrados</h4>
                <div class="table-responsive">
                    <table class="reports-table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Tipo</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($user = $ultimosUsuarios->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($user['nome']); ?></strong>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.9rem;"><?php echo htmlspecialchars($user['email']); ?></div>
                                    </td>
                                    <td>
                                        <span class="type-badge type-<?php echo $user['tipo']; ?>">
                                            <?php echo ucfirst($user['tipo']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.85rem; color: var(--text-secondary);">
                                            <?php echo $user['data']; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Informações adicionais -->
        <div class="table-card">
            <h4><i class="fas fa-trophy"></i> Cursos Mais Populares</h4>
            <div class="table-responsive">
                <table class="reports-table">
                    <thead>
                        <tr>
                            <th>Posição</th>
                            <th>Curso</th>
                            <th>Inscritos</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $position = 1;
                        while($curso = $cursosPopulares->fetch_assoc()): 
                            $medal = $position <= 3 ? 
                                ($position == 1 ? '🥇' : ($position == 2 ? '🥈' : '🥉')) : 
                                $position . 'º';
                        ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700; font-size: 1.1rem; text-align: center;">
                                        <?php echo $medal; ?>
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($curso['titulo']); ?></strong>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: var(--primary); font-size: 1.1rem;">
                                        <?php echo $curso['inscritos']; ?>
                                    </div>
                                </td>
                                <td>
                                    <span style="display: inline-block; padding: 0.25rem 0.75rem; background: rgba(16, 185, 129, 0.1); color: var(--success); border-radius: 20px; font-size: 0.8rem; font-weight: 600;">
                                        <?php echo $curso['inscritos'] > 0 ? 'Ativo' : 'Sem inscritos'; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php 
                        $position++;
                        endwhile; 
                        ?>
                    </tbody>
                </table>
            </div>
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

// Dados para os gráficos
const cursosLabels = <?php echo json_encode(array_column($cursosData, 'titulo')); ?>;
const cursosInscritos = <?php echo json_encode(array_column($cursosData, 'inscritos')); ?>;

// Gráfico de barras - Inscrições por curso
const ctxCursos = document.getElementById('graficoCursos').getContext('2d');
new Chart(ctxCursos, {
    type: 'bar',
    data: {
        labels: cursosLabels,
        datasets: [{
            label: 'Número de Inscritos',
            data: cursosInscritos,
            backgroundColor: '#2563eb',
            borderColor: '#1d4ed8',
            borderWidth: 1,
            borderRadius: 6,
            barPercentage: 0.6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top',
                labels: {
                    font: {
                        size: 12,
                        family: 'Inter'
                    }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.7)',
                titleFont: {
                    family: 'Inter',
                    size: 12
                },
                bodyFont: {
                    family: 'Inter',
                    size: 11
                },
                padding: 10
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(226, 232, 240, 0.5)'
                },
                ticks: {
                    font: {
                        family: 'Inter',
                        size: 11
                    },
                    color: '#64748b'
                },
                title: {
                    display: true,
                    text: 'Número de Inscritos',
                    font: {
                        family: 'Inter',
                        size: 12,
                        weight: 'bold'
                    },
                    color: '#1e293b'
                }
            },
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    font: {
                        family: 'Inter',
                        size: 11
                    },
                    color: '#64748b',
                    maxRotation: 45,
                    minRotation: 0
                }
            }
        }
    }
});

// Gráfico de pizza - Distribuição de usuários
const ctxUsuarios = document.getElementById('graficoUsuarios').getContext('2d');
new Chart(ctxUsuarios, {
    type: 'pie',
    data: {
        labels: ['Alunos', 'Professores'],
        datasets: [{
            data: [<?php echo $totalAlunos; ?>, <?php echo $totalProfessores; ?>],
            backgroundColor: ['#10b981', '#f59e0b'],
            borderColor: ['#0da271', '#d97706'],
            borderWidth: 2,
            hoverOffset: 15
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    font: {
                        family: 'Inter',
                        size: 12
                    },
                    padding: 20,
                    usePointStyle: true,
                    pointStyle: 'circle'
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.7)',
                titleFont: {
                    family: 'Inter',
                    size: 12
                },
                bodyFont: {
                    family: 'Inter',
                    size: 11
                },
                padding: 10,
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.raw || 0;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = Math.round((value / total) * 100);
                        return `${label}: ${value} (${percentage}%)`;
                    }
                }
            }
        }
    }
});

// Animar entrada dos elementos
document.addEventListener('DOMContentLoaded', () => {
    const statCards = document.querySelectorAll('.stat-card');
    const charts = document.querySelectorAll('.chart-card');
    const tables = document.querySelectorAll('.table-card');
    
    // Animar cards de estatísticas
    statCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Animar gráficos e tabelas
    setTimeout(() => {
        charts.forEach((chart, index) => {
            chart.style.opacity = '0';
            chart.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                chart.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                chart.style.opacity = '1';
                chart.style.transform = 'translateY(0)';
            }, index * 150);
        });
        
        tables.forEach((table, index) => {
            table.style.opacity = '0';
            table.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                table.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                table.style.opacity = '1';
                table.style.transform = 'translateY(0)';
            }, index * 150 + 300);
        });
    }, 500);
});
</script>
</body>
</html>