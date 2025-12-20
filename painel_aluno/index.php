<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'aluno') {
    header("Location: ../index.php");
    exit();
}

require_once("../config.php");

// Buscar todos os cursos
$sql = "SELECT c.id, c.titulo, c.descricao, u.nome AS professor 
        FROM cursos c
        LEFT JOIN usuarios u ON c.professor_id = u.id
        ORDER BY c.id DESC";
$cursos = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>📚 Painel do Aluno</title>
<style>
body { font-family: Arial, sans-serif; background: #f8fafc; margin:0; padding:20px; }
h1 { color:#1e293b; }
.cursos { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px,1fr)); gap:20px; margin-top:20px; }
.card { background:#fff; padding:20px; border-radius:12px; box-shadow:0 4px 8px rgba(0,0,0,.1); }
.card h3 { margin:0; color:#0f172a; }
.card p { color:#475569; font-size:14px; }
.card small { color:#64748b; }
button { background:#2563eb; color:#fff; padding:8px 12px; border:none; border-radius:8px; cursor:pointer; margin-top:10px; }
button:hover { background:#1d4ed8; }
</style>
</head>
<body>
    <h1>📚 Meus Cursos</h1>
    <p>Aqui estão todos os cursos disponíveis na plataforma:</p>

    <div class="cursos">
        <?php if ($cursos): ?>
            <?php foreach ($cursos as $curso): ?>
                <div class="card">
                    <h3><?= htmlspecialchars($curso['titulo']); ?></h3>
                    <p><?= htmlspecialchars($curso['descricao']); ?></p>
                    <small>👨‍🏫 Professor: <?= htmlspecialchars($curso['professor'] ?? "Admin"); ?></small><br>
                    <form action="ver_curso.php" method="GET">
                        <input type="hidden" name="id" value="<?= $curso['id']; ?>">
                        <button type="submit">➡️ Ver Curso</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Nenhum curso cadastrado ainda.</p>
        <?php endif; ?>
    </div>
</body>
</html>
