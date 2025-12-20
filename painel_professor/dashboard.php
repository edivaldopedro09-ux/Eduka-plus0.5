<?php
session_start();
require_once("../config.php");

// Segurança: só professores
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'professor') {
    header("Location: ../index.php");
    exit();
}

$professor_id = $_SESSION['usuario_id'];

// Total de cursos
$sql = "SELECT COUNT(*) AS total_cursos FROM cursos WHERE professor_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_cursos = $row ? $row['total_cursos'] : 0;

// Total de alunos matriculados
$sql = "SELECT COUNT(*) AS total_alunos FROM inscricoes i 
        JOIN cursos c ON i.curso_id = c.id 
        WHERE c.professor_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_alunos = $row ? $row['total_alunos'] : 0;

// Cursos do professor
$sql = "SELECT c.id AS curso_id, c.titulo AS curso_nome, c.imagem,
               (SELECT COUNT(*) FROM inscricoes i WHERE i.curso_id=c.id) AS total_alunos
        FROM cursos c
        WHERE c.professor_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$result = $stmt->get_result();
$cursos = $result->fetch_all(MYSQLI_ASSOC);

// Notificações não lidas
$sql = "SELECT COUNT(*) AS nao_lidas FROM notificacoes WHERE destinatario_id=? AND lida=0";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $professor_id);
$stmt->execute();
$result = $stmt->get_result();
$contador = $result->fetch_assoc()['nao_lidas'] ?? 0;
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Painel Professor — Eduka Plus Angola</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
:root {
  --bg:#0a0f1c;
  --sidebar:#0e1528;
  --card:#16213e;
  --text:#e5e7eb;
  --primary:#3b82f6;
  --accent:#16a34a;
  --muted:#94a3b8;
  --border:#1f2937;
}

* { box-sizing:border-box; margin:0; padding:0; }

body {
  font-family:"Segoe UI",system-ui;
  background: linear-gradient(-45deg,#0d47a1,#1565c0,#1e3a8a,#0f172a);
  background-size: 400% 400%;
  color:var(--text);
  display:grid;
  grid-template-columns:240px 1fr;
  min-height:100vh;
  animation: gradientBG 15s ease infinite;
}

@keyframes gradientBG {
  0%{background-position:0% 50%;}
  50%{background-position:100% 50%;}
  100%{background-position:0% 50%;}
}

/* Sidebar */
aside {
  background:var(--sidebar);
  padding:22px;
  border-right:1px solid var(--border);
  display:flex;
  flex-direction:column;
  justify-content:space-between;
  transition: transform 0.3s ease;
  z-index:9998;
}
aside.hidden { transform: translateX(-100%); }
.brand { font-weight:700; margin-bottom:22px; display:flex; gap:10px; align-items:center; font-size:18px;}
.dot { width:10px;height:10px; border-radius:50%; background:var(--primary); box-shadow:0 0 10px var(--primary); }
nav a { display:block; padding:12px 14px; margin:6px 0; border-radius:8px; text-decoration:none; color:var(--text); background:rgba(59,130,246,0.05); border:1px solid transparent; transition:.2s; }
nav a:hover { background:rgba(59,130,246,0.15); border-color:var(--primary); transform:translateX(4px); }

/* Botão mobile */
#btnToggleMenu {
  display:none; /* padrão desktop */
  position:fixed;
  top:20px;
  left:20px;
  z-index:9999;
  background:var(--primary);
  border:none;
  color:#fff;
  font-size:24px;
  padding:10px 14px;
  border-radius:8px;
  cursor:pointer;
  box-shadow:0 4px 12px rgba(0,0,0,.5);
}

/* Header */
header { display:flex; justify-content:space-between; align-items:center; padding:18px 24px; border-bottom:1px solid var(--border); background:rgba(17,24,39,0.6); backdrop-filter: blur(6px); position:sticky; top:0; z-index:10; }

/* Main */
main { padding:24px; display:flex; flex-direction:column; gap:20px; }

