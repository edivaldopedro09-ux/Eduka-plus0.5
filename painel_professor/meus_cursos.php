<?php
session_start();
require_once("../config.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'professor') {
    header("Location: ../index.php");
    exit();
}

$professor_id = $_SESSION['usuario_id'];

// --- Criar novo curso ---
if (isset($_POST['criar'])) {
    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'];
    $imagem = null;

    if (!empty($_FILES['imagem']['name'])) {
        $ext = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
        $imagem = uniqid() . "." . $ext;
        move_uploaded_file($_FILES['imagem']['tmp_name'], "../uploads/" . $imagem);
    }

    $stmt = $conn->prepare("INSERT INTO cursos (titulo, descricao, imagem, professor_id) VALUES (?,?,?,?)");
    if ($stmt) {
        $stmt->bind_param("sssi", $titulo, $descricao, $imagem, $professor_id);
        $stmt->execute();
        $stmt->close();
        header("Location: meus_cursos.php");
        exit();
    } else {
        die("Erro ao criar curso: " . $conn->error);
    }
}

// --- Editar curso ---
if (isset($_POST['editar'])) {
    $id = $_POST['id'];
    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'];

    if (!empty($_FILES['imagem']['name'])) {
        $ext = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
        $imagem = uniqid() . "." . $ext;
        move_uploaded_file($_FILES['imagem']['tmp_name'], "../uploads/" . $imagem);

        $stmt = $conn->prepare("UPDATE cursos SET titulo=?, descricao=?, imagem=? WHERE id=? AND professor_id=?");
        if ($stmt) $stmt->bind_param("sssii", $titulo, $descricao, $imagem, $id, $professor_id);
    } else {
        $stmt = $conn->prepare("UPDATE cursos SET titulo=?, descricao=? WHERE id=? AND professor_id=?");
        if ($stmt) $stmt->bind_param("ssii", $titulo, $descricao, $id, $professor_id);
    }

    if ($stmt) {
        $stmt->execute();
        $stmt->close();
        header("Location: meus_cursos.php");
        exit();
    } else {
        die("Erro ao editar curso: " . $conn->error);
    }
}

// --- Excluir curso ---
if (isset($_GET['excluir'])) {
    $id = $_GET['excluir'];
    $stmt = $conn->prepare("DELETE FROM cursos WHERE id=? AND professor_id=?");
    if ($stmt) {
        $stmt->bind_param("ii", $id, $professor_id);
        $stmt->execute();
        $stmt->close();
        header("Location: meus_cursos.php");
        exit();
    } else {
        die("Erro ao excluir curso: " . $conn->error);
    }
}

// --- Listar cursos ---
$cursos = $conn->query("SELECT * FROM cursos WHERE professor_id=$professor_id");
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Meus Cursos - Eduka Plus</title>
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

/* Formulário de Criar Curso */
.create-course-section {
    background: var(--surface-light);
    border-radius: var(--radius);
    padding: 2rem;
    margin-bottom: 2.5rem;
    border: 1px solid var(--border);
    box-shadow: var(--shadow-sm);
}

.section-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
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

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-label {
    font-weight: 600;
    color: var(--text-primary);
    font-size: 0.95rem;
}

.form-label span {
    color: var(--danger);
}

.form-input,
.form-textarea {
    padding: 0.875rem 1rem;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    font-family: 'Inter', sans-serif;
    font-size: 0.95rem;
    color: var(--text-primary);
    background: var(--surface-light);
    transition: all 0.3s ease;
}

.form-input:focus,
.form-textarea:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-textarea {
    min-height: 120px;
    resize: vertical;
}

.file-upload {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    border: 2px dashed var(--border);
    border-radius: var(--radius);
    background: var(--surface-dark);
    cursor: pointer;
    transition: all 0.3s ease;
}

.file-upload:hover {
    border-color: var(--primary);
    background: rgba(37, 99, 235, 0.05);
}

.file-upload input[type="file"] {
    position: absolute;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}

.file-upload-content {
    text-align: center;
    color: var(--text-secondary);
}

.file-upload-content i {
    font-size: 2rem;
    margin-bottom: 0.75rem;
    color: var(--primary);
    opacity: 0.7;
}

.file-upload-text {
    font-size: 0.9rem;
    margin-top: 0.5rem;
}

.file-upload-text small {
    color: var(--text-muted);
    font-size: 0.8rem;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border-light);
}

