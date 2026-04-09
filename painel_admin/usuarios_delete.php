<?php
session_start();
require_once("../config.php");

// Segurança: só admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("ID do usuário não informado.");
}

$usuario_id = intval($_GET['id']);

// Buscar informações do usuário
$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id=?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die("Usuário não encontrado.");
}

$tipo = $user['tipo'];

// Verificar dependências
$tem_dependencias = false;
$mensagem = "";

// Se for aluno, verificar inscrições
if ($tipo === 'aluno') {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM inscricoes WHERE aluno_id=?");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    if ($total > 0) {
        $tem_dependencias = true;
        $mensagem .= "❌ O aluno possui inscrições em cursos.<br>";
    }

    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM progresso WHERE aluno_id=?");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    if ($total > 0) {
        $tem_dependencias = true;
        $mensagem .= "❌ O aluno possui progresso registrado.<br>";
    }
}

// Se for professor, verificar cursos
if ($tipo === 'professor') {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM cursos WHERE professor_id=?");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    if ($total > 0) {
        $tem_dependencias = true;
        $mensagem .= "❌ O professor possui cursos cadastrados.<br>";
    }
}

// Se houver dependências, não permitir deletar
if ($tem_dependencias) {
    echo "<h2>Não é possível deletar este usuário</h2>";
    echo $mensagem;
    echo "<a href='usuarios.php'>Voltar</a>";
    exit();
}

// Caso não haja dependências, deletar o usuário
$stmt = $conn->prepare("DELETE FROM usuarios WHERE id=?");
$stmt->bind_param("i", $usuario_id);
if ($stmt->execute()) {
    echo "<script>alert('Usuário deletado com sucesso!'); window.location='usuarios.php';</script>";
} else {
    echo "Erro ao deletar usuário: " . $stmt->error;
}
?>
