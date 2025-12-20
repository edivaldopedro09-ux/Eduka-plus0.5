<?php
session_start();
require_once("../config.php");

// Segurança: só alunos
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'aluno') {
    header("Location: ../index.php");
    exit();
}

$aluno_id = $_SESSION['usuario_id'];

// Buscar todos os cursos com imagem
$sql = "SELECT c.id AS curso_id, c.titulo AS curso_nome, c.imagem 
        FROM cursos c 
        ORDER BY c.id DESC";
$result = $conn->query($sql);
$cursos = $result->fetch_all(MYSQLI_ASSOC);

// Função para verificar matrícula e progresso
function getCursoStatus($conn, $aluno_id, $curso_id) {
    $stmt = $conn->prepare("SELECT * FROM inscricoes WHERE aluno_id=? AND curso_id=?");
    $stmt->bind_param("ii", $aluno_id, $curso_id);
    $stmt->execute();
    $inscricao = $stmt->get_result()->fetch_assoc();

    $matriculado = $inscricao ? true : false;
    $concluido = false;
    $percent = 0;

    if ($matriculado) {
        // Total de aulas
        $stmt2 = $conn->prepare("SELECT COUNT(*) AS total FROM aulas WHERE curso_id=?");
        $stmt2->bind_param("i", $curso_id);
        $stmt2->execute();
        $total = $stmt2->get_result()->fetch_assoc()['total'] ?? 0;

        // Aulas concluídas
        $stmt3 = $conn->prepare("SELECT COUNT(*) AS concluido FROM progresso WHERE aluno_id=? AND aula_id IN (SELECT id FROM aulas WHERE curso_id=?) AND concluido=1");
        $stmt3->bind_param("ii", $aluno_id, $curso_id);
        $stmt3->execute();
        $concluido_count = $stmt3->get_result()->fetch_assoc()['concluido'] ?? 0;

        $percent = $total > 0 ? round(($concluido_count / $total) * 100) : 0;
        $concluido = ($percent == 100);

        // Atualizar automaticamente o status do curso
        if ($concluido) {
            $stmt4 = $conn->prepare("UPDATE inscricoes SET concluido=1 WHERE aluno_id=? AND curso_id=?");
            $stmt4->bind_param("ii", $aluno_id, $curso_id);
            $stmt4->execute();

            // Criar certificado se não existir
            $stmt5 = $conn->prepare("SELECT id FROM certificados WHERE aluno_id=? AND curso_id=?");
            $stmt5->bind_param("ii", $aluno_id, $curso_id);
            $stmt5->execute();
            $existe = $stmt5->get_result()->fetch_assoc();

            if (!$existe) {
                $codigo_autenticacao = uniqid("CERT_", true);
                $stmt6 = $conn->prepare("INSERT INTO certificados (aluno_id, curso_id, data_emissao, codigo_autenticacao) VALUES (?,?,NOW(),?)");
                $stmt6->bind_param("iis", $aluno_id, $curso_id, $codigo_autenticacao);
                $stmt6->execute();
            }
        }
    }

    return [
        'matriculado' => $matriculado,
        'percent' => $percent,
        'concluido' => $concluido
    ];
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Meus Cursos — Eduka Plus</title>
<style>
:root {
    --bg:#0f172a;
    --card:#111827;
    --muted:#94a3b8;
    --text:#e5e7eb;
    --primary:#f59e0b;
}
body {margin:0; font-family:system-ui; background:var(--bg); color:var(--text);}
header {padding:16px 24px; background:#0b1020; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #1f2937;}
a.btn {background:var(--primary); color:#111827; padding:6px 12px; border-radius:8px; text-decoration:none; font-weight:600; margin-top:6px; display:inline-block;}
.container {padding:24px; display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:20px;}
.card {background:var(--card); padding:18px; border-radius:12px; border:1px solid #1f2937; box-shadow:0 6px 18px rgba(0,0,0,.25); display:flex; flex-direction:column;}
.curso-img {width:100%; height:160px; object-fit:cover; border-radius:10px; margin-bottom:12px;}
.progress {height:12px; background:#334155; border-radius:6px; margin-top:8px; overflow:hidden;}
.progress-inner {height:100%; background:var(--primary); border-radius:6px;}
.status {margin-top:12px; font-weight:600;}
</style>
</head>
<body>
<header>
    <div>Olá, <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong> 👋</div>
    <div>
        <a href="dashboard.php" class="btn">⬅ Voltar</a>
        <a href="../logout.php" class="btn">Sair</a>
    </div>
</header>

<div class="container">
<?php foreach($cursos as $curso): 
    $status = getCursoStatus($conn, $aluno_id, $curso['curso_id']); 
    $img_path = $curso['imagem'] && file_exists('../uploads/'.$curso['imagem']) 
                ? '../uploads/'.$curso['imagem'] 
                : '../uploads/default.png';
?>
    <div class="card">
        <img src="<?php echo $img_path; ?>" alt="Imagem do curso" class="curso-img">
        <h3><?php echo htmlspecialchars($curso['curso_nome']); ?></h3>

        <?php if($status['matriculado']): ?>
            <div class="progress">
                <div class="progress-inner" style="width:<?php echo $status['percent']; ?>%;"></div>
            </div>
            <div class="status">
                <?php echo $status['concluido'] ? "✅ Concluído" : "⏳ Em andamento"; ?>
            </div>

            <?php if($status['concluido']): ?>
                <a href="certificados.php?curso_id=<?php echo $curso['curso_id']; ?>" class="btn">📄 Obter Certificado</a>
            <?php else: ?>
                <a href="ver_aulas.php?curso_id=<?php echo $curso['curso_id']; ?>" class="btn">📚 Ver Aulas</a>
            <?php endif; ?>

        <?php else: ?>
            <div class="status">❌ Não matriculado</div>
            <a href="matricular.php?curso_id=<?php echo $curso['curso_id']; ?>" class="btn">📌 Matricular-se</a>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
</div>
</body>
</html>