.submit-btn {
    background: var(--primary);
    color: white;
    border: none;
    padding: 0.875rem 2rem;
    border-radius: var(--radius);
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.submit-btn:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(37, 99, 235, 0.3);
}

.cancel-btn {
    background: var(--surface-light);
    color: var(--text-secondary);
    border: 1px solid var(--border);
    padding: 0.875rem 2rem;
    border-radius: var(--radius);
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
}

.cancel-btn:hover {
    background: var(--surface-dark);
    color: var(--text-primary);
}

/* Lista de Cursos */
.courses-section {
    margin-top: 2rem;
}

.courses-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.courses-header h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.courses-count {
    background: var(--surface-dark);
    color: var(--text-secondary);
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
}

.courses-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
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
    overflow: hidden;
    border: 1px solid var(--border);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    opacity: 0;
    transform: translateY(20px);
    box-shadow: var(--shadow-sm);
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

.course-image-container {
    position: relative;
    height: 180px;
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
    color: var(--primary);
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    box-shadow: var(--shadow-sm);
}

.course-content {
    padding: 1.5rem;
}

.course-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
    color: var(--text-primary);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.course-description {
    color: var(--text-secondary);
    font-size: 0.95rem;
    margin-bottom: 1.25rem;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Formulário de Edição */
.edit-form {
    background: var(--surface-dark);
    border-radius: var(--radius);
    padding: 1.25rem;
    margin-top: 1rem;
    border: 1px solid var(--border);
    display: none;
}

.edit-form.active {
    display: block;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.edit-form .form-group {
    margin-bottom: 1rem;
}

.edit-form .form-input,
.edit-form .form-textarea {
    background: var(--surface-light);
}

.edit-form-actions {
    display: flex;
    gap: 0.75rem;
    margin-top: 1rem;
}

.save-btn {
    background: var(--success);
    color: white;
    border: none;
    padding: 0.6rem 1.25rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.3s ease;
    flex: 1;
}

.save-btn:hover {
    background: #0da271;
}

.cancel-edit-btn {
    background: var(--surface-light);
    color: var(--text-secondary);
    border: 1px solid var(--border);
    padding: 0.6rem 1.25rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.3s ease;
    flex: 1;
    text-align: center;
    text-decoration: none;
}

.cancel-edit-btn:hover {
    background: var(--surface-dark);
    color: var(--text-primary);
}

/* Ações do Card */
.course-actions {
    display: flex;
    gap: 0.75rem;
    margin-top: 1rem;
}

.action-btn {
    flex: 1;
    padding: 0.6rem;
    border-radius: 8px;
    border: 1px solid var(--border);
    background: var(--surface-dark);
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 0.85rem;
    text-align: center;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
    font-weight: 500;
    cursor: pointer;
}

.action-btn:hover {
    background: rgba(37, 99, 235, 0.1);
    color: var(--primary);
    border-color: rgba(37, 99, 235, 0.3);
    transform: translateY(-2px);
}

.action-btn.edit-btn {
    background: rgba(245, 158, 11, 0.1);
    border-color: rgba(245, 158, 11, 0.2);
    color: var(--warning);
}

.action-btn.edit-btn:hover {
    background: rgba(245, 158, 11, 0.2);
    border-color: var(--warning);
}

.action-btn.delete-btn {
    background: rgba(239, 68, 68, 0.1);
    border-color: rgba(239, 68, 68, 0.2);
    color: var(--danger);
}

.action-btn.delete-btn:hover {
    background: rgba(239, 68, 68, 0.2);
    border-color: var(--danger);
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

/* Modal de Confirmação */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal-overlay.active {
    display: flex;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal {
    background: var(--surface-light);
    border-radius: var(--radius);
    padding: 2rem;
    max-width: 450px;
    width: 90%;
    box-shadow: var(--shadow-xl);
    animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.25rem;
}

.modal-header i {
    font-size: 1.5rem;
    color: var(--danger);
}

.modal-header h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
}

.modal-body {
    margin-bottom: 1.5rem;
    color: var(--text-secondary);
    line-height: 1.6;
}

.modal-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}

.modal-btn {
    padding: 0.75rem 1.5rem;
    border-radius: var(--radius);
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
    font-size: 0.95rem;
}

.modal-btn.cancel {
    background: var(--surface-light);
    color: var(--text-secondary);
    border: 1px solid var(--border);
}

.modal-btn.cancel:hover {
    background: var(--surface-dark);
    color: var(--text-primary);
}

.modal-btn.confirm {
    background: var(--danger);
    color: white;
}

.modal-btn.confirm:hover {
    background: #dc2626;
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

/* Animações */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-in {
    animation: fadeInUp 0.6s ease forwards;
}

/* Responsividade */
@media (max-width: 768px) {
    .main-content {
        padding: 1.25rem;
        padding-top: 4.5rem;
    }
    
    .courses-grid {
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
    
    .form-actions {
        flex-direction: column;
    }
    
    .submit-btn,
    .cancel-btn {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .course-actions {
        flex-direction: column;
    }
    
    .action-btn {
        width: 100%;
    }
    
    .edit-form-actions {
        flex-direction: column;
    }
    
    .save-btn,
    .cancel-edit-btn {
        width: 100%;
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
            <a href="./meus_cursos.php" class="nav-item active">
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
            <h1>📚 Meus Cursos</h1>
            <p>Crie, edite e gerencie todos os seus cursos em um só lugar.</p>
        </div>
        
        <div class="header-actions">
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                <span>Voltar ao Dashboard</span>
            </a>
            <a href="../logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Sair</span>
            </a>
        </div>
    </header>

    <!-- Formulário para Criar Curso -->
    <section class="create-course-section fade-in">
        <div class="section-title">
            <i class="fas fa-plus-circle"></i>
            <h2>Criar Novo Curso</h2>
        </div>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="titulo">Título do Curso <span>*</span></label>
                    <input type="text" id="titulo" name="titulo" class="form-input" placeholder="Ex: Matemática Básica" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="imagem">Imagem do Curso</label>
                    <div class="file-upload">
                        <input type="file" id="imagem" name="imagem" accept="image/*">
                        <div class="file-upload-content">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Clique para enviar uma imagem</p>
                            <p class="file-upload-text"><small>Formatos: JPG, PNG, GIF (Máx: 2MB)</small></p>
                        </div>
                    </div>
                </div>
                
                <div class="form-group full-width">
                    <label class="form-label" for="descricao">Descrição do Curso <span>*</span></label>
                    <textarea id="descricao" name="descricao" class="form-textarea" placeholder="Descreva o conteúdo, objetivos e público-alvo do curso..." required></textarea>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="reset" class="cancel-btn">Limpar Campos</button>
                <button type="submit" name="criar" class="submit-btn">
                    <i class="fas fa-plus"></i>
                    <span>Criar Curso</span>
                </button>
            </div>
        </form>
    </section>

    <!-- Lista de Cursos -->
    <section class="courses-section">
        <div class="courses-header">
            <h2><i class="fas fa-list"></i> Meus Cursos Criados</h2>
            <span class="courses-count">
                <?php 
                    $num_cursos = $cursos->num_rows;
                    echo $num_cursos . ($num_cursos == 1 ? ' curso' : ' cursos');
                ?>
            </span>
        </div>
        
        <div class="courses-grid">
            <?php if($cursos->num_rows > 0): ?>
                <?php while($curso = $cursos->fetch_assoc()): ?>
                    <div class="course-card fade-in" id="course-<?php echo $curso['id']; ?>">
                        <div class="course-image-container">
                            <?php if($curso['imagem'] && file_exists('../uploads/'.$curso['imagem'])): ?>
                                <img src="../uploads/<?php echo $curso['imagem']; ?>" class="course-image" alt="Imagem do curso">
                            <?php else: ?>
                                <img src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" class="course-image" alt="Imagem padrão do curso">
                            <?php endif; ?>
                            <div class="course-badge">
                                ID: <?php echo $curso['id']; ?>
                            </div>
                        </div>
                        
                        <div class="course-content">
                            <h3 class="course-title"><?php echo htmlspecialchars($curso['titulo']); ?></h3>
                            <p class="course-description"><?php echo htmlspecialchars($curso['descricao']); ?></p>
                            
                            <!-- Formulário de Edição (oculto por padrão) -->
                            <div class="edit-form" id="edit-form-<?php echo $curso['id']; ?>">
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="id" value="<?php echo $curso['id']; ?>">
                                    
                                    <div class="form-group">
                                        <label class="form-label">Título</label>
                                        <input type="text" name="titulo" class="form-input" value="<?php echo htmlspecialchars($curso['titulo']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Descrição</label>
                                        <textarea name="descricao" class="form-textarea" required><?php echo htmlspecialchars($curso['descricao']); ?></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Alterar Imagem</label>
                                        <input type="file" name="imagem" class="form-input" accept="image/*">
                                    </div>
                                    
                                    <div class="edit-form-actions">
                                        <button type="submit" name="editar" class="save-btn">
                                            <i class="fas fa-save"></i> Salvar Alterações
                                        </button>
                                        <a href="#" class="cancel-edit-btn" onclick="hideEditForm(<?php echo $curso['id']; ?>)">
                                            Cancelar
                                        </a>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- Ações do Card -->
                            <div class="course-actions">
                                <button class="action-btn edit-btn" onclick="showEditForm(<?php echo $curso['id']; ?>)">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <a href="#" class="action-btn delete-btn" onclick="confirmDelete(<?php echo $curso['id']; ?>, '<?php echo htmlspecialchars(addslashes($curso['titulo'])); ?>')">
                                    <i class="fas fa-trash"></i> Excluir
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state fade-in">
                    <div class="empty-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3>Nenhum curso criado ainda</h3>
                    <p>Você ainda não criou nenhum curso. Use o formulário acima para criar seu primeiro curso e começar a compartilhar conhecimento.</p>
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

<!-- Modal de Confirmação de Exclusão -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal">
        <div class="modal-header">
            <i class="fas fa-exclamation-triangle"></i>
            <h3>Confirmar Exclusão</h3>
        </div>
        <div class="modal-body" id="modalMessage">
            Tem certeza que deseja excluir este curso? Esta ação não pode ser desfeita.
        </div>
        <div class="modal-actions">
            <button class="modal-btn cancel" onclick="closeModal()">Cancelar</button>
            <a href="#" class="modal-btn confirm" id="confirmDeleteBtn">Excluir Curso</a>
        </div>
    </div>
</div>

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

// Mostrar/ocultar formulário de edição
function showEditForm(courseId) {
    // Esconder todos os formulários de edição
    document.querySelectorAll('.edit-form').forEach(form => {
        form.classList.remove('active');
    });
    
    // Mostrar o formulário específico
    const editForm = document.getElementById(`edit-form-${courseId}`);
    if (editForm) {
        editForm.classList.add('active');
        
        // Rolar até o formulário
        editForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

function hideEditForm(courseId) {
    const editForm = document.getElementById(`edit-form-${courseId}`);
    if (editForm) {
        editForm.classList.remove('active');
    }
    
    // Prevenir comportamento padrão do link
    event.preventDefault();
    return false;
}

// Modal de confirmação de exclusão
let deleteUrl = '';

function confirmDelete(courseId, courseTitle) {
    const modal = document.getElementById('deleteModal');
    const modalMessage = document.getElementById('modalMessage');
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    
    // Configurar mensagem
    modalMessage.innerHTML = `
        <p>Tem certeza que deseja excluir o curso <strong>"${courseTitle}"</strong>?</p>
        <p style="margin-top: 10px; color: var(--danger); font-size: 0.9rem;">
            <i class="fas fa-exclamation-circle"></i> Esta ação não pode ser desfeita e removerá permanentemente o curso e todos os seus dados associados.
        </p>
    `;
    
    // Configurar URL de exclusão
    deleteUrl = `?excluir=${courseId}`;
    confirmBtn.href = deleteUrl;
    
    // Mostrar modal
    modal.classList.add('active');
    
    // Prevenir comportamento padrão do link
    event.preventDefault();
    return false;
}

function closeModal() {
    const modal = document.getElementById('deleteModal');
    modal.classList.remove('active');
}

// Fechar modal ao clicar fora
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
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

document.querySelectorAll('.course-card, .create-course-section').forEach(el => {
    observer.observe(el);
});

// Preview da imagem selecionada
const fileInput = document.getElementById('imagem');
if (fileInput) {
    fileInput.addEventListener('change', function() {
        const fileUploadContent = document.querySelector('.file-upload-content');
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                fileUploadContent.innerHTML = `
                    <img src="${e.target.result}" style="max-width: 100%; max-height: 120px; border-radius: 8px; margin-bottom: 10px;" alt="Pré-visualização">
                    <p>${fileInput.files[0].name}</p>
                    <p class="file-upload-text"><small>Clique para alterar</small></p>
                `;
            };
            
            reader.readAsDataURL(this.files[0]);
        }
    });
}

// Validação de formulário
const forms = document.querySelectorAll('form');
forms.forEach(form => {
    form.addEventListener('submit', function(e) {
        const requiredFields = this.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.style.borderColor = 'var(--danger)';
                field.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.1)';
            } else {
                field.style.borderColor = '';
                field.style.boxShadow = '';
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Por favor, preencha todos os campos obrigatórios.');
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
</script>
</body>
</html>