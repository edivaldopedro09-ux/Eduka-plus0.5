<?php
session_start();
require_once("../config.php");

// Segurança: só alunos
if(!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo']!=='aluno'){
    header("Location: ../index.php");
    exit();
}

$aluno_id = $_SESSION['usuario_id'];
$nome_aluno = $_SESSION['usuario_nome'] ?? "Aluno";

/* ================================
   TOTAL DE CURSOS MATRICULADOS
================================== */
$sql = "SELECT COUNT(*) AS total FROM inscricoes WHERE aluno_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$aluno_id);
$stmt->execute();
$total_cursos = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

/* ================================
   TOTAL DE AULAS CONCLUÍDAS
================================== */
$sql = "SELECT COUNT(*) AS total 
        FROM progresso p
        INNER JOIN aulas a ON p.aula_id=a.id
        WHERE p.aluno_id=? AND p.concluido=1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$aluno_id);
$stmt->execute();
$total_aulas = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

/* ================================
   TOTAL DE CERTIFICADOS
================================== */
$sql = "SELECT COUNT(DISTINCT c.id) AS total
        FROM cursos c
        INNER JOIN inscricoes i ON c.id=i.curso_id
        WHERE i.aluno_id=? 
          AND NOT EXISTS (
             SELECT 1 FROM aulas a 
             LEFT JOIN progresso p 
             ON p.aula_id=a.id AND p.aluno_id=i.aluno_id
             WHERE a.curso_id=c.id AND (p.concluido IS NULL OR p.concluido=0)
          )";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$aluno_id);
$stmt->execute();
$total_certificados = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

/* ================================
   ÚLTIMOS CURSOS MATRICULADOS
================================== */
$sql = "SELECT c.id, c.titulo, c.imagem
        FROM cursos c 
        INNER JOIN inscricoes i ON c.id=i.curso_id 
        WHERE i.aluno_id=? 
        ORDER BY i.id DESC 
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$aluno_id);
$stmt->execute();
$cursos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* ================================
   PROGRESSO POR CURSO
================================== */
$sql = "SELECT 
            c.id, 
            c.titulo,
            (SELECT COUNT(*) FROM aulas a WHERE a.curso_id=c.id) AS total_aulas,
            (SELECT COUNT(*) FROM progresso p 
             INNER JOIN aulas a2 ON p.aula_id=a2.id 
             WHERE p.aluno_id=? AND p.concluido=1 AND a2.curso_id=c.id) AS aulas_concluidas
        FROM cursos c
        INNER JOIN inscricoes i ON c.id=i.curso_id
        WHERE i.aluno_id=?
        ORDER BY c.titulo";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $aluno_id, $aluno_id);
$stmt->execute();
$progresso_cursos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* ================================
   ÚLTIMAS MENSAGENS DO CHAT (por turma)
================================== */
$sql = "SELECT m.*, u.nome as remetente_nome, u.tipo as remetente_tipo, c.titulo as curso_nome
        FROM mensagens m
        INNER JOIN usuarios u ON m.usuario_id=u.id
        INNER JOIN cursos c ON m.curso_id=c.id
        INNER JOIN inscricoes i ON m.curso_id=i.curso_id
        WHERE i.aluno_id=?
        ORDER BY m.data_envio DESC
        LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$aluno_id);
$stmt->execute();
$mensagens = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* ================================
   TOTAL DE NOTIFICAÇÕES
================================== */
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM notificacoes 
    WHERE 
        (
            destinatario_id = ? 
            OR destinatario_tipo = 'aluno' 
            OR destinatario_tipo = 'todos'
        )
");
$stmt->bind_param("i", $aluno_id);
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
<title>Dashboard — Eduka Plus</title>
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

/* Container principal */
.main-dashboard-container {
    display: flex;
    flex-direction: column;
    gap: 2rem;
    margin-bottom: 2.5rem;
}

