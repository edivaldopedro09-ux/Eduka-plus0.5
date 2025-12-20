<?php
session_start();
require_once("../config.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'professor') {
    header("Location: ../index.php");
    exit();
}

$professor_id = $_SESSION['usuario_id'];
$material_id = intval($_GET['id'] ?? 0);

// Buscar material e verificar permissão
$sql = "SELECT m.*, c.professor_id FROM materiais m INNER JOIN cursos c ON m.curso_id=c.id WHERE m.id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $material_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) { die("Material não encontrado."); }
$material = $result->fetch_assoc();

if($material['professor_id'] != $professor_id) die("Sem permissão.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'] ?? $material['titulo'];

    if(isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === 0){
        $upload_dir = "../uploads/materials/";
        if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $filename = time() . "_" . basename($_FILES['arquivo']['name']);
        $target_file = $upload_dir . $filename;

        if(move_uploaded_file($_FILES['arquivo']['tmp_name'], $target_file)){
            $material['arquivo'] = $filename;
        }
    }

    $sql = "UPDATE materiais SET titulo=?, arquivo=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $titulo, $material['arquivo'], $material_id);
    $stmt->execute();

    header("Location: gerenciar_materiais.php?curso_id=".$material['curso_id']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Editar Material — Eduka Plus</title>
<style>
body{font-family:system-ui;background:#0f172a;color:#e5e7eb;padding:20px;}
input, label{display:block;margin:10px 0;}
a.btn, button{background:#16a34a;color:#111827;padding:6px 12px;border-radius:6px;text-decoration:none;margin-right:6px;}
</style>
</head>
<body>

<h2>Editar Material</h2>
<form method="post" enctype="multipart/form-data">
    <label>Título:</label>
    <input type="text" name="titulo" value="<?php echo htmlspecialchars($material['titulo']); ?>" required>
    
    <label>Arquivo atual: <?php echo htmlspecialchars($material['arquivo']); ?></label>
    <input type="file" name="arquivo">

    <button type="submit">Salvar Alterações</button>
    <a href="gerenciar_materiais.php?curso_id=<?php echo $material['curso_id']; ?>" class="btn">⬅ Voltar</a>
</form>

</body>
</html>
