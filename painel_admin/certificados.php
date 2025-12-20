<?php
session_start();
require_once("../config.php");

// Segurança: só admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Buscar todos os certificados
$sql = "SELECT cr.id, cr.status, cr.data_emissao, cr.comprovativo,
               u.nome AS aluno, c.titulo AS curso
        FROM certificados cr
        JOIN usuarios u ON u.id = cr.aluno_id
        JOIN cursos c ON c.id = cr.curso_id
        ORDER BY cr.id DESC";
$result = $conn->query($sql);
$certificados = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Gerenciar Certificados — Admin</title>
<style>
body{font-family:system-ui;background:#f1f5f9;margin:0;}
header{padding:16px;background:#1e293b;color:#fff;display:flex;justify-content:space-between;align-items:center;}
a.btn,button.btn{background:#0ea5e9;color:#fff;padding:6px 12px;border-radius:6px;text-decoration:none;font-weight:600;margin:4px;cursor:pointer;border:none;}
a.btn-danger,button.btn-danger{background:#dc2626;}
.container{padding:24px;}
table{width:100%;border-collapse:collapse;background:#fff;border-radius:8px;overflow:hidden;}
th,td{padding:10px;border-bottom:1px solid #e2e8f0;text-align:left;}
th{background:#f8fafc;}
.status{font-weight:bold;}
.pendente{color:#f59e0b;}
.pago{color:#16a34a;}
</style>
</head>
<body>
<header>
  <div>📜 Gerenciar Certificados</div>
  <div><a href="dashboard.php" class="btn">⬅ Voltar</a></div>
</header>

<div class="container">
<table>
  <tr>
    <th>Aluno</th>
    <th>Curso</th>
    <th>Status</th>
    <th>Data Emissão</th>
    <th>Comprovativo</th>
    <th>Ações</th>
  </tr>
  <?php foreach($certificados as $cert): ?>
  <tr>
    <td><?= htmlspecialchars($cert['aluno']); ?></td>
    <td><?= htmlspecialchars($cert['curso']); ?></td>
    <td class="status <?= $cert['status']; ?>"><?= ucfirst($cert['status']); ?></td>
    <td><?= $cert['data_emissao'] ? date("d/m/Y H:i", strtotime($cert['data_emissao'])) : "-"; ?></td>
    <td>
        <?php if($cert['comprovativo']): ?>
            <a href="../uploads/comprovativos/<?= htmlspecialchars($cert['comprovativo']); ?>" target="_blank" class="btn">Ver</a>
        <?php else: ?>
            <span style="color:#64748b;">Nenhum</span>
        <?php endif; ?>
    </td>
    <td>
        <?php if($cert['status'] === "pendente" && $cert['comprovativo']): ?>
            <form action="validar_certificado.php" method="post" style="display:inline;">
                <input type="hidden" name="id" value="<?= $cert['id']; ?>">
                <button type="submit" name="acao" value="aprovar" class="btn">✅ Aprovar</button>
                <button type="submit" name="acao" value="rejeitar" class="btn-danger">❌ Rejeitar</button>
            </form>
        <?php elseif($cert['status'] === "pago"): ?>
            <span>✅ Já liberado</span>
        <?php else: ?>
            <span>⏳ Aguardando</span>
        <?php endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
</div>
</body>
</html>
