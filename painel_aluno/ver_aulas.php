<?php
session_start();
require_once("../config.php");

if(!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo']!=='aluno'){
    header("Location: ../index.php");
    exit();
}

$aluno_id = $_SESSION['usuario_id'];

// Verificar se o curso_id foi passado
if(!isset($_GET['curso_id'])){
    die("Parâmetros inválidos.");
}
$curso_id = intval($_GET['curso_id']);

// Verificar se o aluno está matriculado nesse curso
$sql_check = "SELECT * FROM inscricoes WHERE aluno_id=? AND curso_id=?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ii",$aluno_id,$curso_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
if($result_check->num_rows==0){
    die("Você não tem permissão para acessar este curso.");
}

// Buscar todas as aulas do curso
$sql = "SELECT * FROM aulas WHERE curso_id=? ORDER BY id ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$curso_id);
$stmt->execute();
$aulas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Tratar conclusão das aulas via POST
if(isset($_POST['toggle_aula_id'])){
    $aula_id = intval($_POST['toggle_aula_id']);

    // Verifica se já existe progresso
    $sql_prog = "SELECT * FROM progresso WHERE aluno_id=? AND aula_id=?";
    $stmt_prog = $conn->prepare($sql_prog);
    $stmt_prog->bind_param("ii",$aluno_id,$aula_id);
    $stmt_prog->execute();
    $res_prog = $stmt_prog->get_result();

    if($res_prog->num_rows==0){
        $sql_insert = "INSERT INTO progresso (aluno_id, curso_id, aula_id, concluido, concluido_em) VALUES (?,?,?,?,NOW())";
        $stmt_insert = $conn->prepare($sql_insert);
        $concluido=1;
        $stmt_insert->bind_param("iiii",$aluno_id,$curso_id,$aula_id,$concluido);
        $stmt_insert->execute();
    } else {
        $prog = $res_prog->fetch_assoc();
        $novo_status = $prog['concluido'] ? 0 : 1;
        $sql_upd = "UPDATE progresso SET concluido=?, concluido_em=NOW() WHERE id=?";
        $stmt_upd = $conn->prepare($sql_upd);
        $stmt_upd->bind_param("ii",$novo_status,$prog['id']);
        $stmt_upd->execute();
    }

    header("Location: ver_aulas.php?curso_id=".$curso_id);
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Aulas — Eduka Plus</title>
<style>
body{font-family:system-ui;background:#0f172a;color:#e5e7eb;padding:20px;}
h2{margin-bottom:20px;}
.card{background:#111827;border-radius:14px;padding:20px;margin-bottom:12px;border:1px solid #1f2937;}
.btn{background:#f59e0b;color:#111827;padding:6px 12px;border-radius:6px;text-decoration:none;margin-right:6px;}
.completed{color:#16a34a;font-weight:bold;}
</style>
</head>
<body>

<h2>Aulas do Curso</h2>

<?php if(count($aulas)==0): ?>
    <p>Este curso ainda não possui aulas.</p>
<?php else: ?>
    <?php foreach($aulas as $a): 
        // Verificar progresso
        $sql_prog = "SELECT concluido FROM progresso WHERE aluno_id=? AND aula_id=?";
        $stmt_prog = $conn->prepare($sql_prog);
        $stmt_prog->bind_param("ii",$aluno_id,$a['id']);
        $stmt_prog->execute();
        $res_prog = $stmt_prog->get_result()->fetch_assoc();
        $concluido = $res_prog['concluido'] ?? 0;
    ?>
    <div class="card">
        <h3><?php echo htmlspecialchars($a['titulo']); ?></h3>
       <?php if(!empty($a['video'])): ?>
    <video width="100%" controls>
        <source src="../<?php echo htmlspecialchars($a['video']); ?>" type="video/mp4">
        Seu navegador não suporta vídeo.
    </video>
<?php endif; ?>


        <?php if(!empty($a['material'])): ?>
            <p>Materiais:</p>
            <ul>
            <?php
            $materiais = explode(",",$a['material']);
            foreach($materiais as $m): ?>
                <li><a href="../materiais/<?php echo htmlspecialchars($m); ?>" class="btn" download><?php echo htmlspecialchars($m); ?></a></li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="toggle_aula_id" value="<?php echo $a['id']; ?>">
            <button type="submit" class="btn"><?php echo $concluido ? "✅ Concluída" : "☑ Marcar como Concluída"; ?></button>
        </form>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<a href="dashboard.php" class="btn">⬅ Voltar para Dashboard</a>

</body>
</html>
