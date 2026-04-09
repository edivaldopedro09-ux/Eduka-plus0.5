<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../index.html");
    exit();
}

include("../config.php");

$sql = "SELECT i.id, u.nome AS aluno, c.titulo AS curso, i.status 
        FROM inscricoes i
        JOIN usuarios u ON i.aluno_id = u.id
        JOIN cursos c ON i.curso_id = c.id
        ORDER BY i.id DESC";
$result = $conn->query($sql);

// Mensagem de feedback
$msg = "";
$tipo_msg = "";
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case "adicionado":
            $msg = "Inscrição adicionada com sucesso!";
            $tipo_msg = "sucesso";
            break;
        case "editado":
            $msg = "Inscrição editada com sucesso!";
            $tipo_msg = "sucesso";
            break;
        case "excluido":
            $msg = "Inscrição excluída com sucesso!";
            $tipo_msg = "sucesso";
            break;
        case "erro":
            $msg = "Ocorreu um erro na operação!";
            $tipo_msg = "erro";
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel - Inscrições</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0; padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        h2 { margin-bottom: 20px; color: #333; }
        table {
            width: 100%; border-collapse: collapse; margin-top: 15px;
        }
        th, td {
            padding: 12px; border-bottom: 1px solid #ddd; text-align: left;
        }
        th { background: #007bff; color: #fff; }
        tr:nth-child(even) { background: #f9f9f9; }
        a.btn {
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 4px;
            color: #fff;
            font-size: 14px;
        }
        .btn-add { background: #28a745; }
        .btn-edit { background: #ffc107; color: #000; }
        .btn-del { background: #dc3545; }
        .msg {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-weight: bold;
        }
        .sucesso { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .erro { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Gerenciar Inscrições</h2>

        <?php if ($msg): ?>
            <div class="msg <?= $tipo_msg ?>"><?= $msg ?></div>
        <?php endif; ?>

        <a href="inscricoes_add.php" class="btn btn-add">+ Nova Inscrição</a>

        <table>
            <tr>
                <th>ID</th>
                <th>Aluno</th>
                <th>Curso</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= $row['aluno'] ?></td>
                <td><?= $row['curso'] ?></td>
                <td><?= ucfirst($row['status']) ?></td>
                <td>
                    <a href="inscricoes_edit.php?id=<?= $row['id'] ?>" class="btn btn-edit">Editar</a>
                    <a href="inscricoes_delete.php?id=<?= $row['id'] ?>" class="btn btn-del" onclick="return confirm('Tem certeza que deseja excluir?')">Excluir</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>
