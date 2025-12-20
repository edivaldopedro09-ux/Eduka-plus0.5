<?php
session_start();
require_once("../config.php");

// 🔒 Verifica se é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Buscar todos os usuários
$sql = "SELECT * FROM usuarios ORDER BY id DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Gerir Usuários — Painel Admin</title>
  <style>
    body { font-family: Arial, sans-serif; background:#f1f5f9; margin:0; }
    header { background:#0f172a; color:#fff; padding:16px; }
    h1 { margin:0; font-size:20px; }
    .container { padding:20px; }
    .btn { padding:8px 14px; background:#0f172a; color:#fff; border:none; border-radius:6px; text-decoration:none; }
    .btn:hover { background:#1e293b; }
    table { width:100%; border-collapse:collapse; margin-top:20px; }
    th, td { padding:12px; border-bottom:1px solid #ddd; text-align:left; }
    th { background:#e2e8f0; }
    img { width:40px; height:40px; border-radius:50%; }
    .actions a { margin-right:8px; text-decoration:none; color:#0f172a; font-weight:bold; }
    .actions a:hover { text-decoration:underline; }
  </style>
</head>
<body>
  <header>
    <h1>Painel Admin — Gestão de Usuários</h1>
  </header>

  <div class="container">
    <a href="usuarios_add.php" class="btn">➕ Novo Usuário</a>

    <table>
      <tr>
        <th>ID</th>
        <th>Foto</th>
        <th>Nome</th>
        <th>Email</th>
        <th>Tipo</th>
        <th>Ações</th>
      </tr>
      <?php while($u = $result->fetch_assoc()) { ?>
      <tr>
        <td><?= $u['id'] ?></td>
        <td>
          <?php if ($u['foto']) { ?>
            <img src="../uploads/<?= $u['foto'] ?>" alt="foto">
          <?php } else { ?>
            <img src="../uploads/default.png" alt="foto">
          <?php } ?>
        </td>
        <td><?= htmlspecialchars($u['nome']) ?></td>
        <td><?= htmlspecialchars($u['email']) ?></td>
        <td><?= ucfirst($u['tipo']) ?></td>
        <td class="actions">
          <a href="usuarios_edit.php?id=<?= $u['id'] ?>">✏️ Editar</a>
          <a href="usuarios_delete.php?id=<?= $u['id'] ?>" onclick="return confirm('Deseja realmente excluir?')">🗑 Excluir</a>
        </td>
      </tr>
      <?php } ?>
    </table>
  </div>
</body>
</html>
