<?php
session_start();
require_once("../config.php");

// Segurança reforçada: só Admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    $_SESSION['error'] = "Acesso não autorizado";
    header("Location: ../index.php");
    exit();
}

// Timeout de sessão (1 hora)
$session_timeout = 3600;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    session_unset();
    session_destroy();
    header("Location: ../index.php?timeout=1");
    exit();
}
$_SESSION['last_activity'] = time();

$admin_id = $_SESSION['usuario_id'];
$nome_admin = htmlspecialchars($_SESSION['usuario_nome'] ?? "Administrador");

/* ================================
   FUNÇÃO HELPER PARA CONTAGENS SEGURAS
=================================== */
function getCount($conn, $sql, $types = "", $params = []) {
    try {
        if ($types !== "" && !empty($params)) {
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                error_log("Erro na preparação da query: " . $conn->error);
                return 0;
            }
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            $stmt->close();
            return (int)($row['total'] ?? 0);
        } else {
            $res = $conn->query($sql);
            if (!$res) {
                error_log("Erro na query: " . $conn->error);
                return 0;
            }
            $row = $res->fetch_assoc();
            return (int)($row['total'] ?? 0);
        }
    } catch (Exception $e) {
        error_log("Erro em getCount: " . $e->getMessage());
        return 0;
    }
}

/* ================================
   ESTATÍSTICAS PRINCIPAIS
=================================== */
// Total de Usuários (todos os tipos da tabela usuarios)
$total_usuarios = getCount($conn, "SELECT COUNT(*) AS total FROM usuarios");

// Total de Professores
$total_professores = getCount($conn, "SELECT COUNT(*) AS total FROM usuarios WHERE tipo='professor'");

// Total de Alunos
$total_alunos = getCount($conn, "SELECT COUNT(*) AS total FROM usuarios WHERE tipo='aluno'");

// Total de Cursos
$total_cursos = getCount($conn, "SELECT COUNT(*) AS total FROM cursos");

// Total de Inscrições
$total_inscricoes = getCount($conn, "SELECT COUNT(*) AS total FROM inscricoes");

// Total de Certificados
$total_certificados = getCount($conn, "SELECT COUNT(*) AS total FROM certificados");

// Cursos mais populares
$sql_cursos_populares = "
    SELECT c.id, c.titulo, c.imagem, COUNT(i.id) as total_inscritos
    FROM cursos c
    LEFT JOIN inscricoes i ON c.id = i.curso_id
    GROUP BY c.id
    ORDER BY total_inscritos DESC
    LIMIT 5
";
$result_cursos_populares = $conn->query($sql_cursos_populares);
$cursos_populares = $result_cursos_populares ? $result_cursos_populares->fetch_all(MYSQLI_ASSOC) : [];

// Últimas inscrições
$sql_ultimas_inscricoes = "
    SELECT i.*, u.nome as aluno_nome, u.email as aluno_email, c.titulo as curso_titulo
    FROM inscricoes i
    INNER JOIN usuarios u ON i.aluno_id = u.id
    INNER JOIN cursos c ON i.curso_id = c.id
    WHERE u.tipo = 'aluno'
    ORDER BY i.inscrito_em DESC
    LIMIT 8
";
$result_inscricoes = $conn->query($sql_ultimas_inscricoes);
$ultimas_inscricoes = $result_inscricoes ? $result_inscricoes->fetch_all(MYSQLI_ASSOC) : [];

// Estatísticas do mês atual
$mes_atual = date('Y-m');
$total_inscricoes_mes = getCount($conn, 
    "SELECT COUNT(*) AS total FROM inscricoes WHERE DATE_FORMAT(inscrito_em, '%Y-%m') = ?", 
    "s", [$mes_atual]
);

$total_novos_usuarios_mes = getCount($conn,
    "SELECT COUNT(*) AS total FROM usuarios WHERE DATE_FORMAT(data_cadastro, '%Y-%m') = ?",
    "s", [$mes_atual]
);

// Gráfico: Inscrições por mês (últimos 6 meses)
$labels_grafico = [];
$valores_grafico = [];

