<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'professor') {
    header("Location: ../index.php");
    exit();
}

require_once("../config.php");

$professor_id = $_SESSION['usuario_id'];
$msg = "";
$msg_type = "";

// Cursos do professor para o select
$sql_cursos = "SELECT * FROM cursos WHERE professor_id = ? ORDER BY titulo";
$stmt = $conn->prepare($sql_cursos);
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$cursos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Contadores para sidebar
$sql_total_cursos = "SELECT COUNT(*) AS total FROM cursos WHERE professor_id=?";
$stmt = $conn->prepare($sql_total_cursos);
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$total_cursos = $stmt->get_result()->fetch_assoc()['total'];

$sql_total_materiais = "SELECT COUNT(*) AS total FROM materiais m 
                        INNER JOIN cursos c ON m.curso_id = c.id 
                        WHERE c.professor_id=?";
$stmt = $conn->prepare($sql_total_materiais);
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$total_materiais = $stmt->get_result()->fetch_assoc()['total'];

$sql_total_alunos = "SELECT COUNT(*) AS total FROM inscricoes i 
                     JOIN cursos c ON i.curso_id = c.id 
                     WHERE c.professor_id=?";
$stmt = $conn->prepare($sql_total_alunos);
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$total_alunos = $stmt->get_result()->fetch_assoc()['total'];

$sql_notificacoes = "SELECT COUNT(*) AS nao_lidas FROM notificacoes 
                     WHERE destinatario_id=? AND lida=0";
$stmt = $conn->prepare($sql_notificacoes);
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$notificacoes_nao_lidas = $stmt->get_result()->fetch_assoc()['nao_lidas'];

// ➕ Adicionar material
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add'])) {
    $curso_id = intval($_POST['curso_id']);
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao']);

    // Verificar se o curso pertence ao professor
    $sql_verify = "SELECT id FROM cursos WHERE id=? AND professor_id=?";
    $stmt = $conn->prepare($sql_verify);
    $stmt->bind_param("ii", $curso_id, $professor_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] == 0) {
            $arquivo = $_FILES['arquivo']['name'];
            $file_ext = strtolower(pathinfo($arquivo, PATHINFO_EXTENSION));
            $allowed_ext = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'zip', 'rar', 'jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_ext, $allowed_ext)) {
                $unique_name = uniqid() . '_' . time() . '.' . $file_ext;
                $destino = "../uploads/materiais/" . $unique_name;
                
                // Criar diretório se não existir
                if (!file_exists("../uploads/materiais/")) {
                    mkdir("../uploads/materiais/", 0777, true);
                }
                
                if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $destino)) {
                    $sql = "INSERT INTO materiais (curso_id, titulo, descricao, arquivo) VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("isss", $curso_id, $titulo, $descricao, $unique_name);
                    
                    if ($stmt->execute()) {
                        $msg = "Material adicionado com sucesso!";
                        $msg_type = "success";
                    } else {
                        $msg = "Erro ao salvar no banco de dados.";
                        $msg_type = "error";
                    }
                } else {
                    $msg = "Erro ao fazer upload do arquivo.";
                    $msg_type = "error";
                }
            } else {
                $msg = "Tipo de arquivo não permitido. Use: " . implode(', ', $allowed_ext);
                $msg_type = "error";
            }
        } else {
            $msg = "Por favor, selecione um arquivo.";
            $msg_type = "error";
        }
    } else {
        $msg = "Curso não encontrado ou não pertence a você.";
        $msg_type = "error";
    }
}

