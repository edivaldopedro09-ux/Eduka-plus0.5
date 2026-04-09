<?php
session_start();
require_once("config.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_SESSION['reset_user_id'])) {
        die("Sessão expirada ou inválida.");
    }

    $id = $_SESSION['reset_user_id'];
    $nova = $_POST['nova_senha'];
    $confirma = $_POST['confirma_senha'];

    if ($nova !== $confirma) {
        die("❌ As senhas não coincidem!");
    }

    $hash = password_hash($nova, PASSWORD_DEFAULT);

    $sql = "UPDATE usuarios SET senha=?, reset_token=NULL, reset_expira=NULL WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $hash, $id);

    if ($stmt->execute()) {
        unset($_SESSION['reset_user_id']);
        echo "<p style='color:green;text-align:center;'>✅ Senha alterada com sucesso!</p>";
        echo "<p style='text-align:center;'><a href='login.php'>Ir para login</a></p>";
    } else {
        echo "Erro ao redefinir senha.";
    }
}