$sql_grafico = "
    SELECT DATE_FORMAT(inscrito_em, '%Y-%m') AS ym, COUNT(*) AS total
    FROM inscricoes
    WHERE inscrito_em >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY ym
    ORDER BY ym ASC
";
$res_grafico = $conn->query($sql_grafico);
$dados_grafico = [];

if ($res_grafico) {
    while ($row = $res_grafico->fetch_assoc()) {
        $dados_grafico[$row['ym']] = (int)$row['total'];
    }
}

// Preencher todos os meses (mesmo sem dados)
$data_inicio = new DateTime(date('Y-m-01'));
$data_inicio->modify('-5 months');

for ($i = 0; $i < 6; $i++) {
    $ym = $data_inicio->format('Y-m');
    $labels_grafico[] = $data_inicio->format('M/Y');
    $valores_grafico[] = $dados_grafico[$ym] ?? 0;
    $data_inicio->modify('+1 month');
}

/* ================================
   DADOS PARA SIDEBAR E MENU
=================================== */
// Últimas notificações
$sql_notificacoes = "
    SELECT n.*, u.nome as remetente_nome
    FROM notificacoes n
    LEFT JOIN usuarios u ON n.remetente_id = u.id
    WHERE n.destinatario_tipo = 'admin' OR n.destinatario_tipo = 'todos'
    ORDER BY n.data_envio DESC
    LIMIT 5
";
$result_notificacoes = $conn->query($sql_notificacoes);
$ultimas_notificacoes = $result_notificacoes ? $result_notificacoes->fetch_all(MYSQLI_ASSOC) : [];

// Total de notificações não lidas
$total_notificacoes_nao_lidas = getCount($conn,
    "SELECT COUNT(*) AS total FROM notificacoes 
    WHERE (destinatario_tipo = 'admin' OR destinatario_tipo = 'todos') 
    AND lida = 0",
    "", []
);

// Últimas atividades (inscrições e progressos)
$sql_atividades = "
    (SELECT 
        'inscricao' as tipo,
        u.nome as usuario_nome,
        u.tipo as usuario_tipo,
        c.titulo as curso_titulo,
        i.inscrito_em as data_acao
    FROM inscricoes i
    INNER JOIN usuarios u ON i.aluno_id = u.id
    INNER JOIN cursos c ON i.curso_id = c.id
    ORDER BY i.inscrito_em DESC
    LIMIT 5)
    
    UNION ALL
    
    (SELECT 
        'progresso' as tipo,
        u.nome as usuario_nome,
        u.tipo as usuario_tipo,
        c.titulo as curso_titulo,
        p.concluido_em as data_acao
    FROM progresso p
    INNER JOIN usuarios u ON p.aluno_id = u.id
    INNER JOIN aulas a ON p.aula_id = a.id
    INNER JOIN cursos c ON a.curso_id = c.id
    WHERE p.concluido = 1
    ORDER BY p.concluido_em DESC
    LIMIT 5)
    
    ORDER BY data_acao DESC
    LIMIT 10
";

$result_atividades = $conn->query($sql_atividades);
$ultimas_atividades = $result_atividades ? $result_atividades->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin — Eduka Plus</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --primary: #1a237e;
    --primary-dark: #0d1b66;
    --primary-light: #283593;
    --secondary: #7b1fa2;
    --secondary-dark: #6a1b9a;
    --background: #f5f7fa;
    --surface: #ffffff;
    --surface-light: #ffffff;
    --surface-dark: #f8fafc;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --text-muted: #94a3b8;
    --border: #e2e8f0;
    --border-light: #f1f5f9;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --info: #3b82f6;
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

/* Skip link para acessibilidade */
.skip-link {
    position: absolute;
    top: -40px;
    left: 0;
    background: var(--primary);
    color: white;
    padding: 8px;
    z-index: 10000;
    text-decoration: none;
    border-radius: var(--radius);
}

.skip-link:focus {
    top: 0;
}

/* Sidebar */
#sidebar {
    width: var(--sidebar-width);
    background: linear-gradient(180deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    padding: 2rem 1.5rem;
    display: flex;
    flex-direction: column;
    position: fixed;
    height: 100vh;
    z-index: 100;
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: var(--shadow-xl);
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
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.logo-container {
    width: 42px;
    height: 42px;
    border-radius: var(--radius);
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
}

.logo-container img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    filter: brightness(0) invert(1);
}

