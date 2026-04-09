<?php
session_start();
require_once("../config.php");

// Segurança: só alunos
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'aluno') {
    header("Location: ../index.php");
    exit();
}

$aluno_id = $_SESSION['usuario_id'];

// Marcar aula como concluída se aula_id estiver presente
if(isset($_GET['aula_id']) && is_numeric($_GET['aula_id'])){
    $aula_id = intval($_GET['aula_id']);
    // Inserir ou atualizar progresso da aula
    $stmt = $conn->prepare("INSERT INTO progresso (aluno_id, aula_id, concluido) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE concluido=1");
    $stmt->bind_param("ii", $aluno_id, $aula_id);
    $stmt->execute();

    // Verificar curso da aula
    $stmt2 = $conn->prepare("SELECT curso_id FROM aulas WHERE id=?");
    $stmt2->bind_param("i", $aula_id);
    $stmt2->execute();
    $curso_id = $stmt2->get_result()->fetch_assoc()['curso_id'] ?? null;

    // Total de aulas do curso
    $stmt3 = $conn->prepare("SELECT COUNT(*) AS total FROM aulas WHERE curso_id=?");
    $stmt3->bind_param("i", $curso_id);
    $stmt3->execute();
    $total_aulas = $stmt3->get_result()->fetch_assoc()['total'] ?? 0;

    // Aulas concluídas pelo aluno
    $stmt4 = $conn->prepare("SELECT COUNT(*) AS concluidas FROM progresso p INNER JOIN aulas a ON p.aula_id=a.id WHERE p.aluno_id=? AND a.curso_id=? AND p.concluido=1");
    $stmt4->bind_param("ii", $aluno_id, $curso_id);
    $stmt4->execute();
    $aulas_concluidas = $stmt4->get_result()->fetch_assoc()['concluidas'] ?? 0;

    // Marcar curso como concluído se todas aulas finalizadas
    if($total_aulas > 0 && $aulas_concluidas >= $total_aulas){
        $stmt5 = $conn->prepare("UPDATE inscricoes SET concluido=1 WHERE aluno_id=? AND curso_id=?");
        $stmt5->bind_param("ii", $aluno_id, $curso_id);
        $stmt5->execute();
    }

    header("Location: progresso.php");
    exit();
}

// Buscar cursos do aluno
$sql = "SELECT 
            c.id AS curso_id, 
            c.titulo AS curso_nome, 
            c.descricao,
            c.imagem,
            i.concluido,
            i.inscrito_em,
            (SELECT COUNT(*) FROM aulas a WHERE a.curso_id=c.id) AS total_aulas,
            (SELECT COUNT(*) FROM progresso p WHERE p.aluno_id=? AND p.aula_id IN (SELECT id FROM aulas WHERE curso_id=c.id) AND p.concluido=1) AS aulas_concluidas
        FROM cursos c
        INNER JOIN inscricoes i ON c.id=i.curso_id
        WHERE i.aluno_id=?
        ORDER BY i.concluido ASC, i.inscrito_em DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $aluno_id, $aluno_id);
$stmt->execute();
$cursos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calcular estatísticas gerais
$total_cursos = count($cursos);
$cursos_concluidos = 0;
$total_aulas_geral = 0;
$aulas_concluidas_geral = 0;

foreach($cursos as $curso) {
    if($curso['concluido'] == 1) $cursos_concluidos++;
    $total_aulas_geral += $curso['total_aulas'];
    $aulas_concluidas_geral += $curso['aulas_concluidas'];
}

$percent_geral = $total_aulas_geral > 0 ? round(($aulas_concluidas_geral/$total_aulas_geral)*100) : 0;
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Progresso — Eduka Plus</title>
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

.logo-container {
    width: 42px;
    height: 42px;
    border-radius: var(--radius);
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
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
    background: rgba(245, 158, 11, 0.1);
    color: var(--warning);
}

.stat-card:nth-child(3) .stat-icon {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success);
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

/* Barra de Progresso Geral */
.progress-section {
    background: var(--surface-light);
    border-radius: var(--radius);
    padding: 2rem;
    margin-bottom: 2.5rem;
    border: 1px solid var(--border);
}

.progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.progress-header h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.progress-overview {
    margin-bottom: 1.5rem;
}

.progress-label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.progress-label span {
    font-weight: 600;
    color: var(--text-primary);
    font-size: 1.1rem;
}

.progress-percentage {
    font-weight: 700;
    color: var(--primary);
    font-size: 1.25rem;
}

.progress-bar {
    height: 12px;
    background: var(--surface-dark);
    border-radius: 6px;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
    border-radius: 6px;
    position: relative;
    transition: width 1s ease-in-out;
}

.progress-fill::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    animation: shimmer 2s infinite;
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.progress-stats {
    display: flex;
    justify-content: space-between;
    color: var(--text-secondary);
    font-size: 0.9rem;
}

/* Filtros */
.filters-section {
    background: var(--surface-light);
    border-radius: var(--radius);
    padding: 1.5rem;
    margin-bottom: 2rem;
    border: 1px solid var(--border);
}

.filters-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.filters-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-options {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.filter-btn {
    padding: 0.5rem 1.5rem;
    border: 1px solid var(--border);
    border-radius: 20px;
    background: var(--surface-light);
    color: var(--text-secondary);
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.filter-btn:hover {
    background: var(--surface-dark);
    color: var(--text-primary);
}

.filter-btn.active {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

/* Lista de Cursos */
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

.courses-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

@media (max-width: 768px) {
    .courses-grid {
        grid-template-columns: 1fr;
    }
}

/* Card do Curso */
.course-card {
    background: var(--surface-light);
    border-radius: var(--radius);
    border: 1px solid var(--border);
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    opacity: 0;
    transform: translateY(20px);
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

.course-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--border);
    background: linear-gradient(135deg, rgba(37, 99, 235, 0.05) 0%, rgba(14, 165, 233, 0.05) 100%);
}

.course-title {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1rem;
}

.course-image {
    width: 60px;
    height: 60px;
    border-radius: 10px;
    object-fit: cover;
    flex-shrink: 0;
}

.course-info h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
    line-height: 1.3;
}

.course-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-top: 0.75rem;
    font-size: 0.85rem;
    color: var(--text-secondary);
}

