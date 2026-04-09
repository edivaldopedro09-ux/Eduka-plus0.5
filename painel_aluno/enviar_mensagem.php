<?php
session_start();
include("../config.php");

// Verifica login do aluno
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'aluno') {
    header("Location: ../index.html");
    exit();
}

$aluno_id = $_SESSION['usuario_id'];

// Verifica se recebeu o curso_id
if (!isset($_POST['curso_id'])) {
    die("Curso não informado.");
}
$curso_id = intval($_POST['curso_id']);

// Verifica se a mensagem foi enviada
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['mensagem'])) {
    $mensagem = trim($_POST['mensagem']);
    if ($mensagem !== "") {
        // Inserir a mensagem no banco
        $stmt = $conn->prepare("INSERT INTO mensagens (curso_id, usuario_id, mensagem, data_envio) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $curso_id, $aluno_id, $mensagem);

        if ($stmt->execute()) {
            header("Location: chat.php?curso_id=" . $curso_id);
            exit();
        } else {
            echo "Erro ao enviar a mensagem: " . $stmt->error;
        }
    } else {
        echo "Mensagem não pode estar vazia.";
    }
} else {
    echo "Requisição inválida.";
}
