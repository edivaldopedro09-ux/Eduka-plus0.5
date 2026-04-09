<?php
session_start();
require_once("../config.php");

// Segurança: só professores
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'professor') {
    header("Location: ../index.php");
    exit();
}

$professor_id = $_SESSION['usuario_id'];

// Buscar todos os cursos do professor
$sql = "SELECT id, titulo FROM cursos WHERE professor_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$result = $stmt->get_result();
$cursos = $result->fetch_all(MYSQLI_ASSOC);

// Contar total de alunos
$total_alunos = 0;
foreach($cursos as $curso) {
    $sql2 = "SELECT COUNT(*) as total FROM inscricoes WHERE curso_id=?";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("i", $curso['id']);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    $row = $result2->fetch_assoc();
    $total_alunos += $row['total'];
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Alunos Matriculados — Eduka Plus</title>
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

.back-btn {
    background: var(--surface-light);
    color: var(--text-secondary);
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

.back-btn:hover {
    background: var(--primary);
    color: white;
    transform: translateY(-2px);
    border-color: var(--primary);
}

.export-btn {
    background: var(--surface-light);
    color: var(--success);
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

.export-btn:hover {
    background: var(--success);
    color: white;
    transform: translateY(-2px);
    border-color: var(--success);
}

/* Estatísticas */
.stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2.5rem;
}

.stat-card {
    background: var(--surface-light);
    border-radius: var(--radius);
    padding: 1.5rem;
    border: 1px solid var(--border);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-4px);
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
    border-radius: 10px;
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
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text-secondary);
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
    color: var(--text-primary);
    line-height: 1;
}

.stat-description {
    font-size: 0.85rem;
    color: var(--text-muted);
}

/* Cursos e Alunos */
.courses-section {
    margin-top: 2rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-light);
}

.section-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: var(--text-primary);
}

.section-title i {
    color: var(--primary);
    font-size: 1.2rem;
}

.section-title h2 {
    font-size: 1.5rem;
    font-weight: 600;
}

