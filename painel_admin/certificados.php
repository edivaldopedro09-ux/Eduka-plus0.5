<?php
session_start();
require_once("../config.php");

// Segurança: só admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$admin_id = $_SESSION['usuario_id'];
$nome_admin = $_SESSION['usuario_nome'] ?? "Administrador";

// Estatísticas de certificados
$sql_stats = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pago' THEN 1 ELSE 0 END) as aprovados,
    SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as pendentes
    FROM certificados";
$stats_result = $conn->query($sql_stats);
$stats = $stats_result->fetch_assoc();

// Buscar todos os certificados
$sql = "SELECT cr.id, cr.status, cr.data_emissao, cr.comprovativo, cr.codigo_autenticacao,
               u.nome AS aluno, u.email as aluno_email,
               c.titulo AS curso
        FROM certificados cr
        JOIN usuarios u ON u.id = cr.aluno_id
        JOIN cursos c ON c.id = cr.curso_id
        ORDER BY cr.id DESC";
$result = $conn->query($sql);
$certificados = $result->fetch_all(MYSQLI_ASSOC);

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
<title>Gestão de Certificados — Painel Admin Eduka Plus</title>
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
    padding: 0.5rem 1rem;
    border-radius: var(--radius);
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    font-size: 0.85rem;
}

.btn-success:hover {
    background: #0da271;
    transform: translateY(-2px);
}

.btn-warning {
    background: var(--warning);
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: var(--radius);
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    font-size: 0.85rem;
}

.btn-warning:hover {
    background: #d97706;
    transform: translateY(-2px);
}

.btn-danger {
    background: var(--danger);
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: var(--radius);
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    font-size: 0.85rem;
}

.btn-danger:hover {
    background: #dc2626;
    transform: translateY(-2px);
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

/* Seção de Certificados */
.certificates-section {
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

/* Filtros */
.filters-bar {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
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

/* Tabela de Certificados */
.certificates-table-container {
    overflow-x: auto;
    border-radius: var(--radius);
    border: 1px solid var(--border);
    background: var(--surface-light);
}

.certificates-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1000px;
}

.certificates-table thead {
    background: var(--surface-dark);
}

.certificates-table th {
    padding: 1rem 1.25rem;
    text-align: left;
    font-weight: 600;
    color: var(--text-secondary);
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid var(--border);
}

.certificates-table td {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--border-light);
    color: var(--text-primary);
    vertical-align: middle;
}

.certificates-table tbody tr {
    transition: all 0.2s ease;
}

.certificates-table tbody tr:hover {
    background: rgba(37, 99, 235, 0.03);
}

.certificates-table tbody tr:last-child td {
    border-bottom: none;
}

/* Células da tabela */
.aluno-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.aluno-nome {
    font-weight: 600;
    font-size: 0.95rem;
}

.aluno-email {
    font-size: 0.85rem;
    color: var(--text-secondary);
}

.curso-info {
    font-weight: 500;
    color: var(--text-primary);
}

/* Status Badge */
.status-badge {
    padding: 0.5rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    display: inline-block;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-pendente {
    background: rgba(245, 158, 11, 0.1);
    color: var(--warning);
    border: 1px solid rgba(245, 158, 11, 0.2);
}

.status-pago {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success);
    border: 1px solid rgba(16, 185, 129, 0.2);
}

/* Data de emissão */
.emissao-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.data-emissao {
    font-weight: 600;
    font-size: 0.9rem;
}

.data-hora {
    font-size: 0.8rem;
    color: var(--text-secondary);
}

/* Ações */
.actions-cell {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.action-btn {
    padding: 0.4rem 0.75rem;
    border-radius: 6px;
    border: 1px solid var(--border);
    background: var(--surface-dark);
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 0.8rem;
    font-weight: 500;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    cursor: pointer;
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}

.action-view:hover {
    background: rgba(37, 99, 235, 0.1);
    color: var(--primary);
    border-color: rgba(37, 99, 235, 0.3);
}

.action-approve:hover {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success);
    border-color: rgba(16, 185, 129, 0.3);
}

.action-reject:hover {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger);
    border-color: rgba(239, 68, 68, 0.3);
}

.action-download:hover {
    background: rgba(14, 165, 233, 0.1);
    color: var(--info);
    border-color: rgba(14, 165, 233, 0.3);
}