.brand-text {
    font-weight: 700;
    font-size: 1.4rem;
    color: white;
}

.brand-subtitle {
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.8);
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
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    border-radius: var(--radius);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    font-weight: 500;
}

.nav-item:hover {
    background: rgba(255, 255, 255, 0.15);
    color: white;
    transform: translateX(4px);
}

.nav-item.active {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.nav-item.active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 4px;
    background: white;
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
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.user-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: var(--radius);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, white 0%, #e0e0e0 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary);
    font-weight: 700;
    font-size: 1rem;
    border: 2px solid rgba(255, 255, 255, 0.5);
}

.user-details h4 {
    font-size: 0.95rem;
    font-weight: 600;
    color: white;
}

.user-details p {
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.8);
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

.logout-btn {
    background: var(--surface-light);
    color: var(--danger);
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

.logout-btn:hover {
    background: var(--danger);
    color: white;
    transform: translateY(-2px);
    border-color: var(--danger);
}

/* Cartões de estatísticas */
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
    opacity: 0;
    transform: translateY(20px);
    animation: fadeInUp 0.6s ease forwards;
}

@keyframes fadeInUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.stat-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--shadow-lg);
    border-color: rgba(26, 35, 126, 0.2);
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
    background: rgba(26, 35, 126, 0.1);
    color: var(--primary);
}

.stat-card:nth-child(2) .stat-icon {
    background: rgba(123, 31, 162, 0.1);
    color: var(--secondary);
}

.stat-card:nth-child(3) .stat-icon {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success);
}

.stat-card:nth-child(4) .stat-icon {
    background: rgba(245, 158, 11, 0.1);
    color: var(--warning);
}

.stat-card:nth-child(5) .stat-icon {
    background: rgba(59, 130, 246, 0.1);
    color: var(--info);
}

.stat-card:nth-child(6) .stat-icon {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger);
}

.stat-content h3 {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--text-secondary);
    margin-bottom: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-value {
    font-size: 2.25rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
    color: var(--text-primary);
    line-height: 1;
}

.stat-description {
    font-size: 0.875rem;
    color: var(--text-muted);
}

.stat-trend {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    margin-top: 0.75rem;
    color: var(--success);
    font-weight: 500;
}

.stat-trend.down {
    color: var(--danger);
}

/* Container principal */
.main-dashboard-container {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    margin-bottom: 2.5rem;
}

@media (max-width: 1024px) {
    .main-dashboard-container {
        grid-template-columns: 1fr;
    }
}

/* Seções */
.dashboard-section {
    background: var(--surface-light);
    border-radius: var(--radius);
    padding: 1.5rem;
    border: 1px solid var(--border);
    box-shadow: var(--shadow-sm);
    margin-bottom: 1.5rem;
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
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.view-all-btn {
    background: var(--surface-light);
    color: var(--text-secondary);
    border: 1px solid var(--border);
    padding: 0.5rem 1rem;
    border-radius: var(--radius);
    text-decoration: none;
    font-weight: 500;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.view-all-btn:hover {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

/* Gráfico */
.chart-container {
    background: white;
    padding: 1.5rem;
    border-radius: var(--radius);
    border: 1px solid var(--border);
}

/* Tabelas */
.simple-table {
    width: 100%;
    border-collapse: collapse;
    background: var(--surface-light);
    border-radius: var(--radius);
    overflow: hidden;
}

.simple-table thead {
    background: var(--surface-dark);
}

.simple-table th {
    padding: 1rem 1.25rem;
    text-align: left;
    font-weight: 600;
    color: var(--text-secondary);
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid var(--border);
}

.simple-table td {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--border-light);
    color: var(--text-primary);
}

.simple-table tbody tr {
    transition: all 0.2s ease;
}

.simple-table tbody tr:hover {
    background: rgba(26, 35, 126, 0.03);
}

.simple-table tbody tr:last-child td {
    border-bottom: none;
}

.badge {
    display: inline-block;
    padding: 0.35rem 0.75rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
}

.badge-success {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success);
}

.badge-warning {
    background: rgba(245, 158, 11, 0.1);
    color: var(--warning);
}

.badge-primary {
    background: rgba(26, 35, 126, 0.1);
    color: var(--primary);
}

.badge-secondary {
    background: rgba(123, 31, 162, 0.1);
    color: var(--secondary);
}

.badge-info {
    background: rgba(59, 130, 246, 0.1);
    color: var(--info);
}

/* User cell */
.user-cell {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.user-avatar-small {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 0.8rem;
}

/* Lista de cursos */
.courses-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.course-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border-radius: var(--radius);
    border: 1px solid var(--border);
    transition: all 0.3s ease;
    text-decoration: none;
    color: var(--text-primary);
    background: var(--surface-light);
}

.course-item:hover {
    background: rgba(26, 35, 126, 0.05);
    border-color: rgba(26, 35, 126, 0.2);
    transform: translateX(4px);
}

.course-image {
    width: 60px;
    height: 60px;
    border-radius: 10px;
    object-fit: cover;
}

/* Status do sistema */
.system-status {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    padding: 1.5rem;
    border-radius: var(--radius);
    margin-top: 1.5rem;
}

.system-status h4 {
    font-size: 1.1rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.status-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
    padding: 0.5rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: var(--radius);
    backdrop-filter: blur(10px);
}

.status-indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--success);
}

