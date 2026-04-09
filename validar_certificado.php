<?php
require_once("config.php");

// Pega o código da URL
$codigo = $_GET['codigo'] ?? '';

if (empty($codigo)) {
    die("❌ Código de autenticação não informado.");
}

// Consulta na base de dados
$sql = "SELECT c.codigo_autenticacao, c.data_emissao, u.nome AS aluno, cs.titulo AS curso
        FROM certificados c
        JOIN usuarios u ON u.id = c.aluno_id
        JOIN cursos cs ON cs.id = c.curso_id
        WHERE c.codigo_autenticacao = ? AND c.status='pago' 
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $codigo);
$stmt->execute();
$result = $stmt->get_result();
$cert = $result->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Validação de Certificado</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f7f9fc;
            color: #333;
            text-align: center;
            padding: 40px;
        }
        .container {
            max-width: 650px;
            margin: auto;
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #004080;
        }
        .valido {
            color: green;
            font-weight: bold;
            font-size: 18px;
        }
        .invalido {
            color: red;
            font-weight: bold;
            font-size: 18px;
        }
        .dados {
            margin-top: 20px;
            text-align: left;
            line-height: 1.6;
        }
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #777;
        }
        .codigo {
            font-family: monospace;
            font-size: 16px;
            background: #eee;
            padding: 8px 12px;
            display: inline-block;
            border-radius: 5px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Validação de Certificado</h1>

    <?php if ($cert): ?>
        <p class="valido">✅ Certificado válido e autenticado!</p>
        <div class="dados">
            <p><strong>Aluno:</strong> <?= htmlspecialchars($cert['aluno']) ?></p>
            <p><strong>Curso:</strong> <?= htmlspecialchars($cert['curso']) ?></p>
            <p><strong>Data de Emissão:</strong> <?= date("d/m/Y", strtotime($cert['data_emissao'])) ?></p>
        </div>
        <p class="codigo">Código: <?= htmlspecialchars($cert['codigo_autenticacao']) ?></p>
    <?php else: ?>
        <p class="invalido">❌ Código inválido ou certificado não encontrado.</p>
        <p class="codigo"><?= htmlspecialchars($codigo) ?></p>
    <?php endif; ?>

    <div class="footer">
        © <?= date("Y") ?> Eduka Plus Angola - Sistema de Certificação
    </div>
</div>
</body>
</html>
