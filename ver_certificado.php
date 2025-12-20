<?php
require_once("config.php");

$codigo = trim($_GET['codigo'] ?? "");

$mensagem = "";
if (!empty($codigo)) {
  $sql = "SELECT cr.id, u.nome AS aluno, c.titulo AS curso, cr.data_emissao
          FROM certificados cr
          JOIN usuarios u ON u.id = cr.aluno_id
          JOIN cursos c ON c.id = cr.curso_id
          WHERE cr.codigo = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $codigo);
  $stmt->execute();
  $res = $stmt->get_result();

  if ($res && $res->num_rows > 0) {
    $cert = $res->fetch_assoc();
    $mensagem = "
      <div class='card' style='text-align:center;'>
        ✅ <h2>Certificado Válido</h2>
        <p><strong>Aluno:</strong> ".htmlspecialchars($cert['aluno'])."</p>
        <p><strong>Curso:</strong> ".htmlspecialchars($cert['curso'])."</p>
        <p><strong>Data de Emissão:</strong> ".date('d/m/Y', strtotime($cert['data_emissao']))."</p>
        <p><strong>Código:</strong> ".htmlspecialchars($codigo)."</p>
      </div>
    ";
  } else {
    $mensagem = "<div class='card' style='text-align:center;color:#f87171;'>❌ Certificado inválido ou não encontrado.</div>";
  }
} else {
  $mensagem = "<div class='card' style='text-align:center;color:#f87171;'>⚠ Nenhum código informado.</div>";
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Validação Pública de Certificado | Eduka Plus Angola</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    :root {
      --bg: #0f172a;
      --card: #1e293b;
      --text: #e2e8f0;
      --primary: #3b82f6;
      --accent: #facc15;
      --muted: #94a3b8;
      --radius: 14px;
    }
    * {box-sizing: border-box; margin: 0; padding: 0;}
    body {
      font-family: system-ui, sans-serif;
      background: linear-gradient(-45deg,#0d47a1,#1565c0,#1e3a8a,#0f172a);
      color: var(--text);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    header {
      display: flex; justify-content: space-between; align-items: center;
      padding: 20px;
      background: rgba(30,41,59,0.7); backdrop-filter: blur(8px);
      position: sticky; top:0; z-index:1000;
    }
    header .logo {font-weight: 700; font-size: 1.3rem; color: var(--accent);}
    nav {display: flex; gap: 20px;}
    nav a {color: var(--text); text-decoration: none; transition: .3s;}
    nav a:hover {color: var(--accent);}
    section {padding: 60px 20px; max-width:800px; margin:auto; flex:1;}
    h2 {color: var(--accent); margin-bottom:20px; text-align:center;}
    .card {
      background:var(--card); padding:20px; border-radius:var(--radius);
      border:1px solid #334155; margin-top:20px; text-align:center;
    }
    footer {
      text-align:center; padding:20px; background:#1e293b;
      margin-top:40px; border-top:1px solid #334155;
    }
    footer a {color:var(--accent); text-decoration:none; margin:0 8px;}
  </style>
</head>
<body>
<header>
  <div class="logo">Eduka Plus Angola</div>
  <nav>
    <a href="index.php">Início</a>
    <a href="cursos.php">Cursos</a>
    <a href="validar_certificado.php">Validar Certificado</a>
  </nav>
</header>

<section>
  <h2>Validação Pública de Certificado</h2>
  <?php echo $mensagem; ?>
</section>

<footer>
  <div>
    <a href="ajuda.php">Ajuda</a> |
    <a href="privacidade.php">Privacidade</a> |
    <a href="termos.php">Termos</a>
  </div>
  <div>© <?php echo date("Y"); ?> Eduka Plus Angola — Todos os direitos reservados.</div>
</footer>
</body>
</html>
