<?php
session_start();
require_once("config.php");

// Segurança: só usuários logados podem acessar
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$id = $_SESSION['usuario_id'];
$tipo = $_SESSION['usuario_tipo'];
$mensagem = "";

// Determinar link da dashboard de acordo com o tipo de usuário
$dashboard_link = "#";
if ($tipo === "aluno") {
    $dashboard_link = "painel_aluno/dashboard.php";
} elseif ($tipo === "professor") {
    $dashboard_link = "painel_professor/dashboard.php";
} elseif ($tipo === "admin") {
    $dashboard_link = "painel_admin/dashboard.php";
}

// Atualizar foto
if (isset($_POST['atualizar_foto']) && isset($_FILES['foto'])) {
    $file = $_FILES['foto'];
    if ($file['error'] == 0) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $novo_nome = "foto_" . $id . "." . $ext;
        $caminho = "uploads/" . $novo_nome;

        if (!is_dir("uploads")) mkdir("uploads", 0777, true);

        if (move_uploaded_file($file['tmp_name'], $caminho)) {
            $sql = "UPDATE usuarios SET foto = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $caminho, $id);
            if ($stmt->execute()) {
                $_SESSION['usuario_foto'] = $caminho;
                $mensagem = "✅ Foto atualizada com sucesso!";
            }
        } else {
            $mensagem = "❌ Erro ao enviar a foto!";
        }
    }
}

// Atualizar senha
if (isset($_POST['atualizar_senha'])) {
    $senha_atual = $_POST['senha_atual'];
    $nova_senha = $_POST['nova_senha'];
    $confirmar = $_POST['confirmar_senha'];

    $sql = "SELECT senha FROM usuarios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row && password_verify($senha_atual, $row['senha'])) {
        if ($nova_senha === $confirmar) {
            $nova_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $sql = "UPDATE usuarios SET senha = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $nova_hash, $id);
            if ($stmt->execute()) {
                $mensagem = "✅ Senha alterada com sucesso!";
            }
        } else {
            $mensagem = "⚠️ A nova senha e a confirmação não coincidem!";
        }
    } else {
        $mensagem = "❌ Senha atual incorreta!";
    }
}

// Buscar dados do usuário
$sql = "SELECT * FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Meu Perfil</title>
<link rel="stylesheet" href="css/perfil.css">

</head>
<body>
<div class="perfil-container">
    <a href="<?php echo $dashboard_link; ?>" class="btn-voltar">← Voltar</a>

    <div class="perfil-header">
        <?php if ($usuario['foto']) { ?>
            <img src="<?php echo $usuario['foto']; ?>" alt="Foto de perfil">
        <?php } else { ?>
            <img src="https://via.placeholder.com/120" alt="Foto de perfil">
        <?php } ?>
        <h2><?php echo htmlspecialchars($usuario['nome']); ?></h2>
        <p><?php echo htmlspecialchars($usuario['email']); ?></p>
        <p>Tipo: <?php echo htmlspecialchars($usuario['tipo']); ?></p>
    </div>

    <?php if ($mensagem) echo "<div class='msg'>$mensagem</div>"; ?>

    <form method="POST" enctype="multipart/form-data">
        <h3>Atualizar Foto</h3>
        <input type="file" name="foto" required>
        <button type="submit" name="atualizar_foto">Salvar Foto</button>
    </form>

    <form method="POST">
        <h3>Alterar Senha</h3>
        <input type="password" name="senha_atual" placeholder="Senha atual" required>
        <input type="password" name="nova_senha" placeholder="Nova senha" required>
        <input type="password" name="confirmar_senha" placeholder="Confirmar nova senha" required>
        <button type="submit" name="atualizar_senha">Atualizar Senha</button>
    </form>
</div>
</body>
</html>
