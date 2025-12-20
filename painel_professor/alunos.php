<?php
session_start();
require_once("../config.php");

// Segurança: só professores
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'professor') {
    header("Location: ../index.php");
    exit();
}

$professor_id = $_SESSION['usuario_id'];

// Verificar se foi passado curso_id
$curso_id = intval($_GET['curso_id'] ?? 0);
if ($curso_id <= 0) {
    die("Curso inválido!");
}

// Verificar se o professor realmente ministra o curso
$sql = "SELECT * FROM cursos WHERE id=? AND professor_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $curso_id, $professor_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    die("Você não tem permissão para acessar este curso!");
}

$curso = $result->fetch_assoc();

// Buscar alunos matriculados
$sql = "SELECT u.id AS aluno_id, u.nome AS aluno_nome, u.email, i.concluido,
               (SELECT COUNT(*) FROM progresso p WHERE p.aluno_id=u.id AND p.aula_id IN (SELECT id FROM aulas WHERE curso_id=i.curso_id)) AS aulas_concluidas,
               (SELECT COUNT(*) FROM aulas a WHERE a.curso_id=i.curso_id) AS total_aulas
        FROM inscricoes i
        INNER JOIN usuarios u ON i.aluno_id = u.id
        WHERE i.curso_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $curso_id);
$stmt->execute();
$result = $stmt->get_result();
$alunos = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Alunos Matriculados — Eduka Plus</title>
<style>
:root { --bg:#0f172a; --card:#111827; --text:#e5e7eb; --primary:#f59e0b; --muted:#94a3b8;}
body{margin:0;font-family:system-ui;background:var(--bg);color:var(--text);}
header{padding:16px 24px;background:#0b1020;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #1f2937;}
.container{padding:24px;}
table{width:100%;border-collapse:collapse;}
th, td{padding:12px;border-bottom:1px solid #1f2937;text-align:left;}
th{color:var(--primary);}
.progress{height:12px;background:#334155;border-radius:6px;overflow:hidden;}
.progress-inner{height:100%;background:var(--primary);border-radius:6px;}
</style>
</head>
<body>

<header>
  <div>Curso: <strong><?php echo htmlspecialchars($curso['titulo']); ?></strong></div>
  <div><a href="dashboard.php" style="color:var(--primary);text-decoration:none;">⬅ Voltar</a></div>
</header>

<div class="container">
<table>
    <tr>
        <th>Aluno</th>
        <th>Email</th>
        <th>Progresso</th>
        <th>Status</th>
    </tr>
<?php foreach($alunos as $aluno): 
    $percent = ($aluno['total_aulas']>0)? round(($aluno['aulas_concluidas']/$aluno['total_aulas'])*100) : 0;
    $status = ($aluno['concluido']==1) ? "✅ Concluído" : "⏳ Em andamento";
?>
<tr>
    <td><?php echo htmlspecialchars($aluno['aluno_nome']); ?></td>
    <td><?php echo htmlspecialchars($aluno['email']); ?></td>
    <td>
        <div class="progress">
            <div class="progress-inner" style="width:<?php echo $percent; ?>%;"></div>
        </div>
        <?php echo $percent; ?>%
    </td>
    <td><?php echo $status; ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>

</body>
</html>
