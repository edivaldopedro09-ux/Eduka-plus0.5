<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'professor') {
    header("Location: ../index.php");
    exit();
}

require_once("../config.php");

$professor_id = $_SESSION['usuario_id'];
$msg = "";

// Cursos do professor
$sql_cursos = "SELECT * FROM cursos WHERE professor_id = ?";
$stmt = $conn->prepare($sql_cursos);
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$cursos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ➕ Adicionar material
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add'])) {
    $curso_id = $_POST['curso_id'];
    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'];

    $arquivo = $_FILES['arquivo']['name'];
    $destino = "../uploads/" . basename($arquivo);
    if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $destino)) {
        $sql = "INSERT INTO materiais (curso_id, titulo, descricao, arquivo) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isss", $curso_id, $titulo, $descricao, $arquivo);
        $stmt->execute();
        $msg = "✅ Material adicionado!";
    } else {
        $msg = "❌ Erro ao enviar o arquivo.";
    }
}

// ✏️ Editar material
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit'])) {
    $id = $_POST['id'];
    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'];

    if (!empty($_FILES['arquivo']['name'])) {
        $arquivo = $_FILES['arquivo']['name'];
        $destino = "../uploads/" . basename($arquivo);
        if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $destino)) {
            $sql = "UPDATE materiais SET titulo=?, descricao=?, arquivo=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $titulo, $descricao, $arquivo, $id);
        }
    } else {
        $sql = "UPDATE materiais SET titulo=?, descricao=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $titulo, $descricao, $id);
    }
    $stmt->execute();
    $msg = "✏️ Material atualizado!";
}

// ❌ Excluir material
if (isset($_GET['del'])) {
    $id = intval($_GET['del']);
    $sql = "DELETE m FROM materiais m 
            INNER JOIN cursos c ON m.curso_id=c.id 
            WHERE m.id=? AND c.professor_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id, $professor_id);
    if ($stmt->execute()) {
        $msg = "🗑️ Material excluído!";
    }
}

// Listar materiais
$sql_m = "SELECT m.*, c.titulo AS curso FROM materiais m 
          INNER JOIN cursos c ON m.curso_id = c.id
          WHERE c.professor_id = ?
          ORDER BY m.data_upload DESC";
$stmt = $conn->prepare($sql_m);
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$materiais = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>📂 Materiais — Professor</title>
<style>
:root {
  --bg: #0f172a;
  --card: #1e293b;
  --text: #e2e8f0;
  --muted: #94a3b8;
  --primary: #3b82f6;
}
body {
  font-family: "Segoe UI", sans-serif;
  margin: 0;
  background: linear-gradient(135deg, #0a1220, #0f172a 60%);
  color: var(--text);
  padding: 20px;
}
h1 { color: var(--primary); margin-bottom: 10px; }
form {
  background: var(--card);
  padding: 20px;
  margin-bottom: 20px;
  border-radius: 12px;
  box-shadow: 0 6px 16px rgba(0,0,0,.4);
}
input,select,textarea {
  width: 100%; padding: 10px; margin: 8px 0;
  border: 1px solid #334155;
  border-radius: 8px;
  background: #0f172a; color: var(--text);
}
button {
  padding: 10px 15px;
  background: var(--primary);
  color: #fff;
  border: none; border-radius: 8px;
  cursor: pointer; transition: .2s;
}
button:hover { background: #2563eb; }
.card {
  background: var(--card);
  padding: 15px;
  border-radius: 10px;
  margin-bottom: 15px;
  box-shadow: 0 4px 12px rgba(0,0,0,.3);
}
.card small { color: var(--muted); }
.actions { margin-top: 10px; }
.actions a { color: var(--primary); margin-right: 12px; text-decoration: none; }
.actions a:hover { text-decoration: underline; }
summary { cursor: pointer; color: var(--primary); }
.back-btn {
  display: inline-block;
  margin-bottom: 15px;
  padding: 10px 16px;
  background: #334155;
  color: #fff;
  border-radius: 8px;
  text-decoration: none;
  transition: .2s;
}
.back-btn:hover { background: #475569; }
</style>
</head>
<body>
    <a href="dashboard.php" class="back-btn">⬅ Voltar para Dashboard</a>
    <h1>📂 Gerenciar Materiais</h1>
    <?php if ($msg) echo "<p><strong>$msg</strong></p>"; ?>

    <!-- Formulário Novo -->
    <form method="POST" enctype="multipart/form-data">
        <h2>➕ Novo Material</h2>
        <label>Curso:</label>
        <select name="curso_id" required>
            <?php foreach ($cursos as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['titulo']) ?></option>
            <?php endforeach; ?>
        </select>
        <label>Título:</label>
        <input type="text" name="titulo" required>
        <label>Descrição:</label>
        <textarea name="descricao"></textarea>
        <label>Arquivo:</label>
        <input type="file" name="arquivo" required>
        <button type="submit" name="add">Salvar</button>
    </form>

    <h2>📑 Meus Materiais</h2>
    <?php if ($materiais): ?>
        <?php foreach ($materiais as $m): ?>
            <div class="card">
                <h3><?= htmlspecialchars($m['titulo']); ?> <small>(<?= htmlspecialchars($m['curso']); ?>)</small></h3>
                <p><?= htmlspecialchars($m['descricao']); ?></p>
                <p><small>📅 Enviado em: <?= date("d/m/Y H:i", strtotime($m['data_upload'])); ?></small></p>
                <a href="../uploads/<?= $m['arquivo']; ?>" target="_blank">📥 Baixar</a>

                <div class="actions">
                    <details>
                        <summary>✏️ Editar</summary>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="id" value="<?= $m['id']; ?>">
                            <label>Título:</label>
                            <input type="text" name="titulo" value="<?= htmlspecialchars($m['titulo']); ?>" required>
                            <label>Descrição:</label>
                            <textarea name="descricao"><?= htmlspecialchars($m['descricao']); ?></textarea>
                            <label>Arquivo (opcional):</label>
                            <input type="file" name="arquivo">
                            <button type="submit" name="edit">Salvar Alterações</button>
                        </form>
                    </details>
                    <a href="?del=<?= $m['id']; ?>" onclick="return confirm('Tem certeza que deseja excluir?')">🗑️ Excluir</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>📭 Nenhum material adicionado ainda.</p>
    <?php endif; ?>
</body>
</html>
