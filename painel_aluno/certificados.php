<?php
session_start();
require_once("../config.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'aluno') {
    header("Location: ../index.php");
    exit();
}

$aluno_id = $_SESSION['usuario_id'];

$sql = "SELECT c.titulo AS curso, cr.status, cr.data_emissao, c.id AS curso_id, cr.id AS certificado_id
        FROM certificados cr
        JOIN cursos c ON c.id = cr.curso_id
        WHERE cr.aluno_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $aluno_id);
$stmt->execute();
$result = $stmt->get_result();
$certificados = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Meus Certificados — Eduka Plus</title>
<style>
body{font-family:system-ui;background:#0f172a;color:#e5e7eb;margin:0;}
header{padding:16px;background:#0b1020;border-bottom:1px solid #1f2937;display:flex;justify-content:space-between;align-items:center;}
a.btn{background:#f59e0b;color:#111827;padding:6px 12px;border-radius:8px;text-decoration:none;font-weight:600;margin-top:6px;display:inline-block;}
.container{padding:24px;display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;}
.card{background:#111827;padding:18px;border-radius:12px;border:1px solid #1f2937;box-shadow:0 6px 18px rgba(0,0,0,.25);}
p strong{color:#fbbf24;}
.ref-box{background:#1f2937;padding:10px;border-radius:8px;margin-top:10px;font-size:14px;}
</style>
</head>
<body>
<header>
  <div>🎓 Meus Certificados</div>
  <div><a href="meus_cursos.php" class="btn">⬅ Voltar</a></div>
</header>
<div class="container">
<?php if(empty($certificados)): ?>
  <p>Nenhum certificado solicitado ainda.</p>
<?php else: ?>
  <?php foreach($certificados as $cert): ?>
  <div class="card">
     <h3><?php echo htmlspecialchars($cert['curso']); ?></h3>
     <p>Status: <strong><?php echo ucfirst($cert['status']); ?></strong></p>
     
    <?php if($cert['status']==="pago"): ?>
    <p>📅 Emitido em: <?php echo date("d/m/Y H:i", strtotime($cert['data_emissao'])); ?></p>
    <a href="gerar_certificado.php?curso_id=<?= $cert['curso_id'] ?>" class="btn">📄 Baixar Certificado</a>

<?php else: ?>
    <p>⏳ Aguardando pagamento</p>
    <div class="ref-box">
        💰 <strong>Valor:</strong> 500,00 KZ <br>
        🏦 <strong>Referência Bancária:</strong> ATLANTICO — Conta nº 300089688 10 001 <br>
        🏦 <strong>Referência Bancária:</strong> ATLANTICO  — IBAN 0055.0000.0008.9688.1016.5 Nome: Edivaldo dos Santos Pedro<br>
        📱 <strong>Multicaixa Express:</strong> +244 936 863 110 <br>
        🌍 <strong>PayPay Ao:</strong> 929057372
    </div>
    <p style="margin-top:8px;">Após o pagamento, envie o comprovativo abaixo:</p>

    <form action="upload_comprovativo.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="certificado_id" value="<?= $cert['certificado_id'] ?>">
        <input type="file" name="comprovativo" accept="image/*,.pdf" required>
        <button type="submit" class="btn">📤 Enviar Comprovativo</button>
    </form>

    <?php if(!empty($cert['comprovativo'])): ?>
        <p>✅ Comprovativo enviado: <a href="../uploads/comprovativos/<?= htmlspecialchars($cert['comprovativo']); ?>" target="_blank">Ver Arquivo</a></p>
    <?php endif; ?>
<?php endif; ?>

  </div>
  <?php endforeach; ?>
<?php endif; ?>
</div>
</body>
</html>