.course-meta span {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.course-badge {
    display: inline-block;
    padding: 0.35rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.badge-completed {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success);
}

.badge-in-progress {
    background: rgba(245, 158, 11, 0.1);
    color: var(--warning);
}

.badge-not-started {
    background: rgba(100, 116, 139, 0.1);
    color: var(--text-secondary);
}

.course-content {
    padding: 1.5rem;
}

.course-progress {
    margin-bottom: 1.5rem;
}

.progress-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.progress-percent {
    font-weight: 700;
    color: var(--primary);
    font-size: 1.1rem;
}

.progress-text {
    color: var(--text-secondary);
    font-size: 0.9rem;
    font-weight: 500;
}

.course-bar {
    height: 8px;
    background: var(--surface-dark);
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.course-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
    border-radius: 4px;
    transition: width 1s ease-in-out;
}

.course-actions {
    display: flex;
    gap: 0.75rem;
    margin-top: 1.5rem;
}

.action-btn {
    flex: 1;
    padding: 0.75rem;
    border-radius: 8px;
    border: 1px solid var(--border);
    background: var(--surface-dark);
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
    text-align: center;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    cursor: pointer;
}

.action-btn:hover {
    background: rgba(37, 99, 235, 0.1);
    color: var(--primary);
    border-color: rgba(37, 99, 235, 0.3);
    transform: translateY(-2px);
}

.action-btn.primary {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

.action-btn.primary:hover {
    background: var(--primary-dark);
    border-color: var(--primary-dark);
}

.action-btn.success {
    background: rgba(16, 185, 129, 0.1);
    border-color: rgba(16, 185, 129, 0.2);
    color: var(--success);
}

.action-btn.success:hover {
    background: rgba(16, 185, 129, 0.2);
    border-color: var(--success);
}

/* Empty State */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    text-align: center;
    background: var(--surface-light);
    border-radius: var(--radius);
    border: 2px dashed var(--border);
    grid-column: 1 / -1;
}

.empty-icon {
    font-size: 3.5rem;
    color: var(--text-muted);
    margin-bottom: 1.5rem;
    opacity: 0.5;
}

.empty-state h3 {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.75rem;
}

.empty-state p {
    color: var(--text-secondary);
    margin-bottom: 2rem;
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
    
    .course-title {
        flex-direction: column;
    }
    
    .course-image {
        width: 100%;
        height: 120px;
    }
    
    .course-actions {
        flex-direction: column;
    }
}

@media (max-width: 480px) {
    .filter-options {
        flex-direction: column;
    }
    
    .filter-btn {
        width: 100%;
        text-align: center;
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
            <div class="logo-container">
                <img src="../imagens/logo.jpg" alt="Eduka Plus Logo">
            </div>
            <div>
                <div class="brand-text">Eduka Plus</div>
                <div class="brand-subtitle">Painel do Aluno</div>
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
            <a href="./progresso.php" class="nav-item active">
                <span class="nav-icon"><i class="fas fa-chart-line"></i></span>
                <span>Progresso</span>
            </a>
            <a href="./materiais.php" class="nav-item">
                <span class="nav-icon"><i class="fas fa-folder-open"></i></span>
                <span>Materiais</span>
            </a>
            <a href="./certificados.php" class="nav-item">
                <span class="nav-icon"><i class="fas fa-award"></i></span>
                <span>Certificados</span>
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
                <p>Aluno</p>
            </div>
        </div>
    </div>
</aside>

<!-- Conteúdo Principal -->
<main class="main-content">
    <!-- Header -->
    <header class="main-header">
        <div class="welcome-section">
            <h1>📊 Meu Progresso</h1>
            <p>Acompanhe seu desempenho em todos os cursos matriculados.</p>
        </div>
        
        <div class="header-actions">
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                <span>Voltar ao Dashboard</span>
            </a>
        </div>
    </header>

    <!-- Estatísticas -->
    <div class="stats-cards">
        <div class="stat-card fade-in">
            <div class="stat-icon">
                <i class="fas fa-book-open"></i>
            </div>
            <div class="stat-content">
                <h3>Cursos Matriculados</h3>
                <div class="stat-value"><?php echo $total_cursos; ?></div>
                <p class="stat-description">Total de cursos inscritos</p>
            </div>
        </div>
        
        <div class="stat-card fade-in" style="animation-delay: 0.1s">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <h3>Cursos Concluídos</h3>
                <div class="stat-value"><?php echo $cursos_concluidos; ?></div>
                <p class="stat-description">Cursos finalizados com sucesso</p>
            </div>
        </div>
        
        <div class="stat-card fade-in" style="animation-delay: 0.2s">
            <div class="stat-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-content">
                <h3>Progresso Geral</h3>
                <div class="stat-value"><?php echo $percent_geral; ?>%</div>
                <p class="stat-description">Média de conclusão das aulas</p>
            </div>
        </div>
    </div>

    <!-- Barra de Progresso Geral -->
    <section class="progress-section fade-in" style="animation-delay: 0.3s">
        <div class="progress-header">
            <h2><i class="fas fa-tasks"></i> Progresso Geral</h2>
        </div>
        
        <div class="progress-overview">
            <div class="progress-label">
                <span>Seu progresso total nos cursos</span>
                <div class="progress-percentage"><?php echo $percent_geral; ?>%</div>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo $percent_geral; ?>%;"></div>
            </div>
            <div class="progress-stats">
                <span><?php echo $aulas_concluidas_geral; ?> de <?php echo $total_aulas_geral; ?> aulas concluídas</span>
                <span><?php echo $cursos_concluidos; ?> de <?php echo $total_cursos; ?> cursos concluídos</span>
            </div>
        </div>
    </section>

    <!-- Filtros -->
    <section class="filters-section fade-in" style="animation-delay: 0.4s">
        <div class="filters-header">
            <div class="filters-title">
                <i class="fas fa-filter"></i>
                <span>Filtrar Cursos</span>
            </div>
        </div>
        
        <div class="filter-options">
            <button class="filter-btn active" onclick="filterCourses('all')">Todos os Cursos</button>
            <button class="filter-btn" onclick="filterCourses('completed')">Concluídos</button>
            <button class="filter-btn" onclick="filterCourses('in-progress')">Em Andamento</button>
            <button class="filter-btn" onclick="filterCourses('not-started')">Não Iniciados</button>
        </div>
    </section>

    <!-- Lista de Cursos -->
    <section class="courses-section">
        <div class="section-header">
            <div class="section-title">
                <i class="fas fa-list-ol"></i>
                <h2>Progresso por Curso</h2>
            </div>
            <div class="section-actions">
                <div style="color: var(--text-secondary); font-size: 0.9rem;">
                    <?php echo count($cursos); ?> curso<?php echo count($cursos) != 1 ? 's' : ''; ?> encontrado<?php echo count($cursos) != 1 ? 's' : ''; ?>
                </div>
            </div>
        </div>
        
        <div class="courses-grid">
            <?php if(count($cursos) === 0): ?>
                <div class="empty-state fade-in">
                    <div class="empty-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3>Nenhum curso matriculado</h3>
                    <p>Você ainda não está matriculado em nenhum curso. Explore nossa plataforma e matricule-se nos cursos disponíveis.</p>
                    <a href="meus_cursos.php" class="back-btn" style="display: inline-flex; margin-top: 1rem;">
                        <i class="fas fa-search"></i>
                        <span>Explorar Cursos</span>
                    </a>
                </div>
            <?php else: ?>
                <?php foreach($cursos as $index => $curso): 
                    $percent = ($curso['total_aulas'] > 0) ? round(($curso['aulas_concluidas']/$curso['total_aulas'])*100) : 0;
                    $concluido = ($curso['concluido']==1);
                    
                    // Determinar status do curso
                    if($concluido) {
                        $status = 'completed';
                        $statusClass = 'badge-completed';
                        $statusText = 'Concluído';
                    } elseif($curso['aulas_concluidas'] > 0) {
                        $status = 'in-progress';
                        $statusClass = 'badge-in-progress';
                        $statusText = 'Em Andamento';
                    } else {
                        $status = 'not-started';
                        $statusClass = 'badge-not-started';
                        $statusText = 'Não Iniciado';
                    }
                    
                    // Formatar data de inscrição
                    $inscrito_em = new DateTime($curso['inscrito_em']);
                    $dataFormatada = $inscrito_em->format('d/m/Y');
                ?>
                <div class="course-card fade-in" 
                     style="animation-delay: <?php echo $index * 0.1; ?>s"
                     data-status="<?php echo $status; ?>">
                    <div class="course-header">
                        <div class="course-title">
                            <?php if(!empty($curso['imagem'])): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($curso['imagem']); ?>" class="course-image" alt="Imagem do curso">
                            <?php else: ?>
                                <div class="course-image" style="background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                                    <i class="fas fa-book"></i>
                                </div>
                            <?php endif; ?>
                            <div class="course-info">
                                <h3><?php echo htmlspecialchars($curso['curso_nome']); ?></h3>
                                <span class="course-badge <?php echo $statusClass; ?>">
                                    <?php echo $statusText; ?>
                                </span>
                                <div class="course-meta">
                                    <span><i class="far fa-calendar"></i> Inscrito em <?php echo $dataFormatada; ?></span>
                                    <span><i class="fas fa-video"></i> <?php echo $curso['total_aulas']; ?> aulas</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="course-content">
                        <div class="course-progress">
                            <div class="progress-info">
                                <div class="progress-percent"><?php echo $percent; ?>%</div>
                                <div class="progress-text">
                                    <?php echo $curso['aulas_concluidas']; ?> de <?php echo $curso['total_aulas']; ?> aulas concluídas
                                </div>
                            </div>
                            <div class="course-bar">
                                <div class="course-fill" style="width: <?php echo $percent; ?>%;"></div>
                            </div>
                        </div>
                        
                        <div class="course-actions">
                            <?php if(!$concluido): ?>
                                <a href="ver_curso.php?id=<?php echo $curso['curso_id']; ?>" class="action-btn primary">
                                    <i class="fas fa-play-circle"></i>
                                    <span>Continuar</span>
                                </a>
                            <?php else: ?>
                                <a href="certificados.php" class="action-btn success">
                                    <i class="fas fa-award"></i>
                                    <span>Ver Certificado</span>
                                </a>
                            <?php endif; ?>
                            <a href="ver_curso.php?id=<?php echo $curso['curso_id']; ?>" class="action-btn">
                                <i class="fas fa-eye"></i>
                                <span>Ver Detalhes</span>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
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
            entry.target.classList.add('show');
        }
    });
}, observerOptions);

