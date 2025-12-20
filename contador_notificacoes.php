<?php
session_start();
require_once("../config.php");
$usuario_id = $_SESSION['usuario_id'];

$sql = "SELECT COUNT(*) AS nao_lidas FROM notificacoes WHERE usuario_id=? AND lida=0";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$contador = $result->fetch_assoc()['nao_lidas'] ?? 0;

echo json_encode(['nao_lidas'=>$contador]);
