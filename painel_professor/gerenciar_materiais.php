<?php
session_start();
require_once("../config.php");

// Segurança: só professores
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
if ($result->num_rows == 0) { 
    die("Você não tem permissão para este curso."); 
}
$curso = $result->fetch_assoc();

// Buscar materiais do curso
$sql = "SELECT * FROM materiais WHERE curso_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $curso_id);
$stmt->execute();
$result = $stmt->get_result();
$materiais = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Gerenciar Materiais — Eduka Plus</title>
<style>
body{font-family:system-ui;background:#0f172a;color:#e5e7eb;padding:20px;}
a.btn{background:#16a34a;color:#111827;padding:6px 12px;border-radius:6px;text-decoration:none;margin-right:6px;}
table{width:100%;border-collapse:collapse;margin-top:20px;}
th, td{padding:12px;border-bottom:1px solid #334155;}
th{color:#f59e0b;text-align:left;}
</style>
</head>
<body>

<h2>Materiais do Curso: <?php echo htmlspecialchars($curso['titulo']); ?></h2>

<a href="adicionar_material.php?curso_id=<?php echo $curso['id']; ?>" class="btn">➕ Adicionar Material</a>
<a href="dashboard.php" class="btn">⬅ Voltar</a>

<table>
<tr>
<th>Título</th>
<th>Arquivo</th>
<th>Ações</th>
</tr>
<?php foreach($materiais as $material): ?>
<tr>
<td><?php echo htmlspecialchars($material['titulo']); ?></td>
<td>
<?php 
$ext = pathinfo($material['arquivo'], PATHINFO_EXTENSION);
echo htmlspecialchars($material['arquivo']); 
?>
</td>
<td>
<a href="editar_material.php?id=<?php echo $material['id']; ?>" class="btn">✏ Editar</a>
<a href="excluir_material.php?id=<?php echo $material['id']; ?>" class="btn" onclick="return confirm('Deseja realmente excluir?')">🗑 Excluir</a>
</td>
</tr>
<?php endforeach; ?>
</table>

</body>
</html>
