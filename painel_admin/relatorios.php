<?php
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once("../config.php");

// --- Estatísticas rápidas ---
$totalAlunos = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo='aluno'")->fetch_assoc()['total'];
$totalProfessores = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo='professor'")->fetch_assoc()['total'];
$totalCursos = $conn->query("SELECT COUNT(*) as total FROM cursos")->fetch_assoc()['total'];

// --- Cursos com número de alunos inscritos ---
$sql = "SELECT c.titulo, COUNT(i.aluno_id) as inscritos
        FROM cursos c
        LEFT JOIN inscricoes i ON c.id = i.curso_id
        GROUP BY c.id, c.titulo
        ORDER BY inscritos DESC";
$cursos = $conn->query($sql);

$cursosData = [];
while($c = $cursos->fetch_assoc()){
    $cursosData[] = $c;
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Relatório - Admin</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body{font-family:Arial, sans-serif;background:#f9fafb;margin:0;padding:20px;}
        h2{margin-bottom:15px;}
        .topbar{margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;}
        .back{padding:8px 12px;background:#374151;color:#fff;border-radius:6px;text-decoration:none;}
        .export button{margin-left:10px;padding:8px 14px;border:none;border-radius:6px;cursor:pointer;}
        .pdf{background:#dc2626;color:#fff;}
        .excel{background:#16a34a;color:#fff;}
        .cards{display:flex;gap:20px;margin-bottom:20px;}
        .card{flex:1;background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,.1);text-align:center;}
        .card h3{margin:0;font-size:22px;color:#2563eb;}
        .card p{margin:6px 0 0;font-size:16px;color:#374151;}
        table{width:100%;border-collapse:collapse;background:#fff;box-shadow:0 2px 6px rgba(0,0,0,.1);margin-top:20px;}
        th,td{padding:12px;border-bottom:1px solid #ddd;text-align:left;}
        th{background:#2563eb;color:#fff;}
        canvas{background:#fff;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,.1);margin:20px 0;padding:15px;}
    </style>
</head>
<body>
    <div class="topbar">
        <a class="back" href="./dashboard.php">← Voltar ao Dashboard</a>
        <div class="export">
            <form action="relatorio_pdf.php" method="post" style="display:inline;">
                <button type="submit" class="pdf">📄 Exportar PDF</button>
            </form>
            <form action="relatorio_excel.php" method="post" style="display:inline;">
                <button type="submit" class="excel">📊 Exportar Excel</button>
            </form>
        </div>
    </div>

    <h2>Relatórios da Plataforma</h2>

    <!-- Estatísticas rápidas -->
    <div class="cards">
        <div class="card">
            <h3><?php echo $totalAlunos; ?></h3>
            <p>Alunos</p>
        </div>
        <div class="card">
            <h3><?php echo $totalProfessores; ?></h3>
            <p>Professores</p>
        </div>
        <div class="card">
            <h3><?php echo $totalCursos; ?></h3>
            <p>Cursos</p>
        </div>
    </div>

    <!-- Gráfico de cursos com mais inscritos -->
    <h3>📊 Inscrições por Curso</h3>
    <canvas id="graficoCursos" height="120"></canvas>

    <!-- Gráfico de distribuição de usuários -->
    <h3>📊 Distribuição de Usuários</h3>
    <canvas id="graficoUsuarios" height="120"></canvas>

    <!-- Tabela -->
    <h3>Detalhes dos Cursos</h3>
    <table>
        <tr>
            <th>Curso</th>
            <th>Inscritos</th>
        </tr>
        <?php foreach($cursosData as $c): ?>
            <tr>
                <td><?php echo htmlspecialchars($c['titulo']); ?></td>
                <td><?php echo $c['inscritos']; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <script>
        // Dados dos cursos para o gráfico
        const cursos = <?php echo json_encode(array_column($cursosData, 'titulo')); ?>;
        const inscritos = <?php echo json_encode(array_column($cursosData, 'inscritos')); ?>;

        const ctx1 = document.getElementById('graficoCursos').getContext('2d');
        new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: cursos,
                datasets: [{
                    label: 'Inscritos',
                    data: inscritos,
                    backgroundColor: '#2563eb'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Gráfico de usuários
        const ctx2 = document.getElementById('graficoUsuarios').getContext('2d');
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['Alunos', 'Professores'],
                datasets: [{
                    data: [<?php echo $totalAlunos; ?>, <?php echo $totalProfessores; ?>],
                    backgroundColor: ['#16a34a','#f59e0b']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    </script>
</body>
</html>
