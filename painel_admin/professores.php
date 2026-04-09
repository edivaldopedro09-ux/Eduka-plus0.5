<?php
session_start();

// Só admin pode acessar
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once("../config.php");

// --- Criar professor ---
if (isset($_POST['criar'])) {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $sql = "INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, 'professor')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $nome, $email, $senha);
    $stmt->execute();
    header("Location: professores.php");
    exit();
}

// --- Editar professor ---
if (isset($_POST['editar'])) {
    $id = $_POST['id'];
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    if (!empty($senha)) {
        $sql = "UPDATE usuarios SET nome=?, email=?, senha=? WHERE id=? AND tipo='professor'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $nome, $email, $senha, $id);
    } else {
        $sql = "UPDATE usuarios SET nome=?, email=? WHERE id=? AND tipo='professor'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $nome, $email, $id);
    }
    $stmt->execute();
    header("Location: professores.php");
    exit();
}

// --- Deletar professor ---
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $sql = "DELETE FROM usuarios WHERE id=? AND tipo='professor'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: professores.php");
    exit();
}

// --- Listar professores ---
$sql = "SELECT * FROM usuarios WHERE tipo='professor'";
$professores = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Admin - Professores</title>
    <style>
        body{font-family:Arial, sans-serif;background:#f3f4f6;margin:0;padding:20px;}
        h2{margin-bottom:15px;}
        form{background:#fff;padding:15px;border-radius:8px;margin-bottom:20px;box-shadow:0 2px 6px rgba(0,0,0,.1);}
        input{width:100%;padding:8px;margin:6px 0;border:1px solid #ccc;border-radius:6px;}
        button{padding:10px 14px;border:none;border-radius:6px;background:#2563eb;color:#fff;cursor:pointer;}
        button:hover{background:#1e40af;}
        table{width:100%;border-collapse:collapse;background:#fff;box-shadow:0 2px 6px rgba(0,0,0,.1);}
        th,td{padding:12px;border-bottom:1px solid #ddd;text-align:left;}
        th{background:#2563eb;color:#fff;}
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

    <h2>Gerenciar Professores</h2>

    <!-- Criar -->
    <form method="POST">
        <h3>Novo Professor</h3>
        <input type="text" name="nome" placeholder="Nome completo" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="text" name="senha" placeholder="Senha" required>
        <button type="submit" name="criar">Cadastrar</button>
    </form>

    <h3>Professores Cadastrados</h3>
    <table>
        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Email</th>
            <th>Ações</th>
        </tr>
        <?php while ($p = $professores->fetch_assoc()): ?>
            <tr>
                <td><?php echo $p['id']; ?></td>
                <td><?php echo htmlspecialchars($p['nome']); ?></td>
                <td><?php echo htmlspecialchars($p['email']); ?></td>
                <td>
                    <!-- Form de edição embutido -->
                    <form method="POST" style="display:inline-block;">
                        <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                        <input type="text" name="nome" value="<?php echo htmlspecialchars($p['nome']); ?>" required>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($p['email']); ?>" required>
                        <input type="text" name="senha" placeholder="Nova senha (opcional)">
                        <button type="submit" name="editar">Salvar</button>
                    </form>
                    <a href="?delete=<?php echo $p['id']; ?>" onclick="return confirm('Excluir este professor?')">Excluir</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