document.querySelectorAll('.stat-card, .progress-section, .filters-section, .course-card').forEach(el => {
    observer.observe(el);
});

// Filtro de cursos
function filterCourses(filter) {
    const courseCards = document.querySelectorAll('.course-card');
    const filterButtons = document.querySelectorAll('.filter-btn');
    
    // Atualizar botões ativos
    filterButtons.forEach(btn => {
        btn.classList.remove('active');
        if (btn.textContent.includes(filter === 'all' ? 'Todos' : 
                                   filter === 'completed' ? 'Concluídos' : 
                                   filter === 'in-progress' ? 'Em Andamento' : 'Não Iniciados')) {
            btn.classList.add('active');
        }
    });
    
    // Filtrar cursos
    courseCards.forEach(card => {
        const status = card.getAttribute('data-status');
        
        if (filter === 'all' || status === filter) {
            card.style.display = 'block';
            setTimeout(() => {
                card.classList.add('show');
            }, 100);
        } else {
            card.classList.remove('show');
            setTimeout(() => {
                card.style.display = 'none';
            }, 300);
        }
    });
    
    // Atualizar contador
    const visibleCourses = Array.from(courseCards).filter(card => card.style.display !== 'none');
    const counter = document.querySelector('.section-actions div');
    if (counter && visibleCourses.length > 0) {
        counter.textContent = `${visibleCourses.length} curso${visibleCourses.length !== 1 ? 's' : ''} encontrado${visibleCourses.length !== 1 ? 's' : ''}`;
    } else if (counter && visibleCourses.length === 0) {
        counter.textContent = 'Nenhum curso encontrado';
    }
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

// Animar barras de progresso quando visíveis
function animateProgressBars() {
    const progressBars = document.querySelectorAll('.course-fill, .progress-fill');
    
    progressBars.forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0';
        
        setTimeout(() => {
            bar.style.width = width;
        }, 300);
    });
}

