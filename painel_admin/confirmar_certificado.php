<?php
session_start();
require_once("../config.php");

// Apenas admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        die("ID inválido.");
    }

    $stmt = $conn->prepare("UPDATE certificados SET status='pago', data_pagamento=NOW() WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: certificados.php?ok=1");
        exit();
    } else {
        die("Erro ao atualizar: " . $conn->error);
    }
}