// ✏️ Editar material
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit'])) {
    $id = intval($_POST['id']);
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao']);

    // Verificar se o material pertence ao professor
    $sql_verify = "SELECT m.id FROM materiais m 
                   INNER JOIN cursos c ON m.curso_id = c.id 
                   WHERE m.id=? AND c.professor_id=?";
    $stmt = $conn->prepare($sql_verify);
    $stmt->bind_param("ii", $id, $professor_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] == 0) {
            $arquivo = $_FILES['arquivo']['name'];
            $file_ext = strtolower(pathinfo($arquivo, PATHINFO_EXTENSION));
            $allowed_ext = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'zip', 'rar', 'jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_ext, $allowed_ext)) {
                $unique_name = uniqid() . '_' . time() . '.' . $file_ext;
                $destino = "../uploads/materiais/" . $unique_name;
                
                if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $destino)) {
                    $sql = "UPDATE materiais SET titulo=?, descricao=?, arquivo=? WHERE id=?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sssi", $titulo, $descricao, $unique_name, $id);
                }
            } else {
                $msg = "Tipo de arquivo não permitido.";
                $msg_type = "error";
                goto skip_edit;
            }
        } else {
            $sql = "UPDATE materiais SET titulo=?, descricao=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $titulo, $descricao, $id);
        }
        
        if ($stmt->execute()) {
            $msg = "Material atualizado com sucesso!";
            $msg_type = "success";
        } else {
            $msg = "Erro ao atualizar o material.";
            $msg_type = "error";
        }
    } else {
        $msg = "Material não encontrado ou não pertence a você.";
        $msg_type = "error";
    }
    
    skip_edit:
}

// ❌ Excluir material
if (isset($_GET['del'])) {
    $id = intval($_GET['del']);
    
    // Verificar se o material pertence ao professor antes de excluir
    $sql_verify = "SELECT m.arquivo FROM materiais m 
                   INNER JOIN cursos c ON m.curso_id = c.id 
                   WHERE m.id=? AND c.professor_id=?";
    $stmt = $conn->prepare($sql_verify);
    $stmt->bind_param("ii", $id, $professor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $material = $result->fetch_assoc();
        
        // Excluir arquivo físico
        $file_path = "../uploads/materiais/" . $material['arquivo'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Excluir do banco de dados
        $sql = "DELETE FROM materiais WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $msg = "Material excluído com sucesso!";
            $msg_type = "success";
        } else {
            $msg = "Erro ao excluir o material.";
            $msg_type = "error";
        }
    } else {
        $msg = "Material não encontrado ou não pertence a você.";
        $msg_type = "error";
    }
}

// Listar materiais com filtros
$filter_curso = isset($_GET['curso']) ? intval($_GET['curso']) : 0;
$filter_busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';

$sql_m = "SELECT m.*, c.titulo AS curso_nome, c.id AS curso_id 
          FROM materiais m 
          INNER JOIN cursos c ON m.curso_id = c.id
          WHERE c.professor_id = ?";
          
$params = [$professor_id];
$types = "i";

if ($filter_curso > 0) {
    $sql_m .= " AND m.curso_id = ?";
    $params[] = $filter_curso;
    $types .= "i";
}

if (!empty($filter_busca)) {
    $sql_m .= " AND (m.titulo LIKE ? OR m.descricao LIKE ?)";
    $search_term = "%$filter_busca%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

$sql_m .= " ORDER BY m.data_upload DESC";

$stmt = $conn->prepare($sql_m);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$materiais = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Materiais — Eduka Plus Angola</title>
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

.nav-badge {
  margin-left: auto;
  background: var(--primary);
  color: white;
  padding: 0.25rem 0.5rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  min-width: 20px;
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
  margin-bottom: 2rem;
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

/* Mensagens de feedback */
.message {
  padding: 1rem 1.5rem;
  border-radius: var(--radius);
  margin-bottom: 2rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  animation: fadeIn 0.5s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}

.message.success {
  background: rgba(16, 185, 129, 0.1);
  border: 1px solid rgba(16, 185, 129, 0.2);
  color: var(--success);
}

.message.error {
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.2);
  color: var(--danger);
}

.message i {
  font-size: 1.2rem;
}

/* Filtros */
.filters-section {
  background: var(--surface-light);
  border-radius: var(--radius);
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

.filters-header h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-primary);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.filters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.filter-label {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--text-secondary);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.filter-select, .filter-input {
  padding: 0.75rem;
  border: 1px solid var(--border);
  border-radius: 8px;
  background: white;
  color: var(--text-primary);
  font-size: 0.95rem;
  transition: all 0.3s ease;
}

.filter-select:focus, .filter-input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.filter-actions {
  display: flex;
  gap: 1rem;
  align-items: flex-end;
}

.filter-btn {
  background: var(--surface-dark);
  border: 1px solid var(--border);
  color: var(--text-secondary);
  padding: 0.75rem 1.5rem;
  border-radius: var(--radius);
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.filter-btn:hover {
  background: var(--primary);
  color: white;
  border-color: var(--primary);
}

.filter-btn.reset {
  background: transparent;
  color: var(--danger);
  border-color: var(--danger);
}

.filter-btn.reset:hover {
  background: var(--danger);
  color: white;
}

/* Formulário de Novo Material */
.new-material-form {
  background: var(--surface-light);
  border-radius: var(--radius);
  padding: 2rem;
  margin-bottom: 2.5rem;
  border: 1px solid var(--border);
  box-shadow: var(--shadow-md);
}

.form-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--border-light);
}

