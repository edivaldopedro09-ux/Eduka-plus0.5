<?php
ob_start();
session_start();
require_once("config.php");

$mensagem = "";
$codigo_valido = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $codigo = trim($_POST['codigo'] ?? "");

  if (!empty($codigo)) {
    $sql = "SELECT cr.id, u.nome AS aluno, c.titulo AS curso, cr.data_emissao, cr.codigo_autenticacao
            FROM certificados cr
            JOIN usuarios u ON u.id = cr.aluno_id
            JOIN cursos c ON c.id = cr.curso_id
            WHERE cr.codigo_autenticacao = ? AND cr.status = 'pago'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows > 0) {
      $cert = $res->fetch_assoc();
      $codigo_valido = $cert['codigo_autenticacao']; // usado no JS
      $mensagem = "<div class='card' style='text-align:center;'>
          ✅ <h3>Certificado Válido</h3>
          <p><strong>Aluno:</strong> ".htmlspecialchars($cert['aluno'])."</p>
          <p><strong>Curso:</strong> ".htmlspecialchars($cert['curso'])."</p>
          <p><strong>Data de Emissão:</strong> ".date("d/m/Y", strtotime($cert['data_emissao']))."</p>
          <p><strong>Código:</strong> ".htmlspecialchars($cert['codigo_autenticacao'])."</p>
          <p><a href='certificado.php?codigo=".urlencode($cert['codigo_autenticacao'])."' target='_blank' 
             style='color:#3b82f6; font-weight:bold;'>📄 Visualizar Certificado</a></p>
      </div>";
    } else {
      $mensagem = "<div class='card' style='text-align:center;color:#f87171;'>
          ❌ Código inválido ou certificado não encontrado.
      </div>";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Validação de Certificado | Eduka Plus Angola</title>
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
    .hamburger {display:none; flex-direction: column; gap:5px; cursor:pointer;}
    .hamburger div {width:25px; height:3px; background: var(--text);}
    @media(max-width:768px){
      nav {display:none; flex-direction: column; background:#1e293b;
        position:absolute; top:70px; right:20px; padding:20px; border-radius:var(--radius);}
      nav.active {display:flex;}
      .hamburger {display:flex;}
    }
    section {padding: 60px 20px; max-width:900px; margin:auto; flex:1;}
    h2 {color: var(--accent); margin-bottom:20px; text-align:center;}
    form {display:flex; flex-direction:column; gap:15px; background:var(--card); padding:30px; border-radius:var(--radius);}
    input {
      padding:12px; border-radius:var(--radius); border:1px solid #334155;
      background:#0f172a; color:white; font-size:1rem;
    }
    button {
      background: var(--primary); color:white; padding:12px; border:none;
      border-radius:var(--radius); font-size:1rem; cursor:pointer; transition:.3s;
    }
    button:hover {background:#2563eb;}
    .card {
      background:var(--card); padding:20px; border-radius:var(--radius);
      border:1px solid #334155; margin-top:20px;
    }
    footer {
      text-align:center; padding:20px; background:#1e293b;
      margin-top:40px; border-top:1px solid #334155;
    }
    footer a {color:var(--accent); text-decoration:none; margin:0 8px;}
    #reader {width:100%; max-width:500px; margin:30px auto;}
        .btn-login {
  background: var(--card);
  border: 1px solid var(--primary);
  color: var(--primary);
  padding: 8px 16px;
  border-radius: var(--radius);
  transition: .3s;
}
.btn-login:hover {
  background: var(--primary);
  color: #fff;
}

.btn-cadastro {
  background: var(--accent);
  color: #000;
  padding: 8px 16px;
  border-radius: var(--radius);
  font-weight: bold;
  transition: .3s;
}
.btn-cadastro:hover {
  background: #fbbf24;
}
  </style>
  <script src="https://unpkg.com/html5-qrcode/minified/html5-qrcode.min.js"></script>
</head>
<body>
<header>
  <div class="logo">Eduka Plus Angola</div>
  <nav id="menu">
      <a href="index.php">Início</a>
      <a href="cursos.php">Cursos</a>
      <a href="verificar_certificado.php">Validar Certificado</a>
      <a href="login.php" class="btn-login">Login</a>
      <a href="cadastro.php" class="btn-cadastro">Cadastro</a>
  </nav>
  <div class="hamburger" onclick="document.getElementById('menu').classList.toggle('active')">
    <div></div><div></div><div></div>
  </div>
</header>

<section>
  <h2>Validação de Certificado</h2>
  <p style="text-align:center; color:var(--muted); margin-bottom:30px;">
    Insira o código do certificado ou escaneie o QR Code para verificar sua autenticidade.
  </p>

  <!-- Validação manual -->
  <form method="POST">
    <input type="text" name="codigo" id="codigo" placeholder="Digite o código do certificado" required>
    <button type="submit">Validar</button>
  </form>

  <!-- Scanner QR -->
  <div id="reader"></div>

  <?php if(!empty($mensagem)) echo $mensagem; ?>
</section>

<footer>
  <div>
    <a href="ajuda.php">Ajuda</a> |
    <a href="privacidade.php">Privacidade</a> |
    <a href="termos.php">Termos</a>
  </div>
  <div>© <?php echo date("Y"); ?> Eduka Plus Angola — Todos os direitos reservados.</div>
</footer>
<!-- Botão WhatsApp Fixo -->
<a href="https://wa.me/244958922590" target="_blank" class="whatsapp-btn">
  <img src="uploads/what.png" alt="WhatsApp" />
</a>

<style>
.whatsapp-btn {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 60px;
    height: 60px;
    z-index: 10000;
    border-radius: 50%;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    transition: transform 0.3s, box-shadow 0.3s;
}
.whatsapp-btn img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.whatsapp-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 18px rgba(0,0,0,0.4);
}
</style>
<script>
  function onScanSuccess(decodedText, decodedResult) {
    document.getElementById("codigo").value = decodedText;
    document.forms[0].submit();
  }
  function onScanError(errorMessage) {}

  let html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: 250 });
  html5QrcodeScanner.render(onScanSuccess, onScanError);

  // Abrir PDF automaticamente se certificado válido
  <?php if(!empty($codigo_valido)): ?>
    window.open("certificado.php?codigo=<?php echo urlencode($codigo_valido); ?>", "_blank");
  <?php endif; ?>
</script>
</body>
</html>
