<?php
session_start();
include("../config.php");

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.html");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$usuario_tipo = $_SESSION['usuario_tipo']; // 'aluno' ou 'professor'

// Pega o curso
if (!isset($_GET['curso_id'])) {
    die("Curso não informado.");
}
$curso_id = intval($_GET['curso_id']);

// Identifica professor do curso
$sql_prof = $conn->prepare("SELECT professor_id FROM cursos WHERE id = ?");
$sql_prof->bind_param("i", $curso_id);
$sql_prof->execute();
$res_prof = $sql_prof->get_result();
if ($res_prof->num_rows == 0) {
    die("Curso não encontrado.");
}
$professor_id = $res_prof->fetch_assoc()['professor_id'];

// Salvar nova mensagem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['mensagem'])) {
    $mensagem = trim($_POST['mensagem']);
    $remetente = $usuario_tipo;

    if ($usuario_tipo == "aluno") {
        $aluno_id = $usuario_id;
    } else {
        // professor
        $aluno_id = intval($_POST['aluno_id']); // professor deve escolher o aluno na conversa
    }

    $sql_insert = $conn->prepare("INSERT INTO mensagens (curso_id, aluno_id, professor_id, remetente, mensagem) VALUES (?,?,?,?,?)");
    $sql_insert->bind_param("iiiss", $curso_id, $aluno_id, $professor_id, $remetente, $mensagem);
    $sql_insert->execute();
    header("Location: chat.php?curso_id=$curso_id&aluno_id=$aluno_id");
    exit();
}

// Determina o aluno_id
if ($usuario_tipo == "aluno") {
    $aluno_id = $usuario_id;
} else {
    $aluno_id = isset($_GET['aluno_id']) ? intval($_GET['aluno_id']) : 0;
}

// Buscar mensagens
$mensagens = [];
if ($aluno_id > 0) {
    $sql_msgs = $conn->prepare("SELECT * FROM mensagens WHERE curso_id=? AND aluno_id=? AND professor_id=? ORDER BY data_envio ASC");
    $sql_msgs->bind_param("iii", $curso_id, $aluno_id, $professor_id);
    $sql_msgs->execute();
    $res_msgs = $sql_msgs->get_result();
    while ($row = $res_msgs->fetch_assoc()) {
        $mensagens[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Chat do Curso</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f9;
            margin: 0;
            padding: 0;
        }
        .chat-container {
            max-width: 800px;
            margin: 40px auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 80vh;
        }
        .chat-header {
            background: #0077cc;
            color: #fff;
            padding: 15px;
            font-size: 18px;
            font-weight: bold;
        }
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }
        .msg {
            margin: 10px 0;
            max-width: 70%;
            padding: 10px;
            border-radius: 6px;
            clear: both;
        }
        .aluno {
            background: #e1f5fe;
            float: left;
        }
        .professor {
            background: #c8e6c9;
            float: right;
        }
        .msg small {
            display: block;
            margin-top: 5px;
            font-size: 11px;
            color: #555;
        }
        .chat-form {
            display: flex;
            border-top: 1px solid #ddd;
        }
        .chat-form textarea {
            flex: 1;
            padding: 10px;
            border: none;
            resize: none;
        }
        .chat-form button {
            padding: 10px 20px;
            border: none;
            background: #0077cc;
            color: #fff;
            cursor: pointer;
        }
        .chat-form button:hover {
            background: #005fa3;
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            Chat do Curso #<?php echo $curso_id; ?>
        </div>
        <div class="chat-messages">
            <?php if ($aluno_id == 0 && $usuario_tipo == "professor"): ?>
                <p>Selecione um aluno para conversar.</p>
            <?php else: ?>
                <?php if (empty($mensagens)): ?>
                    <p>Nenhuma mensagem ainda.</p>
                <?php else: ?>
                    <?php foreach ($mensagens as $m): ?>
                        <div class="msg <?php echo $m['remetente']; ?>">
                            <?php echo htmlspecialchars($m['mensagem']); ?>
                            <small><?php echo ucfirst($m['remetente']); ?> - <?php echo $m['data_envio']; ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php if ($aluno_id > 0 || $usuario_tipo == "aluno"): ?>
        <form class="chat-form" method="post">
            <textarea name="mensagem" placeholder="Digite sua mensagem..." required></textarea>
            <?php if ($usuario_tipo == "professor"): ?>
                <input type="hidden" name="aluno_id" value="<?php echo $aluno_id; ?>">
            <?php endif; ?>
            <button type="submit">Enviar</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>
