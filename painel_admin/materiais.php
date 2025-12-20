<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once("../config.php");

$msg = "";

// Buscar todos os cursos
$sql_cursos = "SELECT c.id, c.titulo, u.nome AS professor 
               FROM cursos c 
               LEFT JOIN usuarios u ON c.professor_id = u.id";
$cursos = $conn->query($sql_cursos)->fetch_all(MYSQLI_ASSOC);

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
    $sql = "DELETE FROM materiais WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $msg = "🗑️ Material excluído!";
    }
}

// Listar todos os materiais
$sql_m = "SELECT m.*, c.titulo AS curso, u.nome AS professor 
          FROM materiais m 
          INNER JOIN cursos c ON m.curso_id = c.id
          LEFT JOIN usuarios u ON c.professor_id = u.id
          ORDER BY m.data_upload DESC";
$materiais = $conn->query($sql_m)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>📂 Materiais — Admin</title>
<style>
body{ font-family:Arial, sans-serif; background:#f1f5f9; margin:0; padding:20px;}
h1{ color:#1e293b;}
form{ background:#fff; padding:20px; margin-bottom:20px; border-radius:10px; box-shadow:0 4px 8px rgba(0,0,0,.1);}
input,select,textarea{ width:100%; padding:10px; margin:8px 0; border:1px solid #ccc; border-radius:8px;}
button{ padding:10px 15px; background:#16a34a; color:#fff; border:none; border-radius:8px; cursor:pointer;}
button:hover{ background:#15803d;}
.card{ background:#fff; padding:15px; border-radius:10px; margin-bottom:10px; box-shadow:0 4px 8px rgba(0,0,0,.05);}
.actions a{ margin-right:10px; text-decoration:none; }
</style>
</head>
<body>
    <h1>📂 Gerenciar Materiais (Admin)</h1>
    <?php if ($msg) echo "<p><strong>$msg</strong></p>"; ?>

    <!-- Formulário Novo -->
    <form method="POST" enctype="multipart/form-data">
        <h2>➕ Novo Material</h2>
        <label>Curso:</label>
        <select name="curso_id" required>
            <?php foreach ($cursos as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['titulo']) ?> (<?= htmlspecialchars($c['professor'] ?? "Sem professor") ?>)</option>
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

    <h2>📑 Todos os Materiais</h2>
    <?php foreach ($materiais as $m): ?>
        <div class="card">
            <h3><?= htmlspecialchars($m['titulo']); ?> 
                <small>(<?= htmlspecialchars($m['curso']); ?> - Prof: <?= htmlspecialchars($m['professor']); ?>)</small>
            </h3>
            <p><?= htmlspecialchars($m['descricao']); ?></p>
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
</body>
</html>