.form-header h3 {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--text-primary);
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.form-toggle {
  background: var(--surface-dark);
  border: 1px solid var(--border);
  color: var(--text-secondary);
  padding: 0.5rem 1rem;
  border-radius: var(--radius);
  cursor: pointer;
  font-size: 0.9rem;
  transition: all 0.3s ease;
}

.form-toggle:hover {
  background: var(--primary);
  color: white;
  border-color: var(--primary);
}

.form-content {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.form-group label {
  font-size: 0.95rem;
  font-weight: 600;
  color: var(--text-secondary);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.form-group label i {
  color: var(--primary);
}

.form-group.full-width {
  grid-column: 1 / -1;
}

.form-input, .form-textarea, .form-file {
  padding: 0.875rem;
  border: 1px solid var(--border);
  border-radius: 8px;
  background: white;
  color: var(--text-primary);
  font-size: 0.95rem;
  transition: all 0.3s ease;
  font-family: 'Inter', sans-serif;
}

.form-input:focus, .form-textarea:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-textarea {
  min-height: 120px;
  resize: vertical;
}

.form-file {
  padding: 1rem;
  background: var(--surface-dark);
  cursor: pointer;
}

.form-file:hover {
  background: var(--surface);
}

.form-actions {
  grid-column: 1 / -1;
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 1rem;
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
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.submit-btn:hover {
  background: var(--primary-dark);
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.cancel-btn {
  background: var(--surface-dark);
  color: var(--text-secondary);
  border: 1px solid var(--border);
  padding: 0.875rem 2rem;
  border-radius: var(--radius);
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  transition: all 0.3s ease;
}

.cancel-btn:hover {
  background: var(--surface);
  color: var(--text-primary);
}

/* Grid de Materiais */
.materials-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
}

.material-card {
  background: var(--surface-light);
  border-radius: var(--radius);
  overflow: hidden;
  border: 1px solid var(--border);
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: var(--shadow-sm);
  opacity: 0;
  transform: translateY(20px);
}

.material-card.show {
  opacity: 1;
  transform: translateY(0);
}

.material-card:hover {
  transform: translateY(-5px);
  box-shadow: var(--shadow-lg);
  border-color: rgba(37, 99, 235, 0.2);
}

.material-header {
  background: linear-gradient(135deg, rgba(37, 99, 235, 0.1) 0%, rgba(14, 165, 233, 0.1) 100%);
  padding: 1.25rem;
  border-bottom: 1px solid var(--border-light);
}

.material-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 0.5rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-height: 1.4;
}

.material-course {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  color: var(--text-secondary);
}

.material-course i {
  color: var(--primary);
}

.material-content {
  padding: 1.25rem;
}

.material-description {
  color: var(--text-secondary);
  font-size: 0.95rem;
  line-height: 1.5;
  margin-bottom: 1.25rem;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.material-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--border-light);
}

.file-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.file-icon {
  width: 48px;
  height: 48px;
  border-radius: 8px;
  background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.25rem;
}

.file-details h4 {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 0.25rem;
}

.file-details p {
  font-size: 0.8rem;
  color: var(--text-secondary);
}

.upload-date {
  text-align: right;
}

.upload-date .label {
  font-size: 0.8rem;
  color: var(--text-secondary);
  margin-bottom: 0.25rem;
}

.upload-date .date {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--text-primary);
}

