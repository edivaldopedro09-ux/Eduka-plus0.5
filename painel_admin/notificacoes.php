<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}
require_once("../config.php");

// Excluir notificação
if (isset($_GET['del'])) {
    $id = intval($_GET['del']);
    $conn->query("DELETE FROM notificacoes WHERE id=$id");
    header("Location: notificacoes.php");
    exit();
}

// Buscar notificações
$sql = "SELECT n.id, n.titulo, n.mensagem, n.data_envio, n.destinatario_tipo, u.nome AS destinatario
        FROM notificacoes n
        LEFT JOIN usuarios u ON n.destinatario_id = u.id
        ORDER BY n.data_envio DESC";
$notificacoes = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Gerir Notificações</title>
    <style>
        :root { --bg:#0f172a; --card:#111827; --muted:#94a3b8; --text:#e5e7eb; --primary:#3b82f6; }
        body{ margin:0; font-family: system-ui, -apple-system, Segoe UI, Roboto;
            background: linear-gradient(120deg,#0b1220,#0e1620 40%,#0b1220 100%);
            color: var(--text); min-height:100vh; display:grid; grid-template-columns: 240px 1fr;}
        aside{ background:#0b1020; padding:22px; border-right:1px solid #1f2937;}
        .brand{ font-weight:700; margin-bottom:22px; display:flex; gap:8px; align-items:center;}
        .dot{ width:10px; height:10px; border-radius:50%; background:var(--primary); box-shadow:0 0 14px var(--primary);}
        nav a{ display:block; padding:10px; margin:6px 0; border-radius:10px; text-decoration:none;
            color:var(--text); background:#0f172a; border:1px solid #1f2937; transition:.2s;}
        nav a:hover{ transform:translateX(3px); border-color:#334155;}
        header{ display:flex; justify-content:space-between; align-items:center; padding:18px 24px; border-bottom:1px solid #1f2937;
            background:rgba(17,24,39,0.4); backdrop-filter: blur(6px);}
        .content{ padding:24px;}
        .card{ background:var(--card); border-radius:14px; padding:20px; border:1px solid #1f2937;
            box-shadow:0 8px 26px rgba(0,0,0,.25); margin-bottom:20px;}
        table{ width:100%; border-collapse: collapse; margin-top:20px;}
        th, td{ padding:12px; border-bottom:1px solid #1f2937; text-align:left;}
        th{ background:#1e293b;}
        .btn{ padding:6px 12px; border-radius:6px; text-decoration:none; font-size:14px;}
        .btn-del{ background:#ef4444; color:#fff;}
        .btn-del:hover{ opacity:0.9;}
    </style>
</head>
<body>
    <aside>
        <div class="brand"><span class="dot"></span> Plataforma • Admin</div>
        <nav>
            <a href="./dashboard.php">🏠 Dashboard</a>
            <a href="./usuarios.php">👥 Usuários</a>
            <a href="./cursos.php">📚 Cursos</a>
            <a href="./inscricoes.php">📝 Inscrições</a>
            <a href="./certificados.php">🎓 Certificados</a>
            <a href="./notificacoes.php">📢 Notificações</a>
            <a href="./notificacoes_add.php">➕ Enviar Notificação</a>
            <a href="../index.php">↩ Sair</a>
        </nav>
    </aside>

    <main>
        <header>
            <h2>📢 Gestão de Notificações</h2>
        </header>
        <div class="content">
            <div class="card">
                <h3>Notificações Enviadas</h3>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Mensagem</th>
                            <th>Data</th>
                            <th>Destino</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($notificacoes): ?>
                            <?php foreach ($notificacoes as $n): ?>
                                <tr>
                                    <td><?= $n['id'] ?></td>
                                    <td><?= htmlspecialchars($n['titulo']) ?></td>
                                    <td><?= htmlspecialchars($n['mensagem']) ?></td>
                                    <td><?= $n['data_envio'] ?></td>
                                    <td>
                                        <?php
                                        if ($n['destinatario_tipo'] === 'todos') echo "🌍 Todos";
                                        elseif ($n['destinatario_tipo'] === 'alunos') echo "🎓 Alunos";
                                        elseif ($n['destinatario_tipo'] === 'professores') echo "👨‍🏫 Professores";
                                        elseif ($n['destinatario_tipo'] === 'usuario') echo "👤 " . htmlspecialchars($n['destinatario']);
                                        ?>
                                    </td>
                                    <td>
                                        <a class="btn btn-del" href="?del=<?= $n['id'] ?>" onclick="return confirm('Tem certeza que deseja excluir esta notificação?')">🗑️ Excluir</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6">Nenhuma notificação enviada.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
