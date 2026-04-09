<?php
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'professor') {
    header("Location: ../index.php");
    exit();
}

require_once("../config.php");

$professor_id = $_SESSION['usuario_id'];
$curso_id = intval($_GET['curso_id'] ?? 0);

// Verificar se o curso pertence ao professor
$stmt = $conn->prepare("SELECT * FROM cursos WHERE id=? AND professor_id=?");
$stmt->bind_param("ii", $curso_id, $professor_id);
$stmt->execute();
$curso = $stmt->get_result()->fetch_assoc();
if (!$curso) {
    die("Curso não encontrado ou você não tem permissão.");
}

// Função para upload seguro de vídeo
function salvarVideo($file) {
    $tipos_permitidos = ['mp4'];
    $max_size = 50 * 1024 * 1024; // 50MB
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $tipos_permitidos)) return null;
        if ($file['size'] > $max_size) return null;
        $nome = uniqid() . "." . $ext;
        move_uploaded_file($file['tmp_name'], "../uploads/videos/" . $nome);
        return $nome;
    }
    return null;
}

// Criar nova aula
if (isset($_POST['criar'])) {
    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'];
    $video = null;

    if (!empty($_FILES['video']['name'])) {
        $vid = salvarVideo($_FILES['video']);
        if ($vid) $video = $vid;
    }

    $stmt = $conn->prepare("INSERT INTO aulas (curso_id, titulo, descricao, video) VALUES (?,?,?,?)");
    $stmt->bind_param("isss", $curso_id, $titulo, $descricao, $video);
    $stmt->execute();
    $stmt->close();
    header("Location: gerenciar_aulas.php?curso_id=$curso_id");
    exit();
}

// Editar aula
if (isset($_POST['editar'])) {
    $id = $_POST['id'];
    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'];

    if (!empty($_FILES['video']['name'])) {
        $vid = salvarVideo($_FILES['video']);
        if ($vid) {
            $stmt = $conn->prepare("UPDATE aulas SET titulo=?, descricao=?, video=? WHERE id=? AND curso_id=?");
            $stmt->bind_param("sssii", $titulo, $descricao, $vid, $id, $curso_id);
        }
    } else {
        $stmt = $conn->prepare("UPDATE aulas SET titulo=?, descricao=? WHERE id=? AND curso_id=?");
        $stmt->bind_param("ssii", $titulo, $descricao, $id, $curso_id);
    }
    $stmt->execute();
    $stmt->close();
    header("Location: gerenciar_aulas.php?curso_id=$curso_id");
    exit();
}

// Excluir aula
if (isset($_GET['excluir'])) {
    $id = intval($_GET['excluir']);
    $stmt = $conn->prepare("DELETE FROM aulas WHERE id=? AND curso_id=?");
    $stmt->bind_param("ii", $id, $curso_id);
    $stmt->execute();
    $stmt->close();
    header("Location: gerenciar_aulas.php?curso_id=$curso_id");
    exit();
}

