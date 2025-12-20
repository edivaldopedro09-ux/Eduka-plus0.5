<?php
session_start();
require_once("../config.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'aluno') {
    header("Location: ../index.php");
    exit();
}

$aluno_id = $_SESSION['usuario_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['certificado_id'])) {
    $certificado_id = intval($_POST['certificado_id']);

    // Validar se pertence ao aluno
    $stmt = $conn->prepare("SELECT * FROM certificados WHERE id=? AND aluno_id=?");
    $stmt->bind_param("ii", $certificado_id, $aluno_id);
    $stmt->execute();
    $cert = $stmt->get_result()->fetch_assoc();

    if (!$cert) {
        die("Certificado não encontrado.");
    }

    if (!empty($_FILES['comprovativo']['name'])) {
        $ext = pathinfo($_FILES['comprovativo']['name'], PATHINFO_EXTENSION);
        $nomeArquivo = "comp_" . $aluno_id . "_" . $certificado_id . "." . $ext;
        $destino = "../uploads/comprovativos/" . $nomeArquivo;

        // Criar pasta se não existir
        if (!is_dir("../uploads/comprovativos/")) {
            mkdir("../uploads/comprovativos/", 0777, true);
        }

        if (move_uploaded_file($_FILES['comprovativo']['tmp_name'], $destino)) {
            // Atualizar no BD
            $stmt2 = $conn->prepare("UPDATE certificados SET comprovativo=? WHERE id=?");
            $stmt2->bind_param("si", $nomeArquivo, $certificado_id);
            $stmt2->execute();

            header("Location: meus_certificados.php?ok=1");
            exit();
        } else {
            echo "Erro ao enviar o arquivo.";
        }
    } else {
        echo "Selecione um arquivo para enviar.";
    }
}
?>
