<?php
session_start();
require_once("../config.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'professor') {
    header("Location: ../index.php");
    exit();
}

$professor_id = $_SESSION['usuario_id'];
$aula_id = intval($_GET['id'] ?? 0);

// Buscar aula e verificar permissão
$sql = "SELECT a.*, c.professor_id FROM aulas a INNER JOIN cursos c ON a.curso_id=c.id WHERE a.id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $aula_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) { die("Aula não encontrada."); }
$aula = $result->fetch_assoc();

if($aula['professor_id'] != $professor_id) die("Sem permissão.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'] ?? $aula['titulo'];
    $descricao = $_POST['descricao'] ?? $aula['descricao'];

    if(isset($_FILES['video']) && $_FILES['video']['error'] === 0){
        $upload_dir = "../uploads/videos/";
        if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $video_filename = time() . "_" . basename($_FILES['video']['name']);
        move_uploaded_file($_FILES['video']['tmp_name'], $upload_dir . $video_filename);
        $aula['video'] = $video_filename;
    }

    $sql = "UPDATE aulas SET titulo=?, descricao=?, video=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $titulo, $descricao, $aula['video'], $aula_id);
    $stmt->execute();

    header("Location: gerenciar_aulas.php?curso_id=".$aula['curso_id']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Editar Aula — Eduka Plus</title>
<style>
body{font-family:system-ui;background:#0f172a;color:#e5e7eb;padding:20px;}
input, textarea, label{display:block;margin:10px 0;}
a.btn, button{background:#16a34a;color:#111827;padding:6px 12px;border-radius:6px;text-decoration:none;margin-right:6px;}
</style>
</head>
<body>

<h2>Editar Aula</h2>

<form method="post" enctype="multipart/form-data">
    <label>Título:</label>
    <input type="text" name="titulo" value="<?php echo htmlspecialchars($aula['titulo']); ?>" required>
    
    <label>Descrição:</label>
    <textarea name="descricao" rows="4"><?php echo htmlspecialchars($aula['descricao']); ?></textarea>

    <label>Vídeo atual: <?php echo htmlspecialchars($aula['video']); ?></label>
    <input type="file" name="video">

    <button type="submit">Salvar Alterações</button>
    <a href="gerenciar_aulas.php?curso_id=<?php echo $aula['curso_id']; ?>" class="btn">⬅ Voltar</a>
</form>

</body>
</html>