/* Cards */
.card { 
  background:var(--card); 
  border-radius:14px; 
  padding:20px; 
  border:1px solid var(--border); 
  box-shadow:0 8px 24px rgba(0,0,0,.4); 
  transition:transform .4s, opacity .6s; 
  opacity:0;
}
.card.show { opacity:1; transform:translateY(0);}
.card:hover { transform:translateY(-6px); }
.num { font-size:32px; font-weight:700; margin-top:6px; }
.muted { color:var(--muted); font-size:14px; }

/* Botões */
.btn { border:1px solid var(--border); background:#0f172a; color:var(--text); padding:8px 12px; border-radius:8px; text-decoration:none; margin-top:10px; display:inline-block; transition:.2s; }
.btn:hover { background:var(--primary); border-color:var(--primary); color:#fff; }
.btn-green { background:var(--accent); border:1px solid #15803d; color:#fff; }
.btn-green:hover { background:#22c55e; }

/* Lista de cursos */
.course-list { display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:20px; }
.course-img { width:100%; height:150px; object-fit:cover; border-radius:10px; margin-bottom:10px; transition: transform .3s, box-shadow .3s; }
.course-img:hover { transform:scale(1.05); box-shadow:0 6px 20px rgba(0,0,0,.6); }

/* Layout gráfico + cursos lado a lado */
.graph-courses-container {
  display:flex;
  gap:20px;
  flex-wrap:wrap;
}
.graph-container { flex:0 0 250px; background:var(--card); border-radius:14px; padding:16px; height:250px; }
.courses-container { flex:1; display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:20px; }

/* Footer */
footer { margin-top:30px; padding:16px; text-align:center; font-size:14px; color:var(--muted); border-top:1px solid var(--border); background:rgba(17,24,39,0.6); border-radius:10px; }

/* Responsivo */
@media(max-width:768px) { body { grid-template-columns:200px 1fr; } }
@media(max-width:768px){ 
  body { grid-template-columns:1fr; } 
  aside { position:fixed; top:0; left:0; height:100%; width:240px; transform:translateX(-100%); transition:transform 0.3s ease; }
  aside.show { transform:translateX(0); }
  #btnToggleMenu { display:block; }
}
@media(max-width:480px){ 
  .course-img { height:120px; } 
  .btn { font-size:14px; padding:6px 10px; } 
  .graph-courses-container { flex-direction:column; }
  .graph-container { width:100%; height:180px; }
}
</style>
</head>
<body>

<!-- Botão para abrir/fechar menu -->
<button id="btnToggleMenu">☰</button>

<aside id="sidebar">
  <div>
    <div class="brand"><span class="dot"></span> Eduka Plus • Professor</div>
    <nav>
        <a href="./dashboard.php">🏠 Dashboard</a>
        <a href="../perfil.php">👤 Meu Perfil</a>
        <a href="./meus_cursos.php">📚 Meus Cursos</a>
        <a href="./materiais.php">📂 Materiais</a>
        <a href="./alunos_matriculados.php">🧑‍🎓 Alunos</a>
    </nav>
  </div>
</aside>

<main>
<header>
  <div>Olá, <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong> 👋</div>
  <div style="display:flex;align-items:center;gap:20px;position:relative;">
    <a href="../logout.php" class="btn">Sair</a>
  </div>
</header>

<!-- Painel de estatísticas -->
<div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:20px; margin-bottom:20px;">
  <div class="card" title="Quantidade de cursos que você criou">
    <h4>📚 Total de Cursos</h4>
    <div class="num" data-count="<?php echo $total_cursos; ?>">0</div>
    <div class="muted">Cursos criados por você</div>
  </div>

  <div class="card" title="Total de alunos matriculados em todos os seus cursos">
    <h4>🧑‍🎓 Total de Alunos</h4>
    <div class="num" data-count="<?php echo $total_alunos; ?>">0</div>
    <div class="muted">Alunos matriculados</div>
  </div>

  <div class="card" title="Notificações não lidas">
    <h4>🔔 Notificações</h4>
    <div class="num" data-count="<?php echo $contador; ?>">0</div>
    <div class="muted">Não lidas</div>
  </div>
</div>

<!-- Gráfico + Lista de Cursos lado a lado -->
<div class="graph-courses-container">
  <div class="graph-container">
    <h4>Alunos por Curso</h4>
    <canvas id="chartCursos" height="200"></canvas>
  </div>

  <div class="courses-container">
  <?php foreach($cursos as $curso): ?>
    <div class="card">
      <?php 
      $imgCurso = !empty($curso['imagem']) 
                  ? '../uploads/'.htmlspecialchars($curso['imagem']) 
                  : '../uploads/default.jpg';
      ?>
      <img src="<?php echo $imgCurso; ?>" class="course-img" alt="Imagem do curso">
      <h4><?php echo htmlspecialchars($curso['curso_nome']); ?></h4>
      <p>Alunos matriculados: <span class="num" data-count="<?php echo $curso['total_alunos']; ?>">0</span></p>
      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a href="alunos.php?curso_id=<?php echo $curso['curso_id']; ?>" class="btn">👥 Ver alunos</a>
        <a href="gerenciar_aulas.php?curso_id=<?php echo $curso['curso_id']; ?>" class="btn">🎥 Aulas</a>
        <a href="gerenciar_materiais.php?curso_id=<?php echo $curso['curso_id']; ?>" class="btn">📂 Materiais</a>
        <a href="chat_list.php?curso_id=<?php echo $curso['curso_id']; ?>" class="btn btn-green">💬 Chat</a>
      </div>
    </div>
  <?php endforeach; ?>
  </div>
</div>

<footer>
  <div style="margin-bottom:8px;">
    <a href="ajuda.php" style="color:#facc15; text-decoration:none; margin:0 10px;">Ajuda</a> |
    <a href="privacidade.php" style="color:#facc15; text-decoration:none; margin:0 10px;">Privacidade</a> |
    <a href="termos.php" style="color:#facc15; text-decoration:none; margin:0 10px;">Termos</a>
  </div>
  <div>© <?php echo date("Y"); ?> Eduka Plus Angola — Todos os direitos reservados.</div>
  <p>Versão 3.0.0</p>
</footer>

<script>
// Toggle sidebar mobile
const btnMenu = document.getElementById('btnToggleMenu');
const sidebar = document.getElementById('sidebar');
btnMenu.addEventListener('click', () => { sidebar.classList.toggle('show'); });

// Contagem animada suave
function animateCount(el, duration = 1200) {
    const target = +el.getAttribute('data-count');
    let start = 0;
    const increment = target / (duration / 16);
    function update() {
        start += increment;
        if(start >= target) el.innerText = target;
        else { el.innerText = Math.floor(start); requestAnimationFrame(update); }
    }
    requestAnimationFrame(update);
}
document.querySelectorAll('.num').forEach(el => animateCount(el));

// Mostrar cards com animação
document.querySelectorAll('.card').forEach((card,i)=>{
    setTimeout(()=>{card.classList.add('show');}, i*100);
});

// Gráfico horizontal Chart.js com gradiente
const cursosNomes = <?php echo json_encode(array_column($cursos,'curso_nome')); ?>;
const alunosQtd = <?php echo json_encode(array_map(fn($c)=>intval($c['total_alunos']), $cursos)); ?>;
const ctx = document.getElementById('chartCursos').getContext('2d');
const gradientColors = cursosNomes.map((c,i)=>{
    const grad = ctx.createLinearGradient(0,0,200,0);
    grad.addColorStop(0, `hsl(${(i*50)%360},70%,50%)`);
    grad.addColorStop(1, `hsl(${(i*50+50)%360},70%,60%)`);
    return grad;
});

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: cursosNomes,
        datasets: [{
            label: 'Alunos matriculados',
            data: alunosQtd,
            backgroundColor: gradientColors,
            borderWidth: 1
        }]
    },
    options: {
        indexAxis: 'y',
        responsive:true,
        maintainAspectRatio:false,
        scales: {
            x: { beginAtZero:true, ticks:{color:'#e5e7eb'} },
            y: { ticks:{color:'#e5e7eb'} }
        },
        plugins: {
            legend:{display:false},
            tooltip:{backgroundColor:'#16213e', titleColor:'#fff', bodyColor:'#fff'}
        }
    }
});
</script>
</body>
</html>