/* Seção de Progresso */
.progress-section {
    background: var(--surface-light);
    border-radius: var(--radius);
    padding: 1.5rem;
    border: 1px solid var(--border);
    box-shadow: var(--shadow-sm);
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

/* Tabela de Progresso Simples */
.progress-table {
    width: 100%;
    border-collapse: collapse;
    background: var(--surface-light);
    border-radius: var(--radius);
    overflow: hidden;
}

.progress-table thead {
    background: var(--surface-dark);
}

.progress-table th {
    padding: 1rem 1.25rem;
    text-align: left;
    font-weight: 600;
    color: var(--text-secondary);
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid var(--border);
}

.progress-table td {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--border-light);
    color: var(--text-primary);
}

.progress-table tbody tr {
    transition: all 0.2s ease;
}

.progress-table tbody tr:hover {
    background: rgba(37, 99, 235, 0.03);
}

.progress-table tbody tr:last-child td {
    border-bottom: none;
}

.curso-cell {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.curso-icon {
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
}

.curso-info h4 {
    font-size: 0.95rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.progress-bar-container {
    width: 100%;
    height: 8px;
    background: var(--surface-dark);
    border-radius: 4px;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
    border-radius: 4px;
    transition: width 0.6s ease;
}

.progress-percent {
    font-weight: 700;
    color: var(--primary);
    text-align: center;
    min-width: 50px;
}

.progress-stats {
    color: var(--text-secondary);
    font-size: 0.85rem;
    text-align: center;
}

.curso-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
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
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.action-btn:hover {
    background: rgba(37, 99, 235, 0.1);
    color: var(--primary);
    border-color: rgba(37, 99, 235, 0.3);
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

/* Seção de Últimos Cursos */
.latest-courses {
    background: var(--surface-light);
    border-radius: var(--radius);
    padding: 1.5rem;
    border: 1px solid var(--border);
    box-shadow: var(--shadow-sm);
}

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
    background: rgba(37, 99, 235, 0.05);
    border-color: rgba(37, 99, 235, 0.2);
    transform: translateX(4px);
}

.course-image {
    width: 60px;
    height: 60px;
    border-radius: 10px;
    object-fit: cover;
}

.course-details h4 {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.course-details p {
    font-size: 0.85rem;
    color: var(--text-secondary);
}

/* Seção de Chat */
.chat-section {
    margin-top: 2rem;
}

.chat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-light);
}

.chat-header h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.chat-container {
    background: var(--surface-light);
    border-radius: var(--radius);
    border: 1px solid var(--border);
    overflow: hidden;
}

.chat-messages {
    max-height: 300px;
    overflow-y: auto;
    padding: 1.5rem;
}

.empty-chat {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 2rem;
    text-align: center;
    color: var(--text-secondary);
}

.empty-chat i {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    opacity: 0.5;
    color: var(--text-muted);
}

.message-item {
    margin-bottom: 1.25rem;
    display: flex;
    gap: 1rem;
}

.message-item:last-child {
    margin-bottom: 0;
}

.message-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 0.8rem;
    flex-shrink: 0;
}

.message-content {
    flex: 1;
}

.message-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.message-sender {
    font-weight: 600;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.message-sender .badge {
    font-size: 0.7rem;
    padding: 0.15rem 0.5rem;
    border-radius: 12px;
    font-weight: 500;
}

.badge-professor {
    background: rgba(37, 99, 235, 0.1);
    color: var(--primary);
}

.badge-aluno {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success);
}

.message-time {
    font-size: 0.8rem;
    color: var(--text-muted);
}

.message-text {
    background: var(--surface-dark);
    padding: 0.875rem 1rem;
    border-radius: 12px;
    color: var(--text-primary);
    line-height: 1.5;
}

.message-item.professor .message-text {
    background: rgba(37, 99, 235, 0.1);
    border-left: 3px solid var(--primary);
}

.message-item.aluno .message-text {
    background: rgba(16, 185, 129, 0.1);
    border-left: 3px solid var(--success);
}

.chat-form {
    display: flex;
    gap: 1rem;
    padding: 1.5rem;
    border-top: 1px solid var(--border);
    background: var(--surface-dark);
}

.chat-form select,
.chat-form input {
    flex: 1;
    padding: 0.75rem 1rem;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    font-family: 'Inter', sans-serif;
    font-size: 0.95rem;
    color: var(--text-primary);
    background: var(--surface-light);
    transition: all 0.3s ease;
}

