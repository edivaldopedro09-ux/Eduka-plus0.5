<?php
session_start();
include("../config.php");

// Verifica login
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'professor') {
    header("Location: ../index.html");
    exit();
}

$prof_id = $_SESSION['usuario_id'];

// Verifica curso
if (!isset($_GET['curso_id'])) {
    die("Curso não informado.");
}
$curso_id = intval($_GET['curso_id']);

// Confirma se o professor é dono do curso
$sql = $conn->prepare("SELECT * FROM cursos WHERE id=? AND professor_id=?");
$sql->bind_param("ii", $curso_id, $prof_id);
$sql->execute();
$res = $sql->get_result();
if ($res->num_rows == 0) {
    die("Você não é responsável por este curso.");
}

// Enviar mensagem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['mensagem'])) {
    $mensagem = trim($_POST['mensagem']);
    if ($mensagem !== "") {
        $stmt = $conn->prepare("INSERT INTO mensagens (curso_id, usuario_id, mensagem, data_envio) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $curso_id, $prof_id, $mensagem);
        $stmt->execute();
    }
}

// Buscar mensagens
$sql = $conn->prepare("
    SELECT m.*, u.nome, u.tipo 
    FROM mensagens m
    INNER JOIN usuarios u ON m.usuario_id = u.id
    WHERE m.curso_id=?
    ORDER BY m.data_envio ASC
");
$sql->bind_param("i", $curso_id);
$sql->execute();
$mensagens = $sql->get_result();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Chat da Turma (Professor)</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f4f6f9; margin:0; padding:0; }
        .chat-container { max-width:800px; margin:40px auto; background:#fff; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,.1); padding:20px; }
        h1 { color:#0077cc; text-align:center; margin-bottom:15px; }
        .back-btn { display:inline-block; margin-bottom:15px; padding:8px 14px; background:#6c757d; color:#fff; border-radius:5px; text-decoration:none; font-size:14px; }
        .back-btn:hover { background:#5a6268; }
        .messages { border:1px solid #ddd; padding:15px; height:400px; overflow-y:auto; margin-bottom:15px; background:#fafafa; }
        .msg { margin-bottom:12px; padding:8px 12px; border-radius:6px; max-width:70%; clear:both; }
        .msg.professor { background:#0077cc; color:#fff; float:right; }
        .msg.aluno { background:#e9ecef; float:left; }
        .msg strong { display:block; font-size:13px; margin-bottom:4px; }
        form { display:flex; }
        input[type="text"] { flex:1; padding:10px; border:1px solid #ddd; border-radius:6px 0 0 6px; outline:none; }
        button { padding:10px 20px; border:none; background:#28a745; color:#fff; border-radius:0 6px 6px 0; cursor:pointer; }
        button:hover { background:#1e7e34; }
    </style>
</head>
<body>
    <div class="chat-container">
        <a href="dashboard.php" class="back-btn">← Voltar</a>
        <h1>Chat da Turma (Curso #<?php echo $curso_id; ?>)</h1>
        <div class="messages">
            <?php while ($m = $mensagens->fetch_assoc()): ?>
                <div class="msg <?php echo ($m['tipo'] === 'professor') ? 'professor' : 'aluno'; ?>">
                    <strong><?php echo htmlspecialchars($m['nome']); ?></strong>
                    <?php echo nl2br(htmlspecialchars($m['mensagem'])); ?>
                    <div style="font-size:11px; color:#666; margin-top:3px;">
                        <?php echo date("d/m/Y H:i", strtotime($m['data_envio'])); ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <form method="POST">
            <input type="text" name="mensagem" placeholder="Digite sua mensagem..." required>
            <button type="submit">Enviar</button>
        </form>
    </div>
</body>
</html>
