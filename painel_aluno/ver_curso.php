<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'aluno') {
    header("Location: ../index.php");
    exit();
}

require_once("../config.php");

$aluno_id = $_SESSION['usuario_id'];

// Verifica se o ID foi passado
if (!isset($_GET['id'])) {
    echo "Curso não encontrado.";
    exit();
}

$curso_id = intval($_GET['id']);

// Buscar informações do curso
$sql = "SELECT c.id, c.titulo, c.descricao, u.nome AS professor 
        FROM cursos c
        LEFT JOIN usuarios u ON c.professor_id = u.id
        WHERE c.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $curso_id);
$stmt->execute();
$curso = $stmt->get_result()->fetch_assoc();

if (!$curso) {
    echo "Curso não encontrado.";
    exit();
}

// Verificar se o aluno já está inscrito
$sql = "SELECT * FROM inscricoes WHERE aluno_id = ? AND curso_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $aluno_id, $curso_id);
$stmt->execute();
$inscricao = $stmt->get_result()->fetch_assoc();

// Se o aluno clicar em "Matricular-se"
if (isset($_POST['matricular']) && !$inscricao) {
    $sql = "INSERT INTO inscricoes (aluno_id, curso_id, data_inscricao) VALUES (?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $aluno_id, $curso_id);
    $stmt->execute();
    header("Location: ver_curso.php?id=" . $curso_id);
    exit();
}

// Buscar materiais só se o aluno estiver matriculado
$materiais = [];
if ($inscricao) {
    $sql = "SELECT * FROM materiais WHERE curso_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $curso_id);
    $stmt->execute();
    $materiais = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($curso['titulo']); ?> - Plataforma</title>
<style>
body { font-family: Arial, sans-serif; background: #f8fafc; margin:0; padding:20px; }
h1 { color:#1e293b; }
.card { background:#fff; padding:20px; border-radius:12px; box-shadow:0 4px 8px rgba(0,0,0,.1); margin-bottom:20px; }
button { background:#2563eb; color:#fff; padding:8px 12px; border:none; border-radius:8px; cursor:pointer; margin-top:10px; }
button:hover { background:#1d4ed8; }
ul { padding-left:20px; }
.alert { padding:10px; border-radius:8px; margin-top:10px; }
.success { background:#dcfce7; color:#166534; }
.warning { background:#fef9c3; color:#854d0e; }
</style>
</head>
<body>
    <h1>📚 <?= htmlspecialchars($curso['titulo']); ?></h1>
    <div class="card">
        <p><?= nl2br(htmlspecialchars($curso['descricao'])); ?></p>
        <p><strong>👨‍🏫 Professor:</strong> <?= htmlspecialchars($curso['professor'] ?? "Admin"); ?></p>
        
        <?php if ($inscricao): ?>
            <div class="alert success">✔ Você já está matriculado neste curso!</div>
        <?php else: ?>
            <form method="POST">
                <button type="submit" name="matricular">➡️ Matricular-se neste curso</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>📂 Materiais do Curso</h2>
        <?php if ($inscricao): ?>
            <?php if ($materiais): ?>
                <ul>
                    <?php foreach ($materiais as $mat): ?>
                        <li>
                            <?= htmlspecialchars($mat['titulo']); ?> - 
                            <a href="../uploads/<?= htmlspecialchars($mat['arquivo']); ?>" target="_blank">📥 Baixar</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>Nenhum material disponível ainda.</p>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert warning">⚠️ Faça a inscrição para acessar os materiais.</div>
        <?php endif; ?>
    </div>
</body>
</html>