// Executar animação quando a página carregar
window.addEventListener('load', () => {
    setTimeout(animateProgressBars, 500);
});

// Ordenar cursos por progresso
function sortCourses(criteria) {
    const coursesContainer = document.querySelector('.courses-grid');
    const courseCards = Array.from(document.querySelectorAll('.course-card'));
    
    courseCards.sort((a, b) => {
        const aPercent = parseInt(a.querySelector('.progress-percent').textContent);
        const bPercent = parseInt(b.querySelector('.progress-percent').textContent);
        
        if (criteria === 'progress') {
            return bPercent - aPercent; // Maior progresso primeiro
        } else if (criteria === 'name') {
            const aName = a.querySelector('h3').textContent;
            const bName = b.querySelector('h3').textContent;
            return aName.localeCompare(bName); // Ordem alfabética
        } else if (criteria === 'date') {
            return 0; // Implementar ordenação por data se disponível
        }
        
        return 0;
    });
    
    // Reinserir cursos ordenados
    courseCards.forEach(card => coursesContainer.appendChild(card));
}

// Exportar progresso (funcionalidade futura)
function exportProgress() {
    alert('Funcionalidade de exportação será implementada em breve!\n\nVocê poderá baixar um relatório detalhado do seu progresso em PDF ou CSV.');
}
</script>
</body>
</html>