/* Código de autenticação */
.codigo-autenticacao {
    font-family: 'Courier New', monospace;
    font-size: 0.85rem;
    background: var(--surface-dark);
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    color: var(--text-primary);
    display: inline-block;
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
    
    .filters-bar {
        flex-direction: column;
    }
    
    .filter-select, .search-box {
        min-width: 100%;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .actions-cell {
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
        
        <nav class="sidebar-nav">
            <a href="./dashboard.php" class="nav-item">
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
            <a href="./certificados.php" class="nav-item active">
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
            <h1>🏆 Gestão de Certificados</h1>
            <p>Gerencie todos os certificados emitidos na plataforma</p>
        </div>
        
        <div class="header-actions">
            <a href="notificacoes.php" class="notification-btn">
                <i class="fas fa-bell"></i>
                <?php if($total_notificacoes > 0): ?>
                    <span class="notification-badge"><?php echo $total_notificacoes; ?></span>
                <?php endif; ?>
            </a>
            <a href="dashboard.php" class="btn-outline">
                <i class="fas fa-arrow-left"></i>
                <span>Voltar ao Dashboard</span>
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
                <i class="fas fa-award"></i>
            </div>
            <div class="stat-content">
                <h3>Total de Certificados</h3>
                <div class="stat-value"><?php echo $stats['total'] ?? 0; ?></div>
                <p class="stat-description">Certificados emitidos</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <h3>Certificados Aprovados</h3>
                <div class="stat-value"><?php echo $stats['aprovados'] ?? 0; ?></div>
                <p class="stat-description">Status: Pago</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <h3>Certificados Pendentes</h3>
                <div class="stat-value"><?php echo $stats['pendentes'] ?? 0; ?></div>
                <p class="stat-description">Aguardando aprovação</p>
            </div>
        </div>
    </div>

    <!-- Seção de Certificados -->
    <section class="certificates-section">
        <div class="section-header">
            <h3><i class="fas fa-list"></i> Todos os Certificados</h3>
            <div>
                <?php if($stats['pendentes'] > 0): ?>
                    <a href="certificados.php?filter=pending" class="btn-warning">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $stats['pendentes']; ?> Pendentes</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Filtros -->
        <div class="filters-bar">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Buscar por aluno, curso ou código...">
            </div>
            <select class="filter-select" id="statusFilter">
                <option value="">Todos os status</option>
                <option value="pago">Aprovados</option>
                <option value="pendente">Pendentes</option>
            </select>
        </div>
        
        <?php if(empty($certificados)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-award"></i>
                </div>
                <h3>Nenhum certificado encontrado</h3>
                <p>Não há certificados emitidos na plataforma ainda.</p>
            </div>
        <?php else: ?>
            <div class="certificates-table-container">
                <table class="certificates-table" id="certificatesTable">
                    <thead>
                        <tr>
                            <th>Aluno</th>
                            <th>Curso</th>
                            <th>Status</th>
                            <th>Data de Emissão</th>
                            <th>Código de Autenticação</th>
                            <th>Comprovativo</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($certificados as $cert): 
                            $dataEmissao = new DateTime($cert['data_emissao']);
                            $dataFormatada = $dataEmissao->format('d/m/Y');
                            $horaFormatada = $dataEmissao->format('H:i');
                        ?>
                        <tr data-aluno="<?php echo htmlspecialchars(strtolower($cert['aluno'])); ?>"
                            data-curso="<?php echo htmlspecialchars(strtolower($cert['curso'])); ?>"
                            data-status="<?php echo $cert['status']; ?>"
                            data-codigo="<?php echo htmlspecialchars(strtolower($cert['codigo_autenticacao'] ?? '')); ?>">
                            <td>
                                <div class="aluno-info">
                                    <div class="aluno-nome"><?php echo htmlspecialchars($cert['aluno']); ?></div>
                                    <div class="aluno-email"><?php echo htmlspecialchars($cert['aluno_email']); ?></div>
                                </div>
                            </td>
                            <td>
                                <div class="curso-info"><?php echo htmlspecialchars($cert['curso']); ?></div>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $cert['status']; ?>">
                                    <?php echo ucfirst($cert['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="emissao-info">
                                    <div class="data-emissao"><?php echo $dataFormatada; ?></div>
                                    <div class="data-hora"><?php echo $horaFormatada; ?></div>
                                </div>
                            </td>
                            <td>
                                <?php if($cert['codigo_autenticacao']): ?>
                                    <span class="codigo-autenticacao" title="Código de autenticação">
                                        <?php echo htmlspecialchars($cert['codigo_autenticacao']); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 0.85rem;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($cert['comprovativo']): ?>
                                    <a href="../uploads/comprovativos/<?php echo htmlspecialchars($cert['comprovativo']); ?>" 
                                       target="_blank" 
                                       class="action-btn action-view"
                                       title="Ver comprovativo">
                                        <i class="fas fa-file-invoice"></i>
                                        <span>Ver</span>
                                    </a>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 0.85rem;">Sem comprovativo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions-cell">
                                    <?php if($cert['status'] === "pendente" && $cert['comprovativo']): ?>
                                        <form action="validar_certificado.php" method="post" style="display: inline;">
                                            <input type="hidden" name="id" value="<?php echo $cert['id']; ?>">
                                            <button type="submit" 
                                                    name="acao" 
                                                    value="aprovar" 
                                                    class="action-btn action-approve"
                                                    title="Aprovar certificado"
                                                    onclick="return confirm('Deseja aprovar este certificado?')">
                                                <i class="fas fa-check"></i>
                                                <span>Aprovar</span>
                                            </button>
                                            <button type="submit" 
                                                    name="acao" 
                                                    value="rejeitar" 
                                                    class="action-btn action-reject"
                                                    title="Rejeitar certificado"
                                                    onclick="return confirm('Deseja rejeitar este certificado?')">
                                                <i class="fas fa-times"></i>
                                                <span>Rejeitar</span>
                                            </button>
                                        </form>
                                    <?php elseif($cert['status'] === "pago"): ?>
                                        <span style="color: var(--success); font-size: 0.85rem; font-weight: 600;">
                                            <i class="fas fa-check-circle"></i> Liberado
                                        </span>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-size: 0.85rem;">
                                            <i class="fas fa-clock"></i> Aguardando
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if($cert['status'] === "pago"): ?>
                                        <a href="gerar_certificado.php?id=<?php echo $cert['id']; ?>" 
                                           class="action-btn action-download"
                                           title="Baixar certificado">
                                            <i class="fas fa-download"></i>
                                            <span>Baixar</span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
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
const statusFilter = document.getElementById('statusFilter');

function filterCertificates() {
    const searchTerm = searchInput.value.toLowerCase();
    const selectedStatus = statusFilter.value;
    
    const rows = document.querySelectorAll('#certificatesTable tbody tr');
    let visibleCount = 0;
    
    rows.forEach(row => {
        const aluno = row.getAttribute('data-aluno');
        const curso = row.getAttribute('data-curso');
        const status = row.getAttribute('data-status');
        const codigo = row.getAttribute('data-codigo');
        
        const matchesSearch = searchTerm === '' || 
                            aluno.includes(searchTerm) || 
                            curso.includes(searchTerm) ||
                            codigo.includes(searchTerm);
        const matchesStatus = selectedStatus === '' || status === selectedStatus;
        
        if (matchesSearch && matchesStatus) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
}

if (searchInput) searchInput.addEventListener('input', filterCertificates);
if (statusFilter) statusFilter.addEventListener('change', filterCertificates);

// Animar entrada das linhas
document.addEventListener('DOMContentLoaded', () => {
    const rows = document.querySelectorAll('#certificatesTable tbody tr');
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

// Confirmação aprimorada para ações
const approveButtons = document.querySelectorAll('button[name="acao"][value="aprovar"]');
const rejectButtons = document.querySelectorAll('button[name="acao"][value="rejeitar"]');

approveButtons.forEach(button => {
    button.addEventListener('click', function(e) {
        const aluno = this.closest('tr').querySelector('.aluno-nome').textContent;
        const curso = this.closest('tr').querySelector('.curso-info').textContent;
        
        if (!confirm(`Deseja realmente APROVAR o certificado para:\n\nAluno: ${aluno}\nCurso: ${curso}\n\nEsta ação marcará o certificado como pago e liberará para o aluno.`)) {
            e.preventDefault();
        }
    });
});

rejectButtons.forEach(button => {
    button.addEventListener('click', function(e) {
        const aluno = this.closest('tr').querySelector('.aluno-nome').textContent;
        const curso = this.closest('tr').querySelector('.curso-info').textContent;
        
        if (!confirm(`Deseja realmente REJEITAR o certificado para:\n\nAluno: ${aluno}\nCurso: ${curso}\n\nEsta ação requerirá que o aluno envie novo comprovativo.`)) {
            e.preventDefault();
        }
    });
});
</script>
</body>
</html>