<?php
session_start();
require_once("../config.php");

// Apenas alunos
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'aluno') {
    header("Location: ../index.php");
    exit();
}

$aluno_id = $_SESSION['usuario_id'];
$curso_id = intval($_GET['curso_id'] ?? 0);

if ($curso_id <= 0) {
    die("Curso inválido.");
}

// Verifica se já existe certificado para esse aluno+curso
$stmt = $conn->prepare("SELECT id, status FROM certificados WHERE aluno_id=? AND curso_id=?");
$stmt->bind_param("ii", $aluno_id, $curso_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $row = $res->fetch_assoc();
    if ($row['status'] === 'pago') {
        header("Location: certificados.php?msg=ja_pago");
        exit();
    } else {
        header("Location: certificados.php?msg=ja_pendente");
        exit();
    }
}

// Insere novo registro pendente
$stmt = $conn->prepare("INSERT INTO certificados (aluno_id, curso_id, status, data_criacao) VALUES (?, ?, 'pendente', NOW())");
$stmt->bind_param("ii", $aluno_id, $curso_id);
if ($stmt->execute()) {
    header("Location: certificados.php?msg=criado");
    exit();
} else {
    die("Erro ao criar pedido de certificado: " . $conn->error);
}
