<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once("../config.php");

if (!isset($_GET['id'])) {
    header("Location: cursos.php");
    exit();
}

$curso_id = intval($_GET['id']);

// Usar transação para garantir consistência
$conn->begin_transaction();

try {
    // Excluir inscrições do curso
    $stmt = $conn->prepare("DELETE FROM inscricoes WHERE curso_id=?");
    $stmt->bind_param("i", $curso_id);
    $stmt->execute();
    $stmt->close();

    // Excluir materiais do curso
    $stmt = $conn->prepare("DELETE FROM materiais WHERE curso_id=?");
    $stmt->bind_param("i", $curso_id);
    $stmt->execute();
    $stmt->close();

    // Excluir aulas do curso
    $stmt = $conn->prepare("DELETE FROM aulas WHERE curso_id=?");
    $stmt->bind_param("i", $curso_id);
    $stmt->execute();
    $stmt->close();

    // Finalmente excluir o curso
    $stmt = $conn->prepare("DELETE FROM cursos WHERE id=?");
    $stmt->bind_param("i", $curso_id);
    $stmt->execute();
    $stmt->close();

    // Commit se tudo deu certo
    $conn->commit();

    header("Location: cursos.php?msg=Curso excluído com sucesso");
    exit();

} catch (Exception $e) {
    // Rollback em caso de erro
    $conn->rollback();
    die("Erro ao excluir o curso: " . $e->getMessage());
}
?>
