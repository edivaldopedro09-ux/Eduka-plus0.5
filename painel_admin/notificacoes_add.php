<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once("../config.php");

// Buscar usuários para opção individual
$usuarios = $conn->query("SELECT id, nome, tipo FROM usuarios ORDER BY nome ASC")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'];
    $mensagem = $_POST['mensagem'];
    $destinatario_tipo = $_POST['destinatario_tipo'];
    $destinatario_id = $_POST['destinatario_id'] ?? null;

    if ($destinatario_tipo === 'usuario' && !empty($destinatario_id)) {
        // Notificação para usuário específico
        $stmt = $conn->prepare("INSERT INTO notificacoes 
            (titulo, mensagem, data_envio, destinatario_tipo, destinatario_id) 
            VALUES (?, ?, NOW(), ?, ?)");
        $stmt->bind_param("sssi", $titulo, $mensagem, $destinatario_tipo, $destinatario_id);
    } else {
        // Notificação para todos / alunos / professores
        $stmt = $conn->prepare("INSERT INTO notificacoes 
            (titulo, mensagem, data_envio, destinatario_tipo, destinatario_id) 
            VALUES (?, ?, NOW(), ?, NULL)");
        $stmt->bind_param("sss", $titulo, $mensagem, $destinatario_tipo);
    }

    $stmt->execute();

    echo "<script>alert('✅ Notificação enviada com sucesso!'); window.location='enviar_notificacao.php';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Enviar Notificação</title>
    <style>
        body { font-family: Arial, sans-serif; background:#0f172a; color:#e5e7eb; padding:30px;}
        form { background:#111827; padding:20px; border-radius:12px; max-width:500px; margin:auto; }
        label { display:block; margin-top:12px; font-weight:bold;}
        input, textarea, select { width:100%; padding:10px; margin-top:6px; border-radius:8px; border:1px solid #1f2937; background:#1e293b; color:#e5e7eb;}
        button { margin-top:16px; padding:10px; background:#3b82f6; color:white; border:none; border-radius:8px; cursor:pointer;}
        button:hover { background:#2563eb; }
        .hidden { display:none; }
    </style>
    <script>
        function toggleUserSelect() {
            let tipo = document.getElementById('destinatario_tipo').value;
            document.getElementById('usuario_select').style.display = (tipo === 'usuario') ? 'block' : 'none';
        }
    </script>
</head>
<body>
    <h2>📢 Enviar Notificação</h2>
    <form method="POST">
        <label>Título:</label>
        <input type="text" name="titulo" required>

        <label>Mensagem:</label>
        <textarea name="mensagem" rows="4" required></textarea>

        <label>Enviar para:</label>
        <select name="destinatario_tipo" id="destinatario_tipo" onchange="toggleUserSelect()" required>
            <option value="todos">🌍 Todos (Alunos + Professores)</option>
            <option value="alunos">🎓 Apenas Alunos</option>
            <option value="professores">👨‍🏫 Apenas Professores</option>
            <option value="usuario">👤 Usuário específico</option>
        </select>

        <div id="usuario_select" class="hidden">
            <label>Escolher usuário:</label>
            <select name="destinatario_id">
                <option value="">-- Selecione --</option>
                <?php foreach ($usuarios as $u): ?>
                    <option value="<?= $u['id']; ?>">
                        <?= htmlspecialchars($u['nome']); ?> (<?= $u['tipo']; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit">🚀 Enviar</button>
    </form>
</body>
</html>
