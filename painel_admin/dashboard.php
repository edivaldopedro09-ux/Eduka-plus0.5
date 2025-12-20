<?php
session_start();
require_once("../config.php");

// Segurança: só Admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$nome_admin = $_SESSION['usuario_nome'] ?? "Admin";

// ---- Helpers de contagem seguros ----
function getCount($conn, $sql, $types = "", $params = []) {
    if ($types !== "" && !empty($params)) {
        $stmt = $conn->prepare($sql);
        if (!$stmt) return 0;
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        return (int)($res['total'] ?? 0);
    } else {
        $res = $conn->query($sql);
        if (!$res) return 0;
        $row = $res->fetch_assoc();
        return (int)($row['total'] ?? 0);
    }
}

// ---- Cards: contagens principais ----
$total_usuarios     = getCount($conn, "SELECT COUNT(*) AS total FROM usuarios");
$total_professores  = getCount($conn, "SELECT COUNT(*) AS total FROM usuarios WHERE tipo='professor'");
$total_alunos       = getCount($conn, "SELECT COUNT(*) AS total FROM usuarios WHERE tipo='aluno'");
$total_cursos       = getCount($conn, "SELECT COUNT(*) AS total FROM cursos");
$total_inscricoes   = getCount($conn, "SELECT COUNT(*) AS total FROM inscricoes");
$total_certificados = getCount($conn, "SELECT COUNT(*) AS total FROM certificados");

// ---- Gráfico: inscrições por mês (últimos 6 meses) ----
// Observação: requer coluna `inscrito_em` em `inscricoes` (DATETIME/TIMESTAMP).
$labels = [];
$values = [];

$sqlGraf = "
    SELECT DATE_FORMAT(inscrito_em, '%Y-%m') AS ym, COUNT(*) AS total
    FROM inscricoes
    WHERE inscrito_em >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY ym
    ORDER BY ym ASC
";
$resGraf = $conn->query($sqlGraf);
$map = [];
if ($resGraf) {
    while ($r = $resGraf->fetch_assoc()) {
        $map[$r['ym']] = (int)$r['total'];
    }
}

