<?php
session_start();
require_once("../config.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'professor') {
    header("Location: ../index.php");
    exit();
}

$professor_id = $_SESSION['usuario_id'];
$aula_id = intval($_GET['id'] ?? 0);

// Buscar aula e verificar permissão
$sql = "SELECT a.*, c.professor_id FROM aulas a INNER JOIN cursos c ON a.curso_id=c.id WHERE a.id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $aula_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) { die("Aula não encontrada."); }
$aula = $result->fetch_assoc();

if($aula['professor_id'] != $professor_id) die("Sem permissão.");

// Excluir arquivo de vídeo
$video_path = "../uploads/videos/".$aula['video'];
if(file_exists($video_path)) unlink($video_path);

// Excluir do banco
$sql = "DELETE FROM aulas WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $aula_id);
$stmt->execute();

header("Location: gerenciar_aulas.php?curso_id=".$aula['curso_id']);
exit();
