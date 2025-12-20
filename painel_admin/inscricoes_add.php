<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../index.html");
    exit();
}

include("../config.php");

// Buscar lista de alunos
$alunos = $conn->query("SELECT id, nome FROM usuarios WHERE tipo='aluno' ORDER BY nome");

// Buscar lista de cursos
$cursos = $conn->query("SELECT id, titulo FROM cursos ORDER BY titulo");

// Salvar nova inscrição
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $aluno_id = intval($_POST['aluno_id']);
    $curso_id = intval($_POST['curso_id']);
    $status   = $conn->real_escape_string($_POST['status']);

    $sql_add = "INSERT INTO inscricoes (aluno_id, curso_id, status) 
                VALUES ('$aluno_id', '$curso_id', '$status')";

    if ($conn->query($sql_add)) {
        header("Location: inscricoes.php?msg=adicionado");
        exit();
    } else {
        $erro = "Erro ao adicionar inscrição: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Adicionar Inscrição</title>
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
        }
        h2 { margin-bottom: 20px; color: #333; }
        form label { display: block; margin-top: 10px; font-weight: bold; }
        form select, form input[type=text] {
            width: 100%; padding: 8px; margin-top: 5px;
            border: 1px solid #ccc; border-radius: 4px;
        }
        button {
            margin-top: 15px;
            padding: 10px 18px;
            background: #28a745;
            color: #fff; border: none; border-radius: 5px;
            cursor: pointer;
        }
        button:hover { background: #218838; }
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
        <h2>Adicionar Nova Inscrição</h2>

        <?php if (isset($erro)): ?>
            <div class="erro"><?= $erro ?></div>
        <?php endif; ?>

        <form method="post">
            <label>Aluno:</label>
            <select name="aluno_id" required>
                <option value="">-- Selecione um aluno --</option>
                <?php while ($a = $alunos->fetch_assoc()): ?>
                    <option value="<?= $a['id'] ?>"><?= $a['nome'] ?></option>
                <?php endwhile; ?>
            </select>

            <label>Curso:</label>
            <select name="curso_id" required>
                <option value="">-- Selecione um curso --</option>
                <?php while ($c = $cursos->fetch_assoc()): ?>
                    <option value="<?= $c['id'] ?>"><?= $c['titulo'] ?></option>
                <?php endwhile; ?>
            </select>

            <label>Status:</label>
            <select name="status" required>
                <option value="ativo">Ativo</option>
                <option value="pendente">Pendente</option>
                <option value="cancelado">Cancelado</option>
                <option value="concluido">Concluído</option>
            </select>

            <button type="submit">Salvar Inscrição</button>
        </form>
    </div>
</body>
</html>
