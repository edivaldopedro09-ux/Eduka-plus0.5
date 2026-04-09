<?php
session_start();
require_once("../config.php");

// Segurança: só alunos
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'aluno') {
    header("Location: ../index.php");
    exit();
}

$aluno_id = $_SESSION['usuario_id'];

// Buscar certificados do aluno que estão pagos
$stmt = $conn->prepare("
    SELECT cert.id, cert.curso_id, cert.data_emissao, cert.codigo_autenticacao, c.titulo AS curso_nome
    FROM certificados cert
    INNER JOIN cursos c ON cert.curso_id = c.id
    WHERE cert.aluno_id = ? AND cert.status = 'pago'
    ORDER BY cert.data_emissao DESC
");
$stmt->bind_param("i", $aluno_id);
$stmt->execute();
$result = $stmt->get_result();
$certificados = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Meus Certificados — Eduka Plus</title>
    <style>
        :root {
            --bg:#0f172a;
            --card:#111827;
            --muted:#94a3b8;
            --text:#e5e7eb;
            --primary:#f59e0b;
        }
        body{margin:0;font-family:system-ui;background:var(--bg);color:var(--text);}
        header{padding:16px 24px;background:#0b1020;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #1f2937;}
        a.btn{background:var(--primary);color:#111827;padding:6px 12px;border-radius:8px;text-decoration:none;font-weight:600;margin-top:6px;display:inline-block;}
        .container{padding:24px;display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px;}
        .card{background:var(--card);padding:18px;border-radius:12px;border:1px solid #1f2937;box-shadow:0 6px 18px rgba(0,0,0,.25);}
        .info{margin:6px 0;}
        .muted{color:var(--muted);font-size:14px;}
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
<?php if(count($certificados) > 0): ?>
    <?php foreach($certificados as $cert): ?>
        <div class="card">
            <h3><?php echo htmlspecialchars($cert['curso_nome']); ?></h3>
            <div class="info muted">Emitido em: <?php echo date("d/m/Y", strtotime($cert['data_emissao'])); ?></div>
            <div class="info">Código: <code><?php echo htmlspecialchars($cert['codigo_autenticacao']); ?></code></div>
            <a href="certificados.php?curso_id=<?php echo $cert['curso_id']; ?>" class="btn">📄 Ver Certificado</a>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p style="grid-column:1/-1;text-align:center;">🚫 Você ainda não possui certificados pagos disponíveis.</p>
<?php endif; ?>
</div>
</body>
</html>
