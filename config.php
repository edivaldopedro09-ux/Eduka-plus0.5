<?php
$host = "localhost";
$user = "root";   // coloque seu usuário do MySQL
$pass = "";       // coloque a senha se tiver
$db   = "plataforma";

// Conexão
$conn = new mysqli($host, $user, $pass, $db);

// Verificar erro
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}
?>
