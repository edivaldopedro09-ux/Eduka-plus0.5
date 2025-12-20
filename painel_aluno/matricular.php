<?php
session_start();
require_once("../config.php");

if(!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo']!=='aluno'){
    header("Location: ../index.php");
    exit();
}

$aluno_id = $_SESSION['usuario_id'];

if(!isset($_GET['curso_id'])){
    die("Parâmetros inválidos.");
}
$curso_id = intval($_GET['curso_id']);

// Verifica se já está matriculado
$sql_check = "SELECT * FROM inscricoes WHERE aluno_id=? AND curso_id=?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ii",$aluno_id,$curso_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if($result_check->num_rows==0){
    // Inserir inscrição
    $sql_insert = "INSERT INTO inscricoes (aluno_id, curso_id, inscrito_em) VALUES (?,?,NOW())";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("ii",$aluno_id,$curso_id);
    $stmt_insert->execute();
}

// Redirecionar para ver aulas
header("Location: ver_aulas.php?curso_id=".$curso_id);
exit();
