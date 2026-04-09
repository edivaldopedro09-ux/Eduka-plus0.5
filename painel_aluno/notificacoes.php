<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'aluno') {
    header("Location: ../index.php");
    exit();
}

require_once("../config.php");

$aluno_id = $_SESSION['usuario_id'];
$aluno_nome = $_SESSION['usuario_nome'] ?? "Aluno";

// Buscar notificações para aluno
$stmt = $conn->prepare("
    SELECT id, titulo, mensagem, data_envio, lida
    FROM notificacoes
    WHERE destinatario_tipo = 'todos'
       OR destinatario_tipo = 'alunos'
       OR destinatario_tipo = 'aluno'
       OR (destinatario_tipo = 'usuario' AND destinatario_id = ?)
    ORDER BY data_envio DESC
");
$stmt->bind_param("i", $aluno_id);
$stmt->execute();
$result = $stmt->get_result();
$notificacoes = $result->fetch_all(MYSQLI_ASSOC);

// Marcar como lidas
if (!empty($notificacoes)) {
    $ids = implode(',', array_column($notificacoes, 'id'));
    $conn->query("UPDATE notificacoes SET lida = 1 WHERE id IN ($ids)");
}

// Contar notificações não lidas
$stmt_unread = $conn->prepare("
    SELECT COUNT(*) as nao_lidas 
    FROM notificacoes 
    WHERE (destinatario_tipo = 'todos' 
        OR destinatario_tipo = 'alunos' 
        OR destinatario_tipo = 'aluno' 
        OR (destinatario_tipo = 'usuario' AND destinatario_id = ?))
        AND lida = 0
");
$stmt_unread->bind_param("i", $aluno_id);
$stmt_unread->execute();
$unread_result = $stmt_unread->get_result();
$nao_lidas = $unread_result->fetch_assoc()['nao_lidas'] ?? 0;
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📢 Notificações — Eduka Plus Angola</title>
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

    /* Header */
    .main-header {
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        padding: 1.5rem 2rem;
        color: white;
        box-shadow: var(--shadow-md);
    }

    .header-content {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .header-left {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }

    .back-btn {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: white;
        text-decoration: none;
        padding: 0.75rem 1.25rem;
        border-radius: var(--radius);
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
    }

    .back-btn:hover {
        background: rgba(255, 255, 255, 0.25);
        transform: translateX(-4px);
    }

    .header-title {
        display: flex;
        flex-direction: column;
    }

    .header-title h1 {
        font-size: 1.75rem;
        font-weight: 700;
        color: white;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .header-subtitle {
        font-size: 0.95rem;
        opacity: 0.9;
        margin-top: 0.25rem;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .user-avatar {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        border: 2px solid rgba(255, 255, 255, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 1rem;
        color: white;
        overflow: hidden;
    }

    .user-name {
        font-weight: 500;
        font-size: 0.95rem;
    }

    /* Container Principal */
    .main-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem;
    }

    /* Estatísticas */
    .stats-section {
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
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .stat-card:hover {
        border-color: var(--primary);
        transform: translateY(-4px);
        box-shadow: var(--shadow-md);
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: var(--radius);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        color: white;
        flex-shrink: 0;
    }

    .stat-total .stat-icon {
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    }

    .stat-unread .stat-icon {
        background: linear-gradient(135deg, var(--warning) 0%, #f59e0b 100%);
    }

    .stat-today .stat-icon {
        background: linear-gradient(135deg, var(--success) 0%, #10b981 100%);
    }

    .stat-archived .stat-icon {
        background: linear-gradient(135deg, var(--info) 0%, #8b5cf6 100%);
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

    /* Ações */
    .actions-section {
        background: var(--surface);
        border-radius: var(--radius);
        padding: 1.5rem;
        margin-bottom: 2rem;
        border: 1px solid var(--border);
        box-shadow: var(--shadow-sm);
    }

    .actions-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .actions-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .actions-title i {
        color: var(--primary);
    }

    .action-buttons {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .action-btn {
        padding: 0.75rem 1.5rem;
        border-radius: var(--radius);
        border: none;
        font-weight: 500;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-family: 'Inter', sans-serif;
    }

    .action-btn.primary {
        background: var(--primary);
        color: white;
        border: 1px solid var(--primary);
    }

    .action-btn.primary:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(37, 99, 235, 0.3);
    }

    .action-btn.secondary {
        background: var(--surface);
        color: var(--text-primary);
        border: 1px solid var(--border);
    }

    .action-btn.secondary:hover {
        background: var(--surface-dark);
        border-color: var(--primary-light);
        transform: translateY(-2px);
    }

    .action-btn.danger {
        background: var(--surface);
        color: var(--danger);
        border: 1px solid var(--border);
    }

    .action-btn.danger:hover {
        background: var(--danger);
        color: white;
        border-color: var(--danger);
        transform: translateY(-2px);
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

    .filter-select {
        padding: 0.75rem 1rem;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        background: var(--surface);
        color: var(--text-primary);
        font-family: 'Inter', sans-serif;
        font-size: 0.95rem;
        transition: all 0.3s ease;
    }

    .filter-select:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    /* Lista de Notificações */
    .notifications-container {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    /* Notificação */
    .notification {
        background: var(--surface);
        border-radius: var(--radius);
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

    .notification:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-lg);
        border-color: var(--primary-light);
    }

    .notification.unread {
        border-left: 4px solid var(--primary);
    }

    .notification.read {
        border-left: 4px solid var(--success);
    }

    .notification-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--border-light);
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
    }

    .notification-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .notification-title i {
        color: var(--primary);
        font-size: 1.1rem;
    }

    .notification-badge {
        padding: 0.4rem 1rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-shrink: 0;
    }

    .badge-unread {
        background: rgba(37, 99, 235, 0.1);
        color: var(--primary);
        border: 1px solid rgba(37, 99, 235, 0.2);
    }

    .badge-read {
        background: rgba(16, 185, 129, 0.1);
        color: var(--success);
        border: 1px solid rgba(16, 185, 129, 0.2);
    }

    .badge-today {
        background: rgba(245, 158, 11, 0.1);
        color: var(--warning);
        border: 1px solid rgba(245, 158, 11, 0.2);
    }

    .notification-body {
        padding: 1.5rem;
    }

    .notification-message {
        color: var(--text-secondary);
        font-size: 0.95rem;
        line-height: 1.7;
        margin-bottom: 1.5rem;
    }

    .notification-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 1rem;
        border-top: 1px solid var(--border-light);
    }

    .notification-time {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.85rem;
        color: var(--text-muted);
    }

    .notification-time i {
        font-size: 0.9rem;
    }

    .notification-actions {
        display: flex;
        gap: 0.75rem;
    }

    .notification-btn {
        padding: 0.5rem 1rem;
        border-radius: 6px;
        border: none;
        font-size: 0.85rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-family: 'Inter', sans-serif;
    }

    .notification-btn.mark-read {
        background: rgba(37, 99, 235, 0.1);
        color: var(--primary);
        border: 1px solid rgba(37, 99, 235, 0.2);
    }

    .notification-btn.mark-read:hover {
        background: var(--primary);
        color: white;
    }

    .notification-btn.delete {
        background: rgba(239, 68, 68, 0.1);
        color: var(--danger);
        border: 1px solid rgba(239, 68, 68, 0.2);
    }

    .notification-btn.delete:hover {
        background: var(--danger);
        color: white;
    }

    /* Estado vazio */
    .empty-state {
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
    @media (max-width: 768px) {
        .main-header {
            padding: 1rem;
        }

        .header-content {
            flex-direction: column;
            gap: 1rem;
            align-items: stretch;
        }

        .header-left {
            justify-content: space-between;
        }

        .user-info {
            justify-content: flex-end;
        }

        .main-container {
            padding: 1rem;
        }

        .stats-section {
            grid-template-columns: repeat(2, 1fr);
        }

        .actions-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }

        .action-buttons {
            width: 100%;
            justify-content: center;
        }

        .filters-grid {
            grid-template-columns: 1fr;
        }

        .notification-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .notification-footer {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }

        .notification-actions {
            width: 100%;
            justify-content: flex-start;
        }
    }

    @media (max-width: 480px) {
        .stats-section {
            grid-template-columns: 1fr;
        }

        .stat-card {
            flex-direction: column;
            text-align: center;
        }

        .header-title h1 {
            font-size: 1.5rem;
        }

        .notification {
            margin: 0.5rem;
        }
    }

    /* Scrollbar personalizada */
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

    /* Animação para as notificações */
    .notification:nth-child(1) { animation-delay: 0.1s; }
    .notification:nth-child(2) { animation-delay: 0.2s; }
    .notification:nth-child(3) { animation-delay: 0.3s; }
    .notification:nth-child(4) { animation-delay: 0.4s; }
    .notification:nth-child(5) { animation-delay: 0.5s; }
    .notification:nth-child(6) { animation-delay: 0.6s; }

    /* Indicador de nova notificação */
    .new-notification-indicator {
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        color: white;
        padding: 1rem 1.5rem;
        border-radius: var(--radius);
        box-shadow: var(--shadow-lg);
        display: none;
        align-items: center;
        gap: 0.75rem;
        z-index: 1000;
        animation: slideInUp 0.3s ease;
    }

    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Modal de detalhes */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        align-items: center;
        justify-content: center;
        z-index: 2000;
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .modal {
        background: var(--surface);
        border-radius: var(--radius-lg);
        padding: 2rem;
        max-width: 600px;
        width: 90%;
        max-height: 80vh;
        overflow-y: auto;
        animation: slideIn 0.4s ease;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="header-content">
            <div class="header-left">
                <a href="dashboard.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Voltar ao Dashboard
                </a>
                <div class="header-title">
                    <h1><i class="fas fa-bell"></i> Minhas Notificações</h1>
                    <div class="header-subtitle">
                        Acompanhe todas as suas notificações em um só lugar
                    </div>
                </div>
            </div>

            <div class="user-info">
                <div class="user-avatar">
                    <?php
                    $nome = $_SESSION['usuario_nome'];
                    $iniciais = strtoupper(substr($nome, 0, 1) . substr($nome, strpos($nome, ' ') + 1, 1));
                    echo $iniciais;
                    ?>
                </div>
                <div class="user-name"><?php echo htmlspecialchars($aluno_nome); ?></div>
            </div>
        </div>
    </header>

    <!-- Container Principal -->
    <main class="main-container">
        <!-- Estatísticas -->
        <div class="stats-section">
            <div class="stat-card stat-total">
                <div class="stat-icon">
                    <i class="fas fa-bell"></i>
                </div>
                <div class="stat-content">
                    <h3>Total de Notificações</h3>
                    <div class="stat-value"><?php echo count($notificacoes); ?></div>
                </div>
            </div>

            <div class="stat-card stat-unread">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="stat-content">
                    <h3>Não Lidas</h3>
                    <div class="stat-value"><?php echo $nao_lidas; ?></div>
                </div>
            </div>

            <?php
            // Calcular notificações de hoje
            $hoje = date('Y-m-d');
            $notificacoes_hoje = 0;
            foreach ($notificacoes as $n) {
                if (date('Y-m-d', strtotime($n['data_envio'])) == $hoje) {
                    $notificacoes_hoje++;
                }
            }
            ?>

            <div class="stat-card stat-today">
                <div class="stat-icon">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-content">
                    <h3>Hoje</h3>
                    <div class="stat-value"><?php echo $notificacoes_hoje; ?></div>
                </div>
            </div>

            <div class="stat-card stat-archived">
                <div class="stat-icon">
                    <i class="fas fa-archive"></i>
                </div>
                <div class="stat-content">
                    <h3>Arquivadas</h3>
                    <div class="stat-value">0</div>
                </div>
            </div>
        </div>

        <!-- Ações -->
        <section class="actions-section">
            <div class="actions-header">
                <h2 class="actions-title">
                    <i class="fas fa-cog"></i>
                    Gerenciar Notificações
                </h2>
                <div class="action-buttons">
                    <button class="action-btn primary" onclick="markAllAsRead()">
                        <i class="fas fa-check-double"></i>
                        Marcar Todas como Lidas
                    </button>
                    <button class="action-btn secondary" onclick="refreshNotifications()">
                        <i class="fas fa-sync-alt"></i>
                        Atualizar
                    </button>
                    <button class="action-btn danger" onclick="deleteAllRead()">
                        <i class="fas fa-trash-alt"></i>
                        Limpar Lidas
                    </button>
                </div>
            </div>
        </section>

        <!-- Filtros -->
        <section class="filters-section">
            <div class="filters-grid">
                <div class="filter-group">
                    <label class="filter-label">Status</label>
                    <select class="filter-select" id="statusFilter">
                        <option value="all">Todas as notificações</option>
                        <option value="unread">Apenas não lidas</option>
                        <option value="read">Apenas lidas</option>
                        <option value="today">Apenas de hoje</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Ordenar por</label>
                    <select class="filter-select" id="sortFilter">
                        <option value="recent">Mais recentes primeiro</option>
                        <option value="oldest">Mais antigas primeiro</option>
                        <option value="unread">Não lidas primeiro</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Período</label>
                    <select class="filter-select" id="periodFilter">
                        <option value="all">Todos os períodos</option>
                        <option value="today">Hoje</option>
                        <option value="week">Esta semana</option>
                        <option value="month">Este mês</option>
                        <option value="older">Mais antigas</option>
                    </select>
                </div>
            </div>
        </section>

        <!-- Lista de Notificações -->
        <div class="notifications-container" id="notificationsContainer">
            <?php if (empty($notificacoes)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-bell-slash"></i>
                    </div>
                    <h3 class="empty-title">Nenhuma notificação encontrada</h3>
                    <p class="empty-description">
                        Você não possui notificações no momento. Novas notificações aparecerão aqui quando houver atualizações em seus cursos.
                    </p>
                    <a href="dashboard.php" class="action-btn primary" style="max-width: 200px; margin: 0 auto;">
                        <i class="fas fa-arrow-left"></i>
                        Voltar ao Dashboard
                    </a>
                </div>
            <?php else: ?>
                <?php 
                $hoje = date('Y-m-d');
                $ontem = date('Y-m-d', strtotime('-1 day'));
                $last_date = null;
                
                foreach ($notificacoes as $n): 
                    $data_envio = date('Y-m-d', strtotime($n['data_envio']));
                    $is_today = ($data_envio == $hoje);
                    $is_yesterday = ($data_envio == $ontem);
                    $is_unread = ($n['lida'] == 0);
                    
                    // Determinar badge
                    $badge_class = 'badge-read';
                    $badge_text = 'Lida';
                    $badge_icon = 'fas fa-check-circle';
                    
                    if ($is_unread) {
                        $badge_class = 'badge-unread';
                        $badge_text = 'Não lida';
                        $badge_icon = 'fas fa-exclamation-circle';
                    } elseif ($is_today) {
                        $badge_class = 'badge-today';
                        $badge_text = 'Hoje';
                        $badge_icon = 'fas fa-calendar-day';
                    }
                    
                    // Formatar data
                    $time_display = date('H:i', strtotime($n['data_envio']));
                    $date_display = date('d/m/Y', strtotime($n['data_envio']));
                    
                    if ($is_today) {
                        $date_display = "Hoje";
                    } elseif ($is_yesterday) {
                        $date_display = "Ontem";
                    }
                    
                    // Separador de data
                    if ($data_envio != $last_date):
                        $last_date = $data_envio;
                ?>
                <div class="date-separator" style="text-align: center; margin: 1rem 0; position: relative;">
                    <span style="background: var(--surface); padding: 0.5rem 1rem; border-radius: 20px; color: var(--text-muted); font-size: 0.9rem; border: 1px solid var(--border);">
                        <?php 
                        if ($is_today) echo "Hoje";
                        elseif ($is_yesterday) echo "Ontem";
                        else echo date('d/m/Y', strtotime($n['data_envio']));
                        ?>
                    </span>
                </div>
                <?php endif; ?>
                
                <div class="notification <?php echo $is_unread ? 'unread' : 'read'; ?>" 
                     data-id="<?php echo $n['id']; ?>"
                     data-date="<?php echo $data_envio; ?>"
                     data-unread="<?php echo $is_unread ? 'true' : 'false'; ?>">
                    <div class="notification-header">
                        <div style="flex: 1;">
                            <h3 class="notification-title">
                                <i class="fas fa-bell"></i>
                                <?php echo htmlspecialchars($n['titulo']); ?>
                            </h3>
                        </div>
                        <div class="notification-badge <?php echo $badge_class; ?>">
                            <i class="<?php echo $badge_icon; ?>"></i>
                            <span><?php echo $badge_text; ?></span>
                        </div>
                    </div>
                    
                    <div class="notification-body">
                        <div class="notification-message">
                            <?php echo nl2br(htmlspecialchars($n['mensagem'])); ?>
                        </div>
                        
                        <div class="notification-footer">
                            <div class="notification-time">
                                <i class="far fa-clock"></i>
                                <span><?php echo $date_display; ?> • <?php echo $time_display; ?></span>
                            </div>
                            
                            <div class="notification-actions">
                                <?php if ($is_unread): ?>
                                <button class="notification-btn mark-read" onclick="markAsRead(<?php echo $n['id']; ?>)">
                                    <i class="fas fa-check"></i>
                                    Marcar como lida
                                </button>
                                <?php endif; ?>
                                <button class="notification-btn delete" onclick="deleteNotification(<?php echo $n['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                    Excluir
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Indicador de nova notificação -->
    <div class="new-notification-indicator" id="newNotificationIndicator">
        <i class="fas fa-bell"></i>
        <span>Nova notificação recebida!</span>
        <button onclick="location.reload()" style="background: none; border: none; color: white; cursor: pointer;">
            <i class="fas fa-sync-alt"></i>
        </button>
    </div>

    <!-- Modal de detalhes -->
    <div class="modal-overlay" id="notificationModal" onclick="closeModal()">
        <div class="modal" onclick="event.stopPropagation()">
            <div id="modalContent">
                <!-- Conteúdo será carregado dinamicamente -->
            </div>
        </div>
    </div>

    <script>
    // Elementos DOM
    const notificationsContainer = document.getElementById('notificationsContainer');
    const statusFilter = document.getElementById('statusFilter');
    const sortFilter = document.getElementById('sortFilter');
    const periodFilter = document.getElementById('periodFilter');
    const newNotificationIndicator = document.getElementById('newNotificationIndicator');
    const notificationModal = document.getElementById('notificationModal');
    const modalContent = document.getElementById('modalContent');

    // Filtrar notificações
    function filterNotifications() {
        const status = statusFilter.value;
        const sort = sortFilter.value;
        const period = periodFilter.value;
        const notifications = document.querySelectorAll('.notification');
        
        let filteredNotifications = Array.from(notifications);
        
        // Filtrar por status
        if (status === 'unread') {
            filteredNotifications = filteredNotifications.filter(n => n.getAttribute('data-unread') === 'true');
        } else if (status === 'read') {
            filteredNotifications = filteredNotifications.filter(n => n.getAttribute('data-unread') === 'false');
        } else if (status === 'today') {
            const today = new Date().toISOString().split('T')[0];
            filteredNotifications = filteredNotifications.filter(n => n.getAttribute('data-date') === today);
        }
        
        // Filtrar por período
        if (period !== 'all') {
            const now = new Date();
            filteredNotifications = filteredNotifications.filter(n => {
                const notificationDate = new Date(n.getAttribute('data-date'));
                const timeDiff = now.getTime() - notificationDate.getTime();
                const daysDiff = timeDiff / (1000 * 3600 * 24);
                
                if (period === 'today') return daysDiff < 1;
                if (period === 'week') return daysDiff < 7;
                if (period === 'month') return daysDiff < 30;
                if (period === 'older') return daysDiff >= 30;
                return true;
            });
        }
        
        // Ordenar
        if (sort === 'recent') {
            filteredNotifications.sort((a, b) => {
                const dateA = new Date(a.getAttribute('data-date'));
                const dateB = new Date(b.getAttribute('data-date'));
                return dateB - dateA;
            });
        } else if (sort === 'oldest') {
            filteredNotifications.sort((a, b) => {
                const dateA = new Date(a.getAttribute('data-date'));
                const dateB = new Date(b.getAttribute('data-date'));
                return dateA - dateB;
            });
        } else if (sort === 'unread') {
            filteredNotifications.sort((a, b) => {
                const unreadA = a.getAttribute('data-unread') === 'true';
                const unreadB = b.getAttribute('data-unread') === 'true';
                if (unreadA && !unreadB) return -1;
                if (!unreadA && unreadB) return 1;
                return 0;
            });
        }
        
        // Esconder todas
        notifications.forEach(n => n.style.display = 'none');
        
        // Mostrar filtradas
        filteredNotifications.forEach(n => n.style.display = 'block');
        
        // Mostrar mensagem se não houver resultados
        const emptyState = notificationsContainer.querySelector('.empty-state');
        const visibleNotifications = filteredNotifications.filter(n => n.style.display !== 'none');
        
        if (visibleNotifications.length === 0 && !emptyState) {
            const emptyDiv = document.createElement('div');
            emptyDiv.className = 'empty-state';
            emptyDiv.innerHTML = `
                <div class="empty-icon">
                    <i class="fas fa-search"></i>
                </div>
                <h3 class="empty-title">Nenhuma notificação encontrada</h3>
                <p class="empty-description">
                    Tente ajustar os filtros para encontrar suas notificações.
                </p>
                <button onclick="resetFilters()" class="action-btn primary" style="max-width: 200px; margin: 0 auto;">
                    <i class="fas fa-redo"></i>
                    Limpar Filtros
                </button>
            `;
            notificationsContainer.appendChild(emptyDiv);
        } else if (emptyState && visibleNotifications.length > 0) {
            emptyState.remove();
        }
    }

    // Event listeners para filtros
    statusFilter.addEventListener('change', filterNotifications);
    sortFilter.addEventListener('change', filterNotifications);
    periodFilter.addEventListener('change', filterNotifications);

    // Resetar filtros
    window.resetFilters = function() {
        statusFilter.value = 'all';
        sortFilter.value = 'recent';
        periodFilter.value = 'all';
        filterNotifications();
    };

    // Marcar como lida
    function markAsRead(id) {
        const notification = document.querySelector(`.notification[data-id="${id}"]`);
        if (notification) {
            notification.classList.remove('unread');
            notification.classList.add('read');
            notification.setAttribute('data-unread', 'false');
            
            const badge = notification.querySelector('.notification-badge');
            badge.className = 'notification-badge badge-read';
            badge.innerHTML = '<i class="fas fa-check-circle"></i><span>Lida</span>';
            
            // Remover botão "Marcar como lida"
            const markReadBtn = notification.querySelector('.mark-read');
            if (markReadBtn) markReadBtn.remove();
            
            // Atualizar contador
            updateUnreadCount();
            
            // Simular requisição ao servidor
            fetch('mark_as_read.php?id=' + id, { method: 'POST' })
                .catch(error => console.error('Erro:', error));
        }
    }

    // Marcar todas como lidas
    function markAllAsRead() {
        const unreadNotifications = document.querySelectorAll('.notification.unread');
        unreadNotifications.forEach(notification => {
            const id = notification.getAttribute('data-id');
            markAsRead(id);
        });
        
        showToast('Todas as notificações foram marcadas como lidas!', 'success');
    }

    // Excluir notificação
    function deleteNotification(id) {
        if (confirm('Tem certeza que deseja excluir esta notificação?')) {
            const notification = document.querySelector(`.notification[data-id="${id}"]`);
            if (notification) {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(-100%)';
                
                setTimeout(() => {
                    notification.remove();
                    updateStats();
                    showToast('Notificação excluída com sucesso!', 'success');
                    
                    // Verificar se não há mais notificações
                    const remaining = document.querySelectorAll('.notification').length;
                    if (remaining === 0) {
                        location.reload();
                    }
                }, 300);
                
                // Simular requisição ao servidor
                fetch('delete_notification.php?id=' + id, { method: 'POST' })
                    .catch(error => console.error('Erro:', error));
            }
        }
    }

    // Excluir todas as lidas
    function deleteAllRead() {
        const readNotifications = document.querySelectorAll('.notification.read');
        if (readNotifications.length === 0) {
            showToast('Não há notificações lidas para excluir.', 'info');
            return;
        }
        
        if (confirm(`Tem certeza que deseja excluir ${readNotifications.length} notificação(ões) lida(s)?`)) {
            readNotifications.forEach(notification => {
                const id = notification.getAttribute('data-id');
                
                // Simular requisição ao servidor
                fetch('delete_notification.php?id=' + id, { method: 'POST' })
                    .catch(error => console.error('Erro:', error));
            });
            
            // Recarregar página
            location.reload();
        }
    }

    // Atualizar contador de não lidas
    function updateUnreadCount() {
        const unreadCount = document.querySelectorAll('.notification[data-unread="true"]').length;
        const statValue = document.querySelector('.stat-unread .stat-value');
        if (statValue) {
            statValue.textContent = unreadCount;
        }
    }

    // Atualizar estatísticas
    function updateStats() {
        const total = document.querySelectorAll('.notification').length;
        const unread = document.querySelectorAll('.notification[data-unread="true"]').length;
        const today = new Date().toISOString().split('T')[0];
        const todayCount = document.querySelectorAll(`.notification[data-date="${today}"]`).length;
        
        const statValues = document.querySelectorAll('.stat-value');
        if (statValues.length >= 3) {
            statValues[0].textContent = total;
            statValues[1].textContent = unread;
            statValues[2].textContent = todayCount;
        }
    }

    // Atualizar notificações
    function refreshNotifications() {
        const btn = event.target.closest('button');
        const originalHTML = btn.innerHTML;
        
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Atualizando...';
        btn.disabled = true;
        
        setTimeout(() => {
            location.reload();
        }, 1000);
    }

    // Mostrar toast
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = 'notification-toast';
        toast.innerHTML = `
            <div style="background: ${type === 'success' ? 'var(--success)' : 'var(--danger)'}; color: white; padding: 1rem; border-radius: var(--radius); display: flex; align-items: center; gap: 0.75rem; box-shadow: var(--shadow-lg);">
                <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i>
                <span>${message}</span>
            </div>
        `;
        
        toast.style.position = 'fixed';
        toast.style.bottom = '2rem';
        toast.style.right = '2rem';
        toast.style.zIndex = '1000';
        toast.style.animation = 'slideInUp 0.3s ease';
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(20px)';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // Verificar novas notificações (simulação)
    setInterval(() => {
        // Em um sistema real, você faria uma requisição AJAX
        // fetch('check_new_notifications.php')
        // .then(response => response.json())
        // .then(data => {
        //     if (data.new_notifications > 0) {
        //         showNewNotificationIndicator(data.new_notifications);
        //     }
        // });
    }, 30000); // Verificar a cada 30 segundos

    // Mostrar indicador de nova notificação
    function showNewNotificationIndicator(count) {
        newNotificationIndicator.querySelector('span').textContent = 
            `${count} nova(s) notificação(ões) recebida(s)!`;
        newNotificationIndicator.style.display = 'flex';
        
        setTimeout(() => {
            newNotificationIndicator.style.opacity = '0';
            setTimeout(() => {
                newNotificationIndicator.style.display = 'none';
                newNotificationIndicator.style.opacity = '1';
            }, 300);
        }, 5000);
    }

    // Modal functions
    function openModal(notificationId) {
        // Em um sistema real, você carregaria os detalhes da notificação
        // fetch(`notification_details.php?id=${notificationId}`)
        // .then(response => response.json())
        // .then(data => {
        //     modalContent.innerHTML = `
        //         <h2>${data.titulo}</h2>
        //         <p>${data.mensagem}</p>
        //         <p><small>${data.data_envio}</small></p>
        //     `;
        //     notificationModal.style.display = 'flex';
        // });
    }

    function closeModal() {
        notificationModal.style.display = 'none';
    }

    // Inicializar
    document.addEventListener('DOMContentLoaded', function() {
        // Aplicar filtros iniciais
        filterNotifications();
        
        // Animar notificações
        const notifications = document.querySelectorAll('.notification');
        notifications.forEach((notification, index) => {
            notification.style.animationDelay = `${index * 0.1}s`;
        });
        
        // Adicionar efeito de clique nas notificações
        notifications.forEach(notification => {
            notification.addEventListener('click', function(e) {
                if (!e.target.closest('.notification-btn')) {
                    const id = this.getAttribute('data-id');
                    openModal(id);
                }
            });
        });
    });

    // Ajustar para mobile
    window.addEventListener('resize', function() {
        if (window.innerWidth <= 768) {
            document.querySelectorAll('.notification').forEach(n => {
                n.style.margin = '0.5rem 0';
            });
        }
    });
    </script>
</body>
</html>