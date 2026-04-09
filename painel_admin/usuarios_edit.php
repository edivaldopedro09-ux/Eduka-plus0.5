<?php
session_start();
require_once("../config.php");

// 🔒 Verifica se é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Verifica se veio o ID
if (!isset($_GET['id'])) {
    header("Location: usuarios.php");
    exit();
}

$id = intval($_GET['id']);

// Busca os dados do usuário
$sql = "SELECT * FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();

if (!$usuario) {
    echo "Usuário não encontrado!";
    exit();
}

// Atualiza os dados
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $tipo = $_POST['tipo'];

    // Atualiza senha apenas se o admin digitar uma nova
    if (!empty($_POST['senha'])) {
        $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    } else {
        $senha = $usuario['senha'];
    }

    // Upload da foto
    $foto = $usuario['foto'];
    if (!empty($_FILES['foto']['name'])) {
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $novo_nome = uniqid() . "." . strtolower($ext);
        $destino = "../uploads/" . $novo_nome;

        if (move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
            $foto = $novo_nome;
        }
    }

    $sql_update = "UPDATE usuarios SET nome=?, email=?, senha=?, tipo=?, foto=? WHERE id=?";
    $stmt_up = $conn->prepare($sql_update);
    $stmt_up->bind_param("sssssi", $nome, $email, $senha, $tipo, $foto, $id);

    if ($stmt_up->execute()) {
        echo "<script>alert('Usuário atualizado com sucesso!'); window.location='usuarios.php';</script>";
    } else {
        echo "Erro: " . $stmt_up->error;
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Editar Usuário — Painel Admin</title>
  <style>
    body { font-family: Arial, sans-serif; background:#f1f5f9; margin:0; }
    header { background:#0f172a; color:#fff; padding:16px; }
    h1 { margin:0; font-size:20px; }
    .container { padding:20px; max-width:500px; margin:auto; }
    form { background:#fff; padding:20px; border-radius:10px; box-shadow:0 5px 15px rgba(0,0,0,.1); }
    input, select {
        width:100%; padding:12px; margin:8px 0; border-radius:8px; border:1px solid #ccc;
    }
    input:focus, select:focus { outline:none; border-color:#0f172a; }
    .btn {
        width:100%; padding:12px; margin-top:14px; border:none; border-radius:8px;
        background:#0f172a; color:#fff; font-weight:700; cursor:pointer;
        transition:.2s;
    }
    .btn:hover { background:#1e293b; }
    a { text-decoration:none; color:#0f172a; font-weight:bold; display:inline-block; margin-top:15px; }
    .avatar { margin:10px 0; }
    .avatar img { width:80px; height:80px; border-radius:50%; object-fit:cover; }
  </style>
</head>
<body>
  <header>
    <h1>Painel Admin — Editar Usuário</h1>
  </header>

  <div class="container">
    <form method="post" enctype="multipart/form-data">
        <input type="text" name="nome" value="<?= htmlspecialchars($usuario['nome']) ?>" required>
        <input type="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" required>
        <input type="password" name="senha" placeholder="Nova senha (opcional)">
        <select name="tipo" required>
            <option value="aluno" <?= $usuario['tipo'] == 'aluno' ? 'selected' : '' ?>>Aluno</option>
            <option value="professor" <?= $usuario['tipo'] == 'professor' ? 'selected' : '' ?>>Professor</option>
            <option value="admin" <?= $usuario['tipo'] == 'admin' ? 'selected' : '' ?>>Administrador</option>
        </select>

        <div class="avatar">
            <?php if ($usuario['foto']) { ?>
                <p>Foto atual:</p>
                <img src="../uploads/<?= $usuario['foto'] ?>" alt="Foto">
            <?php } ?>
        </div>

        <div>
            Alterar foto: <br>
            <input type="file" name="foto" accept="image/*">
        </div>

        <button type="submit" class="btn">Salvar alterações</button>
    </form>
    <a href="usuarios.php">⬅ Voltar</a>
  </div>
</body>
</html>