// Listar aulas
$stmt = $conn->prepare("SELECT * FROM aulas WHERE curso_id=? ORDER BY id ASC");
$stmt->bind_param("i", $curso_id);
$stmt->execute();
$aulas = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Gerenciar Aulas - <?php echo htmlspecialchars($curso['titulo']); ?></title>
<style>
:root {
    --bg:#0f172a; --card:#1e293b; --accent:#3b82f6; --accent-hover:#2563eb; --danger:#dc2626; 
    --warning:#f59e0b; --text:#f8fafc; --muted:#94a3b8;
}
body {margin:0; font-family:"Segoe UI",sans-serif; background:var(--bg); color:var(--text); padding:20px;}
.topbar {margin-bottom:30px; display:flex; justify-content:space-between; align-items:center;}
.btn {padding:10px 16px; border:none; border-radius:6px; cursor:pointer; font-weight:600; text-decoration:none; transition:0.3s;}
.back {background:var(--accent); color:#fff;}
.back:hover {background:var(--accent-hover);}
.logout {background:var(--danger); color:#fff;}
.logout:hover {opacity:0.85;}
h2 {margin-bottom:20px;}
.form-box {background:var(--card); padding:20px; margin-bottom:30px; border-radius:10px; box-shadow:0 6px 15px rgba(0,0,0,.4);}
input, textarea {width:100%; padding:10px; margin:8px 0; border:1px solid #374151; border-radius:6px; background:#0f172a; color:var(--text);}
input[type="file"] {background:var(--card); padding:6px;}
button {background:var(--accent); color:#fff; padding:10px 15px; border:none; border-radius:6px; cursor:pointer; transition:0.3s;}
button:hover {background:var(--accent-hover);}
.grid {display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:20px;}
.card {background:var(--card); border-radius:12px; overflow:hidden; box-shadow:0 6px 16px rgba(0,0,0,.5); transition:0.3s; display:flex; flex-direction:column;}
.card:hover {transform:translateY(-6px); box-shadow:0 10px 20px rgba(0,0,0,.7);}
.card-content {padding:15px; flex:1; display:flex; flex-direction:column;}
.card h3 {margin-bottom:10px; font-size:18px; color:var(--text);}
.card p {font-size:14px; color:var(--muted); flex:1; margin-bottom:12px;}
.actions {display:flex; gap:8px; flex-wrap:wrap;}
.edit-btn, .delete-btn {padding:8px 12px; border-radius:6px; font-size:14px; font-weight:500; text-align:center; text-decoration:none; display:inline-block;}
.edit-btn {background:var(--warning); color:#fff;}
.delete-btn {background:var(--danger); color:#fff;}
.edit-form {display:none; margin-top:12px; background:#0f172a; padding:12px; border-radius:8px;}
.edit-toggle {cursor:pointer; color:var(--accent); font-weight:600; margin-bottom:8px;}
@media (max-width:600px){body{padding:12px;} h2{font-size:20px;} .topbar{flex-direction:column; gap:10px;}}
</style>
<script>
function toggleEdit(id) {
    const form = document.getElementById('edit-form-'+id);
    if(form.style.display==='none' || form.style.display==='') form.style.display='block';
    else form.style.display='none';
}
</script>
</head>
<body>
<div class="topbar">
    <a class="btn back" href="meus_cursos.php">← Voltar</a>
    <a class="btn logout" href="../logout.php">Sair</a>
</div>

<h2>Gerenciar Aulas - <?php echo htmlspecialchars($curso['titulo']); ?></h2>

<!-- Criar nova aula -->
<div class="form-box">
<h3>Adicionar Nova Aula</h3>
<form method="POST" enctype="multipart/form-data">
    <input type="text" name="titulo" placeholder="Título da aula" required>
    <textarea name="descricao" placeholder="Descrição da aula" required></textarea>
    <input type="file" name="video" accept="video/mp4">
    <button type="submit" name="criar">Adicionar Aula</button>
</form>
</div>

<!-- Listagem das aulas -->
<div class="grid">
<?php while($aula = $aulas->fetch_assoc()): ?>
<div class="card">
    <div class="card-content">
        <h3><?php echo htmlspecialchars($aula['titulo']); ?></h3>
        <p><?php echo htmlspecialchars($aula['descricao']); ?></p>
        <?php if($aula['video']): ?>
            <video width="100%" controls style="margin-bottom:10px;">
                <source src="../uploads/videos/<?php echo $aula['video']; ?>" type="video/mp4">
                Seu navegador não suporta o vídeo.
            </video>
        <?php endif; ?>
        <div class="actions">
            <span class="edit-toggle" onclick="toggleEdit(<?php echo $aula['id']; ?>)">✏️ Editar</span>
            <a class="delete-btn" href="?excluir=<?php echo $aula['id']; ?>&curso_id=<?php echo $curso_id; ?>" onclick="return confirm('Excluir esta aula?')">Excluir</a>
        </div>
        <form id="edit-form-<?php echo $aula['id']; ?>" class="edit-form" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo $aula['id']; ?>">
            <input type="text" name="titulo" value="<?php echo htmlspecialchars($aula['titulo']); ?>" required>
            <textarea name="descricao" required><?php echo htmlspecialchars($aula['descricao']); ?></textarea>
            <input type="file" name="video" accept="video/mp4">
            <button type="submit" name="editar">Salvar</button>
        </form>
    </div>
</div>
<?php endwhile; ?>
</div>
</body>
</html>
