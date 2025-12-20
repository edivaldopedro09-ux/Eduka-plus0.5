<?php
session_start();
require_once("../config.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'professor') {
    header("Location: ../index.php");
    exit();
}

$professor_id = $_SESSION['usuario_id'];
$curso_id = intval($_GET['curso_id'] ?? 0);

// Verifica se o professor possui o curso
$sql = "SELECT * FROM cursos WHERE id=? AND professor_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $curso_id, $professor_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) { die("Você não tem permissão para este curso."); }
$curso = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'] ?? '';
    
    if(isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === 0){
        $upload_dir = "../uploads/materials/";
        if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $filename = time() . "_" . basename($_FILES['arquivo']['name']);
        $target_file = $upload_dir . $filename;

        if(move_uploaded_file($_FILES['arquivo']['tmp_name'], $target_file)){
            $sql = "INSERT INTO materiais (curso_id, titulo, arquivo) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iss", $curso_id, $titulo, $filename);
            $stmt->execute();
            header("Location: gerenciar_materiais.php?curso_id=$curso_id");
            exit();
        } else {
            $error = "Erro ao enviar o arquivo.";
        }
    } else {
        $error = "Escolha um arquivo válido.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Adicionar Material — Eduka Plus</title>
<style>
body{font-family:system-ui;background:#0f172a;color:#e5e7eb;padding:20px;}
input, label{display:block;margin:10px 0;}
a.btn, button{background:#16a34a;color:#111827;padding:6px 12px;border-radius:6px;text-decoration:none;margin-right:6px;}
</style>
</head>
<body>

<h2>Adicionar Material ao Curso: <?php echo htmlspecialchars($curso['titulo']); ?></h2>
<?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
<form method="post" enctype="multipart/form-data">
    <label>Título:</label>
    <input type="text" name="titulo" required>
    
    <label>Arquivo:</label>
    <input type="file" name="arquivo" required>

    <button type="submit">Adicionar</button>
    <a href="gerenciar_materiais.php?curso_id=<?php echo $curso['id']; ?>" class="btn">⬅ Voltar</a>
</form>

</body>
</html>