.material-actions {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem;
}

.action-btn {
  padding: 0.75rem;
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
  gap: 0.5rem;
  font-weight: 500;
}

.action-btn:hover {
  background: rgba(37, 99, 235, 0.1);
  color: var(--primary);
  border-color: rgba(37, 99, 235, 0.3);
  transform: translateY(-2px);
}

.action-btn.download {
  background: var(--primary);
  color: white;
  border-color: var(--primary);
}

.action-btn.download:hover {
  background: var(--primary-dark);
}

.action-btn.edit {
  background: rgba(245, 158, 11, 0.1);
  color: var(--warning);
  border-color: rgba(245, 158, 11, 0.2);
}

.action-btn.edit:hover {
  background: rgba(245, 158, 11, 0.2);
}

.action-btn.delete {
  background: rgba(239, 68, 68, 0.1);
  color: var(--danger);
  border-color: rgba(239, 68, 68, 0.2);
}

.action-btn.delete:hover {
  background: rgba(239, 68, 68, 0.2);
}

/* Modal de Edição */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  opacity: 0;
  visibility: hidden;
  transition: all 0.3s ease;
  backdrop-filter: blur(4px);
}

.modal-overlay.active {
  opacity: 1;
  visibility: visible;
}

.modal-content {
  background: white;
  border-radius: var(--radius);
  padding: 2rem;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  transform: translateY(20px);
  transition: transform 0.3s ease;
  box-shadow: var(--shadow-xl);
}

.modal-overlay.active .modal-content {
  transform: translateY(0);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--border-light);
}

.modal-header h3 {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--text-primary);
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.modal-close {
  background: none;
  border: none;
  color: var(--text-secondary);
  font-size: 1.5rem;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 50%;
  transition: all 0.3s ease;
  line-height: 1;
}

.modal-close:hover {
  background: var(--surface-dark);
  color: var(--danger);
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
  margin: 2rem 0;
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
  margin-bottom: 1rem;
  color: var(--text-primary);
}

.empty-state p {
  color: var(--text-secondary);
  margin-bottom: 2rem;
  max-width: 500px;
  line-height: 1.6;
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
  .materials-grid {
    grid-template-columns: 1fr;
  }
  
  .filters-grid {
    grid-template-columns: 1fr;
  }
  
  .form-content {
    grid-template-columns: 1fr;
  }
  
  .material-actions {
    grid-template-columns: 1fr;
  }
  
  .header-actions {
    flex-direction: column;
    gap: 1rem;
  }
  
  .filters-header {
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
  }
  
  .filter-actions {
    width: 100%;
    justify-content: space-between;
  }
}

