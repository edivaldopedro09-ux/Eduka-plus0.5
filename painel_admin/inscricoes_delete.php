<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../index.html");
    exit();
}

include("../config.php");

// Verificar se veio ID
if (!isset($_GET['id'])) {
    header("Location: inscricoes.php");
    exit();
}

$id = intval($_GET['id']);

// Buscar a inscrição
$sql = "SELECT i.id, u.nome AS aluno, c.titulo AS curso, i.status 
        FROM inscricoes i
        JOIN usuarios u ON i.aluno_id = u.id
        JOIN cursos c ON i.curso_id = c.id
        WHERE i.id = $id";
$res = $conn->query($sql);

if ($res->num_rows == 0) {
    header("Location: inscricoes.php?msg=naoencontrado");
    exit();
}

$inscricao = $res->fetch_assoc();

// Excluir caso confirmado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sql_del = "DELETE FROM inscricoes WHERE id = $id";
    if ($conn->query($sql_del)) {
        header("Location: inscricoes.php?msg=deletado");
        exit();
    } else {
        $erro = "Erro ao excluir: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Excluir Inscrição</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0; padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            text-align: center;
        }
        h2 { margin-bottom: 15px; color: #c82333; }
        p { margin-bottom: 20px; }
        .btn {
            display: inline-block;
            padding: 10px 18px;
            border-radius: 5px;
            text-decoration: none;
            color: #fff;
        }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        .erro {
            margin-top: 10px;
            padding: 10px;
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Confirmar Exclusão</h2>

        <p>Tem certeza que deseja excluir esta inscrição?</p>
        <p><strong>Aluno:</strong> <?= $inscricao['aluno'] ?><br>
           <strong>Curso:</strong> <?= $inscricao['curso'] ?><br>
           <strong>Status:</strong> <?= ucfirst($inscricao['status']) ?></p>

        <?php if (isset($erro)): ?>
            <div class="erro"><?= $erro ?></div>
        <?php endif; ?>

        <form method="post">
            <button type="submit" class="btn btn-danger">Excluir</button>
            <a href="inscricoes.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</body>
</html>
