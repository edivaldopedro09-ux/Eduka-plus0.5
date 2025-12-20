<?php
session_start();

// Só admin pode acessar
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once("../config.php");

// --- Criar aluno ---
if (isset($_POST['criar'])) {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $sql = "INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, 'aluno')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $nome, $email, $senha);
    $stmt->execute();
    header("Location: alunos.php");
    exit();
}

// --- Editar aluno ---
if (isset($_POST['editar'])) {
    $id = $_POST['id'];
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    if (!empty($senha)) {
        $sql = "UPDATE usuarios SET nome=?, email=?, senha=? WHERE id=? AND tipo='aluno'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $nome, $email, $senha, $id);
    } else {
        $sql = "UPDATE usuarios SET nome=?, email=? WHERE id=? AND tipo='aluno'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $nome, $email, $id);
    }
    $stmt->execute();
    header("Location: alunos.php");
    exit();
}

// --- Deletar aluno ---
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $sql = "DELETE FROM usuarios WHERE id=? AND tipo='aluno'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: alunos.php");
    exit();
}

// --- Listar alunos ---
$sql = "SELECT * FROM usuarios WHERE tipo='aluno'";
$alunos = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Admin - Alunos</title>
    <style>
        body{font-family:Arial, sans-serif;background:#f3f4f6;margin:0;padding:20px;}
        h2{margin-bottom:15px;}
        form{background:#fff;padding:15px;border-radius:8px;margin-bottom:20px;box-shadow:0 2px 6px rgba(0,0,0,.1);}
        input{width:100%;padding:8px;margin:6px 0;border:1px solid #ccc;border-radius:6px;}
        button{padding:10px 14px;border:none;border-radius:6px;background:#16a34a;color:#fff;cursor:pointer;}
        button:hover{background:#15803d;}
        table{width:100%;border-collapse:collapse;background:#fff;box-shadow:0 2px 6px rgba(0,0,0,.1);}
        th,td{padding:12px;border-bottom:1px solid #ddd;text-align:left;}
        th{background:#16a34a;color:#fff;}
        a{color:#dc2626;text-decoration:none;}
        a:hover{text-decoration:underline;}
        .topbar{margin-bottom:20px;}
        .back{padding:8px 12px;background:#374151;color:#fff;border-radius:6px;text-decoration:none;}
    </style>
</head>
<body>
    <div class="topbar">
        <a class="back" href="./dashboard.php">← Voltar ao Dashboard</a>
    </div>

    <h2>Gerenciar Alunos</h2>

    <!-- Criar -->
    <form method="POST">
        <h3>Novo Aluno</h3>
        <input type="text" name="nome" placeholder="Nome completo" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="text" name="senha" placeholder="Senha" required>
        <button type="submit" name="criar">Cadastrar</button>
    </form>

    <h3>Alunos Cadastrados</h3>
    <table>
        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Email</th>
            <th>Ações</th>
        </tr>
        <?php while ($a = $alunos->fetch_assoc()): ?>
            <tr>
                <td><?php echo $a['id']; ?></td>
                <td><?php echo htmlspecialchars($a['nome']); ?></td>
                <td><?php echo htmlspecialchars($a['email']); ?></td>
                <td>
                    <!-- Form de edição embutido -->
                    <form method="POST" style="display:inline-block;">
                        <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                        <input type="text" name="nome" value="<?php echo htmlspecialchars($a['nome']); ?>" required>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($a['email']); ?>" required>
                        <input type="text" name="senha" placeholder="Nova senha (opcional)">
                        <button type="submit" name="editar">Salvar</button>
                    </form>
                    <a href="?delete=<?php echo $a['id']; ?>" onclick="return confirm('Excluir este aluno?')">Excluir</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
