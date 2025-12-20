<?php
session_start();
require_once("../config.php");

// 🔒 Verifica se é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $tipo = $_POST['tipo'];

    // Upload da foto
    $foto = null;
    if (!empty($_FILES['foto']['name'])) {
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $novo_nome = uniqid() . "." . strtolower($ext);
        $destino = "../uploads/" . $novo_nome;

        if (move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
            $foto = $novo_nome;
        }
    }

    $sql = "INSERT INTO usuarios (nome, email, senha, tipo, foto) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $nome, $email, $senha, $tipo, $foto);

    if ($stmt->execute()) {
        echo "<script>alert('Usuário cadastrado com sucesso!'); window.location='usuarios.php';</script>";
    } else {
        echo "Erro: " . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Novo Usuário — Painel Admin</title>
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
  </style>
</head>
<body>
  <header>
    <h1>Painel Admin — Adicionar Usuário</h1>
  </header>

  <div class="container">
    <form method="post" enctype="multipart/form-data">
        <input type="text" name="nome" placeholder="Nome completo" required>
        <input type="email" name="email" placeholder="E-mail" required>
        <input type="password" name="senha" placeholder="Senha" required>
        <select name="tipo" required>
            <option value="" disabled selected>Selecione o tipo</option>
            <option value="aluno">Aluno</option>
            <option value="professor">Professor</option>
            <option value="admin">Administrador</option>
        </select>
        <div>
            Foto de perfil: <br>
            <input type="file" name="foto" accept="image/*">
        </div>
        <button type="submit" class="btn">Cadastrar</button>
    </form>
    <a href="usuarios.php">⬅ Voltar</a>
  </div>
</body>
</html>
