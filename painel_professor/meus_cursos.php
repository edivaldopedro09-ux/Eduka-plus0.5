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
<title>Meus Cursos - Professor</title>
<style>
:root {
    --bg: #0f172a;
    --card: #1e293b;
    --accent: #3b82f6;
    --accent-hover: #2563eb;
    --danger: #dc2626;
    --warning: #f59e0b;
    --text: #f8fafc;
    --muted: #94a3b8;
}
* {margin:0; padding:0; box-sizing:border-box; font-family:"Segoe UI", sans-serif;}
body {background:var(--bg); color:var(--text); padding:20px;}
.topbar {margin-bottom:30px; display:flex; justify-content:space-between; align-items:center;}
.btn {padding:10px 16px; border:none; border-radius:6px; text-decoration:none; font-weight:600; cursor:pointer; transition:background 0.3s;}
.back {background: var(--accent); color:#fff;} .back:hover {background: var(--accent-hover);}
.logout {background: var(--danger); color:#fff;} .logout:hover {opacity:0.85;}
h2 {margin-bottom:20px;}
.form-box {background: var(--card); padding:20px; margin-bottom:30px; border-radius:10px; box-shadow:0 6px 15px rgba(0,0,0,.4);}
input, textarea {width:100%; padding:10px; margin:8px 0; border:1px solid #374151; border-radius:6px; background:#0f172a; color:var(--text);}
input[type="file"] {background: var(--card); padding:6px;}
button {background: var(--accent); color:#fff; padding:10px 15px; border:none; border-radius:6px; cursor:pointer; transition:background 0.3s;}
button:hover {background: var(--accent-hover);}
.grid {display:grid; grid-template-columns:repeat(auto-fit, minmax(280px,1fr)); gap:20px;}
.card {background: var(--card); border-radius:12px; overflow:hidden; box-shadow:0 6px 16px rgba(0,0,0,.5); transition: transform .3s, box-shadow .3s; display:flex; flex-direction:column;}
.card:hover {transform:translateY(-6px); box-shadow:0 10px 20px rgba(0,0,0,.7);}
.card img {width:100%; height:160px; object-fit:cover;}
.card-content {padding:15px; flex:1; display:flex; flex-direction:column;}
.card h3 {margin-bottom:10px; font-size:18px; color:var(--text);}
.card p {font-size:14px; color:var(--muted); flex:1; margin-bottom:12px;}
.actions {display:flex; gap:8px; flex-wrap:wrap;}
.edit-btn, .delete-btn {padding:8px 12px; border-radius:6px; text-decoration:none; font-size:14px; font-weight:500; display:inline-block; text-align:center;}
.edit-btn {background: var(--warning); color:#fff;}
.delete-btn {background: var(--danger); color:#fff;}
.edit-form {margin-top:12px; background:#0f172a; padding:12px; border-radius:8px;}
@media (max-width:600px) {body{padding:12px;} h2{font-size:20px;} .topbar{flex-direction:column; gap:10px;}}
</style>
</head>
<body>
<div class="topbar">
    <a class="btn back" href="dashboard.php">← Voltar</a>
    <a class="btn logout" href="../logout.php">Sair</a>
</div>

<h2>📚 Meus Cursos</h2>

<!-- Criar curso -->
<div class="form-box">
    <h3>Criar Novo Curso</h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="titulo" placeholder="Título do curso" required>
        <textarea name="descricao" placeholder="Descrição do curso" required></textarea>
        <input type="file" name="imagem" accept="image/*">
        <button type="submit" name="criar">Criar Curso</button>
    </form>
</div>

<!-- Listagem dos cursos -->
<div class="grid">
<?php while($curso = $cursos->fetch_assoc()): ?>
    <div class="card">
        <?php if($curso['imagem'] && file_exists('../uploads/'.$curso['imagem'])): ?>
            <img src="../uploads/<?php echo $curso['imagem']; ?>" alt="Imagem do curso">
        <?php else: ?>
            <img src="../uploads/default.jpg" alt="Sem imagem">
        <?php endif; ?>
        <div class="card-content">
            <h3><?php echo htmlspecialchars($curso['titulo']); ?></h3>
            <p><?php echo htmlspecialchars($curso['descricao']); ?></p>
            <div class="actions">
                <form class="edit-form" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php echo $curso['id']; ?>">
                    <input type="text" name="titulo" value="<?php echo htmlspecialchars($curso['titulo']); ?>" required>
                    <textarea name="descricao" required><?php echo htmlspecialchars($curso['descricao']); ?></textarea>
                    <input type="file" name="imagem" accept="image/*">
                    <button type="submit" name="editar">Salvar</button>
                </form>
                <a class="delete-btn" href="?excluir=<?php echo $curso['id']; ?>" onclick="return confirm('Excluir este curso?')">Excluir</a>
            </div>
        </div>
    </div>
<?php endwhile; ?>
</div>
</body>
</html>
