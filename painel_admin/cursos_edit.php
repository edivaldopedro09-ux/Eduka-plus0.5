<?php
session_start();
require_once("../config.php");

// ✅ Verificar se o usuário é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$mensagem = "";
$erro = "";

// ✅ Verificar ID do curso
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: cursos.php");
    exit();
}

$curso_id = intval($_GET['id']);

// ✅ Buscar informações do curso
$sql = "SELECT * FROM cursos WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $curso_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: cursos.php");
    exit();
}

$curso = $result->fetch_assoc();

// ✅ Atualizar curso
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao']);
    $professor_id = intval($_POST['professor_id']);

    // Upload da imagem (se enviada)
    $imagem = $curso['imagem']; // manter a existente
    if (!empty($_FILES['imagem']['name'])) {
        $target_dir = "../uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir);

        $target_file = $target_dir . time() . "_" . basename($_FILES["imagem"]["name"]);
        if (move_uploaded_file($_FILES["imagem"]["tmp_name"], $target_file)) {
            $imagem = $target_file;
        }
    }

    // Atualizar no banco
    $sql = "UPDATE cursos SET titulo=?, descricao=?, professor_id=?, imagem=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssisi", $titulo, $descricao, $professor_id, $imagem, $curso_id);

    if ($stmt->execute()) {
        $mensagem = "✅ Curso atualizado com sucesso!";
        // Atualiza variáveis locais
        $curso['titulo'] = $titulo;
        $curso['descricao'] = $descricao;
        $curso['professor_id'] = $professor_id;
        $curso['imagem'] = $imagem;
    } else {
        $erro = "❌ Erro ao atualizar curso!";
    }
}

// ✅ Buscar professores para o select
$sql = "SELECT id, nome FROM usuarios WHERE tipo='professor'";
$professores = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Editar Curso — Painel Admin</title>
  <style>
    body { font-family: Arial, sans-serif; background:#0f172a; color:#fff; margin:0; padding:0; }
    .container { width: 600px; margin: 40px auto; background:#111827; padding:30px; border-radius:12px; }
    h2 { margin-bottom:20px; color:#fbbf24; }
    input, textarea, select { width:100%; padding:10px; margin:8px 0; border-radius:6px; border:1px solid #333; background:#1f2937; color:#fff; }
    button { padding:12px; border:none; border-radius:6px; background:#f59e0b; color:#000; font-weight:bold; cursor:pointer; width:100%; }
    button:hover { background:#d97706; }
    .msg { margin:10px 0; padding:10px; border-radius:6px; }
    .sucesso { background:#065f46; color:#bbf7d0; }
    .erro { background:#7f1d1d; color:#fecaca; }
    .preview { margin:10px 0; }
    .preview img { max-width:200px; border-radius:8px; }
    a { color:#fbbf24; text-decoration:none; display:inline-block; margin-top:12px; }
  </style>
</head>
<body>
  <div class="container">
    <h2>✏️ Editar Curso</h2>

    <?php if (!empty($mensagem)) echo "<div class='msg sucesso'>$mensagem</div>"; ?>
    <?php if (!empty($erro)) echo "<div class='msg erro'>$erro</div>"; ?>

    <form method="POST" enctype="multipart/form-data">
      <label>Título</label>
      <input type="text" name="titulo" value="<?php echo htmlspecialchars($curso['titulo']); ?>" required>

      <label>Descrição</label>
      <textarea name="descricao" rows="4" required><?php echo htmlspecialchars($curso['descricao']); ?></textarea>

      <label>Professor</label>
      <select name="professor_id" required>
        <option value="">-- Selecione --</option>
        <?php while ($prof = $professores->fetch_assoc()) { ?>
          <option value="<?php echo $prof['id']; ?>" <?php if ($prof['id'] == $curso['professor_id']) echo "selected"; ?>>
            <?php echo htmlspecialchars($prof['nome']); ?>
          </option>
        <?php } ?>
      </select>

      <label>Imagem</label>
      <input type="file" name="imagem" accept="image/*">
      <?php if (!empty($curso['imagem'])) { ?>
        <div class="preview"><img src="<?php echo $curso['imagem']; ?>" alt="Imagem do Curso"></div>
      <?php } ?>

      <button type="submit">💾 Salvar Alterações</button>
    </form>

    <a href="cursos.php">⬅ Voltar para lista</a>
  </div>
</body>
</html>
