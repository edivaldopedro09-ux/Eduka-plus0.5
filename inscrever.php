<?php
session_start();
require_once("config.php");

// Verifica se veio o ID do curso
if (!isset($_GET['curso_id']) || !is_numeric($_GET['curso_id'])) {
    header("Location: cursos.php");
    exit();
}

$curso_id = (int)$_GET['curso_id'];

// Verifica se usuário está logado
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'aluno') {
    // Redireciona para login/cadastro
    $_SESSION['mensagem'] = "Você precisa estar cadastrado e logado como aluno para se inscrever.";
    header("Location: login.php");
    exit();
}

$aluno_id = $_SESSION['usuario_id'];

// Verifica se já está inscrito
$sql_check = "SELECT id FROM inscricoes WHERE curso_id = ? AND aluno_id = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ii", $curso_id, $aluno_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    $_SESSION['mensagem'] = "Você já está inscrito neste curso.";
    header("Location: meus_cursos.php");
    exit();
}

// Insere inscrição
$sql = "INSERT INTO inscricoes (curso_id, aluno_id, status) VALUES (?, ?, 'ativo')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $curso_id, $aluno_id);

if ($stmt->execute()) {
    $_SESSION['mensagem'] = "Inscrição realizada com sucesso!";
    header("Location: meus_cursos.php");
    exit();
} else {
    $_SESSION['mensagem'] = "Erro ao se inscrever no curso.";
    header("Location: cursos.php");
    exit();
}
?>
