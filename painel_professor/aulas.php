<?php
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'professor') {
    header("Location: ../index.php");
    exit();
}

require_once("../config.php");

$professor_id = $_SESSION['usuario_id'];

// --- Cursos do professor ---
$cursos_prof = $conn->query("SELECT * FROM cursos WHERE professor_id=$professor_id");

// --- Criar Aula ---
if (isset($_POST['criar'])) {
    $curso_id = $_POST['curso_id'];
    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'];
    $tipo = $_POST['tipo'];
    $link = $_POST['link'];

    // garantir que a aula só pode ser criada em curso do professor
    $check = $conn->query("SELECT id FROM cursos WHERE id=$curso_id AND professor_id=$professor_id");
    if ($check->num_rows > 0) {
        $stmt = $conn->prepare("INSERT INTO aulas (curso_id, titulo, descricao, tipo, link) VALUES (?,?,?,?,?)");
        $stmt->bind_param("issss", $curso_id, $titulo, $descricao, $tipo, $link);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: aulas.php");
    exit();
}

// --- Editar Aula ---
if (isset($_POST['editar'])) {
    $id = $_POST['id'];
    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'];
    $tipo = $_POST['tipo'];
    $link = $_POST['link'];

    $stmt = $conn->prepare("UPDATE aulas a
        INNER JOIN cursos c ON a.curso_id=c.id
        SET a.titulo=?, a.descricao=?, a.tipo=?, a.link=?
        WHERE a.id=? AND c.professor_id=?");
    $stmt->bind_param("ssssii", $titulo, $descricao, $tipo, $link, $id, $professor_id);
    $stmt->execute();
    $stmt->close();

    header("Location: aulas.php");
    exit();
}

// --- Excluir Aula ---
if (isset($_GET['excluir'])) {
    $id = $_GET['excluir'];
    $stmt = $conn->prepare("DELETE a FROM aulas a 
        INNER JOIN cursos c ON a.curso_id=c.id 
        WHERE a.id=? AND c.professor_id=?");
    $stmt->bind_param("ii", $id, $professor_id);
    $stmt->execute();
    $stmt->close();
    header("Location: aulas.php");
    exit();
}

// --- Listar aulas dos cursos do professor ---
$aulas = $conn->query("SELECT a.*, c.titulo as curso_nome 
    FROM aulas a
    INNER JOIN cursos c ON a.curso_id=c.id
    WHERE c.professor_id=$professor_id
    ORDER BY c.titulo, a.id");
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>CRUD de Aulas</title>
    <style>
        body{font-family:Arial, sans-serif;background:#f9fafb;padding:20px;}
        h2{margin-bottom:15px;}
        .topbar{margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;}
        .back{padding:8px 12px;background:#374151;color:#fff;border-radius:6px;text-decoration:none;}
        .logout{padding:8px 12px;background:#dc2626;color:#fff;border-radius:6px;text-decoration:none;}
        .form-box{background:#fff;padding:20px;margin-bottom:20px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,.1);}
        input,textarea,select{width:100%;padding:10px;margin:6px 0;border:1px solid #ccc;border-radius:6px;}
        button{background:#2563eb;color:#fff;padding:10px 15px;border:none;border-radius:6px;cursor:pointer;}
        table{width:100%;border-collapse:collapse;background:#fff;box-shadow:0 2px 6px rgba(0,0,0,.1);}
        th,td{padding:10px;border-bottom:1px solid #ddd;text-align:left;}
        th{background:#2563eb;color:#fff;}
        .delete-btn{background:#dc2626;padding:6px 10px;border-radius:4px;text-decoration:none;color:#fff;}
    </style>
</head>
<body>
    <div class="topbar">
        <a class="back" href="dashboard.php">← Voltar ao Dashboard</a>
        <a class="logout" href="../logout.php">Sair</a>
    </div>

    <h2>🎥 CRUD de Aulas</h2>

    <!-- Formulário de Criação -->
    <div class="form-box">
        <h3>Adicionar Nova Aula</h3>
        <form method="POST">
            <label>Curso:</label>
            <select name="curso_id" required>
                <option value="">Selecione</option>
                <?php while($c = $cursos_prof->fetch_assoc()): ?>
                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['titulo']); ?></option>
                <?php endwhile; ?>
            </select>
            <input type="text" name="titulo" placeholder="Título da aula" required>
            <textarea name="descricao" placeholder="Descrição"></textarea>
            <label>Tipo:</label>
            <select name="tipo">
                <option value="video">Vídeo</option>
                <option value="pdf">PDF</option>
                <option value="outro">Outro</option>
            </select>
            <input type="text" name="link" placeholder="Link ou caminho do arquivo">
            <button type="submit" name="criar">Criar Aula</button>
        </form>
    </div>

    <!-- Listagem -->
    <table>
        <tr>
            <th>ID</th>
            <th>Curso</th>
            <th>Título</th>
            <th>Tipo</th>
            <th>Link</th>
            <th>Ações</th>
        </tr>
        <?php while($a = $aulas->fetch_assoc()): ?>
        <tr>
            <td><?php echo $a['id']; ?></td>
            <td><?php echo htmlspecialchars($a['curso_nome']); ?></td>
            <td><?php echo htmlspecialchars($a['titulo']); ?></td>
            <td><?php echo $a['tipo']; ?></td>
            <td><a href="<?php echo htmlspecialchars($a['link']); ?>" target="_blank">Abrir</a></td>
            <td>
                <!-- Form de edição -->
                <form method="POST" style="display:inline-block;">
                    <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                    <input type="text" name="titulo" value="<?php echo htmlspecialchars($a['titulo']); ?>" required>
                    <input type="text" name="descricao" value="<?php echo htmlspecialchars($a['descricao']); ?>">
                    <select name="tipo">
                        <option value="video" <?php if($a['tipo']=="video") echo "selected"; ?>>Vídeo</option>
                        <option value="pdf" <?php if($a['tipo']=="pdf") echo "selected"; ?>>PDF</option>
                        <option value="outro" <?php if($a['tipo']=="outro") echo "selected"; ?>>Outro</option>
                    </select>
                    <input type="text" name="link" value="<?php echo htmlspecialchars($a['link']); ?>">
                    <button type="submit" name="editar">Salvar</button>
                </form>
                <a class="delete-btn" href="?excluir=<?php echo $a['id']; ?>" onclick="return confirm('Excluir aula?')">Excluir</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
