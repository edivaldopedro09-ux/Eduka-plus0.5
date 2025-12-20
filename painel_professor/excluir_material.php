<?php
session_start();
require_once("../config.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'professor') {
    header("Location: ../index.php");
    exit();
}

$professor_id = $_SESSION['usuario_id'];
$material_id = intval($_GET['id'] ?? 0);

// Buscar material e verificar permissão
$sql = "SELECT m.*, c.professor_id FROM materiais m INNER JOIN cursos c ON m.curso_id=c.id WHERE m.id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $material_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) { die("Material não encontrado."); }
$material = $result->fetch_assoc();

if($material['professor_id'] != $professor_id) die("Sem permissão.");

// Excluir arquivo físico
$arquivo_path = "../uploads/materials/".$material['arquivo'];
if(file_exists($arquivo_path)) unlink($arquivo_path);

// Excluir do banco
$sql = "DELETE FROM materiais WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $material_id);
$stmt->execute();

header("Location: gerenciar_materiais.php?curso_id=".$material['curso_id']);
exit();
