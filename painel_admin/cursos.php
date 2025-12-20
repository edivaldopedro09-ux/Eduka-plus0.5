<?php
session_start();
require_once("../config.php");

// ✅ Só admin pode acessar
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Buscar cursos no banco
$sql = "SELECT c.id, c.titulo, c.descricao, c.imagem, u.nome AS professor 
        FROM cursos c
        LEFT JOIN usuarios u ON c.professor_id = u.id
        ORDER BY c.id DESC";
$result = $conn->query($sql);

// Mensagem de sessão
$msg = "";
if (isset($_SESSION['msg'])) {
    $msg = $_SESSION['msg'];
    unset($_SESSION['msg']);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel de Cursos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
           background: linear-gradient(-45deg,#0d47a1,#1565c0,#1e3a8a,#0f172a);
            font-family: Arial, sans-serif;
        }
        .container {
            margin-top: 40px;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0px 4px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s ease-in-out;
        }
        .card:hover {
            transform: scale(1.02);
        }
        .card img {
            border-radius: 15px 15px 0 0;
            height: 200px;
            object-fit: cover;
        }
        .btn-edit {
            background: #3498db;
            color: #fff;
        }
        .btn-edit:hover {
            background: #2980b9;
            color: #fff;
        }
        .btn-delete {
            background: #e74c3c;
            color: #fff;
        }
        .btn-delete:hover {
            background: #c0392b;
            color: #fff;
        }
    </style>
</head>
<body>

<div class="container">
    <h2 class="mb-4 text-center">📚 Gerenciar Cursos</h2>

    <?php if ($msg): ?>
        <div class="alert alert-info text-center"><?= $msg ?></div>
    <?php endif; ?>

    <div class="text-end mb-4">
        <a href="cursos_add.php" class="btn btn-success">➕ Adicionar Curso</a>
        <a href="dashboard.php" class="btn btn-success">voltar</a>
    </div>

    <div class="row">
        <?php if ($result->num_rows > 0): ?>
            <?php while($curso = $result->fetch_assoc()): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <?php if (!empty($curso['imagem'])): ?>
                            <img src="../uploads/<?= htmlspecialchars($curso['imagem']) ?>" class="card-img-top" alt="Imagem do curso">
                        <?php else: ?>
                            <img src="https://via.placeholder.com/400x200?text=Sem+Imagem" class="card-img-top" alt="Imagem do curso">
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($curso['titulo']) ?></h5>
                            <p class="card-text"><?= nl2br(substr($curso['descricao'], 0, 100)) ?>...</p>
                            <p><strong>Professor:</strong> <?= htmlspecialchars($curso['professor'] ?? "Não definido") ?></p>
                        </div>
                        <div class="card-footer text-center">
                            <a href="cursos_edit.php?id=<?= $curso['id'] ?>" class="btn btn-edit btn-sm">✏️ Editar</a>
                            <a href="cursos_delete.php?id=<?= $curso['id'] ?>" class="btn btn-delete btn-sm" onclick="return confirm('Tem certeza que deseja excluir este curso?')">🗑️ Excluir</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-warning text-center">Nenhum curso cadastrado ainda.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
