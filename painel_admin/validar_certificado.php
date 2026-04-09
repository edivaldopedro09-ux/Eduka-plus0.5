<?php
session_start();
require_once("../config.php");

// Segurança: só admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id'], $_POST['acao'])) {
    $id = intval($_POST['id']);
    $acao = $_POST['acao'];

    if ($acao === "aprovar") {
        $status = "pago";
        $stmt = $conn->prepare("UPDATE certificados SET status=?, data_emissao=NOW() WHERE id=?");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
    } elseif ($acao === "rejeitar") {
        $status = "pendente";
        // Apaga comprovativo para que o aluno envie outro
        $stmt = $conn->prepare("UPDATE certificados SET comprovativo=NULL WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
}

header("Location: certificados.php");
exit();
?>