.status-indicator.warning {
    background: var(--warning);
}

.status-indicator.danger {
    background: var(--danger);
}

/* Ações rápidas */
.quick-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
    margin-top: 1rem;
}

.quick-action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    background: var(--surface-dark);
    border-radius: var(--radius);
    text-decoration: none;
    color: var(--text-primary);
    transition: all 0.3s ease;
    text-align: center;
    border: 1px solid var(--border);
}

.quick-action-btn:hover {
    background: var(--primary);
    color: white;
    transform: translateY(-2px);
    border-color: var(--primary);
}

.quick-action-btn i {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.quick-action-btn span {
    font-size: 0.85rem;
    font-weight: 500;
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
    
    .simple-table {
        display: block;
        overflow-x: auto;
    }
    
    .simple-table th,
    .simple-table td {
        padding: 0.75rem;
    }
    
    .quick-actions {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .stat-value {
        font-size: 1.75rem;
    }
    
    .course-item {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .course-image {
        width: 100%;
        height: 120px;
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

/* Animações de atraso */
.stat-card:nth-child(1) { animation-delay: 0.1s; }
.stat-card:nth-child(2) { animation-delay: 0.2s; }
.stat-card:nth-child(3) { animation-delay: 0.3s; }
.stat-card:nth-child(4) { animation-delay: 0.4s; }
.stat-card:nth-child(5) { animation-delay: 0.5s; }
.stat-card:nth-child(6) { animation-delay: 0.6s; }
</style>
</head>
<body>

<a href="#main-content" class="skip-link">Pular para o conteúdo principal</a>

<!-- Botão para menu mobile -->
<button id="btnToggleMenu" aria-label="Alternar menu">
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
        
        <nav class="sidebar-nav" aria-label="Navegação principal">
            <a href="./dashboard.php" class="nav-item active">
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
<main class="main-content" id="main-content">
    <!-- Header -->
    <header class="main-header">
        <div class="welcome-section">
            <h1>👋 Bem-vindo, <?php echo htmlspecialchars($nome_admin); ?></h1>
            <p>Painel de controle administrativo - Gerencie toda a plataforma.</p>
        </div>
        
        <div class="header-actions">
            <a href="notificacoes.php" class="notification-btn" aria-label="Notificações">
                <i class="fas fa-bell"></i>
                <?php if($total_notificacoes_nao_lidas > 0): ?>
                    <span class="notification-badge"><?php echo $total_notificacoes_nao_lidas; ?></span>
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
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3>Total de Usuários</h3>
                <div class="stat-value" data-count="<?php echo $total_usuarios; ?>">0</div>
                <p class="stat-description">Usuários registrados na plataforma</p>
                <div class="stat-trend">
                    <i class="fas fa-arrow-up"></i>
                    <span>+<?php echo $total_novos_usuarios_mes; ?> este mês</span>
                </div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="stat-content">
                <h3>Professores</h3>
                <div class="stat-value" data-count="<?php echo $total_professores; ?>">0</div>
                <p class="stat-description">Professores ativos</p>
                <div class="stat-trend">
                    <i class="fas fa-arrow-up"></i>
                    <span>+<?php echo floor($total_professores * 0.1); ?> este mês</span>
                </div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="stat-content">
                <h3>Alunos</h3>
                <div class="stat-value" data-count="<?php echo $total_alunos; ?>">0</div>
                <p class="stat-description">Alunos matriculados</p>
                <div class="stat-trend">
                    <i class="fas fa-arrow-up"></i>
                    <span>+<?php echo floor($total_alunos * 0.15); ?> este mês</span>
                </div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-book"></i>
            </div>
            <div class="stat-content">
                <h3>Cursos</h3>
                <div class="stat-value" data-count="<?php echo $total_cursos; ?>">0</div>
                <p class="stat-description">Cursos disponíveis</p>
                <div class="stat-trend">
                    <i class="fas fa-arrow-up"></i>
                    <span>+<?php echo floor($total_cursos * 0.05); ?> este mês</span>
                </div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-file-signature"></i>
            </div>
            <div class="stat-content">
                <h3>Inscrições</h3>
                <div class="stat-value" data-count="<?php echo $total_inscricoes; ?>">0</div>
                <p class="stat-description">Total de matrículas</p>
                <div class="stat-trend">
                    <i class="fas fa-arrow-up"></i>
                    <span>+<?php echo $total_inscricoes_mes; ?> este mês</span>
                </div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-certificate"></i>
            </div>
            <div class="stat-content">
                <h3>Certificados</h3>
                <div class="stat-value" data-count="<?php echo $total_certificados; ?>">0</div>
                <p class="stat-description">Certificados emitidos</p>
                <div class="stat-trend">
                    <i class="fas fa-arrow-up"></i>
                    <span>+<?php echo floor($total_certificados * 0.2); ?> este mês</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Container Principal -->
    <div class="main-dashboard-container">
        <!-- Coluna Esquerda -->
        <div>
            <!-- Gráfico de Inscrições -->
            <section class="dashboard-section">
                <div class="section-header">
                    <h3><i class="fas fa-chart-bar"></i> Inscrições - últimos 6 meses</h3>
                </div>
                <div class="chart-container">
                    <canvas id="graficoInscricoes" height="250"></canvas>
                </div>
            </section>

            <!-- Últimas Atividades -->
            <section class="dashboard-section">
                <div class="section-header">
                    <h3><i class="fas fa-history"></i> Últimas Atividades</h3>
                    <a href="./relatorios.php" class="view-all-btn">
                        <i class="fas fa-arrow-right"></i>
                        <span>Ver Relatórios</span>
                    </a>
                </div>
                
                <?php if(empty($ultimas_atividades)): ?>
                    <div class="empty-state" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                        <i class="fas fa-inbox" style="font-size: 2.5rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <h3>Nenhuma atividade recente</h3>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="simple-table">
                            <thead>
                                <tr>
                                    <th>Usuário</th>
                                    <th>Tipo</th>
                                    <th>Ação</th>
                                    <th>Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($ultimas_atividades as $atividade): 
                                    $data = date('d/m/Y H:i', strtotime($atividade['data_acao']));
                                    $badge_class = 'badge-secondary';
                                    if ($atividade['usuario_tipo'] == 'admin') $badge_class = 'badge-warning';
                                    elseif ($atividade['usuario_tipo'] == 'professor') $badge_class = 'badge-primary';
                                    elseif ($atividade['usuario_tipo'] == 'aluno') $badge_class = 'badge-success';
                                ?>
                                <tr>
                                    <td>
                                        <div class="user-cell">
                                            <div class="user-avatar-small">
                                                <?php echo strtoupper(substr($atividade['usuario_nome'], 0, 1)); ?>
                                            </div>
                                            <div style="font-weight: 600;"><?php echo htmlspecialchars($atividade['usuario_nome']); ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <?php echo ucfirst($atividade['usuario_tipo']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($atividade['tipo'] == 'inscricao'): ?>
                                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                <i class="fas fa-user-plus" style="color: var(--success);"></i>
                                                <span>Inscreveu-se em</span>
                                                <strong><?php echo htmlspecialchars($atividade['curso_titulo']); ?></strong>
                                            </div>
                                        <?php else: ?>
                                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                <i class="fas fa-check-circle" style="color: var(--info);"></i>
                                                <span>Concluiu uma aula de</span>
                                                <strong><?php echo htmlspecialchars($atividade['curso_titulo']); ?></strong>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.9rem;"><?php echo $data; ?></div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <!-- Coluna Direita -->
        <div>
            <!-- Cursos mais Populares -->
            <section class="dashboard-section">
                <div class="section-header">
                    <h3><i class="fas fa-fire"></i> Cursos mais Populares</h3>
                    <a href="./cursos.php" class="view-all-btn">
                        <i class="fas fa-arrow-right"></i>
                        <span>Ver Todos</span>
                    </a>
                </div>
                
                <div class="courses-list">
                    <?php if(empty($cursos_populares)): ?>
                        <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
                            <i class="fas fa-book" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                            <p>Nenhum curso disponível</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($cursos_populares as $curso): ?>
                            <a href="./ver_curso.php?id=<?php echo $curso['id']; ?>" class="course-item">
                                <?php if(!empty($curso['imagem'])): ?>
                                    <img src="../uploads/<?php echo htmlspecialchars($curso['imagem']); ?>" class="course-image" alt="Imagem do curso">
                                <?php else: ?>
                                    <div class="course-image" style="background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); display: flex; align-items: center; justify-content: center; color: white;">
                                        <i class="fas fa-book"></i>
                                    </div>
                                <?php endif; ?>
                                <div style="flex: 1;">
                                    <div style="font-weight: 600; margin-bottom: 0.25rem;"><?php echo htmlspecialchars($curso['titulo']); ?></div>
                                    <div style="font-size: 0.85rem; color: var(--text-secondary);">
                                        <i class="fas fa-users"></i> <?php echo $curso['total_inscritos']; ?> inscritos
                                    </div>
                                </div>
                                <div style="color: var(--primary); font-weight: 600;">
                                    <i class="fas fa-chevron-right"></i>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Ações Rápidas -->
            <section class="dashboard-section">
                <div class="section-header">
                    <h3><i class="fas fa-bolt"></i> Ações Rápidas</h3>
                </div>
                <div class="quick-actions">
                    <a href="./cursos_add.php" class="quick-action-btn">
                        <i class="fas fa-plus"></i>
                        <span>Novo Curso</span>
                    </a>
                    <a href="./usuarios_add.php" class="quick-action-btn">
                        <i class="fas fa-user-plus"></i>
                        <span>Novo Usuário</span>
                    </a>
                    <a href="./relatorios.php" class="quick-action-btn">
                        <i class="fas fa-chart-pie"></i>
                        <span>Relatórios</span>
                    </a>
                    <a href="./configuracoes.php" class="quick-action-btn">
                        <i class="fas fa-cog"></i>
                        <span>Configurações</span>
                    </a>
                </div>
            </section>

            <!-- Status do Sistema -->
            <section class="system-status">
                <h4><i class="fas fa-server"></i> Status do Sistema</h4>
                <div>
                    <div class="status-item">
                        <div class="status-indicator"></div>
                        <span>Sistema Online</span>
                    </div>
                    <div class="status-item">
                        <div class="status-indicator"></div>
                        <span>Banco de Dados Conectado</span>
                    </div>
                    <div class="status-item">
                        <div class="status-indicator"></div>
                        <span>Todos os Serviços Ativos</span>
                    </div>
                </div>
                
                <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid rgba(255, 255, 255, 0.1);">
                    <div style="font-size: 0.9rem; opacity: 0.9; margin-bottom: 0.5rem;">
                        Última atualização:
                    </div>
                    <div style="font-weight: 600; font-size: 1.1rem;">
                        <?php echo date('d/m/Y H:i'); ?>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="footer-links">
            <a href="../ajuda.php">Central de Ajuda</a>
            <a href="../suporte.php">Suporte</a>
            <a href="../termos.php">Termos de Uso</a>
            <a href="../privacidade.php">Política de Privacidade</a>
        </div>
        <div class="copyright">
            © <?php echo date("Y"); ?> Eduka Plus Angola — Sistema de Gestão Educacional
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

// Gráfico de Inscrições
const ctx = document.getElementById('graficoInscricoes');
if (ctx) {
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($labels_grafico, JSON_UNESCAPED_UNICODE); ?>,
            datasets: [{
                label: 'Inscrições',
                data: <?php echo json_encode($valores_grafico, JSON_UNESCAPED_UNICODE); ?>,
                backgroundColor: [
                    'rgba(26, 35, 126, 0.7)',
                    'rgba(123, 31, 162, 0.7)',
                    'rgba(16, 185, 129, 0.7)',
                    'rgba(245, 158, 11, 0.7)',
                    'rgba(59, 130, 246, 0.7)',
                    'rgba(239, 68, 68, 0.7)'
                ],
                borderColor: [
                    'rgb(26, 35, 126)',
                    'rgb(123, 31, 162)',
                    'rgb(16, 185, 129)',
                    'rgb(245, 158, 11)',
                    'rgb(59, 130, 246)',
                    'rgb(239, 68, 68)'
                ],
                borderWidth: 1,
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.95)',
                    titleColor: '#1e293b',
                    bodyColor: '#64748b',
                    borderColor: 'rgba(26, 35, 126, 0.2)',
                    borderWidth: 1,
                    cornerRadius: 8,
                    padding: 12,
                    boxPadding: 6,
                }
            },
            scales: {
                x: {
                    grid: {
                        color: 'rgba(226, 232, 240, 0.5)',
                        drawBorder: false
                    },
                    ticks: {
                        color: '#64748b',
                        font: {
                            size: 12,
                            family: "'Inter', sans-serif"
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(226, 232, 240, 0.5)',
                        drawBorder: false
                    },
                    ticks: {
                        color: '#64748b',
                        font: {
                            size: 12,
                            family: "'Inter', sans-serif"
                        },
                        callback: function(value) {
                            return value;
                        }
                    }
                }
            },
            animation: {
                duration: 2000,
                easing: 'easeOutQuart'
            }
        }
    });
}

// Animações de contagem
function animateCounter(element, duration = 1200) {
    const target = parseInt(element.getAttribute('data-count'));
    if (isNaN(target)) return;
    
    const start = 0;
    const startTime = performance.now();
    
    function updateCount(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        const easeOutQuart = 1 - Math.pow(1 - progress, 4);
        const currentValue = Math.floor(easeOutQuart * target);
        
        element.textContent = currentValue.toLocaleString('pt-BR');
        
        if (progress < 1) {
            requestAnimationFrame(updateCount);
        } else {
            element.textContent = target.toLocaleString('pt-BR');
        }
    }
    
    requestAnimationFrame(updateCount);
}

// Iniciar animações de contagem
document.querySelectorAll('.stat-value').forEach(el => {
    animateCounter(el, 1500);
});

// Animar elementos ao rolar
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

document.querySelectorAll('.dashboard-section').forEach(el => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(20px)';
    el.style.transition = 'all 0.6s ease';
    observer.observe(el);
});

// Atualizar status do sistema periodicamente
function updateSystemStatus() {
    fetch('./api/status.php')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'error') {
                document.querySelectorAll('.status-indicator').forEach(indicator => {
                    indicator.classList.add('danger');
                    indicator.classList.remove('warning');
                });
            } else {
                document.querySelectorAll('.status-indicator').forEach(indicator => {
                    indicator.classList.remove('danger', 'warning');
                });
            }
        })
        .catch(error => {
            console.error('Erro ao verificar status:', error);
        });
}

// Verificar status a cada 60 segundos
setInterval(updateSystemStatus, 60000);

// Inicializar status
updateSystemStatus();

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

// Lazy loading para imagens
const images = document.querySelectorAll('img[data-src]');
if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                imageObserver.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
}
</script>
</body>
</html>