<?php
session_start();
require_once("../config.php");

// Segurança: só alunos
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'aluno') {
    header("Location: ../index.php");
    exit();
}

$aluno_id = $_SESSION['usuario_id'];

// Buscar todos os cursos com imagem
$sql = "SELECT c.id AS curso_id, c.titulo AS curso_nome, c.imagem, c.descricao 
        FROM cursos c 
        ORDER BY c.id DESC";
$result = $conn->query($sql);
$cursos = $result->fetch_all(MYSQLI_ASSOC);

// Função para verificar matrícula e progresso
function getCursoStatus($conn, $aluno_id, $curso_id) {
    $stmt = $conn->prepare("SELECT * FROM inscricoes WHERE aluno_id=? AND curso_id=?");
    $stmt->bind_param("ii", $aluno_id, $curso_id);
    $stmt->execute();
    $inscricao = $stmt->get_result()->fetch_assoc();

    $matriculado = $inscricao ? true : false;
    $concluido = false;
    $percent = 0;

    if ($matriculado) {
        // Total de aulas
        $stmt2 = $conn->prepare("SELECT COUNT(*) AS total FROM aulas WHERE curso_id=?");
        $stmt2->bind_param("i", $curso_id);
        $stmt2->execute();
        $total = $stmt2->get_result()->fetch_assoc()['total'] ?? 0;

        // Aulas concluídas
        $stmt3 = $conn->prepare("SELECT COUNT(*) AS concluido FROM progresso WHERE aluno_id=? AND aula_id IN (SELECT id FROM aulas WHERE curso_id=?) AND concluido=1");
        $stmt3->bind_param("ii", $aluno_id, $curso_id);
        $stmt3->execute();
        $concluido_count = $stmt3->get_result()->fetch_assoc()['concluido'] ?? 0;

        $percent = $total > 0 ? round(($concluido_count / $total) * 100) : 0;
        $concluido = ($percent == 100);

        // Atualizar automaticamente o status do curso
        if ($concluido) {
            $stmt4 = $conn->prepare("UPDATE inscricoes SET concluido=1 WHERE aluno_id=? AND curso_id=?");
            $stmt4->bind_param("ii", $aluno_id, $curso_id);
            $stmt4->execute();

            // Criar certificado se não existir
            $stmt5 = $conn->prepare("SELECT id FROM certificados WHERE aluno_id=? AND curso_id=?");
            $stmt5->bind_param("ii", $aluno_id, $curso_id);
            $stmt5->execute();
            $existe = $stmt5->get_result()->fetch_assoc();

            if (!$existe) {
                $codigo_autenticacao = uniqid("CERT_", true);
                $stmt6 = $conn->prepare("INSERT INTO certificados (aluno_id, curso_id, data_emissao, codigo_autenticacao) VALUES (?,?,NOW(),?)");
                $stmt6->bind_param("iis", $aluno_id, $curso_id, $codigo_autenticacao);
                $stmt6->execute();
            }
        }
    }

    return [
        'matriculado' => $matriculado,
        'percent' => $percent,
        'concluido' => $concluido
    ];
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Cursos — Eduka Plus Angola</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
    :root {
        --primary: #2563eb;
        --primary-dark: #1d4ed8;
        --primary-light: #3b82f6;
        --secondary: #0ea5e9;
        --success: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
        --info: #8b5cf6;
        --background: #f8fafc;
        --surface: #ffffff;
        --surface-dark: #f1f5f9;
        --text-primary: #1e293b;
        --text-secondary: #64748b;
        --text-muted: #94a3b8;
        --border: #e2e8f0;
        --border-light: #f1f5f9;
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        --radius: 12px;
        --radius-lg: 16px;
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
        min-height: 100vh;
        line-height: 1.6;
    }

    /* Header Principal */
    .main-header {
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        padding: 1.5rem 2rem;
        color: white;
        box-shadow: var(--shadow-md);
        position: sticky;
        top: 0;
        z-index: 100;
    }

    .header-content {
        max-width: 1400px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .logo-container {
        display: flex;
        align-items: center;
        gap: 1rem;
        text-decoration: none;
        color: white;
    }

    .logo {
        width: 45px;
        height: 45px;
        border-radius: var(--radius);
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(255, 255, 255, 0.2);
        overflow: hidden;
    }

    .logo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .brand-text {
        display: flex;
        flex-direction: column;
    }

    .brand-name {
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .brand-subtitle {
        font-size: 0.85rem;
        opacity: 0.9;
        font-weight: 400;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }

    .welcome-text {
        text-align: right;
    }

    .welcome-text h1 {
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 0.25rem;
    }

    .welcome-text p {
        font-size: 0.9rem;
        opacity: 0.9;
    }

    .user-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        border: 2px solid rgba(255, 255, 255, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 1.2rem;
        color: white;
        overflow: hidden;
    }

    .user-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .header-actions {
        display: flex;
        gap: 1rem;
        align-items: center;
    }

    .nav-btn {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: white;
        padding: 0.75rem 1.25rem;
        border-radius: var(--radius);
        text-decoration: none;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
    }

    .nav-btn:hover {
        background: rgba(255, 255, 255, 0.25);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .nav-btn.logout {
        background: rgba(239, 68, 68, 0.2);
        border-color: rgba(239, 68, 68, 0.3);
    }

    .nav-btn.logout:hover {
        background: rgba(239, 68, 68, 0.3);
    }

    /* Container Principal */
    .main-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem;
    }

    /* Filtros e Busca */
    .filters-section {
        background: var(--surface);
        border-radius: var(--radius-lg);
        padding: 1.5rem;
        margin-bottom: 2rem;
        border: 1px solid var(--border);
        box-shadow: var(--shadow-sm);
    }

    .filters-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .filters-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .filters-title i {
        color: var(--primary);
    }

    .filters-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .filter-label {
        font-weight: 500;
        color: var(--text-secondary);
        font-size: 0.9rem;
    }

    .filter-select, .search-input {
        padding: 0.75rem 1rem;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        background: var(--surface);
        color: var(--text-primary);
        font-family: 'Inter', sans-serif;
        font-size: 0.95rem;
        transition: all 0.3s ease;
    }

    .filter-select:focus, .search-input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    .search-input {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'%3E%3C/circle%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'%3E%3C/line%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: 1rem center;
        background-size: 1.2rem;
        padding-left: 3rem;
    }

    /* Grid de Cursos */
    .courses-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 1.75rem;
    }

    /* Card de Curso */
    .course-card {
        background: var(--surface);
        border-radius: var(--radius-lg);
        overflow: hidden;
        border: 1px solid var(--border);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        opacity: 0;
        transform: translateY(20px);
        animation: fadeInUp 0.6s ease forwards;
        box-shadow: var(--shadow-sm);
    }

    @keyframes fadeInUp {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .course-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--shadow-lg);
        border-color: var(--primary-light);
    }

    .course-image-container {
        position: relative;
        height: 200px;
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
        padding: 0.4rem 1rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: var(--shadow-sm);
    }

    .badge-not-enrolled {
        background: rgba(239, 68, 68, 0.1);
        color: var(--danger);
        border: 1px solid rgba(239, 68, 68, 0.2);
    }

    .badge-in-progress {
        background: rgba(245, 158, 11, 0.1);
        color: var(--warning);
        border: 1px solid rgba(245, 158, 11, 0.2);
    }

    .badge-completed {
        background: rgba(16, 185, 129, 0.1);
        color: var(--success);
        border: 1px solid rgba(16, 185, 129, 0.2);
    }

    .course-content {
        padding: 1.75rem;
    }

    .course-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 0.75rem;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .course-description {
        color: var(--text-secondary);
        font-size: 0.95rem;
        margin-bottom: 1.5rem;
        line-height: 1.6;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* Progresso */
    .progress-section {
        margin-bottom: 1.5rem;
    }

    .progress-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
    }

    .progress-label {
        font-size: 0.9rem;
        color: var(--text-secondary);
        font-weight: 500;
    }

    .progress-percent {
        font-size: 0.95rem;
        font-weight: 600;
        color: var(--primary);
    }

    .progress-bar {
        height: 8px;
        background: var(--border-light);
        border-radius: 4px;
        overflow: hidden;
    }

    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
        border-radius: 4px;
        transition: width 1.2s ease-out;
    }

    .progress-fill.completed {
        background: linear-gradient(90deg, var(--success) 0%, #34d399 100%);
    }

    /* Ações do Curso */
    .course-actions {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }

    .action-btn {
        padding: 0.875rem 1rem;
        border-radius: var(--radius);
        text-decoration: none;
        font-weight: 600;
        text-align: center;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        font-size: 0.95rem;
        border: none;
        cursor: pointer;
    }

    .btn-primary {
        background: var(--primary);
        color: white;
        border: 1px solid var(--primary);
    }

    .btn-primary:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(37, 99, 235, 0.3);
    }

    .btn-secondary {
        background: var(--surface);
        color: var(--text-primary);
        border: 1px solid var(--border);
    }

    .btn-secondary:hover {
        background: var(--surface-dark);
        border-color: var(--primary-light);
        transform: translateY(-2px);
    }

    .btn-success {
        background: var(--success);
        color: white;
        border: 1px solid var(--success);
    }

    .btn-success:hover {
        background: #059669;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(16, 185, 129, 0.3);
    }

    .btn-warning {
        background: var(--warning);
        color: white;
        border: 1px solid var(--warning);
    }

    .btn-warning:hover {
        background: #d97706;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(245, 158, 11, 0.3);
    }

    /* Mensagem quando não há cursos */
    .empty-state {
        grid-column: 1 / -1;
        text-align: center;
        padding: 4rem 2rem;
        background: var(--surface);
        border-radius: var(--radius-lg);
        border: 2px dashed var(--border);
    }

    .empty-icon {
        font-size: 4rem;
        color: var(--text-muted);
        margin-bottom: 1.5rem;
        opacity: 0.5;
    }

    .empty-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 1rem;
    }

    .empty-description {
        color: var(--text-secondary);
        max-width: 500px;
        margin: 0 auto 2rem;
    }

    /* Estatísticas */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2.5rem;
    }

    .stat-card {
        background: var(--surface);
        border-radius: var(--radius);
        padding: 1.5rem;
        border: 1px solid var(--border);
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        border-color: var(--primary);
        transform: translateY(-4px);
        box-shadow: var(--shadow-md);
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 1rem;
        background: rgba(37, 99, 235, 0.1);
        color: var(--primary);
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
        color: var(--text-primary);
        line-height: 1;
    }

    /* Footer */
    .main-footer {
        margin-top: 4rem;
        padding-top: 2rem;
        border-top: 1px solid var(--border);
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
    }

    .footer-links a:hover {
        color: var(--primary);
    }

    .copyright {
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }

    /* Responsividade */
    @media (max-width: 1200px) {
        .courses-grid {
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        }
    }

    @media (max-width: 768px) {
        .main-header {
            padding: 1rem;
        }

        .header-content {
            flex-direction: column;
            gap: 1rem;
            align-items: stretch;
        }

        .user-info {
            justify-content: space-between;
        }

        .welcome-text {
            text-align: left;
        }

        .header-actions {
            justify-content: center;
        }

        .main-container {
            padding: 1rem;
        }

        .courses-grid {
            grid-template-columns: 1fr;
            gap: 1.25rem;
        }

        .filters-grid {
            grid-template-columns: 1fr;
        }

        .filters-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }

        .stats-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 480px) {
        .brand-name {
            font-size: 1.25rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            font-size: 1rem;
        }

        .nav-btn {
            padding: 0.6rem 1rem;
            font-size: 0.9rem;
        }

        .filters-title {
            font-size: 1.25rem;
        }

        .course-card {
            margin: 0.5rem;
        }
    }

    /* Scrollbar personalizada */
    ::-webkit-scrollbar {
        width: 10px;
    }

    ::-webkit-scrollbar-track {
        background: var(--surface-dark);
    }

    ::-webkit-scrollbar-thumb {
        background: var(--primary);
        border-radius: 5px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: var(--primary-dark);
    }

    /* Animação para os cards */
    .course-card:nth-child(1) { animation-delay: 0.1s; }
    .course-card:nth-child(2) { animation-delay: 0.2s; }
    .course-card:nth-child(3) { animation-delay: 0.3s; }
    .course-card:nth-child(4) { animation-delay: 0.4s; }
    .course-card:nth-child(5) { animation-delay: 0.5s; }
    .course-card:nth-child(6) { animation-delay: 0.6s; }
    </style>
