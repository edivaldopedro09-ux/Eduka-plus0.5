<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'professor') {
    header("Location: ../index.php");
    exit();
}

require_once("../config.php");

$professor_id = $_SESSION['usuario_id'];

// Buscar notificações para professor
$stmt = $conn->prepare("
    SELECT id, titulo, mensagem, data_envio, lida
    FROM notificacoes
    WHERE destinatario_tipo = 'todos'
       OR destinatario_tipo = 'professores'
       OR destinatario_tipo = 'professor'
       OR (destinatario_tipo = 'usuario' AND destinatario_id = ?)
    ORDER BY data_envio DESC
");
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$result = $stmt->get_result();
$notificacoes = $result->fetch_all(MYSQLI_ASSOC);

// Contar notificações não lidas
$nao_lidas = 0;
foreach ($notificacoes as $n) {
    if (!$n['lida']) $nao_lidas++;
}

// Marcar como lida se acessada via parâmetro
if (isset($_GET['marcar_lida'])) {
    $id = intval($_GET['marcar_lida']);
    $stmt = $conn->prepare("UPDATE notificacoes SET lida = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: notificacoes.php");
    exit();
}

// Marcar todas como lidas
if (isset($_POST['marcar_todas_lidas'])) {
    $stmt = $conn->prepare("UPDATE notificacoes SET lida = 1 WHERE destinatario_tipo IN ('todos', 'professores', 'professor') OR (destinatario_tipo = 'usuario' AND destinatario_id = ?)");
    $stmt->bind_param("i", $professor_id);
    $stmt->execute();
    header("Location: notificacoes.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificações — Eduka Plus</title>
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

    .action-btn {
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
        cursor: pointer;
        font-size: 0.95rem;
        font-family: 'Inter', sans-serif;
    }

    .action-btn:hover {
        background: var(--primary);
        color: white;
        transform: translateY(-2px);
        border-color: var(--primary);
    }

    .action-btn.danger {
        background: rgba(239, 68, 68, 0.1);
        color: var(--danger);
        border-color: rgba(239, 68, 68, 0.2);
    }

    .action-btn.danger:hover {
        background: var(--danger);
        color: white;
        border-color: var(--danger);
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

    .filter-select {
        padding: 0.75rem 1rem;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        font-family: 'Inter', sans-serif;
        font-size: 0.95rem;
        color: var(--text-primary);
        background: var(--surface-light);
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .filter-select:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    .filter-actions {
        display: flex;
        gap: 1rem;
        margin-top: 1rem;
        padding-top: 1.5rem;
        border-top: 1px solid var(--border-light);
    }

    /* Lista de Notificações */
    .notifications-section {
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

    .notifications-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    /* Card de Notificação */
    .notification-card {
        background: var(--surface-light);
        border-radius: var(--radius);
        border: 1px solid var(--border);
        padding: 1.5rem;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        opacity: 0;
        transform: translateY(20px);
    }

    .notification-card.show {
        opacity: 1;
        transform: translateY(0);
    }

    .notification-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-lg);
        border-color: rgba(37, 99, 235, 0.2);
    }

    .notification-card.nao-lida {
        border-left: 4px solid var(--primary);
        background: rgba(37, 99, 235, 0.03);
    }

    .notification-card.lida {
        opacity: 0.9;
    }

    .notification-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
    }

    .notification-title {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .notification-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }

    .notification-icon.info {
        background: rgba(14, 165, 233, 0.1);
        color: var(--info);
    }

    .notification-icon.warning {
        background: rgba(245, 158, 11, 0.1);
        color: var(--warning);
    }

    .notification-icon.success {
        background: rgba(16, 185, 129, 0.1);
        color: var(--success);
    }

    .notification-icon.danger {
        background: rgba(239, 68, 68, 0.1);
        color: var(--danger);
    }

    .notification-content h3 {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 0.25rem;
    }

    .notification-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        margin-left: 0.5rem;
    }

    .badge-lida {
        background: rgba(16, 185, 129, 0.1);
        color: var(--success);
    }

    .badge-nao-lida {
        background: rgba(37, 99, 235, 0.1);
        color: var(--primary);
    }

    .notification-time {
        font-size: 0.85rem;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .notification-body {
        margin-bottom: 1.25rem;
        color: var(--text-secondary);
        line-height: 1.6;
        padding-left: 3.25rem;
    }

    .notification-actions {
        display: flex;
        gap: 0.75rem;
        padding-left: 3.25rem;
    }

    .notification-btn {
        padding: 0.5rem 1rem;
        border-radius: 6px;
        border: 1px solid var(--border);
        background: var(--surface-light);
        color: var(--text-secondary);
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 500;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
    }

    .notification-btn:hover {
        background: rgba(37, 99, 235, 0.1);
        color: var(--primary);
        border-color: rgba(37, 99, 235, 0.3);
    }

    .notification-btn.mark-read {
        background: rgba(16, 185, 129, 0.1);
        border-color: rgba(16, 185, 129, 0.2);
        color: var(--success);
    }

    .notification-btn.mark-read:hover {
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
        
        .filters-grid {
            grid-template-columns: 1fr;
        }
        
        .filter-actions {
            flex-direction: column;
        }
        
        .notification-header {
            flex-direction: column;
            gap: 1rem;
        }
        
        .notification-body {
            padding-left: 0;
        }
        
        .notification-actions {
            padding-left: 0;
        }
    }

    @media (max-width: 480px) {
        .notification-actions {
            flex-direction: column;
        }
        
        .notification-btn {
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
            <a href="./alunos_matriculados.php" class="nav-item">
                <span class="nav-icon"><i class="fas fa-user-friends"></i></span>
                <span>Alunos</span>
            </a>
            <a href="./notificacoes.php" class="nav-item active">
                <span class="nav-icon"><i class="fas fa-bell"></i></span>
                <span>Notificações</span>
                <?php if($nao_lidas > 0): ?>
                <span style="margin-left: auto; background: var(--primary); color: white; width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 600;">
                    <?php echo $nao_lidas; ?>
                </span>
                <?php endif; ?>
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
            <h1>🔔 Notificações</h1>
            <p>Fique por dentro de todas as atualizações e mensagens importantes.</p>
        </div>
        
        <div class="header-actions">
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                <span>Voltar ao Dashboard</span>
            </a>
            <form method="POST" style="display: inline;">
                <button type="submit" name="marcar_todas_lidas" class="action-btn" onclick="return confirm('Marcar todas as notificações como lidas?')">
                    <i class="fas fa-check-double"></i>
                    <span>Marcar Todas como Lidas</span>
                </button>
            </form>
        </div>
    </header>

    <!-- Estatísticas -->
    <div class="stats-cards">
        <div class="stat-card fade-in">
            <div class="stat-icon">
                <i class="fas fa-bell"></i>
            </div>
            <div class="stat-content">
                <h3>Total de Notificações</h3>
                <div class="stat-value"><?php echo count($notificacoes); ?></div>
                <p class="stat-description">Mensagens recebidas</p>
            </div>
        </div>
        
        <div class="stat-card fade-in" style="animation-delay: 0.1s">
            <div class="stat-icon">
                <i class="fas fa-envelope-open"></i>
            </div>
            <div class="stat-content">
                <h3>Não Lidas</h3>
                <div class="stat-value"><?php echo $nao_lidas; ?></div>
                <p class="stat-description">Mensagens por ler</p>
            </div>
        </div>
        
        <div class="stat-card fade-in" style="animation-delay: 0.2s">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <h3>Última Atualização</h3>
                <div class="stat-value">
                    <?php if(!empty($notificacoes)): 
                        $ultima = new DateTime($notificacoes[0]['data_envio']);
                        echo $ultima->format('d/m');
                    else: 
                        echo '-';
                    endif; ?>
                </div>
                <p class="stat-description">
                    <?php if(!empty($notificacoes)): 
                        echo $ultima->format('H:i');
                    else: 
                        echo 'Nenhuma notificação';
                    endif; ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <section class="filters-section">
        <div class="filters-header">
            <div class="filters-title">
                <i class="fas fa-filter"></i>
                <span>Filtrar Notificações</span>
            </div>
        </div>
        
        <div class="filters-grid">
            <div class="filter-group">
                <label class="filter-label" for="filter-status">Status</label>
                <select id="filter-status" class="filter-select">
                    <option value="all">Todas as notificações</option>
                    <option value="unread">Apenas não lidas</option>
                    <option value="read">Apenas lidas</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label class="filter-label" for="filter-type">Tipo</label>
                <select id="filter-type" class="filter-select">
                    <option value="all">Todos os tipos</option>
                    <option value="system">Sistema</option>
                    <option value="course">Curso</option>
                    <option value="student">Aluno</option>
                    <option value="general">Geral</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label class="filter-label" for="filter-date">Período</label>
                <select id="filter-date" class="filter-select">
                    <option value="all">Todo o período</option>
                    <option value="today">Hoje</option>
                    <option value="week">Esta semana</option>
                    <option value="month">Este mês</option>
                </select>
            </div>
        </div>
        
        <div class="filter-actions">
            <button class="action-btn" onclick="applyFilters()">
                <i class="fas fa-check"></i>
                <span>Aplicar Filtros</span>
            </button>
            <button class="action-btn danger" onclick="clearFilters()">
                <i class="fas fa-times"></i>
                <span>Limpar Filtros</span>
            </button>
        </div>
    </section>

    <!-- Lista de Notificações -->
    <section class="notifications-section">
        <div class="section-header">
            <div class="section-title">
                <i class="fas fa-list"></i>
                <h2>Minhas Notificações</h2>
            </div>
            <div class="section-actions">
                <div style="color: var(--text-secondary); font-size: 0.9rem;">
                    <?php echo count($notificacoes); ?> notificaç<?php echo count($notificacoes) == 1 ? 'ão' : 'ões'; ?>
                </div>
            </div>
        </div>
        
        <div class="notifications-list">
            <?php if (empty($notificacoes)): ?>
                <div class="empty-state fade-in">
                    <div class="empty-icon">
                        <i class="fas fa-bell-slash"></i>
                    </div>
                    <h3>Nenhuma notificação</h3>
                    <p>Você não tem notificações no momento. Quando receber novas mensagens, elas aparecerão aqui.</p>
                </div>
            <?php else: ?>
                <?php foreach ($notificacoes as $index => $n): 
                    $isUnread = !$n['lida'];
                    $notificationType = $n['tipo'] ?? 'system';
                    
                    // Determinar ícone baseado no tipo
                    switch($notificationType) {
                        case 'course':
                            $icon = 'fas fa-graduation-cap';
                            $iconClass = 'info';
                            break;
                        case 'student':
                            $icon = 'fas fa-user-graduate';
                            $iconClass = 'success';
                            break;
                        case 'warning':
                            $icon = 'fas fa-exclamation-triangle';
                            $iconClass = 'warning';
                            break;
                        case 'danger':
                            $icon = 'fas fa-exclamation-circle';
                            $iconClass = 'danger';
                            break;
                        default:
                            $icon = 'fas fa-info-circle';
                            $iconClass = 'info';
                    }
                    
                    // Formatar data
                    $dataEnvio = new DateTime($n['data_envio']);
                    $now = new DateTime();
                    $interval = $now->diff($dataEnvio);
                    
                    if ($interval->days == 0) {
                        $timeText = 'Hoje às ' . $dataEnvio->format('H:i');
                    } elseif ($interval->days == 1) {
                        $timeText = 'Ontem às ' . $dataEnvio->format('H:i');
                    } elseif ($interval->days < 7) {
                        $timeText = $interval->days . ' dias atrás';
                    } else {
                        $timeText = $dataEnvio->format('d/m/Y H:i');
                    }
                ?>
                <div class="notification-card fade-in <?php echo $isUnread ? 'nao-lida' : 'lida'; ?>" 
                     style="animation-delay: <?php echo $index * 0.1; ?>s"
                     data-status="<?php echo $isUnread ? 'unread' : 'read'; ?>"
                     data-type="<?php echo $notificationType; ?>"
                     data-date="<?php echo $n['data_envio']; ?>">
                    <div class="notification-header">
                        <div class="notification-title">
                            <div class="notification-icon <?php echo $iconClass; ?>">
                                <i class="<?php echo $icon; ?>"></i>
                            </div>
                            <div class="notification-content">
                                <h3>
                                    <?= htmlspecialchars($n['titulo']) ?>
                                    <span class="notification-badge <?php echo $isUnread ? 'badge-nao-lida' : 'badge-lida'; ?>">
                                        <?php echo $isUnread ? 'Nova' : 'Lida'; ?>
                                    </span>
                                </h3>
                            </div>
                        </div>
                        <div class="notification-time">
                            <i class="far fa-clock"></i>
                            <span><?php echo $timeText; ?></span>
                        </div>
                    </div>
                    
                    <div class="notification-body">
                        <?= nl2br(htmlspecialchars($n['mensagem'])) ?>
                    </div>
                    
                    <div class="notification-actions">
                        <?php if($isUnread): ?>
                        <a href="?marcar_lida=<?php echo $n['id']; ?>" class="notification-btn mark-read">
                            <i class="fas fa-check"></i>
                            <span>Marcar como Lida</span>
                        </a>
                        <?php endif; ?>
                        <button class="notification-btn" onclick="showNotificationDetails(<?php echo $n['id']; ?>)">
                            <i class="fas fa-eye"></i>
                            <span>Ver Detalhes</span>
                        </button>
                        <button class="notification-btn" onclick="archiveNotification(<?php echo $n['id']; ?>)">
                            <i class="fas fa-archive"></i>
                            <span>Arquivar</span>
                        </button>
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

// Aplicar filtros
function applyFilters() {
    const statusFilter = document.getElementById('filter-status').value;
    const typeFilter = document.getElementById('filter-type').value;
    const dateFilter = document.getElementById('filter-date').value;
    
    const notificationCards = document.querySelectorAll('.notification-card');
    
    notificationCards.forEach(card => {
        let show = true;
        
        // Filtrar por status
        if (statusFilter !== 'all') {
            const status = card.getAttribute('data-status');
            if (status !== statusFilter) {
                show = false;
            }
        }
        
        // Filtrar por tipo
        if (typeFilter !== 'all') {
            const type = card.getAttribute('data-type');
            if (type !== typeFilter) {
                show = false;
            }
        }
        
        // Filtrar por data
        if (dateFilter !== 'all') {
            const dateStr = card.getAttribute('data-date');
            const notificationDate = new Date(dateStr);
            const today = new Date();
            
            switch(dateFilter) {
                case 'today':
                    if (notificationDate.toDateString() !== today.toDateString()) {
                        show = false;
                    }
                    break;
                case 'week':
                    const oneWeekAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
                    if (notificationDate < oneWeekAgo) {
                        show = false;
                    }
                    break;
                case 'month':
                    const oneMonthAgo = new Date(today.getFullYear(), today.getMonth() - 1, today.getDate());
                    if (notificationDate < oneMonthAgo) {
                        show = false;
                    }
                    break;
            }
        }
        
        // Aplicar filtro
        if (show) {
            card.style.display = 'block';
            card.classList.add('show');
        } else {
            card.style.display = 'none';
            card.classList.remove('show');
        }
    });
    
    // Mostrar mensagem se nenhuma notificação for encontrada
    const visibleCards = Array.from(notificationCards).filter(card => card.style.display !== 'none');
    const emptyState = document.querySelector('.empty-state');
    
    if (visibleCards.length === 0 && !emptyState) {
        // Criar mensagem de estado vazio
        const notificationsList = document.querySelector('.notifications-list');
        const emptyMessage = document.createElement('div');
        emptyMessage.className = 'empty-state fade-in';
        emptyMessage.innerHTML = `
            <div class="empty-icon">
                <i class="fas fa-search"></i>
            </div>
            <h3>Nenhuma notificação encontrada</h3>
            <p>Nenhuma notificação corresponde aos filtros aplicados.</p>
            <button class="action-btn" onclick="clearFilters()" style="margin-top: 1rem;">
                <i class="fas fa-times"></i>
                <span>Limpar Filtros</span>
            </button>
        `;
        notificationsList.appendChild(emptyMessage);
    } else if (emptyState && visibleCards.length > 0) {
        emptyState.remove();
    }
}

// Limpar filtros
function clearFilters() {
    document.getElementById('filter-status').value = 'all';
    document.getElementById('filter-type').value = 'all';
    document.getElementById('filter-date').value = 'all';
    
    const notificationCards = document.querySelectorAll('.notification-card');
    notificationCards.forEach(card => {
        card.style.display = 'block';
        card.classList.add('show');
    });
    
    // Remover mensagem de estado vazio se existir
    const emptyState = document.querySelector('.empty-state');
    if (emptyState && !emptyState.querySelector('.empty-icon.fa-bell-slash')) {
        emptyState.remove();
    }
}

// Mostrar detalhes da notificação
function showNotificationDetails(id) {
    // Em uma implementação real, isso buscaria mais detalhes via AJAX
    alert(`Detalhes da notificação #${id}\n\nEsta funcionalidade exibirá informações completas da notificação em um modal.`);
    
    // Exemplo de implementação:
    // fetch(`/api/notificacoes/${id}`)
    //   .then(response => response.json())
    //   .then(data => {
    //       // Abrir modal com os dados
    //       openNotificationModal(data);
    //   });
}

// Arquivar notificação
function archiveNotification(id) {
    if (confirm('Deseja arquivar esta notificação?\n\nNotificações arquivadas podem ser recuperadas posteriormente.')) {
        // Em uma implementação real, isso enviaria uma requisição AJAX
        // fetch(`/api/notificacoes/${id}/archive`, { method: 'POST' })
        //   .then(response => {
        //       if (response.ok) {
        //           // Remover ou ocultar a notificação
        //           document.querySelector(`.notification-card[data-id="${id}"]`).remove();
        //       }
        //   });
        
        // Simulação
        const card = document.querySelector(`.notification-card[data-id="${id}"]`);
        if (card) {
            card.style.opacity = '0.5';
            card.style.transform = 'translateX(-100%)';
            setTimeout(() => card.remove(), 300);
        }
    }
}

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

document.querySelectorAll('.stat-card, .notification-card').forEach(el => {
    observer.observe(el);
});

// Marcar notificação como lida com confirmação visual
document.querySelectorAll('.mark-read').forEach(btn => {
    btn.addEventListener('click', function(e) {
        const card = this.closest('.notification-card');
        if (card) {
            card.classList.remove('nao-lida');
            card.classList.add('lida');
            card.querySelector('.badge-nao-lida').textContent = 'Lida';
            card.querySelector('.badge-nao-lida').classList.replace('badge-nao-lida', 'badge-lida');
            
            // Atualizar contador de não lidas na sidebar
            const badge = document.querySelector('.sidebar-nav .nav-item.active span[style*="background"]');
            if (badge) {
                let count = parseInt(badge.textContent);
                if (count > 1) {
                    badge.textContent = count - 1;
                } else {
                    badge.remove();
                }
            }
        }
    });
});

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

// Notificações em tempo real (simulação)
function checkForNewNotifications() {
    // Em uma implementação real, isso usaria WebSockets ou polling
    console.log('Verificando novas notificações...');
}

// Verificar novas notificações a cada 60 segundos
setInterval(checkForNewNotifications, 60000);

// Adicionar IDs aos cards para funcionalidade de arquivamento
document.querySelectorAll('.notification-card').forEach((card, index) => {
    card.setAttribute('data-id', index + 1);
});
</script>
</body>
</html>