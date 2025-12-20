<?php
session_start();
require_once("../config.php");

// 🔒 Verificação de login e tipo
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao']);
    $professor_id = intval($_POST['professor_id']);

    // Upload da imagem (opcional)
    $imagem = null;
    if (!empty($_FILES['imagem']['name'])) {
        $pasta = "../uploads/cursos/";
        if (!is_dir($pasta)) {
            mkdir($pasta, 0777, true);
        }

        $nomeArquivo = uniqid() . "_" . basename($_FILES["imagem"]["name"]);
        $caminho = $pasta . $nomeArquivo;

        if (move_uploaded_file($_FILES["imagem"]["tmp_name"], $caminho)) {
            $imagem = "uploads/cursos/" . $nomeArquivo;
        }
    }

    // Insere no banco
    $stmt = $conn->prepare("INSERT INTO cursos (titulo, descricao, professor_id, imagem) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssis", $titulo, $descricao, $professor_id, $imagem);

    if ($stmt->execute()) {
        header("Location: cursos.php?msg=Curso adicionado com sucesso!");
        exit();
    } else {
        $erro = "Erro ao adicionar curso: " . $stmt->error;
    }
    $stmt->close();
}

// Busca os professores para o select
$professores = $conn->query("SELECT id, nome FROM usuarios WHERE tipo = 'professor'");
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Adicionar Curso</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; }
        .container { max-width: 600px; margin: 50px auto; background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; margin-bottom: 20px; }
        label { font-weight: bold; display: block; margin: 12px 0 6px; }
        input, textarea, select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 8px; }
        button { margin-top: 20px; width: 100%; background: #007bff; color: #fff; border: none; padding: 12px; border-radius: 8px; cursor: pointer; font-size: 16px; }
        button:hover { background: #0056b3; }
        .msg { color: red; text-align: center; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Adicionar Curso</h2>

        <?php if (!empty($erro)): ?>
            <p class="msg"><?= $erro ?></p>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <label for="titulo">Título</label>
            <input type="text" id="titulo" name="titulo" required>

            <label for="descricao">Descrição</label>
            <textarea id="descricao" name="descricao" rows="4" required></textarea>

            <label for="professor_id">Professor</label>
            <select id="professor_id" name="professor_id" required>
                <option value="">-- Selecione --</option>
                <?php while ($p = $professores->fetch_assoc()): ?>
                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?></option>
                <?php endwhile; ?>
            </select>

            <label for="imagem">Imagem do Curso (opcional)</label>
            <input type="file" id="imagem" name="imagem" accept="image/*">

            <button type="submit">Cadastrar Curso</button>
        </form>
    </div>
</body>
</html>
