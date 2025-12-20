<?php
session_start();

// Segurança: só aluno pode acessar
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'aluno') {
    header("Location: ../index.php");
    exit();
}

require_once("../config.php");

$aluno_id = $_SESSION['usuario_id'];
$nome_aluno = $_SESSION['usuario_nome'] ?? "Aluno";

// Buscar cursos em que o aluno está inscrito
$sql = "SELECT c.id, c.titulo 
        FROM inscricoes i
        INNER JOIN cursos c ON i.curso_id = c.id
        WHERE i.aluno_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $aluno_id);
$stmt->execute();
$cursos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Buscar materiais dos cursos do aluno
$materiais = [];
if (count($cursos) > 0) {
    // cria string segura de ids (já vindo do banco)
    $ids = implode(",", array_map(fn($c) => intval($c['id']), $cursos));

    $sql_m = "SELECT m.*, c.titulo AS curso 
              FROM materiais m 
              INNER JOIN cursos c ON m.curso_id = c.id
              WHERE m.curso_id IN ($ids)
              ORDER BY m.id DESC"; // ordena pelo id (últimos primeiro)
    $materiais = $conn->query($sql_m)->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>📂 Materiais — Aluno</title>
    <style>
        :root { 
            --bg:#0f172a; --card:#111827; --muted:#94a3b8; 
            --text:#e5e7eb; --primary:#3b82f6; --accent:#60a5fa;
        }
        body {
            margin:0; font-family: system-ui, -apple-system, Segoe UI, Roboto;
            background: linear-gradient(135deg,#0b1220,#0e1620 50%,#0b1220);
            background-size: 200% 200%;
            animation: gradient 12s ease infinite;
            color: var(--text); min-height:100vh; 
            display:grid; grid-template-columns: 240px 1fr;
        }
        @keyframes gradient {
            0%{background-position:0% 50%}
            50%{background-position:100% 50%}
            100%{background-position:0% 50%}
        }
        aside {
            background:#0b1020; padding:22px; border-right:1px solid #1f2937;
        }
        .brand {
            font-weight:700; margin-bottom:22px; display:flex; gap:8px; align-items:center;
            color: var(--primary);
        }
        .dot {
            width:10px; height:10px; border-radius:50%; background:var(--primary); 
            box-shadow:0 0 14px var(--primary);
        }
        nav a {
            display:block; padding:10px; margin:6px 0; border-radius:10px;
            text-decoration:none; color:var(--text); background:#0f172a;
            border:1px solid #1f2937; transition:.2s;
        }
        nav a:hover {
            transform:translateX(4px); border-color:var(--accent);
            background:#1e293b;
        }
        header {
            display:flex; justify-content:space-between; align-items:center;
            padding:18px 24px; border-bottom:1px solid #1f2937;
            background:rgba(17,24,39,0.55); backdrop-filter: blur(6px);
        }
        .content { padding:24px; }
        h2 { margin-bottom:18px; color: var(--accent); }
        .card {
            background:var(--card); border-radius:14px; padding:20px;
            border:1px solid #1f2937; box-shadow:0 8px 26px rgba(0,0,0,.25);
            margin-bottom:20px; transition:.25s;
        }
        .card:hover { transform:translateY(-4px); border-color: var(--accent); }
        .card h3 { margin-top:0; color: var(--primary); }
        .muted { color:var(--muted); font-size:14px; }
        .download {
            display:inline-block; margin-top:10px; padding:8px 14px;
            background:var(--primary); color:#fff; border-radius:10px;
            text-decoration:none; font-size:14px; transition:.2s;
        }
        .download:hover { background:var(--accent); }
        .no-material { color:var(--muted); padding:20px; background:rgba(255,255,255,0.02); border-radius:10px; }
        .meta { font-size:13px; color:var(--muted); margin-top:8px; }
    </style>
</head>
<body>
    <aside>
        <div class="brand"><span class="dot"></span> Plataforma • Aluno</div>
        <nav>
            <a href="./dashboard.php">🏠 Dashboard</a>
            <a href="./meus_cursos.php">📚 Meus Cursos</a>
            <a href="./progresso.php">📊 Progresso</a>
            <a href="./materiais.php">📂 Materiais</a>
            <a href="../index.php">↩ Sair</a>
        </nav>
    </aside>

    <main>
        <header>
            <div>📂 Materiais de <strong><?php echo htmlspecialchars($nome_aluno); ?></strong></div>
        </header>
        <div class="content">
            <h2>Materiais dos meus cursos</h2>

            <?php if (count($materiais) > 0): ?>
                <?php foreach ($materiais as $m): ?>
                    <div class="card">
                        <h3><?php echo htmlspecialchars($m['titulo'] ?? 'Untitled'); ?></h3>

                        <p class="muted">Curso: <?php echo htmlspecialchars($m['curso'] ?? '—'); ?></p>

                        <?php
                        // Para evitar o warning: tenta várias chaves comuns que as pessoas usam
                        $descricao = '';
                        if (array_key_exists('descricao', $m) && $m['descricao'] !== null && $m['descricao'] !== '') {
                            $descricao = $m['descricao'];
                        } elseif (array_key_exists('desc', $m) && $m['desc'] !== null && $m['desc'] !== '') {
                            $descricao = $m['desc'];
                        } elseif (array_key_exists('resumo', $m) && $m['resumo'] !== null && $m['resumo'] !== '') {
                            $descricao = $m['resumo'];
                        } elseif (array_key_exists('detalhes', $m) && $m['detalhes'] !== null && $m['detalhes'] !== '') {
                            $descricao = $m['detalhes'];
                        } else {
                            $descricao = '';
                        }
                        ?>

                        <?php if ($descricao !== ''): ?>
                            <p><?php echo nl2br(htmlspecialchars($descricao)); ?></p>
                        <?php else: ?>
                            <p class="muted">Sem descrição disponível.</p>
                        <?php endif; ?>

                        <?php
                        // Verifica se campo 'arquivo' existe e arquivo realmente existe no disco
                        $arquivo_ok = false;
                        if (array_key_exists('arquivo', $m) && !empty($m['arquivo'])) {
                            $filePath = __DIR__ . '/../uploads/' . $m['arquivo'];
                            if (file_exists($filePath)) {
                                $arquivo_ok = true;
                                $fileUrl = '../uploads/' . rawurlencode($m['arquivo']);
                            }
                        }
                        ?>

                        <?php if ($arquivo_ok): ?>
                            <a class="download" href="<?php echo $fileUrl; ?>" download>⬇️ Baixar</a>
                            <div class="meta">Arquivo: <?php echo htmlspecialchars($m['arquivo']); ?></div>
                        <?php else: ?>
                            <div class="no-material">Nenhum arquivo disponível para download.</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="muted">Ainda não há materiais disponíveis para seus cursos.</p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
