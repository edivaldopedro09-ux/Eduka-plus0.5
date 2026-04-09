<?php
session_start();
require_once("../config.php");

if(!isset($_SESSION['usuario_id'])){
    header("Location: ../index.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

// Marcar todas notificações como lidas
if(isset($_GET['marcar_lidas'])){
    $sql = "UPDATE notificacoes SET lida=1 WHERE usuario_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i",$usuario_id);
    $stmt->execute();
}

// Buscar notificações
$sql = "SELECT * FROM notificacoes WHERE usuario_id=? ORDER BY criado_em DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$notificacoes = $result->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Notificações — Eduka Plus</title>
<link rel="stylesheet" href="css/notificacoes.css">

</head>
<body>

<h2>Notificações</h2>
<a href="?marcar_lidas=1" class="btn">Marcar todas como lidas</a>

<?php if(count($notificacoes) == 0): ?>
    <p>Não há notificações.</p>
<?php else: ?>
    <?php foreach($notificacoes as $n): ?>
        <div class="notificacao <?php echo $n['lida'] ? 'lida' : ''; ?>">
            <?php echo htmlspecialchars($n['mensagem']); ?>
            <br><small><?php echo date('d/m/Y H:i', strtotime($n['criado_em'])); ?></small>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<a href="dashboard.php" class="btn">⬅ Voltar</a>

</body>
</html>
