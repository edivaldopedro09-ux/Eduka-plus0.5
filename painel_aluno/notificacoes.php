<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'aluno') {
    header("Location: ../index.php");
    exit();
}

require_once("../config.php");

$aluno_id = $_SESSION['usuario_id'];

// Buscar notificações para aluno
$stmt = $conn->prepare("
    SELECT id, titulo, mensagem, data_envio
    FROM notificacoes
    WHERE destinatario_tipo = 'todos'
       OR destinatario_tipo = 'alunos'
       OR destinatario_tipo = 'aluno'
       OR (destinatario_tipo = 'usuario' AND destinatario_id = ?)
    ORDER BY data_envio DESC
");
$stmt->bind_param("i", $aluno_id);
$stmt->execute();
$result = $stmt->get_result();
$notificacoes = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>📢 Notificações — Aluno</title>
    <style>
        body { font-family: Arial, sans-serif; background:#0f172a; color:#f1f5f9; margin:0; padding:20px;}
        h1 { color:#3b82f6; }
        .card { background:#1e293b; padding:15px; margin-bottom:15px; border-radius:10px; box-shadow:0 4px 8px rgba(0,0,0,.2);}
        .data { font-size:12px; color:#94a3b8; }
        .voltar { display:inline-block; margin-top:20px; padding:10px 15px; background:#3b82f6; color:#fff; border-radius:8px; text-decoration:none;}
        .voltar:hover { background:#2563eb; }
    </style>
</head>
<body>
    <h1>📢 Minhas Notificações</h1>

    <?php if (empty($notificacoes)): ?>
        <p>📭 Nenhuma notificação encontrada.</p>
    <?php else: ?>
        <?php foreach ($notificacoes as $n): ?>
            <div class="card">
                <h3><?= htmlspecialchars($n['titulo']) ?></h3>
                <p><?= nl2br(htmlspecialchars($n['mensagem'])) ?></p>
                <p class="data">Enviado em: <?= date('d/m/Y H:i', strtotime($n['data_envio'])) ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <a href="dashboard.php" class="voltar">⬅ Voltar</a>
</body>
</html>