@media (max-width: 480px) {
  .main-header {
    flex-direction: column;
    gap: 1.5rem;
    align-items: flex-start;
  }
  
  .form-actions {
    flex-direction: column;
  }
  
  .submit-btn, .cancel-btn {
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

/* Utilitários */
.hidden {
  display: none;
}

/* Icones por tipo de arquivo */
.file-icon.pdf { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
.file-icon.word { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); }
.file-icon.excel { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.file-icon.powerpoint { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
.file-icon.image { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); }
.file-icon.zip { background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%); }
.file-icon.default { background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); }
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
        <span class="nav-badge"><?php echo $total_cursos; ?></span>
      </a>
      <a href="./materiais.php" class="nav-item active">
        <span class="nav-icon"><i class="fas fa-folder-open"></i></span>
        <span>Materiais</span>
        <span class="nav-badge"><?php echo $total_materiais; ?></span>
      </a>
      <a href="./alunos_matriculados.php" class="nav-item">
        <span class="nav-icon"><i class="fas fa-user-friends"></i></span>
        <span>Alunos</span>
        <span class="nav-badge"><?php echo $total_alunos; ?></span>
      </a>
      <a href="./aulas.php" class="nav-item">
        <span class="nav-icon"><i class="fas fa-video"></i></span>
        <span>Aulas</span>
      </a>
      <a href="./certificados.php" class="nav-item">
        <span class="nav-icon"><i class="fas fa-certificate"></i></span>
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
      <h1>Gerenciar Materiais</h1>
      <p>Adicione, edite e gerencie materiais complementares para seus cursos.</p>
    </div>
    
    <div class="header-actions">
      <a href="./notificacoes.php" class="notification-btn">
        <i class="fas fa-bell"></i>
        <?php if($notificacoes_nao_lidas > 0): ?>
          <span class="notification-badge"><?php echo $notificacoes_nao_lidas; ?></span>
        <?php endif; ?>
      </a>
      <a href="../logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i>
        <span>Sair</span>
      </a>
    </div>
  </header>

  <!-- Mensagem de Feedback -->
  <?php if($msg): ?>
    <div class="message <?php echo $msg_type; ?> fade-in">
      <i class="fas fa-<?php echo $msg_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
      <span><?php echo $msg; ?></span>
    </div>
  <?php endif; ?>

  <!-- Filtros -->
  <div class="filters-section fade-in" style="animation-delay: 0.1s">
    <div class="filters-header">
      <h3><i class="fas fa-filter"></i> Filtrar Materiais</h3>
      <span class="filter-label"><?php echo count($materiais); ?> materiais encontrados</span>
    </div>
    
    <form method="GET" class="filters-grid">
      <div class="filter-group">
        <label class="filter-label"><i class="fas fa-book"></i> Curso</label>
        <select class="filter-select" name="curso">
          <option value="0">Todos os cursos</option>
          <?php foreach($cursos as $curso): ?>
            <option value="<?php echo $curso['id']; ?>" <?php echo $filter_curso == $curso['id'] ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($curso['titulo']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      
      <div class="filter-group">
        <label class="filter-label"><i class="fas fa-search"></i> Buscar</label>
        <input type="text" class="filter-input" name="busca" placeholder="Título ou descrição..." value="<?php echo htmlspecialchars($filter_busca); ?>">
      </div>
      
      <div class="filter-actions">
        <button type="submit" class="filter-btn">
          <i class="fas fa-filter"></i>
          Aplicar Filtros
        </button>
        <a href="materiais.php" class="filter-btn reset">
          <i class="fas fa-redo"></i>
          Limpar
        </a>
      </div>
    </form>
  </div>

  <!-- Formulário de Novo Material -->
  <div class="new-material-form fade-in" style="animation-delay: 0.2s">
    <div class="form-header">
      <h3><i class="fas fa-plus-circle"></i> Adicionar Novo Material</h3>
      <button type="button" class="form-toggle" onclick="toggleForm()">
        <i class="fas fa-chevron-down"></i>
        Mostrar/Ocultar
      </button>
    </div>
    
    <form method="POST" enctype="multipart/form-data" id="newMaterialForm" class="hidden">
      <div class="form-content">
        <div class="form-group">
          <label><i class="fas fa-book"></i> Curso</label>
          <select class="form-input" name="curso_id" required>
            <?php foreach($cursos as $curso): ?>
              <option value="<?php echo $curso['id']; ?>"><?php echo htmlspecialchars($curso['titulo']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div class="form-group">
          <label><i class="fas fa-heading"></i> Título do Material</label>
          <input type="text" class="form-input" name="titulo" placeholder="Ex: Apresentação da Aula 1" required>
        </div>
        
        <div class="form-group full-width">
          <label><i class="fas fa-align-left"></i> Descrição</label>
          <textarea class="form-textarea" name="descricao" placeholder="Descreva o conteúdo deste material..."></textarea>
        </div>
        
        <div class="form-group">
          <label><i class="fas fa-file-upload"></i> Arquivo</label>
          <input type="file" class="form-file" name="arquivo" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.zip,.rar,.jpg,.jpeg,.png,.gif" required>
          <small style="color: var(--text-muted); margin-top: 0.5rem; font-size: 0.85rem;">
            Formatos permitidos: PDF, Word, Excel, PowerPoint, imagens, arquivos compactados
          </small>
        </div>
        
        <div class="form-actions">
          <button type="button" class="cancel-btn" onclick="toggleForm()">
            <i class="fas fa-times"></i>
            Cancelar
          </button>
          <button type="submit" name="add" class="submit-btn">
            <i class="fas fa-save"></i>
            Salvar Material
          </button>
        </div>
      </div>
    </form>
  </div>

  <!-- Grid de Materiais -->
  <div class="materials-section">
    <div class="filters-header" style="margin-bottom: 1.5rem;">
      <h3><i class="fas fa-folder-open"></i> Meus Materiais</h3>
      <span class="filter-label"><?php echo count($materiais); ?> materiais encontrados</span>
    </div>
    
    <?php if(count($materiais) > 0): ?>
      <div class="materials-grid">
        <?php foreach($materiais as $material): 
          $file_ext = strtolower(pathinfo($material['arquivo'], PATHINFO_EXTENSION));
          $file_icon = 'default';
          
          if (in_array($file_ext, ['pdf'])) $file_icon = 'pdf';
          elseif (in_array($file_ext, ['doc', 'docx'])) $file_icon = 'word';
          elseif (in_array($file_ext, ['xls', 'xlsx'])) $file_icon = 'excel';
          elseif (in_array($file_ext, ['ppt', 'pptx'])) $file_icon = 'powerpoint';
          elseif (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) $file_icon = 'image';
          elseif (in_array($file_ext, ['zip', 'rar'])) $file_icon = 'zip';
        ?>
          <div class="material-card fade-in">
            <div class="material-header">
              <h3 class="material-title"><?php echo htmlspecialchars($material['titulo']); ?></h3>
              <div class="material-course">
                <i class="fas fa-book"></i>
                <span><?php echo htmlspecialchars($material['curso_nome']); ?></span>
              </div>
            </div>
            
            <div class="material-content">
              <?php if(!empty($material['descricao'])): ?>
                <p class="material-description"><?php echo htmlspecialchars($material['descricao']); ?></p>
              <?php endif; ?>
              
              <div class="material-info">
                <div class="file-info">
                  <div class="file-icon <?php echo $file_icon; ?>">
                    <?php 
                      switch($file_icon) {
                        case 'pdf': echo '<i class="fas fa-file-pdf"></i>'; break;
                        case 'word': echo '<i class="fas fa-file-word"></i>'; break;
                        case 'excel': echo '<i class="fas fa-file-excel"></i>'; break;
                        case 'powerpoint': echo '<i class="fas fa-file-powerpoint"></i>'; break;
                        case 'image': echo '<i class="fas fa-file-image"></i>'; break;
                        case 'zip': echo '<i class="fas fa-file-archive"></i>'; break;
                        default: echo '<i class="fas fa-file"></i>';
                      }
                    ?>
                  </div>
                  <div class="file-details">
                    <h4><?php echo strtoupper($file_ext); ?> • <?php 
                      $file_path = "../uploads/materiais/" . $material['arquivo'];
                      if (file_exists($file_path)) {
                        $size = filesize($file_path);
                        if ($size < 1024) echo $size . ' B';
                        elseif ($size < 1048576) echo round($size/1024, 1) . ' KB';
                        else echo round($size/1048576, 1) . ' MB';
                      } else {
                        echo 'N/A';
                      }
                    ?></h4>
                    <p><?php echo htmlspecialchars($material['arquivo']); ?></p>
                  </div>
                </div>
                
                <div class="upload-date">
                  <div class="label">Enviado em</div>
                  <div class="date"><?php echo date('d/m/Y', strtotime($material['data_upload'])); ?></div>
                </div>
              </div>
              
              <div class="material-actions">
                <a href="../uploads/materiais/<?php echo $material['arquivo']; ?>" 
                   download 
                   class="action-btn download">
                  <i class="fas fa-download"></i>
                  Baixar
                </a>
                
                <button type="button" 
                        class="action-btn edit" 
                        onclick="openEditModal(<?php echo htmlspecialchars(json_encode($material)); ?>)">
                  <i class="fas fa-edit"></i>
                  Editar
                </button>
                
                <a href="?del=<?php echo $material['id']; ?>" 
                   onclick="return confirm('Tem certeza que deseja excluir este material?')"
                   class="action-btn delete">
                  <i class="fas fa-trash"></i>
                  Excluir
                </a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty-state fade-in">
        <div class="empty-icon">
          <i class="fas fa-folder-open"></i>
        </div>
        <h3>Nenhum material encontrado</h3>
        <p>Você ainda não adicionou materiais aos seus cursos. Comece adicionando seu primeiro material usando o formulário acima.</p>
        <button type="button" class="submit-btn" onclick="toggleForm()" style="margin-top: 1rem;">
          <i class="fas fa-plus-circle"></i>
          Adicionar Primeiro Material
        </button>
      </div>
    <?php endif; ?>
  </div>

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

<!-- Modal de Edição -->
<div class="modal-overlay" id="editModal">
  <div class="modal-content">
    <div class="modal-header">
      <h3><i class="fas fa-edit"></i> Editar Material</h3>
      <button type="button" class="modal-close" onclick="closeEditModal()">
        <i class="fas fa-times"></i>
      </button>
    </div>
    
    <form method="POST" enctype="multipart/form-data" id="editForm">
      <input type="hidden" name="id" id="edit_id">
      
      <div class="form-content">
        <div class="form-group">
          <label><i class="fas fa-heading"></i> Título do Material</label>
          <input type="text" class="form-input" name="titulo" id="edit_titulo" required>
        </div>
        
        <div class="form-group full-width">
          <label><i class="fas fa-align-left"></i> Descrição</label>
          <textarea class="form-textarea" name="descricao" id="edit_descricao"></textarea>
        </div>
        
        <div class="form-group">
          <label><i class="fas fa-file-upload"></i> Novo Arquivo (opcional)</label>
          <input type="file" class="form-file" name="arquivo" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.zip,.rar,.jpg,.jpeg,.png,.gif">
          <small style="color: var(--text-muted); margin-top: 0.5rem; font-size: 0.85rem;">
            Deixe em branco para manter o arquivo atual
          </small>
        </div>
        
        <div class="form-actions">
          <button type="button" class="cancel-btn" onclick="closeEditModal()">
            <i class="fas fa-times"></i>
            Cancelar
          </button>
          <button type="submit" name="edit" class="submit-btn">
            <i class="fas fa-save"></i>
            Salvar Alterações
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
// Toggle sidebar mobile
const btnMenu = document.getElementById('btnToggleMenu');
const sidebar = document.getElementById('sidebar');

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

// Alternar visibilidade do formulário
function toggleForm() {
  const form = document.getElementById('newMaterialForm');
  const toggleBtn = document.querySelector('.form-toggle i');
  
  form.classList.toggle('hidden');
  
  if (form.classList.contains('hidden')) {
    toggleBtn.className = 'fas fa-chevron-down';
  } else {
    toggleBtn.className = 'fas fa-chevron-up';
    form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }
}

// Modal de edição
function openEditModal(material) {
  document.getElementById('edit_id').value = material.id;
  document.getElementById('edit_titulo').value = material.titulo;
  document.getElementById('edit_descricao').value = material.descricao || '';
  document.getElementById('editModal').classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeEditModal() {
  document.getElementById('editModal').classList.remove('active');
  document.body.style.overflow = 'auto';
}

// Fechar modal ao clicar fora
document.getElementById('editModal').addEventListener('click', function(e) {
  if (e.target === this) {
    closeEditModal();
  }
});

// Fechar modal com ESC
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeEditModal();
  }
});

// Animar cards ao rolar
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

document.querySelectorAll('.material-card, .fade-in').forEach(el => {
  observer.observe(el);
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

// Limpar mensagem após 5 segundos
setTimeout(() => {
  const message = document.querySelector('.message');
  if (message) {
    message.style.opacity = '0';
    setTimeout(() => message.remove(), 300);
  }
}, 5000);

// Validação de arquivo
document.querySelector('input[name="arquivo"]').addEventListener('change', function(e) {
  const file = e.target.files[0];
  if (file) {
    const maxSize = 50 * 1024 * 1024; // 50MB
    if (file.size > maxSize) {
      alert('Arquivo muito grande. O tamanho máximo é 50MB.');
      e.target.value = '';
    }
  }
});
</script>
</body>
</html>