</head>
<body>
    <!-- Header Principal -->
    <header class="main-header">
        <div class="header-content">
            <a href="dashboard.php" class="logo-container">
                <div class="logo">
                    <img src="../imagens/logo.jpg" alt="Eduka Plus Logo">
                </div>
                <div class="brand-text">
                    <div class="brand-name">Eduka Plus Angola</div>
                    <div class="brand-subtitle">Meus Cursos</div>
                </div>
            </a>

            <div class="user-info">
                <div class="welcome-text">
                    <h1>Olá, <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>! 👋</h1>
                    <p>Continue sua jornada de aprendizado</p>
                </div>
                
                <div class="user-avatar">
                    <?php
                    $nome = $_SESSION['usuario_nome'];
                    $iniciais = strtoupper(substr($nome, 0, 1) . substr($nome, strpos($nome, ' ') + 1, 1));
                    echo $iniciais;
                    ?>
                </div>
            </div>

            <div class="header-actions">
                <a href="dashboard.php" class="nav-btn">
                    <i class="fas fa-home"></i>
                    Dashboard
                </a>
                <a href="../logout.php" class="nav-btn logout">
                    <i class="fas fa-sign-out-alt"></i>
                    Sair
                </a>
            </div>
        </div>
    </header>

    <!-- Container Principal -->
    <main class="main-container">
        <!-- Filtros e Estatísticas -->
        <section class="filters-section">
            <div class="filters-header">
                <h2 class="filters-title">
                    <i class="fas fa-graduation-cap"></i>
                    Todos os Cursos Disponíveis
                </h2>
                <div class="search-box">
                    <input type="text" class="search-input" placeholder="Buscar cursos..." id="searchInput">
                </div>
            </div>

            <div class="filters-grid">
                <div class="filter-group">
                    <label class="filter-label">Status</label>
                    <select class="filter-select" id="statusFilter">
                        <option value="all">Todos os cursos</option>
                        <option value="enrolled">Matriculados</option>
                        <option value="not-enrolled">Não matriculados</option>
                        <option value="in-progress">Em andamento</option>
                        <option value="completed">Concluídos</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Ordenar por</label>
                    <select class="filter-select" id="sortFilter">
                        <option value="recent">Mais recentes</option>
                        <option value="progress">Progresso</option>
                        <option value="name">Nome (A-Z)</option>
                    </select>
                </div>
            </div>
        </section>

        <!-- Estatísticas -->
        <?php
        // Calcular estatísticas
        $total_cursos = count($cursos);
        $matriculados = 0;
        $concluidos = 0;
        $progresso_total = 0;
        
        foreach($cursos as $curso) {
            $status = getCursoStatus($conn, $aluno_id, $curso['curso_id']);
            if($status['matriculado']) {
                $matriculados++;
                if($status['concluido']) $concluidos++;
                $progresso_total += $status['percent'];
            }
        }
        
        $progresso_medio = $matriculados > 0 ? round($progresso_total / $matriculados) : 0;
        ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-book-open"></i>
                </div>
                <div class="stat-content">
                    <h3>Cursos Disponíveis</h3>
                    <div class="stat-value"><?php echo $total_cursos; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-content">
                    <h3>Matriculados</h3>
                    <div class="stat-value"><?php echo $matriculados; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="stat-content">
                    <h3>Concluídos</h3>
                    <div class="stat-value"><?php echo $concluidos; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-content">
                    <h3>Progresso Médio</h3>
                    <div class="stat-value"><?php echo $progresso_medio; ?>%</div>
                </div>
            </div>
        </div>

        <!-- Grid de Cursos -->
        <div class="courses-grid" id="coursesContainer">
            <?php if(count($cursos) > 0): ?>
                <?php foreach($cursos as $curso): 
                    $status = getCursoStatus($conn, $aluno_id, $curso['curso_id']); 
                    $img_path = !empty($curso['imagem']) && file_exists('../uploads/'.$curso['imagem']) 
                                ? '../uploads/'.$curso['imagem'] 
                                : 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80';
                    
                    $badge_class = '';
                    $badge_text = '';
                    $badge_icon = '';
                    
                    if($status['matriculado']) {
                        if($status['concluido']) {
                            $badge_class = 'badge-completed';
                            $badge_text = 'Concluído';
                            $badge_icon = 'fas fa-check-circle';
                        } else {
                            $badge_class = 'badge-in-progress';
                            $badge_text = 'Em andamento';
                            $badge_icon = 'fas fa-spinner';
                        }
                    } else {
                        $badge_class = 'badge-not-enrolled';
                        $badge_text = 'Não matriculado';
                        $badge_icon = 'fas fa-clock';
                    }
                ?>
                    <div class="course-card" 
                         data-status="<?php echo $status['matriculado'] ? ($status['concluido'] ? 'completed' : 'in-progress') : 'not-enrolled'; ?>"
                         data-name="<?php echo htmlspecialchars(strtolower($curso['curso_nome'])); ?>">
                        <div class="course-image-container">
                            <img src="<?php echo $img_path; ?>" alt="Imagem do curso" class="course-image">
                            <div class="course-badge <?php echo $badge_class; ?>">
                                <i class="<?php echo $badge_icon; ?>"></i>
                                <span><?php echo $badge_text; ?></span>
                            </div>
                        </div>
                        
                        <div class="course-content">
                            <h3 class="course-title"><?php echo htmlspecialchars($curso['curso_nome']); ?></h3>
                            
                            <?php if(!empty($curso['descricao'])): ?>
                                <p class="course-description"><?php echo htmlspecialchars($curso['descricao']); ?></p>
                            <?php endif; ?>
                            
                            <?php if($status['matriculado']): ?>
                                <div class="progress-section">
                                    <div class="progress-header">
                                        <span class="progress-label">Seu progresso</span>
                                        <span class="progress-percent"><?php echo $status['percent']; ?>%</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill <?php echo $status['concluido'] ? 'completed' : ''; ?>" 
                                             style="width: <?php echo $status['percent']; ?>%"></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="course-actions">
                                <?php if($status['matriculado']): ?>
                                    <?php if($status['concluido']): ?>
                                        <a href="certificados.php?curso_id=<?php echo $curso['curso_id']; ?>" 
                                           class="action-btn btn-success">
                                            <i class="fas fa-certificate"></i>
                                            Ver Certificado
                                        </a>
                                        <a href="ver_aulas.php?curso_id=<?php echo $curso['curso_id']; ?>&review=true" 
                                           class="action-btn btn-secondary">
                                            <i class="fas fa-redo"></i>
                                            Revisar Aulas
                                        </a>
                                    <?php else: ?>
                                        <a href="ver_aulas.php?curso_id=<?php echo $curso['curso_id']; ?>" 
                                           class="action-btn btn-primary">
                                            <i class="fas fa-play-circle"></i>
                                            Continuar Estudando
                                        </a>
                                        <a href="curso_detalhes.php?curso_id=<?php echo $curso['curso_id']; ?>" 
                                           class="action-btn btn-secondary">
                                            <i class="fas fa-info-circle"></i>
                                            Detalhes do Curso
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="matricular.php?curso_id=<?php echo $curso['curso_id']; ?>" 
                                       class="action-btn btn-primary">
                                        <i class="fas fa-user-plus"></i>
                                        Matricular-se Agora
                                    </a>
                                    <a href="curso_detalhes.php?curso_id=<?php echo $curso['curso_id']; ?>" 
                                       class="action-btn btn-secondary">
                                        <i class="fas fa-eye"></i>
                                        Ver Detalhes
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3 class="empty-title">Nenhum curso disponível no momento</h3>
                    <p class="empty-description">
                        Novos cursos serão adicionados em breve. Fique atento às atualizações!
                    </p>
                    <a href="dashboard.php" class="action-btn btn-primary" style="max-width: 200px; margin: 0 auto;">
                        <i class="fas fa-arrow-left"></i>
                        Voltar ao Dashboard
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="footer-links">
            <a href="../ajuda.php">Ajuda</a>
            <a href="../suporte.php">Suporte</a>
            <a href="../termos.php">Termos de Uso</a>
            <a href="../privacidade.php">Privacidade</a>
        </div>
        <div class="copyright">
            © <?php echo date("Y"); ?> Eduka Plus Angola — Transformando a educação angolana.
        </div>
    </footer>

    <script>
    // Filtros e Busca
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const sortFilter = document.getElementById('sortFilter');
        const courses = document.querySelectorAll('.course-card');
        
        function filterAndSortCourses() {
            const searchTerm = searchInput.value.toLowerCase();
            const statusValue = statusFilter.value;
            const sortValue = sortFilter.value;
            
            let filteredCourses = Array.from(courses);
            
            // Filtrar por busca
            if (searchTerm) {
                filteredCourses = filteredCourses.filter(course => {
                    const courseName = course.getAttribute('data-name');
                    return courseName.includes(searchTerm);
                });
            }
            
            // Filtrar por status
            if (statusValue !== 'all') {
                filteredCourses = filteredCourses.filter(course => {
                    return course.getAttribute('data-status') === statusValue;
                });
            }
            
            // Ordenar
            if (sortValue === 'name') {
                filteredCourses.sort((a, b) => {
                    const nameA = a.querySelector('.course-title').textContent.toLowerCase();
                    const nameB = b.querySelector('.course-title').textContent.toLowerCase();
                    return nameA.localeCompare(nameB);
                });
            } else if (sortValue === 'progress') {
                filteredCourses.sort((a, b) => {
                    const progressA = getProgressValue(a);
                    const progressB = getProgressValue(b);
                    return progressB - progressA;
                });
            } else if (sortValue === 'recent') {
                // Mantém a ordem original (mais recentes primeiro)
                filteredCourses.sort((a, b) => {
                    return Array.from(courses).indexOf(a) - Array.from(courses).indexOf(b);
                });
            }
            
            // Esconder todos os cursos
            courses.forEach(course => {
                course.style.display = 'none';
                course.style.order = '';
            });
            
            // Mostrar cursos filtrados e ordenados
            filteredCourses.forEach((course, index) => {
                course.style.display = 'block';
                course.style.order = index;
            });
            
            // Mostrar mensagem se não houver resultados
            const container = document.getElementById('coursesContainer');
            const emptyState = container.querySelector('.empty-state');
            const visibleCourses = filteredCourses.filter(c => c.style.display !== 'none');
            
            if (visibleCourses.length === 0 && !emptyState) {
                const emptyDiv = document.createElement('div');
                emptyDiv.className = 'empty-state';
                emptyDiv.innerHTML = `
                    <div class="empty-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3 class="empty-title">Nenhum curso encontrado</h3>
                    <p class="empty-description">
                        Tente ajustar os filtros ou termos de busca.
                    </p>
                    <button onclick="resetFilters()" class="action-btn btn-primary" style="max-width: 200px; margin: 0 auto;">
                        <i class="fas fa-redo"></i>
                        Limpar Filtros
                    </button>
                `;
                container.appendChild(emptyDiv);
            } else if (emptyState && visibleCourses.length > 0) {
                emptyState.remove();
            }
        }
        
        function getProgressValue(course) {
            const progressElem = course.querySelector('.progress-percent');
            if (progressElem) {
                return parseInt(progressElem.textContent) || 0;
            }
            return 0;
        }
        
        // Event listeners
        searchInput.addEventListener('input', filterAndSortCourses);
        statusFilter.addEventListener('change', filterAndSortCourses);
        sortFilter.addEventListener('change', filterAndSortCourses);
        
        // Função para resetar filtros
        window.resetFilters = function() {
            searchInput.value = '';
            statusFilter.value = 'all';
            sortFilter.value = 'recent';
            filterAndSortCourses();
        };
        
        // Animar barras de progresso
        setTimeout(() => {
            document.querySelectorAll('.progress-fill').forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0';
                setTimeout(() => {
                    bar.style.width = width;
                }, 100);
            });
        }, 500);
        
        // Inicializar filtros
        filterAndSortCourses();
    });

    // Animar cards ao aparecer na tela
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    document.querySelectorAll('.course-card').forEach(card => {
        observer.observe(card);
    });

    // Efeito de hover nos cards
    document.querySelectorAll('.course-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px)';
        });
        
        card.addEventListener('mouseleave', function() {
            if (!document.getElementById('searchInput').value && 
                document.getElementById('statusFilter').value === 'all') {
                this.style.transform = 'translateY(0)';
            }
        });
    });

    // Atualizar estatísticas em tempo real
    function updateStats() {
        const totalCourses = document.querySelectorAll('.course-card').length;
        const enrolledCourses = document.querySelectorAll('.course-card[data-status="in-progress"], .course-card[data-status="completed"]').length;
        const completedCourses = document.querySelectorAll('.course-card[data-status="completed"]').length;
        
        // Calcular progresso médio
        let totalProgress = 0;
        let enrolledCount = 0;
        
        document.querySelectorAll('.course-card[data-status="in-progress"], .course-card[data-status="completed"]').forEach(course => {
            const progressElem = course.querySelector('.progress-percent');
            if (progressElem) {
                totalProgress += parseInt(progressElem.textContent) || 0;
                enrolledCount++;
            }
        });
        
        const avgProgress = enrolledCount > 0 ? Math.round(totalProgress / enrolledCount) : 0;
        
        // Atualizar elementos (se existirem)
        const statElements = document.querySelectorAll('.stat-value');
        if (statElements.length >= 4) {
            statElements[0].textContent = totalCourses;
            statElements[1].textContent = enrolledCourses;
            statElements[2].textContent = completedCourses;
            statElements[3].textContent = avgProgress + '%';
        }
    }

    // Chamar updateStats quando os filtros mudarem
    document.getElementById('searchInput').addEventListener('input', updateStats);
    document.getElementById('statusFilter').addEventListener('change', updateStats);
    </script>
</body>
</html>