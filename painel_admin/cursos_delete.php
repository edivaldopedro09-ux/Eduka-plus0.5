<?php
session_start();
require_once("../config.php");

// ✅ Só admin pode excluir
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// ✅ Verificar ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: cursos.php");
    exit();
}

$curso_id = intval($_GET['id']);

// 🔹 Primeiro, excluir inscrições ligadas a esse curso
$sql = "DELETE FROM inscricoes WHERE curso_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $curso_id);
$stmt->execute();

// 🔹 Agora excluir o curso
$sql = "DELETE FROM cursos WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $curso_id);

if ($stmt->execute()) {
    $_SESSION['msg'] = "✅ Curso excluído com sucesso!";
} else {
    $_SESSION['msg'] = "❌ Erro ao excluir curso.";
}

header("Location: cursos.php");
exit();