.courses-container {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

/* Card do Curso */
.course-card {
    background: var(--surface-light);
    border-radius: var(--radius);
    border: 1px solid var(--border);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    transition: all 0.3s ease;
}

.course-card:hover {
    box-shadow: var(--shadow-lg);
    border-color: rgba(37, 99, 235, 0.2);
}

.course-header {
    background: linear-gradient(135deg, rgba(37, 99, 235, 0.1) 0%, rgba(14, 165, 233, 0.1) 100%);
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.course-header h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.course-header h3 i {
    color: var(--primary);
}

.alunos-count {
    background: var(--surface-dark);
    color: var(--text-secondary);
    padding: 0.4rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.alunos-count i {
    color: var(--success);
}

/* Tabela de Alunos */
.alunos-table {
    width: 100%;
    border-collapse: collapse;
}

.alunos-table thead {
    background: var(--surface-dark);
}

.alunos-table th {
    padding: 1rem 1.25rem;
    text-align: left;
    font-weight: 600;
    color: var(--text-secondary);
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid var(--border);
}

.alunos-table td {
    padding: 1.25rem 1.25rem;
    border-bottom: 1px solid var(--border-light);
    color: var(--text-primary);
}

.alunos-table tbody tr {
    transition: all 0.2s ease;
}

.alunos-table tbody tr:hover {
    background: rgba(37, 99, 235, 0.03);
}

.aluno-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.aluno-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 0.9rem;
}

.aluno-details h4 {
    font-size: 0.95rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.aluno-details p {
    font-size: 0.85rem;
    color: var(--text-secondary);
}

.status-badge {
    display: inline-block;
    padding: 0.35rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.status-ativo {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success);
}

.status-inativo {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger);
}

.date-cell {
    color: var(--text-secondary);
    font-size: 0.9rem;
    font-weight: 500;
}

.empty-state {
    padding: 3rem 2rem;
    text-align: center;
    color: var(--text-secondary);
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
    color: var(--text-muted);
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
    margin-left: auto;
    margin-right: auto;
}

/* Ações */
.aluno-actions {
    display: flex;
    gap: 0.5rem;
}

.action-btn {
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    border: 1px solid var(--border);
    background: var(--surface-light);
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 0.8rem;
    font-weight: 500;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    cursor: pointer;
}

.action-btn:hover {
    background: rgba(37, 99, 235, 0.1);
    color: var(--primary);
    border-color: rgba(37, 99, 235, 0.3);
    transform: translateY(-2px);
}

.action-btn.message-btn {
    background: rgba(14, 165, 233, 0.1);
    border-color: rgba(14, 165, 233, 0.2);
    color: var(--info);
}

.action-btn.message-btn:hover {
    background: rgba(14, 165, 233, 0.2);
    border-color: var(--info);
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

/* Responsividade */
@media (max-width: 768px) {
    .main-content {
        padding: 1.25rem;
        padding-top: 4.5rem;
    }
    
    .stats-cards {
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
    
    .alunos-table {
        display: block;
        overflow-x: auto;
    }
    
    .course-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .aluno-actions {
        flex-wrap: wrap;
    }
}

@media (max-width: 480px) {
    .alunos-table th,
    .alunos-table td {
        padding: 0.75rem;
    }
    
    .aluno-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .action-btn {
        width: 100%;
        justify-content: center;
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

/* Animações */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-in {
    animation: fadeInUp 0.6s ease forwards;
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
            
            <div>
                <div class="brand-text">Eduka Plus</div>
                <div class="brand-subtitle">Painel do Professor</div>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <a href="./dashboard.php" class="nav-item">
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
            <a href="./alunos_matriculados.php" class="nav-item active">
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
            <h1>👥 Alunos Matriculados</h1>
            <p>Acompanhe e gerencie todos os alunos dos seus cursos.</p>
        </div>
        
        <div class="header-actions">
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                <span>Voltar ao Dashboard</span>
            </a>
            <a href="#" class="export-btn" onclick="exportData()">
                <i class="fas fa-file-export"></i>
                <span>Exportar Dados</span>
            </a>
        </div>
    </header>

    <!-- Estatísticas -->
    <div class="stats-cards">
        <div class="stat-card fade-in">
            <div class="stat-icon">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="stat-content">
                <h3>Cursos Ativos</h3>
                <div class="stat-value"><?php echo count($cursos); ?></div>
                <p class="stat-description">Total de cursos criados</p>
            </div>
        </div>
        
        <div class="stat-card fade-in" style="animation-delay: 0.1s">
            <div class="stat-icon">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="stat-content">
                <h3>Total de Alunos</h3>
                <div class="stat-value"><?php echo $total_alunos; ?></div>
                <p class="stat-description">Alunos matriculados</p>
            </div>
        </div>
        
        <div class="stat-card fade-in" style="animation-delay: 0.2s">
            <div class="stat-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-content">
                <h3>Média por Curso</h3>
                <div class="stat-value">
                    <?php 
                        $media = count($cursos) > 0 ? round($total_alunos / count($cursos), 1) : 0;
                        echo $media;
                    ?>
                </div>
                <p class="stat-description">Alunos por curso em média</p>
            </div>
        </div>
    </div>

    <!-- Lista de Cursos com Alunos -->
    <section class="courses-section">
        <div class="section-header">
            <div class="section-title">
                <i class="fas fa-list-ol"></i>
                <h2>Alunos por Curso</h2>
            </div>
            <div class="section-actions">
                <div style="color: var(--text-secondary); font-size: 0.9rem;">
                    Mostrando <?php echo count($cursos); ?> cursos
                </div>
            </div>
        </div>
        
        <div class="courses-container">
            <?php if(count($cursos) > 0): ?>
                <?php foreach($cursos as $curso): 
                    // Buscar alunos matriculados no curso
                    $sql2 = "SELECT u.nome, u.email, i.inscrito_em 
                             FROM inscricoes i
                             INNER JOIN usuarios u ON i.aluno_id = u.id
                             WHERE i.curso_id=?
                             ORDER BY i.inscrito_em DESC";
                    $stmt2 = $conn->prepare($sql2);
                    $stmt2->bind_param("i", $curso['id']);
                    $stmt2->execute();
                    $result2 = $stmt2->get_result();
                    $alunos = $result2->fetch_all(MYSQLI_ASSOC);
                    $total_alunos_curso = count($alunos);
                ?>
                <div class="course-card fade-in">
                    <div class="course-header">
                        <h3>
                            <i class="fas fa-book"></i>
                            <?php echo htmlspecialchars($curso['titulo']); ?>
                        </h3>
                        <div class="alunos-count">
                            <i class="fas fa-users"></i>
                            <span><?php echo $total_alunos_curso; ?> aluno<?php echo $total_alunos_curso != 1 ? 's' : ''; ?></span>
                        </div>
                    </div>
                    
                    <?php if($total_alunos_curso > 0): ?>
                        <div style="overflow-x: auto;">
                            <table class="alunos-table">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;"></th>
                                        <th>Aluno</th>
                                        <th>Email</th>
                                        <th>Data de Inscrição</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($alunos as $aluno): 
                                        $iniciais_aluno = strtoupper(substr($aluno['nome'], 0, 1) . substr($aluno['nome'], strpos($aluno['nome'], ' ') + 1, 1));
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="aluno-avatar">
                                                <?php echo $iniciais_aluno; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="aluno-info">
                                                <div class="aluno-details">
                                                    <h4><?php echo htmlspecialchars($aluno['nome']); ?></h4>
                                                    <span class="status-badge status-ativo">Ativo</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="color: var(--text-primary);"><?php echo htmlspecialchars($aluno['email']); ?></div>
                                        </td>
                                        <td>
                                            <div class="date-cell">
                                                <i class="far fa-calendar-alt" style="margin-right: 5px;"></i>
                                                <?php echo date('d/m/Y', strtotime($aluno['inscrito_em'])); ?>
                                                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                                                    <?php echo date('H:i', strtotime($aluno['inscrito_em'])); ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="aluno-actions">
                                                <a href="chat_list.php?curso_id=<?php echo $curso['id']; ?>&aluno_id=<?php echo $aluno['id']; ?>" class="action-btn message-btn" title="Enviar mensagem">
                                                    <i class="fas fa-envelope"></i>
                                                    <span>Mensagem</span>
                                                </a>
                                                <a href="#" class="action-btn" title="Ver progresso" onclick="verProgresso(<?php echo $aluno['id']; ?>, '<?php echo htmlspecialchars(addslashes($aluno['nome'])); ?>')">
                                                    <i class="fas fa-chart-bar"></i>
                                                    <span>Progresso</span>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-user-slash"></i>
                            <h3>Nenhum aluno matriculado</h3>
                            <p>Este curso ainda não possui alunos matriculados. Compartilhe o curso para atrair estudantes.</p>
                            <a href="meus_cursos.php" class="back-btn" style="display: inline-flex; margin-top: 1rem;">
                                <i class="fas fa-share-alt"></i>
                                <span>Compartilhar Curso</span>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state fade-in">
                    <i class="fas fa-graduation-cap"></i>
                    <h3>Nenhum curso criado</h3>
                    <p>Você ainda não criou nenhum curso. Crie seu primeiro curso para começar a receber alunos.</p>
                    <a href="meus_cursos.php" class="back-btn" style="display: inline-flex; margin-top: 1rem;">
                        <i class="fas fa-plus-circle"></i>
                        <span>Criar Primeiro Curso</span>
                    </a>
                </div>
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

// Animação para mostrar elementos ao rolar
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('fade-in');
        }
    });
}, observerOptions);

document.querySelectorAll('.stat-card, .course-card').forEach(el => {
    observer.observe(el);
});

// Função para exportar dados
function exportData() {
    alert('Funcionalidade de exportação será implementada em breve!');
    // Em uma implementação real, aqui iria:
    // 1. Gerar um CSV ou PDF
    // 2. Baixar o arquivo
    // 3. Ou abrir uma modal com opções de exportação
}

// Função para ver progresso do aluno
function verProgresso(alunoId, alunoNome) {
    alert(`Visualizando progresso de ${alunoNome}\n\nEsta funcionalidade exibirá:\n- Progresso no curso\n- Notas e avaliações\n- Atividades concluídas\n- Estatísticas de aprendizado`);
    
    // Em uma implementação real, aqui iria:
    // 1. Buscar dados do progresso do aluno via AJAX
    // 2. Abrir um modal ou redirecionar para página de progresso
    // 3. Mostrar gráficos e estatísticas
}

// Ordenação da tabela
document.querySelectorAll('.alunos-table th').forEach(th => {
    th.addEventListener('click', function() {
        const table = this.closest('table');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const index = Array.from(this.parentNode.children).indexOf(this);
        
        rows.sort((a, b) => {
            const aText = a.children[index].textContent.trim();
            const bText = b.children[index].textContent.trim();
            
            // Verificar se é data
            if (aText.includes('/')) {
                const aDate = parseDate(aText);
                const bDate = parseDate(bText);
                return aDate - bDate;
            }
            
            // Verificar se é número
            if (!isNaN(aText) && !isNaN(bText)) {
                return aText - bText;
            }
            
            return aText.localeCompare(bText);
        });
        
        // Alternar entre ascendente e descendente
        if (this.classList.contains('asc')) {
            rows.reverse();
            this.classList.remove('asc');
            this.classList.add('desc');
        } else {
            this.classList.remove('desc');
            this.classList.add('asc');
        }
        
        // Remover classes de ordenação de outras colunas
        this.parentNode.querySelectorAll('th').forEach(otherTh => {
            if (otherTh !== this) {
                otherTh.classList.remove('asc', 'desc');
            }
        });
        
        // Reinserir linhas ordenadas
        rows.forEach(row => tbody.appendChild(row));
    });
});

function parseDate(dateStr) {
    const parts = dateStr.split('/');
    if (parts.length === 3) {
        return new Date(parts[2], parts[1] - 1, parts[0]);
    }
    return new Date(dateStr);
}

// Busca em tempo real (se implementada futuramente)
const searchInput = document.createElement('input');
searchInput.type = 'text';
searchInput.placeholder = 'Buscar aluno por nome ou email...';
searchInput.style.cssText = `
    padding: 0.75rem 1rem;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    width: 100%;
    max-width: 400px;
    margin-bottom: 1.5rem;
    font-family: 'Inter', sans-serif;
    font-size: 0.95rem;
    background: var(--surface-light);
    color: var(--text-primary);
`;

// Adicionar busca se houver muitos alunos
if (<?php echo $total_alunos; ?> > 10) {
    document.querySelector('.courses-section').insertBefore(searchInput, document.querySelector('.courses-container'));
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        document.querySelectorAll('.course-card').forEach(card => {
            const rows = card.querySelectorAll('.alunos-table tbody tr');
            let hasMatches = false;
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                    hasMatches = true;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Mostrar/ocultar curso se não houver matches
            const emptyState = card.querySelector('.empty-state');
            if (searchTerm && !hasMatches && !emptyState) {
                card.style.opacity = '0.5';
            } else {
                card.style.opacity = '1';
            }
        });
    });
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
</script>
</body>
</html>