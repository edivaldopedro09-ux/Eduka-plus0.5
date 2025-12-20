<?php
session_start();
require_once("../config.php");

// Segurança: só alunos
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'aluno') {
    header("Location: ../index.php");
    exit();
}

$aluno_id = $_SESSION['usuario_id'];

// Marcar aula como concluída se aula_id estiver presente
if(isset($_GET['aula_id']) && is_numeric($_GET['aula_id'])){
    $aula_id = intval($_GET['aula_id']);
    // Inserir ou atualizar progresso da aula
    $stmt = $conn->prepare("INSERT INTO progresso (aluno_id, aula_id, concluido) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE concluido=1");
    $stmt->bind_param("ii", $aluno_id, $aula_id);
    $stmt->execute();

    // Verificar curso da aula
    $stmt2 = $conn->prepare("SELECT curso_id FROM aulas WHERE id=?");
    $stmt2->bind_param("i", $aula_id);
    $stmt2->execute();
    $curso_id = $stmt2->get_result()->fetch_assoc()['curso_id'];

    // Total de aulas do curso
    $stmt3 = $conn->prepare("SELECT COUNT(*) AS total FROM aulas WHERE curso_id=?");
    $stmt3->bind_param("i", $curso_id);
    $stmt3->execute();
    $total_aulas = $stmt3->get_result()->fetch_assoc()['total'];

    // Aulas concluídas pelo aluno
    $stmt4 = $conn->prepare("SELECT COUNT(*) AS concluidas FROM progresso p INNER JOIN aulas a ON p.aula_id=a.id WHERE p.aluno_id=? AND a.curso_id=? AND p.concluido=1");
    $stmt4->bind_param("ii", $aluno_id, $curso_id);
    $stmt4->execute();
    $aulas_concluidas = $stmt4->get_result()->fetch_assoc()['concluidas'];

    // Marcar curso como concluído se todas aulas finalizadas
    if($total_aulas > 0 && $aulas_concluidas >= $total_aulas){
        $stmt5 = $conn->prepare("UPDATE inscricoes SET concluido=1 WHERE aluno_id=? AND curso_id=?");
        $stmt5->bind_param("ii", $aluno_id, $curso_id);
        $stmt5->execute();
    }

    header("Location: progresso.php");
    exit();
}

// Buscar cursos do aluno
$sql = "SELECT 
            c.id AS curso_id, 
            c.titulo AS curso_nome, 
            i.concluido,
            (SELECT COUNT(*) FROM aulas a WHERE a.curso_id=c.id) AS total_aulas,
            (SELECT COUNT(*) FROM progresso p WHERE p.aluno_id=? AND p.aula_id IN (SELECT id FROM aulas WHERE curso_id=c.id) AND p.concluido=1) AS aulas_concluidas
        FROM cursos c
        INNER JOIN inscricoes i ON c.id=i.curso_id
        WHERE i.aluno_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $aluno_id, $aluno_id);
$stmt->execute();
$cursos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Progresso — Eduka Plus</title>
<style>
:root { --bg:#0f172a; --card:#111827; --muted:#94a3b8; --text:#e5e7eb; --primary:#f59e0b; }
body{margin:0;font-family:system-ui;background:var(--bg);color:var(--text);}
header{padding:16px 24px;background:#0b1020;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #1f2937;}
a.btn{background:var(--primary);color:#111827;padding:6px 12px;border-radius:8px;text-decoration:none;font-weight:600;}
.container{padding:24px;display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;}
.card{background:var(--card);padding:18px;border-radius:12px;border:1px solid #1f2937;box-shadow:0 6px 18px rgba(0,0,0,.25);}
.progress{height:12px;background:#334155;border-radius:6px;margin-top:8px;overflow:hidden;}
.progress-inner{height:100%;background:var(--primary);border-radius:6px;}
.status{margin-top:12px;font-weight:600;}
</style>
</head>
<body>

<header>
  <div>Olá, <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong> 👋</div>
  <div><a href="dashboard.php" class="btn">⬅ Voltar</a></div>
</header>

<div class="container">
<?php if(count($cursos) === 0): ?>
    <p>Você ainda não está matriculado em nenhum curso.</p>
<?php else: ?>
    <?php foreach($cursos as $curso): 
        $percent = ($curso['total_aulas'] > 0) ? round(($curso['aulas_concluidas']/$curso['total_aulas'])*100) : 0;
        $concluido = ($curso['concluido']==1);
    ?>
    <div class="card">
        <h3><?php echo htmlspecialchars($curso['curso_nome']); ?></h3>
        <div class="progress">
            <div class="progress-inner" style="width:<?php echo $percent; ?>%;"></div>
        </div>
        <div class="status">
            <?php echo $concluido ? "✅ Concluído" : "⏳ Em andamento"; ?>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>

</body>
</html>
