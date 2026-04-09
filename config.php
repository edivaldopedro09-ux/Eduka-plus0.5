<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "localhost";
$user = "root";   // coloque seu usuário do MySQL
$pass = "";       // coloque a senha se tiver
$db   = "plataforma";

// Conexão
$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset('utf8mb4');

// Verificar erro
if ($conn->connect_error) {
    error_log("Erro na conexão: " . $conn->connect_error);
    die("Erro na conexão com o banco de dados.");
}
?>