// Gera 6 rótulos contínuos (mesmo se algum mês não tiver inscrições)
$inicio = new DateTime(date('Y-m-01'));
$inicio->modify('-5 months'); // último 6 meses incluindo o atual
for ($i=0; $i<6; $i++) {
    $ym = $inicio->format('Y-m');
    $labels[] = $inicio->format('M/Y'); // Ex.: Aug/2025
    $values[] = $map[$ym] ?? 0;
    $inicio->modify('+1 month');
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Dashboard — Eduka Plus (Admin)</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
@import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap');
*{margin:0;padding:0;box-sizing:border-box;font-family:'Roboto',sans-serif}

body{
  min-height:100vh;background: linear-gradient(-45deg,#0d47a1,#1565c0,#1e3a8a,#0f172a);
  color:#fff;overflow-x:hidden;position:relative;
}
body::before, body::after{
  content:'';position:fixed;width:200%;height:200%;
  background:radial-gradient(circle,#f59e0b,transparent 50%);
  top:-50%;left:-50%;animation:lightMove 20s linear infinite alternate;
  opacity:.15;z-index:0;
}
body::after{
  background:radial-gradient(circle,#3b82f6,transparent 50%);
  animation-duration:25s;
}
@keyframes lightMove{
  0%{transform:translate(0,0) rotate(0deg)}
  50%{transform:translate(15%,15%) rotate(180deg)}
  100%{transform:translate(0,0) rotate(360deg)}
}

header{
  position:relative;z-index:1;background:rgba(17,24,39,.9);
  padding:16px 24px;display:flex;justify-content:space-between;align-items:center;
  border-bottom:1px solid #1f2937;box-shadow:0 6px 18px rgba(0,0,0,.4)
}
header .brand{display:flex;gap:10px;align-items:center;font-weight:700}
header .brand .dot{width:10px;height:10px;border-radius:50%;background:#f59e0b;box-shadow:0 0 12px #f59e0b}

.wrapper{position:relative;z-index:1;display:grid;grid-template-columns:240px 1fr;min-height:calc(100vh - 60px)}
aside{background:#0b1020;border-right:1px solid #1f2937;padding:18px}
nav a{
  display:block;padding:10px 12px;margin:6px 0;border-radius:10px;text-decoration:none;
  color:#e5e7eb;background:#0f172a;border:1px solid #1f2937;transition:.2s
}
nav a:hover{transform:translateX(3px);border-color:#334155}
main{padding:24px}

.grid{
  display:grid;gap:18px;
  grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
}
.card{
  background:rgba(17,24,39,.85);border:1px solid #1f2937;border-radius:16px;
  padding:18px;box-shadow:0 10px 26px rgba(0,0,0,.45);transition:.3s
}
.card:hover{transform:translateY(-4px);box-shadow:0 14px 30px rgba(0,0,0,.6)}
.card h3{color:#f59e0b;font-size:15px;margin-bottom:6px}
.card .num{font-size:28px;font-weight:800}

.panel{
  margin-top:22px;display:grid;gap:18px;grid-template-columns:1fr 1fr;
}
@media (max-width:900px){ .panel{grid-template-columns:1fr} }

.chart-card{padding:20px}
footer{
  margin-top:18px;color:#9ca3af;font-size:12px;display:flex;gap:10px;align-items:center
}
</style>
</head>
<body>
<header>
  <div class="brand"><span class="dot"></span> Eduka Plus • Admin</div>
  <div>Olá, <strong><?php echo htmlspecialchars($nome_admin); ?></strong> | <a href="../logout.php" style="color:#fff;text-decoration:none;background:#ef4444;padding:8px 12px;border-radius:10px">Sair</a></div>
</header>

<div class="wrapper">
  <aside>
    <nav>
      <a href="./dashboard.php">🏠 Dashboard</a>
      <a href="./usuarios.php">👥 Usuários</a>
      <a href="./professores.php">👨‍🏫 Professores</a>
      <a href="./alunos.php">🧑‍🎓 Alunos</a>
      <a href="./cursos.php">📚 Cursos</a>
      <a href="./inscricoes.php">📝 Inscrições</a>
      <a href="./certificados.php">🎓 Certificados</a>
      <a href="./notificacoes.php">📢 Notificações</a>
     <a href="./">ALICE</a>
      <a href="../login.php">↩ Voltar ao Login</a>
    </nav>
  </aside>

  <main>
    <h2 style="margin-bottom:14px;">Visão Geral</h2>
    <div class="grid">
      <div class="card"><h3>Usuários</h3><div class="num"><?php echo $total_usuarios; ?></div></div>
      <div class="card"><h3>Professores</h3><div class="num"><?php echo $total_professores; ?></div></div>
      <div class="card"><h3>Alunos</h3><div class="num"><?php echo $total_alunos; ?></div></div>
      <div class="card"><h3>Cursos</h3><div class="num"><?php echo $total_cursos; ?></div></div>
      <div class="card"><h3>Inscrições</h3><div class="num"><?php echo $total_inscricoes; ?></div></div>
      <div class="card"><h3>Certificados</h3><div class="num"><?php echo $total_certificados; ?></div></div>
    </div>

    <div class="panel">
      <div class="card chart-card">
        <h3>Inscrições — últimos 6 meses</h3>
        <canvas id="grafInscricoes"></canvas>
      </div>
      <div class="card">
        <h3>Atalhos</h3>
        <div style="display:grid;gap:10px;margin-top:10px;grid-template-columns:repeat(auto-fit,minmax(160px,1fr))">
          <a href="./cursos_add.php" style="text-decoration:none;background:#0f172a;border:1px solid #1f2937;padding:10px;border-radius:12px;color:#e5e7eb;text-align:center">➕ Criar Curso</a>
          <a href="./usuarios.php" style="text-decoration:none;background:#0f172a;border:1px solid #1f2937;padding:10px;border-radius:12px;color:#e5e7eb;text-align:center">👥 Gerir Usuários</a>
          <a href="./notificacoes_add.php" style="text-decoration:none;background:#0f172a;border:1px solid #1f2937;padding:10px;border-radius:12px;color:#e5e7eb;text-align:center">🔔 Notificações</a>
          <a href="./relatorios.php" style="text-decoration:none;background:#0f172a;border:1px solid #1f2937;padding:10px;border-radius:12px;color:#e5e7eb;text-align:center">📄 Relatórios</a>
        </div>
      </div>
    </div>

    <footer>
      <span>Eduka Plus — v2.0.1</span> • <span><?php echo date('d/m/Y H:i'); ?></span>
    </footer>
  </main>
</div>

<script>
const ctx = document.getElementById('grafInscricoes');
new Chart(ctx, {
  type: 'bar',
  data: {
    labels: <?php echo json_encode($labels, JSON_UNESCAPED_UNICODE); ?>,
    datasets: [{
      label: 'Inscrições',
      data: <?php echo json_encode($values, JSON_UNESCAPED_UNICODE); ?>,
      backgroundColor: '#f59e0b'
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display:false } },
    scales: { y: { beginAtZero:true, ticks: { stepSize: 1 } } }
  }
});
</script>
</body>
</html>