.chat-form select:focus,
.chat-form input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.chat-form button {
    background: var(--primary);
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: var(--radius);
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.chat-form button:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
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
    
    .chat-form {
        flex-direction: column;
    }
    
    .progress-table {
        display: block;
        overflow-x: auto;
    }
    
    .progress-table th,
    .progress-table td {
        padding: 0.75rem;
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
    
    .curso-cell {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .curso-actions {
        justify-content: flex-start;
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
            <a href="./dashboard.php" class="nav-item active">
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
            <a href="./progresso.php" class="nav-item">
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
            <h1>👋 Bem-vindo, <?php echo htmlspecialchars($nome_aluno); ?></h1>
            <p>Acompanhe seu progresso e continue aprendendo.</p>
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
                <i class="fas fa-book-open"></i>
            </div>
            <div class="stat-content">
                <h3>Cursos Matriculados</h3>
                <div class="stat-value"><?php echo $total_cursos; ?></div>
                <p class="stat-description">Cursos em que você está inscrito</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <h3>Aulas Concluídas</h3>
                <div class="stat-value"><?php echo $total_aulas; ?></div>
                <p class="stat-description">Progresso geral nas aulas</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-award"></i>
            </div>
            <div class="stat-content">
                <h3>Certificados Obtidos</h3>
                <div class="stat-value"><?php echo $total_certificados; ?></div>
                <p class="stat-description">Cursos concluídos com certificado</p>
            </div>
        </div>
    </div>

    <!-- Container Principal -->
    <div class="main-dashboard-container">
        <!-- Seção de Progresso -->
        <section class="progress-section">
            <div class="section-header">
                <h3><i class="fas fa-chart-line"></i> Progresso por Curso</h3>
                <a href="progresso.php" class="view-all-btn">
                    <i class="fas fa-arrow-right"></i>
                    <span>Ver Detalhes</span>
                </a>
            </div>
            
            <?php if(empty($progresso_cursos)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3>Nenhum curso matriculado</h3>
                    <p>Você ainda não está matriculado em nenhum curso. Explore nossa plataforma e matricule-se nos cursos disponíveis.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="progress-table">
                        <thead>
                            <tr>
                                <th>Curso</th>
                                <th style="width: 200px;">Progresso</th>
                                <th style="width: 100px;">Conclusão</th>
                                <th style="width: 150px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($progresso_cursos as $curso): 
                                $total_aulas_curso = $curso['total_aulas'];
                                $aulas_concluidas = $curso['aulas_concluidas'];
                                $percent = $total_aulas_curso > 0 ? round(($aulas_concluidas / $total_aulas_curso) * 100) : 0;
                            ?>
                            <tr>
                                <td>
                                    <div class="curso-cell">
                                        <div class="curso-icon">
                                            <i class="fas fa-book"></i>
                                        </div>
                                        <div class="curso-info">
                                            <h4><?php echo htmlspecialchars($curso['titulo']); ?></h4>
                                            <div style="font-size: 0.8rem; color: var(--text-muted);">
                                                <?php echo $aulas_concluidas; ?> de <?php echo $total_aulas_curso; ?> aulas
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="progress-bar-container">
                                        <div class="progress-bar" style="width: <?php echo $percent; ?>%;"></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="progress-percent"><?php echo $percent; ?>%</div>
                                </td>
                                <td>
                                    <div class="curso-actions">
                                        <a href="ver_curso.php?id=<?php echo $curso['id']; ?>" class="action-btn primary">
                                            <i class="fas fa-play"></i>
                                            <span>Continuar</span>
                                        </a>
                                        <a href="progresso.php" class="action-btn">
                                            <i class="fas fa-chart-bar"></i>
                                            <span>Detalhes</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <!-- Seção de Últimos Cursos -->
        <section class="latest-courses">
            <div class="section-header">
                <h3><i class="fas fa-graduation-cap"></i> Últimos Cursos</h3>
                <a href="meus_cursos.php" class="view-all-btn">
                    <i class="fas fa-arrow-right"></i>
                    <span>Ver Todos</span>
                </a>
            </div>
            
            <div class="courses-list">
                <?php if(empty($cursos)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <h3>Nenhum curso matriculado</h3>
                        <p>Você ainda não está matriculado em nenhum curso.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($cursos as $c): ?>
                        <a href="ver_curso.php?id=<?php echo $c['id']; ?>" class="course-item">
                            <?php if(!empty($c['imagem'])): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($c['imagem']); ?>" class="course-image" alt="Imagem do curso">
                            <?php else: ?>
                                <div class="course-image" style="background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); display: flex; align-items: center; justify-content: center; color: white;">
                                    <i class="fas fa-book"></i>
                                </div>
                            <?php endif; ?>
                            <div class="course-details">
                                <h4><?php echo htmlspecialchars($c['titulo']); ?></h4>
                                <p>Continuar aprendendo</p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <!-- Chat da Turma -->
    <section class="chat-section">
        <div class="chat-header">
            <h3><i class="fas fa-comments"></i> Chat da Turma</h3>
        </div>
        
        <div class="chat-container">
            <div class="chat-messages">
                <?php if(empty($mensagens)): ?>
                    <div class="empty-chat">
                        <i class="fas fa-comment-slash"></i>
                        <h4>Nenhuma mensagem ainda</h4>
                        <p>Inicie a conversa com seus colegas e professores!</p>
                    </div>
                <?php else: ?>
                    <?php foreach($mensagens as $m): 
                        $iniciais = strtoupper(substr($m['remetente_nome'], 0, 1) . substr($m['remetente_nome'], strpos($m['remetente_nome'], ' ') + 1, 1));
                        $dataEnvio = new DateTime($m['data_envio']);
                        $timeText = $dataEnvio->format('d/m H:i');
                    ?>
                    <div class="message-item <?php echo $m['remetente_tipo']; ?>">
                        <div class="message-avatar">
                            <?php echo $iniciais; ?>
                        </div>
                        <div class="message-content">
                            <div class="message-header">
                                <div class="message-sender">
                                    <?php echo htmlspecialchars($m['remetente_nome']); ?>
                                    <span class="badge badge-<?php echo $m['remetente_tipo']; ?>">
                                        <?php echo $m['remetente_tipo'] == 'professor' ? 'Professor' : 'Aluno'; ?>
                                    </span>
                                    <span style="font-size: 0.85rem; color: var(--text-muted);">(<?php echo htmlspecialchars($m['curso_nome']); ?>)</span>
                                </div>
                                <div class="message-time"><?php echo $timeText; ?></div>
                            </div>
                            <div class="message-text">
                                <?php echo htmlspecialchars($m['mensagem']); ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <form class="chat-form" method="post" action="enviar_mensagem.php">
                <select name="curso_id" required>
                    <option value="">📚 Selecione o curso</option>
                    <?php foreach($cursos as $c): ?>
                        <option value="<?php echo $c['id']; ?>">
                            <?php echo htmlspecialchars($c['titulo']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="mensagem" placeholder="Digite sua mensagem..." required>
                <button type="submit">
                    <i class="fas fa-paper-plane"></i>
                    <span>Enviar</span>
                </button>
            </form>
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

// Animar barras de progresso
function animateProgressBars() {
    const progressBars = document.querySelectorAll('.progress-bar');
    
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

// Scroll automático para as mensagens mais recentes
const chatMessages = document.querySelector('.chat-messages');
if (chatMessages) {
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Validação do formulário de chat
const chatForm = document.querySelector('.chat-form');
if (chatForm) {
    chatForm.addEventListener('submit', function(e) {
        const cursoSelect = this.querySelector('select[name="curso_id"]');
        const messageInput = this.querySelector('input[name="mensagem"]');
        
        if (!cursoSelect.value) {
            e.preventDefault();
            cursoSelect.style.borderColor = 'var(--danger)';
            cursoSelect.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.1)';
            alert('Por favor, selecione um curso para enviar a mensagem.');
        } else if (!messageInput.value.trim()) {
            e.preventDefault();
            messageInput.style.borderColor = 'var(--danger)';
            messageInput.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.1)';
            alert('Por favor, digite uma mensagem.');
        }
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