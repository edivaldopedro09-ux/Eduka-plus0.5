<?php
session_start();
require_once("../config.php");

// Segurança: só alunos
if(!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo']!=='aluno'){
    header("Location: ../index.php");
    exit();
}

$aluno_id = $_SESSION['usuario_id'];
$nome_aluno = $_SESSION['usuario_nome'] ?? "Aluno";

/* ================================
   TOTAL DE CURSOS MATRICULADOS
================================== */
$sql = "SELECT COUNT(*) AS total FROM inscricoes WHERE aluno_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$aluno_id);
$stmt->execute();
$total_cursos = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

/* ================================
   TOTAL DE AULAS CONCLUÍDAS
================================== */
$sql = "SELECT COUNT(*) AS total 
        FROM progresso p
        INNER JOIN aulas a ON p.aula_id=a.id
        WHERE p.aluno_id=? AND p.concluido=1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$aluno_id);
$stmt->execute();
$total_aulas = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

/* ================================
   TOTAL DE CERTIFICADOS
================================== */
$sql = "SELECT COUNT(DISTINCT c.id) AS total
        FROM cursos c
        INNER JOIN inscricoes i ON c.id=i.curso_id
        WHERE i.aluno_id=? 
          AND NOT EXISTS (
             SELECT 1 FROM aulas a 
             LEFT JOIN progresso p 
             ON p.aula_id=a.id AND p.aluno_id=i.aluno_id
             WHERE a.curso_id=c.id AND (p.concluido IS NULL OR p.concluido=0)
          )";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$aluno_id);
$stmt->execute();
$total_certificados = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

/* ================================
   ÚLTIMOS CURSOS MATRICULADOS
================================== */
$sql = "SELECT c.id, c.titulo 
        FROM cursos c 
        INNER JOIN inscricoes i ON c.id=i.curso_id 
        WHERE i.aluno_id=? 
        ORDER BY i.id DESC 
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$aluno_id);
$stmt->execute();
$cursos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* ================================
   ÚLTIMAS MENSAGENS DO CHAT (por turma)
================================== */
$sql = "SELECT m.*, u.nome as remetente_nome, u.tipo as remetente_tipo, c.titulo as curso_nome
        FROM mensagens m
        INNER JOIN usuarios u ON m.usuario_id=u.id
        INNER JOIN cursos c ON m.curso_id=c.id
        INNER JOIN inscricoes i ON m.curso_id=i.curso_id
        WHERE i.aluno_id=?
        ORDER BY m.data_envio DESC
        LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$aluno_id);
$stmt->execute();
$mensagens = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* ================================
   TOTAL DE NOTIFICAÇÕES
================================== */
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM notificacoes 
    WHERE 
        (
            destinatario_id = ? 
            OR destinatario_tipo = 'aluno' 
            OR destinatario_tipo = 'todos'
        )
");
$stmt->bind_param("i", $aluno_id);
$stmt->execute();
$stmt->bind_result($total_notificacoes);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Dashboard — Eduka Plus</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
:root {
  --primary:#0d47a1;
  --secondary:#1976d2;
  --card-bg:rgba(255,255,255,0.08);
  --text:#e3f2fd;
}
 
