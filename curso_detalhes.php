<?php
session_start();
require_once("config.php");

// Verificar se foi passado um ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: cursos.php");
    exit();
}

$curso_id = (int)$_GET['id'];

// Buscar curso no banco
$sql = "SELECT c.id, c.titulo, c.descricao, c.imagem, c.criado_em, u.nome AS professor
        FROM cursos c
        LEFT JOIN usuarios u ON c.professor_id = u.id
        WHERE c.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $curso_id);
$stmt->execute();
$result = $stmt->get_result();
$curso = $result->fetch_assoc();

if (!$curso) {
    header("Location: cursos.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($curso['titulo']); ?> - Eduka Plus Angola</title>
  <link rel="stylesheet" href="css/curso_detalhes.css">
</head>
<body>

  <!-- Navbar -->
 <header>
  <div class="logo">Eduka Plus Angola</div>
  <nav id="menu">
    <a href="index.php">Início</a>
    <a href="cursos.php">Cursos</a>
    <a href="login.php" class="btn-login">Login</a>
    <a href="register.php" class="btn-cadastro">Cadastro</a>
  </nav>
  <div class="hamburger" onclick="document.getElementById('menu').classList.toggle('active')">
    <div></div><div></div><div></div>
  </div>
</header>



  <!-- Detalhes do Curso -->
  <section class="container">
    <div class="curso-detalhe">
      <?php if (!empty($curso['imagem'])): ?>
        <img src="uploads/<?php echo htmlspecialchars($curso['imagem']); ?>" alt="Imagem do curso">
      <?php else: ?>
        <img src="uploads/default.jpg" alt="Imagem padrão do curso">
      <?php endif; ?>

      <h1><?php echo htmlspecialchars($curso['titulo']); ?></h1>
      <div class="info">
        Professor: <strong><?php echo htmlspecialchars($curso['professor'] ?? "Não definido"); ?></strong> <br>
        Criado em: <?php echo date("d/m/Y", strtotime($curso['criado_em'])); ?>
      </div>
      <p><?php echo nl2br(htmlspecialchars($curso['descricao'])); ?></p>
      <a href="inscrever.php?curso_id=<?php echo $curso['id']; ?>" class="btn">Inscrever-se</a>
    </div>
  </section>

  <!-- Footer -->
  <footer>
    <div>
      <a href="ajuda.php">Ajuda</a> |
      <a href="privacidade.php">Privacidade</a> |
      <a href="termos.php">Termos</a>
    </div>
    <p>© <?php echo date("Y"); ?> Eduka Plus Angola — Todos os direitos reservados.</p>
  </footer>

  <script src="js/curso_detalhes"></script>
</body>
</html>
