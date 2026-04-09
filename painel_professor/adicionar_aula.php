<?php
session_start();
require_once("../config.php");

// Verifica se é professor
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'professor') {
    header("Location: ../index.php");
    exit();
}

$professor_id = $_SESSION['usuario_id'];
$curso_id = intval($_GET['curso_id'] ?? 0);

// Verifica se o curso realmente pertence ao professor
$sql = "SELECT * FROM cursos WHERE id=? AND professor_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $curso_id, $professor_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) { 
    die("❌ Você não tem permissão para este curso."); 
}
$curso = $result->fetch_assoc();

// Cadastro de aula
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    
    $video_filename = '';

    // Upload do vídeo
    if (isset($_FILES['video']) && $_FILES['video']['error'] === 0) {
        $upload_dir = "../uploads/videos/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $nomeArquivo = time() . "_" . basename($_FILES['video']['name']);
        $target_file = $upload_dir . $nomeArquivo;

        if (move_uploaded_file($_FILES['video']['tmp_name'], $target_file)) {
            // 🔥 Salva no banco apenas o caminho relativo (sem "../")
            $video_filename = "uploads/videos/" . $nomeArquivo;
        }
    }

    // Inserir aula no banco
    $sql = "INSERT INTO aulas (curso_id, titulo, descricao, video) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $curso_id, $titulo, $descricao, $video_filename);
    $stmt->execute();

    header("Location: gerenciar_aulas.php?curso_id=$curso_id");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Adicionar Aula — Eduka Plus</title>
<style>
body{
    font-family:system-ui;
    background:#0f172a;
    color:#e5e7eb;
    padding:20px;
}
form{
    background:#111827;
    padding:20px;
    border-radius:12px;
    max-width:500px;
}
label{
    margin-top:10px;
    display:block;
}
input, textarea{
    width:100%;
    margin-bottom:12px;
    padding:8px;
    border-radius:6px;
    border:1px solid #1f2937;
    background:#1f2937;
    color:#f3f4f6;
}
button, a.btn{
    background:#16a34a;
    color:#111827;
    padding:8px 14px;
    border-radius:6px;
    text-decoration:none;
    font-weight:bold;
    border:none;
    cursor:pointer;
}
a.btn{margin-left:8px;}
</style>
</head>
<body>

<h2>Adicionar Aula ao Curso: <?php echo htmlspecialchars($curso['titulo']); ?></h2>

<form method="post" enctype="multipart/form-data">
    <label>Título da Aula:</label>
    <input type="text" name="titulo" required>
    
    <label>Descrição:</label>
    <textarea name="descricao" rows="4"></textarea>

    <label>Vídeo da Aula (MP4):</label>
    <input type="file" name="video" accept="video/mp4">

    <button type="submit">Salvar Aula</button>
    <a href="gerenciar_aulas.php?curso_id=<?php echo $curso['id']; ?>" class="btn">⬅ Voltar</a>
</form>

</body>
</html>