body {
  font-family: Arial, sans-serif;
  color: var(--text);
  background: linear-gradient(-45deg,#0d47a1,#1565c0,#1e3a8a,#0f172a);
  background-size: 400% 400%;
  animation: gradient 12s ease infinite;
  display:flex;
  min-height:100vh;
}
@keyframes gradient {
  0%{background-position:0% 50%;}
  50%{background-position:100% 50%;}
  100%{background-position:0% 50%;}
}
aside {
  width:240px;
  background:rgba(0,0,0,0.3);
  padding:20px;
  backdrop-filter:blur(6px);
}
aside nav a {
  display:block;
  padding:12px;
  margin:6px 0;
  border-radius:8px;
  color:#fff;
  text-decoration:none;
  background:rgba(255,255,255,0.05);
  transition:0.3s;
}
aside nav a:hover {
  background:rgba(25,118,210,0.7);
}
main {
  flex:1;
  padding:30px;
}
header {
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:30px;
}
.btn {
  background:var(--secondary);
  padding:8px 16px;
  color:white;
  border:none;
  border-radius:6px;
  cursor:pointer;
  text-decoration:none;
}
.btn:hover { background:#0d47a1; }
.card {
  background:var(--card-bg);
  padding:20px;
  border-radius:12px;
  margin-bottom:20px;
  box-shadow:0 6px 12px rgba(0,0,0,0.2);
}
.cards-grid {
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
  gap:20px;
}
.num { font-size:2rem; font-weight:700; }
.muted { font-size:0.85rem; opacity:0.7; }
.chart-container {
  margin:30px 0;
  background:var(--card-bg);
  padding:20px;
  border-radius:12px;
}
/* Cursos */
.cursos-lista {
  list-style:none;
  padding:0;
  margin:0;
}
.cursos-lista li { margin-bottom:10px; }
.curso-link {
  display:block;
  padding:10px;
  background:rgba(21,101,192,0.6);
  border-radius:6px;
  text-decoration:none;
  color:white;
  transition:0.3s;
}
.curso-link:hover { background:rgba(66,165,245,0.8); }
/* Chat */
.chat-box {
  max-height:250px;
  overflow-y:auto;
  background: var(--card-bg);
  padding:15px;
  border-radius:10px;
}
.chat-msg { margin-bottom:12px; display:flex; }
.msg-bubble {
  padding:10px 14px;
  border-radius:12px;
  max-width:70%;
  background:rgba(66,165,245,0.7);
  color:white;
}
.chat-msg.professor .msg-bubble {
  background:rgba(21,101,192,0.7);
  margin-left:auto;
}
.msg-time { font-size:11px; color:#cfd8dc; margin-top:4px; }
.chat-form { display:flex; margin-top:10px; gap:10px; }
.chat-form input, .chat-form select {
  flex:1;
  padding:10px;
  border-radius:6px;
  border:none;
}
</style>
</head>
<body>
<aside>
    <div class="brand" style="font-weight:700;margin-bottom:22px;display:flex;gap:8px;align-items:center;">
        <span style="width:10px;height:10px;border-radius:50%;background:var(--secondary);box-shadow:0 0 14px var(--secondary);"></span> Eduka Plus • Aluno
    </div>
    <nav>
        <a href="./dashboard.php">🏠 Dashboard</a>
        <a href="../perfil.php">👤 Perfil</a>
        <a href="./meus_cursos.php">📚 Meus Cursos</a>
        <a href="./progresso.php">📊 Progresso</a>
        <a href="./materiais.php">📂 Materiais</a>
        <a href="./certificados.php">🎓 Certificados</a>
       
    </nav>
</aside>

<main>
<header>
  <h2>Bem-vindo, <?php echo htmlspecialchars($nome_aluno); ?></h2>
  <div style="display:flex;align-items:center;gap:20px;">
    <div class="notificacoes">
        <a href="notificacoes.php" class="btn">
            🔔 <?php if($total_notificacoes > 0): ?><span>(<?php echo $total_notificacoes; ?>)</span><?php endif; ?>
        </a>
    </div>
    <a href="../logout.php" class="btn">Sair</a>
  </div>
</header>

<div class="content">
<h2>Visão Geral</h2>
<div class="cards-grid">
  <div class="card">
    <h3>Cursos Matriculados</h3>
    <div class="num"><?php echo $total_cursos; ?></div>
    <div class="muted">Cursos em que você está inscrito</div>
  </div>
  <div class="card">
    <h3>Aulas Concluídas</h3>
    <div class="num"><?php echo $total_aulas; ?></div>
    <div class="muted">Progresso geral nas aulas</div>
  </div>
  <div class="card">
    <h3>Certificados Obtidos</h3>
    <div class="num"><?php echo $total_certificados; ?></div>
    <div class="muted">Cursos concluídos com certificado</div>
  </div>
</div>

<div class="chart-container">
  <canvas id="progressoChart" height="140"></canvas>
</div>

<h2>📚 Últimos Cursos</h2>
<div class="card">
  <?php if(empty($cursos)): ?>
    <p class="muted">Você ainda não está matriculado em nenhum curso.</p>
  <?php else: ?>
    <ul class="cursos-lista">
      <?php foreach($cursos as $c): ?>
        <li>
          <a href="ver_curso.php?id=<?php echo $c['id']; ?>" class="curso-link">
            📘 <?php echo htmlspecialchars($c['titulo']); ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>

<h2>💬 Chat da Turma</h2>
<div class="card chat-box">
  <?php if(empty($mensagens)): ?>
    <p class="muted">Nenhuma mensagem ainda. Inicie a conversa!</p>
  <?php else: ?>
    <?php foreach(array_reverse($mensagens) as $m): ?>
      <div class="chat-msg <?php echo $m['remetente_tipo']; ?>">
        <div class="msg-bubble">
          <strong><?php echo htmlspecialchars($m['remetente_nome']); ?> (<?php echo htmlspecialchars($m['curso_nome']); ?>):</strong><br>
          <?php echo htmlspecialchars($m['mensagem']); ?>
          <div class="msg-time"><?php echo $m['data_envio']; ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<form class="chat-form" method="post" action="enviar_mensagem.php">
  <select name="curso_id" required>
    <option value="">📚 Selecione o curso</option>
    <?php foreach($cursos as $c): ?>
      <option value="<?php echo $c['id']; ?>">
        <?php echo htmlspecialchars($c['titulo']); ?>
      </option>
    <?php endforeach; ?>
  </select>
  <input type="text" name="mensagem" placeholder="Digite sua mensagem..." required>
  <button type="submit" class="btn">Enviar</button>
</form>

</div>
<!-- 🚀 FOOTER -->
<footer style="
  margin-top:30px;
  padding:16px;
  text-align:center;
  font-size:14px;
  color:var(--muted);
  border-top:1px solid var(--border);
 
  border-radius:10px;
">
  <div style="margin-bottom:8px;">
    <a href="ajuda.php" style="color:#facc15; text-decoration:none; margin:0 10px;">Ajuda</a> |
    <a href="privacidade.php" style="color:#facc15; text-decoration:none; margin:0 10px;">Privacidade</a> |
    <a href="termos.php" style="color:#facc15; text-decoration:none; margin:0 10px;">Termos</a>
  </div>
  <div>
    © <?php echo date("Y"); ?> Eduka Plus Angola — Todos os direitos reservados.
  </div>
  <p>Eduka Plus Angola Versão 3.0.0</p>
</footer>
</main>

<script>
const ctx = document.getElementById('progressoChart').getContext('2d');
const progressoChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Cursos Concluídos','Aulas Concluídas','Cursos Pendentes'],
        datasets: [{
            label: 'Progresso',
            data: [
              <?php echo $total_certificados; ?>, 
              <?php echo $total_aulas; ?>, 
              <?php echo max(0,$total_cursos-$total_certificados); ?>
            ],
            backgroundColor: ['#16a34a','#3b82f6','#f59e0b'],
            borderRadius: 6
        }]
    },
    options: { 
        indexAxis: 'y',
        responsive:true, 
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#e3f2fd' }},
            y: { grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#e3f2fd' }}
        }
    }
});
</script>
</body>
</html>
