<?php
session_start();

// Segurança: só aluno pode acessar
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'aluno') {
    header("Location: ../index.php");
    exit();
}

require_once("../config.php");

$aluno_id = $_SESSION['usuario_id'];
$nome_aluno = $_SESSION['usuario_nome'] ?? "Aluno";

// Buscar cursos em que o aluno está inscrito
$sql = "SELECT c.id, c.titulo 
        FROM inscricoes i
        INNER JOIN cursos c ON i.curso_id = c.id
        WHERE i.aluno_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $aluno_id);
$stmt->execute();
$cursos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Buscar materiais dos cursos do aluno
$materiais = [];
if (count($cursos) > 0) {
    // cria string segura de ids (já vindo do banco)
    $ids = implode(",", array_map(fn($c) => intval($c['id']), $cursos));

    $sql_m = "SELECT m.*, c.titulo AS curso 
              FROM materiais m 
              INNER JOIN cursos c ON m.curso_id = c.id
              WHERE m.curso_id IN ($ids)
              ORDER BY m.id DESC"; // ordena pelo id (últimos primeiro)
    $materiais = $conn->query($sql_m)->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📂 Materiais — Eduka Plus Angola</title>
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
        display: flex;
        line-height: 1.6;
    }

    /* Sidebar */
    .sidebar {
        width: var(--sidebar-width);
        background: linear-gradient(180deg, var(--surface) 0%, #ffffff 100%);
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
        overflow: hidden;
    }

    .logo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
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

    .user-section {
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
    .menu-toggle {
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

    .menu-toggle:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
    }

    /* Conteúdo principal */
    .main-content {
        flex: 1;
        margin-left: var(--sidebar-width);
        padding: 2rem 2.5rem;
        background: var(--background);
        min-height: 100vh;
    }

    /* Header do conteúdo */
    .content-header {
        background: var(--surface);
        border-radius: var(--radius-lg);
        padding: 2rem;
        margin-bottom: 2rem;
        border: 1px solid var(--border);
        box-shadow: var(--shadow-sm);
    }

    .header-title {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 0.75rem;
    }

    .header-title h1 {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .header-title i {
        color: var(--primary);
        font-size: 2.2rem;
    }

    .header-subtitle {
        color: var(--text-secondary);
        font-size: 1.1rem;
        margin-bottom: 1.5rem;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-top: 2rem;
    }

    .stat-item {
        background: var(--surface-dark);
        padding: 1.5rem;
        border-radius: var(--radius);
        border: 1px solid var(--border);
        transition: all 0.3s ease;
    }

    .stat-item:hover {
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
        color: var(--primary);
        background: rgba(37, 99, 235, 0.1);
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

    /* Filtros */
    .filters-section {
        background: var(--surface);
        border-radius: var(--radius);
        padding: 1.5rem;
        margin-bottom: 2rem;
        border: 1px solid var(--border);
        box-shadow: var(--shadow-sm);
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

    /* Grid de Materiais */
    .materials-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 1.75rem;
    }

    /* Card de Material */
    .material-card {
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

    .material-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--shadow-lg);
        border-color: var(--primary-light);
    }

    .material-header {
        padding: 1.75rem;
        border-bottom: 1px solid var(--border-light);
        position: relative;
    }

    .material-badge {
        position: absolute;
        top: 1.5rem;
        right: 1.5rem;
        padding: 0.4rem 1rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .badge-pdf {
        background: rgba(239, 68, 68, 0.1);
        color: var(--danger);
        border: 1px solid rgba(239, 68, 68, 0.2);
    }

    .badge-doc {
        background: rgba(37, 99, 235, 0.1);
        color: var(--primary);
        border: 1px solid rgba(37, 99, 235, 0.2);
    }

    .badge-ppt {
        background: rgba(245, 158, 11, 0.1);
        color: var(--warning);
        border: 1px solid rgba(245, 158, 11, 0.2);
    }

    .badge-zip {
        background: rgba(139, 92, 246, 0.1);
        color: var(--info);
        border: 1px solid rgba(139, 92, 246, 0.2);
    }

    .badge-other {
        background: rgba(16, 185, 129, 0.1);
        color: var(--success);
        border: 1px solid rgba(16, 185, 129, 0.2);
    }

    .material-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 0.75rem;
        line-height: 1.4;
        padding-right: 80px;
    }

    .material-course {
        color: var(--primary);
        font-weight: 600;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }

    .material-body {
        padding: 1.75rem;
    }

    .material-description {
        color: var(--text-secondary);
        font-size: 0.95rem;
        margin-bottom: 1.5rem;
        line-height: 1.6;
    }

    .material-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--border-light);
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.85rem;
        color: var(--text-muted);
    }

    .meta-item i {
        font-size: 1rem;
    }

    .material-actions {
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

    .file-size {
        font-size: 0.85rem;
        color: var(--text-muted);
        margin-top: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* Estado vazio */
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
        .materials-grid {
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        }
    }

    @media (max-width: 1024px) {
        .main-content {
            margin-left: 0;
            padding: 1.5rem;
            padding-top: 5rem;
        }
        
        .sidebar {
            transform: translateX(-100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .sidebar.show {
            transform: translateX(0);
        }
        
        .menu-toggle {
            display: block;
        }
    }

    @media (max-width: 768px) {
        .content-header {
            padding: 1.5rem;
        }
        
        .header-title h1 {
            font-size: 1.75rem;
        }
        
        .materials-grid {
            grid-template-columns: 1fr;
            gap: 1.25rem;
        }
        
        .filters-grid {
            grid-template-columns: 1fr;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 480px) {
        .main-content {
            padding: 1.25rem;
            padding-top: 4.5rem;
        }
        
        .header-title {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .material-card {
            margin: 0.5rem;
        }
        
        .material-title {
            padding-right: 0;
        }
        
        .material-meta {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.75rem;
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
    .material-card:nth-child(1) { animation-delay: 0.1s; }
    .material-card:nth-child(2) { animation-delay: 0.2s; }
    .material-card:nth-child(3) { animation-delay: 0.3s; }
    .material-card:nth-child(4) { animation-delay: 0.4s; }
    .material-card:nth-child(5) { animation-delay: 0.5s; }
    .material-card:nth-child(6) { animation-delay: 0.6s; }
    </style>
</head>
<body>
    <!-- Botão menu mobile -->
    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <div class="logo">
                <img src="../imagens/logo.jpg" alt="Eduka Plus Logo">
            </div>
            <div>
                <div class="brand-text">Eduka Plus</div>
                <div class="brand-subtitle">Área do Aluno</div>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <a href="./dashboard.php" class="nav-item">
                <span class="nav-icon"><i class="fas fa-home"></i></span>
                <span>Dashboard</span>
            </a>
            <a href="./meus_cursos.php" class="nav-item">
                <span class="nav-icon"><i class="fas fa-graduation-cap"></i></span>
                <span>Meus Cursos</span>
            </a>
            <a href="./progresso.php" class="nav-item">
                <span class="nav-icon"><i class="fas fa-chart-line"></i></span>
                <span>Progresso</span>
            </a>
            <a href="./materiais.php" class="nav-item active">
                <span class="nav-icon"><i class="fas fa-folder-open"></i></span>
                <span>Materiais</span>
            </a>
            <a href="./certificados.php" class="nav-item">
                <span class="nav-icon"><i class="fas fa-certificate"></i></span>
                <span>Certificados</span>
            </a>
        </nav>
        
        <div class="user-section">
            <div class="user-info">
                <div class="user-avatar">
                    <?php
                    $nome = $_SESSION['usuario_nome'];
                    $iniciais = strtoupper(substr($nome, 0, 1) . substr($nome, strpos($nome, ' ') + 1, 1));
                    echo $iniciais;
                    ?>
                </div>
                <div class="user-details">
                    <h4><?php echo htmlspecialchars($nome_aluno); ?></h4>
                    <p>Aluno</p>
                </div>
            </div>
        </div>
    </aside>

    <!-- Conteúdo Principal -->
    <main class="main-content">
        <!-- Header -->
        <section class="content-header">
            <div class="header-title">
                <h1><i class="fas fa-folder-open"></i> Meus Materiais</h1>
            </div>
            <p class="header-subtitle">
                Acesse todos os materiais de apoio dos seus cursos em um só lugar
            </p>
            
            <!-- Estatísticas -->
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Cursos Inscritos</h3>
                        <div class="stat-value"><?php echo count($cursos); ?></div>
                    </div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Materiais Disponíveis</h3>
                        <div class="stat-value"><?php echo count($materiais); ?></div>
                    </div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-download"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Total de Downloads</h3>
                        <div class="stat-value">0</div>
                    </div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Último Material</h3>
                        <div class="stat-value">
                            <?php 
                            if (count($materiais) > 0) {
                                $ultimo = $materiais[0];
                                $data = isset($ultimo['data_criacao']) ? date('d/m', strtotime($ultimo['data_criacao'])) : 'Hoje';
                                echo $data;
                            } else {
                                echo '--';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Filtros -->
        <section class="filters-section">
            <div class="filters-grid">
                <div class="filter-group">
                    <label class="filter-label">Buscar Material</label>
                    <input type="text" class="search-input" placeholder="Digite o nome do material..." id="searchInput">
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Filtrar por Curso</label>
                    <select class="filter-select" id="courseFilter">
                        <option value="all">Todos os cursos</option>
                        <?php foreach($cursos as $curso): ?>
                            <option value="<?php echo htmlspecialchars($curso['titulo']); ?>">
                                <?php echo htmlspecialchars($curso['titulo']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Tipo de Arquivo</label>
                    <select class="filter-select" id="typeFilter">
                        <option value="all">Todos os tipos</option>
                        <option value="pdf">PDF</option>
                        <option value="doc">Word</option>
                        <option value="ppt">PowerPoint</option>
                        <option value="zip">ZIP/RAR</option>
                        <option value="other">Outros</option>
                    </select>
                </div>
            </div>
        </section>

        <!-- Grid de Materiais -->
        <div class="materials-grid" id="materialsContainer">
            <?php if(count($materiais) > 0): ?>
                <?php foreach($materiais as $m): 
                    // Determinar tipo de arquivo e badge
                    $arquivo = $m['arquivo'] ?? '';
                    $file_extension = strtolower(pathinfo($arquivo, PATHINFO_EXTENSION));
                    
                    $badge_class = 'badge-other';
                    $badge_icon = 'fas fa-file';
                    $badge_text = 'Arquivo';
                    
                    if (in_array($file_extension, ['pdf'])) {
                        $badge_class = 'badge-pdf';
                        $badge_icon = 'fas fa-file-pdf';
                        $badge_text = 'PDF';
                    } elseif (in_array($file_extension, ['doc', 'docx'])) {
                        $badge_class = 'badge-doc';
                        $badge_icon = 'fas fa-file-word';
                        $badge_text = 'Word';
                    } elseif (in_array($file_extension, ['ppt', 'pptx'])) {
                        $badge_class = 'badge-ppt';
                        $badge_icon = 'fas fa-file-powerpoint';
                        $badge_text = 'PowerPoint';
                    } elseif (in_array($file_extension, ['zip', 'rar', '7z'])) {
                        $badge_class = 'badge-zip';
                        $badge_icon = 'fas fa-file-archive';
                        $badge_text = 'Compactado';
                    }
                    
                    // Descrição
                    $descricao = '';
                    if (array_key_exists('descricao', $m) && $m['descricao'] !== null && $m['descricao'] !== '') {
                        $descricao = $m['descricao'];
                    } elseif (array_key_exists('desc', $m) && $m['desc'] !== null && $m['desc'] !== '') {
                        $descricao = $m['desc'];
                    }
                    
                    // Verificar arquivo
                    $arquivo_ok = false;
                    if (!empty($arquivo)) {
                        $filePath = __DIR__ . '/../uploads/' . $arquivo;
                        if (file_exists($filePath)) {
                            $arquivo_ok = true;
                            $fileUrl = '../uploads/' . rawurlencode($arquivo);
                            $fileSize = filesize($filePath);
                            $fileSizeFormatted = formatBytes($fileSize);
                        }
                    }
                ?>
                    <div class="material-card" 
                         data-course="<?php echo htmlspecialchars(strtolower($m['curso'] ?? '')); ?>"
                         data-type="<?php echo $file_extension; ?>"
                         data-name="<?php echo htmlspecialchars(strtolower($m['titulo'] ?? '')); ?>">
                        <div class="material-header">
                            <h3 class="material-title"><?php echo htmlspecialchars($m['titulo'] ?? 'Material'); ?></h3>
                            <div class="material-course">
                                <i class="fas fa-book"></i>
                                <?php echo htmlspecialchars($m['curso'] ?? 'Curso'); ?>
                            </div>
                            <div class="material-badge <?php echo $badge_class; ?>">
                                <i class="<?php echo $badge_icon; ?>"></i>
                                <span><?php echo $badge_text; ?></span>
                            </div>
                        </div>
                        
                        <div class="material-body">
                            <?php if(!empty($descricao)): ?>
                                <p class="material-description"><?php echo nl2br(htmlspecialchars($descricao)); ?></p>
                            <?php else: ?>
                                <p class="material-description" style="color: var(--text-muted); font-style: italic;">
                                    Nenhuma descrição fornecida.
                                </p>
                            <?php endif; ?>
                            
                            <div class="material-meta">
                                <div class="meta-item">
                                    <i class="fas fa-calendar"></i>
                                    <span>
                                        <?php 
                                        if (isset($m['data_criacao'])) {
                                            echo date('d/m/Y', strtotime($m['data_criacao']));
                                        } else {
                                            echo 'Data não informada';
                                        }
                                        ?>
                                    </span>
                                </div>
                                
                                <?php if($arquivo_ok): ?>
                                <div class="meta-item">
                                    <i class="fas fa-hdd"></i>
                                    <span><?php echo $fileSizeFormatted ?? '--'; ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="material-actions">
                                <?php if($arquivo_ok): ?>
                                    <a href="<?php echo $fileUrl; ?>" class="action-btn btn-primary" download>
                                        <i class="fas fa-download"></i>
                                        Baixar Material
                                    </a>
                                    <button class="action-btn btn-secondary" onclick="previewMaterial('<?php echo $fileUrl; ?>', '<?php echo $file_extension; ?>')">
                                        <i class="fas fa-eye"></i>
                                        Visualizar
                                    </button>
                                <?php else: ?>
                                    <div class="empty-state" style="grid-column: 1; padding: 1rem; border: 1px dashed var(--border);">
                                        <i class="fas fa-exclamation-circle" style="color: var(--danger);"></i>
                                        <p style="color: var(--text-muted); margin-top: 0.5rem;">
                                            Arquivo não disponível para download
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if($arquivo_ok): ?>
                            <div class="file-size">
                                <i class="fas fa-file"></i>
                                <span>Arquivo: <?php echo htmlspecialchars($arquivo); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-folder-open"></i>
                    </div>
                    <h3 class="empty-title">Nenhum material disponível</h3>
                    <p class="empty-description">
                        Você ainda não possui materiais disponíveis em seus cursos.
                        Acesse suas aulas para encontrar materiais de apoio.
                    </p>
                    <a href="./meus_cursos.php" class="action-btn btn-primary" style="max-width: 200px; margin: 0 auto;">
                        <i class="fas fa-graduation-cap"></i>
                        Ver Meus Cursos
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
    // Função para formatar bytes (usada no PHP)
    <?php
    function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    ?>

    // Menu mobile toggle
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');

    menuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('show');
    });

    // Fechar sidebar ao clicar fora (mobile)
    document.addEventListener('click', (e) => {
        if (window.innerWidth <= 1024) {
            if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        }
    });

    // Filtros e Busca
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const courseFilter = document.getElementById('courseFilter');
        const typeFilter = document.getElementById('typeFilter');
        const materials = document.querySelectorAll('.material-card');
        
        function filterMaterials() {
            const searchTerm = searchInput.value.toLowerCase();
            const courseValue = courseFilter.value;
            const typeValue = typeFilter.value;
            
            let filteredMaterials = Array.from(materials);
            
            // Filtrar por busca
            if (searchTerm) {
                filteredMaterials = filteredMaterials.filter(material => {
                    const materialName = material.getAttribute('data-name');
                    return materialName.includes(searchTerm);
                });
            }
            
            // Filtrar por curso
            if (courseValue !== 'all') {
                filteredMaterials = filteredMaterials.filter(material => {
                    const courseName = material.getAttribute('data-course');
                    return courseName === courseValue.toLowerCase();
                });
            }
            
            // Filtrar por tipo
            if (typeValue !== 'all') {
                filteredMaterials = filteredMaterials.filter(material => {
                    const fileType = material.getAttribute('data-type');
                    if (typeValue === 'pdf') return fileType === 'pdf';
                    if (typeValue === 'doc') return ['doc', 'docx'].includes(fileType);
                    if (typeValue === 'ppt') return ['ppt', 'pptx'].includes(fileType);
                    if (typeValue === 'zip') return ['zip', 'rar', '7z'].includes(fileType);
                    if (typeValue === 'other') {
                        return !['pdf', 'doc', 'docx', 'ppt', 'pptx', 'zip', 'rar', '7z'].includes(fileType);
                    }
                    return true;
                });
            }
            
            // Esconder todos os materiais
            materials.forEach(material => {
                material.style.display = 'none';
                material.style.order = '';
            });
            
            // Mostrar materiais filtrados
            filteredMaterials.forEach((material, index) => {
                material.style.display = 'block';
                material.style.order = index;
            });
            
            // Mostrar mensagem se não houver resultados
            const container = document.getElementById('materialsContainer');
            const emptyState = container.querySelector('.empty-state');
            const visibleMaterials = filteredMaterials.filter(m => m.style.display !== 'none');
            
            if (visibleMaterials.length === 0 && !emptyState) {
                const emptyDiv = document.createElement('div');
                emptyDiv.className = 'empty-state';
                emptyDiv.innerHTML = `
                    <div class="empty-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3 class="empty-title">Nenhum material encontrado</h3>
                    <p class="empty-description">
                        Tente ajustar os filtros ou termos de busca.
                    </p>
                    <button onclick="resetFilters()" class="action-btn btn-primary" style="max-width: 200px; margin: 0 auto;">
                        <i class="fas fa-redo"></i>
                        Limpar Filtros
                    </button>
                `;
                container.appendChild(emptyDiv);
            } else if (emptyState && visibleMaterials.length > 0) {
                emptyState.remove();
            }
        }
        
        // Event listeners
        searchInput.addEventListener('input', filterMaterials);
        courseFilter.addEventListener('change', filterMaterials);
        typeFilter.addEventListener('change', filterMaterials);
        
        // Função para resetar filtros
        window.resetFilters = function() {
            searchInput.value = '';
            courseFilter.value = 'all';
            typeFilter.value = 'all';
            filterMaterials();
        };
        
        // Inicializar filtros
        filterMaterials();
    });

    // Visualizar material
    window.previewMaterial = function(fileUrl, fileType) {
        if (fileType === 'pdf') {
            // Abrir PDF em nova aba
            window.open(fileUrl, '_blank');
        } else if (['doc', 'docx', 'ppt', 'pptx'].includes(fileType)) {
            // Para Office, mostrar alerta de download
            alert('Arquivos do Office precisam ser baixados para visualização.');
        } else {
            // Para outros tipos, tentar abrir
            window.open(fileUrl, '_blank');
        }
    };

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

    document.querySelectorAll('.material-card').forEach(card => {
        observer.observe(card);
    });

    // Efeito de hover nos cards
    document.querySelectorAll('.material-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px)';
        });
        
        card.addEventListener('mouseleave', function() {
            if (!document.getElementById('searchInput').value && 
                document.getElementById('courseFilter').value === 'all' &&
                document.getElementById('typeFilter').value === 'all') {
                this.style.transform = 'translateY(0)';
            }
        });
    });

    // Atualizar estatísticas em tempo real
    function updateStats() {
        const totalMaterials = document.querySelectorAll('.material-card').length;
        const visibleMaterials = document.querySelectorAll('.material-card[style*="display: block"]').length;
        
        // Atualizar elementos
        const statElements = document.querySelectorAll('.stat-value');
        if (statElements.length >= 2) {
            statElements[1].textContent = visibleMaterials;
        }
    }

    // Chamar updateStats quando os filtros mudarem
    document.getElementById('searchInput').addEventListener('input', updateStats);
    document.getElementById('courseFilter').addEventListener('change', updateStats);
    document.getElementById('typeFilter').addEventListener('change', updateStats);
    </script>
</body>
</html>