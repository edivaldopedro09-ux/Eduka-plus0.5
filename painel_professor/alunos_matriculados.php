<?php
session_start();
require_once("../config.php");

// Segurança: só professores
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'professor') {
    header("Location: ../index.php");
    exit();
}

$professor_id = $_SESSION['usuario_id'];

// Buscar todos os cursos do professor
$sql = "SELECT id, titulo FROM cursos WHERE professor_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$result = $stmt->get_result();
$cursos = $result->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Alunos Matriculados — Eduka Plus</title>
<style>
body{font-family:system-ui;background:#0f172a;color:#e5e7eb;padding:20px;}
table{width:100%;border-collapse:collapse;margin-top:20px;}
th, td{padding:12px;border-bottom:1px solid #334155;}
th{color:#f59e0b;text-align:left;}
a.btn{background:#16a34a;color:#111827;padding:6px 12px;border-radius:6px;text-decoration:none;margin-right:6px;}
</style>
</head>
<body>

<h2>Alunos Matriculados nos Meus Cursos</h2>

<?php foreach($cursos as $curso): 
    // Buscar alunos matriculados no curso
    $sql2 = "SELECT u.nome, u.email, i.inscrito_em 
             FROM inscricoes i
             INNER JOIN usuarios u ON i.aluno_id = u.id
             WHERE i.curso_id=?";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("i", $curso['id']);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    $alunos = $result2->fetch_all(MYSQLI_ASSOC);
?>
<h3>Curso: <?php echo htmlspecialchars($curso['titulo']); ?></h3>
<?php if(count($alunos) == 0): ?>
    <p>Nenhum aluno matriculado neste curso.</p>
<?php else: ?>
<table>
<tr>
<th>Nome</th>
<th>Email</th>
<th>Data de inscrição</th>
</tr>
<?php foreach($alunos as $aluno): ?>
<tr>
<td><?php echo htmlspecialchars($aluno['nome']); ?></td>
<td><?php echo htmlspecialchars($aluno['email']); ?></td>
<td><?php echo date('d/m/Y H:i', strtotime($aluno['inscrito_em'])); ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
<?php endforeach; ?>

<a href="dashboard.php" class="btn">⬅ Voltar</a>

</body>
</html>
