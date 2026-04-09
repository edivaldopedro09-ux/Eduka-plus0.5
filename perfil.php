<?php
session_start();
require_once("config.php");

// Segurança: só usuários logados podem acessar
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$id = $_SESSION['usuario_id'];
$tipo = $_SESSION['usuario_tipo'];
$mensagem = "";

// Determinar link da dashboard de acordo com o tipo de usuário
$dashboard_link = "#";
if ($tipo === "aluno") {
    $dashboard_link = "painel_aluno/dashboard.php";
} elseif ($tipo === "professor") {
    $dashboard_link = "painel_professor/dashboard.php";
} elseif ($tipo === "admin") {
    $dashboard_link = "painel_admin/dashboard.php";
}

// Atualizar foto
if (isset($_POST['atualizar_foto']) && isset($_FILES['foto'])) {
    $file = $_FILES['foto'];
    if ($file['error'] == 0) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $novo_nome = "foto_" . $id . "." . $ext;
        $caminho = "uploads/" . $novo_nome;

        if (!is_dir("uploads")) mkdir("uploads", 0777, true);

        if (move_uploaded_file($file['tmp_name'], $caminho)) {
            $sql = "UPDATE usuarios SET foto = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $caminho, $id);
            if ($stmt->execute()) {
                $_SESSION['usuario_foto'] = $caminho;
                $mensagem = "✅ Foto atualizada com sucesso!";
            }
        } else {
            $mensagem = "❌ Erro ao enviar a foto!";
        }
    }
}

// Atualizar senha
if (isset($_POST['atualizar_senha'])) {
    $senha_atual = $_POST['senha_atual'];
    $nova_senha = $_POST['nova_senha'];
    $confirmar = $_POST['confirmar_senha'];

    $sql = "SELECT senha FROM usuarios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row && password_verify($senha_atual, $row['senha'])) {
        if ($nova_senha === $confirmar) {
            $nova_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $sql = "UPDATE usuarios SET senha = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $nova_hash, $id);
            if ($stmt->execute()) {
                $mensagem = "✅ Senha alterada com sucesso!";
            }
        } else {
            $mensagem = "⚠️ A nova senha e a confirmação não coincidem!";
        }
    } else {
        $mensagem = "❌ Senha atual incorreta!";
    }
}

// Buscar dados do usuário
$sql = "SELECT * FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Meu Perfil — Eduka Plus</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --primary: #2563eb;
    --primary-dark: #1d4ed8;
    --primary-light: #3b82f6;
    --secondary: #0ea5e9;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --background: #f8fafc;
    --surface: #ffffff;
    --text: #1e293b;
    --text-light: #64748b;
    --border: #e2e8f0;
    --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    --radius: 12px;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    color: var(--text);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.perfil-container {
    width: 100%;
    max-width: 500px;
    background: var(--surface);
    border-radius: var(--radius);
    box-shadow: var(--shadow-lg);
    overflow: hidden;
    animation: fadeIn 0.5s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.btn-voltar {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    background: var(--primary);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 500;
    margin: 20px;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.btn-voltar:hover {
    background: var(--primary-dark);
    transform: translateX(-4px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}

.perfil-header {
    text-align: center;
    padding: 40px 20px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    color: white;
    position: relative;
}

.perfil-header img {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 4px solid rgba(255, 255, 255, 0.3);
    object-fit: cover;
    margin-bottom: 20px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    transition: transform 0.3s ease;
}

.perfil-header img:hover {
    transform: scale(1.05);
}

.perfil-header h2 {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 8px;
}

.perfil-header p {
    font-size: 16px;
    opacity: 0.9;
    margin-bottom: 4px;
}

.user-badge {
    display: inline-block;
    background: rgba(255, 255, 255, 0.2);
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
    margin-top: 10px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.msg {
    margin: 20px;
    padding: 16px;
    border-radius: var(--radius);
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 12px;
    animation: slideIn 0.3s ease;
}

.msg-success {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success);
    border-left: 4px solid var(--success);
}

.msg-warning {
    background: rgba(245, 158, 11, 0.1);
    color: var(--warning);
    border-left: 4px solid var(--warning);
}

.msg-error {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger);
    border-left: 4px solid var(--danger);
}

@keyframes slideIn {
    from { opacity: 0; transform: translateX(-20px); }
    to { opacity: 1; transform: translateX(0); }
}

form {
    padding: 30px;
    margin: 20px;
    background: var(--surface);
    border-radius: var(--radius);
    border: 1px solid var(--border);
    transition: all 0.3s ease;
}

form:hover {
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.05);
    border-color: var(--primary-light);
}

form h3 {
    font-size: 20px;
    color: var(--primary);
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 10px;
    padding-bottom: 12px;
    border-bottom: 2px solid var(--border);
}

form h3 i {
    font-size: 22px;
}

.form-group {
    margin-bottom: 20px;
}

input[type="file"] {
    width: 100%;
    padding: 16px;
    border: 2px dashed var(--border);
    border-radius: var(--radius);
    background: var(--background);
    cursor: pointer;
    transition: all 0.3s ease;
    font-family: 'Inter', sans-serif;
}

input[type="file"]:hover {
    border-color: var(--primary);
    background: rgba(37, 99, 235, 0.05);
}

input[type="file"]::file-selector-button {
    background: var(--primary);
    color: white;
    border: none;
    padding: 10px 16px;
    border-radius: 6px;
    margin-right: 12px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
}

input[type="file"]::file-selector-button:hover {
    background: var(--primary-dark);
}

input[type="password"] {
    width: 100%;
    padding: 16px;
    border: 2px solid var(--border);
    border-radius: var(--radius);
    font-size: 16px;
    font-family: 'Inter', sans-serif;
    transition: all 0.3s ease;
    background: var(--surface);
}

input[type="password"]:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

input[type="password"]:hover {
    border-color: var(--primary-light);
}

.password-wrapper {
    position: relative;
}

.toggle-password {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--text-light);
    cursor: pointer;
    padding: 4px;
    font-size: 18px;
}

.toggle-password:hover {
    color: var(--primary);
}

button[type="submit"] {
    width: 100%;
    padding: 16px;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: var(--radius);
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    font-family: 'Inter', sans-serif;
}

button[type="submit"]:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(37, 99, 235, 0.2);
}

button[type="submit"]:active {
    transform: translateY(0);
}

.file-info {
    font-size: 14px;
    color: var(--text-light);
    margin-top: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.file-info i {
    font-size: 12px;
}

/* Responsividade */
@media (max-width: 768px) {
    body {
        padding: 15px;
    }
    
    .perfil-container {
        max-width: 100%;
    }
    
    .btn-voltar {
        margin: 15px;
        padding: 10px 16px;
    }
    
    .perfil-header {
        padding: 30px 15px;
    }
    
    .perfil-header img {
        width: 100px;
        height: 100px;
    }
    
    .perfil-header h2 {
        font-size: 24px;
    }
    
    form {
        padding: 20px;
        margin: 15px;
    }
}

@media (max-width: 480px) {
    .perfil-header {
        padding: 25px 10px;
    }
    
    .perfil-header img {
        width: 80px;
        height: 80px;
    }
    
    form {
        padding: 15px;
        margin: 10px;
    }
    
    input[type="password"],
    input[type="file"],
    button[type="submit"] {
        padding: 14px;
    }
}

/* Animações */
form {
    animation: fadeInUp 0.5s ease;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Placeholder avatar */
.avatar-placeholder {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    border: 4px solid rgba(255, 255, 255, 0.3);
    font-size: 48px;
    color: white;
}

/* Focus states */
input:focus, button:focus {
    outline: none;
}

/* Custom scrollbar */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: var(--background);
}

::-webkit-scrollbar-thumb {
    background: var(--primary);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: var(--primary-dark);
}
</style>
</head>
<body>
<div class="perfil-container">
    <a href="<?php echo $dashboard_link; ?>" class="btn-voltar">
        <i class="fas fa-arrow-left"></i>
        Voltar para Dashboard
    </a>

    <div class="perfil-header">
        <?php if ($usuario['foto']) { ?>
            <img src="<?php echo $usuario['foto']; ?>" alt="Foto de perfil" id="profileImage">
        <?php } else { ?>
            <div class="avatar-placeholder">
                <i class="fas fa-user"></i>
            </div>
        <?php } ?>
        <h2><?php echo htmlspecialchars($usuario['nome']); ?></h2>
        <p><?php echo htmlspecialchars($usuario['email']); ?></p>
        <span class="user-badge">
            <i class="fas fa-user-tag"></i>
            <?php echo htmlspecialchars($usuario['tipo']); ?>
        </span>
    </div>

    <?php 
    if ($mensagem) {
        $msgClass = 'msg';
        if (strpos($mensagem, '✅') !== false) $msgClass .= ' msg-success';
        elseif (strpos($mensagem, '⚠️') !== false) $msgClass .= ' msg-warning';
        elseif (strpos($mensagem, '❌') !== false) $msgClass .= ' msg-error';
        
        echo "<div class='$msgClass'><i class='fas fa-info-circle'></i><span>$mensagem</span></div>";
    }
    ?>

    <form method="POST" enctype="multipart/form-data" id="photoForm">
        <h3><i class="fas fa-camera"></i> Atualizar Foto</h3>
        <div class="form-group">
            <input type="file" name="foto" accept="image/*" required id="fileInput">
            <div class="file-info">
                <i class="fas fa-info-circle"></i>
                Formatos: JPG, PNG, GIF • Máx: 5MB
            </div>
        </div>
        <button type="submit" name="atualizar_foto">
            <i class="fas fa-save"></i>
            Salvar Foto
        </button>
    </form>

    <form method="POST" id="passwordForm">
        <h3><i class="fas fa-lock"></i> Alterar Senha</h3>
        <div class="form-group">
            <div class="password-wrapper">
                <input type="password" name="senha_atual" placeholder="Senha atual" required id="currentPassword">
                <button type="button" class="toggle-password" data-target="currentPassword">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>
        <div class="form-group">
            <div class="password-wrapper">
                <input type="password" name="nova_senha" placeholder="Nova senha" required id="newPassword">
                <button type="button" class="toggle-password" data-target="newPassword">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>
        <div class="form-group">
            <div class="password-wrapper">
                <input type="password" name="confirmar_senha" placeholder="Confirmar nova senha" required id="confirmPassword">
                <button type="button" class="toggle-password" data-target="confirmPassword">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>
        <button type="submit" name="atualizar_senha">
            <i class="fas fa-key"></i>
            Atualizar Senha
        </button>
    </form>
</div>

<script>
// Toggle para mostrar/ocultar senha
document.querySelectorAll('.toggle-password').forEach(button => {
    button.addEventListener('click', function() {
        const targetId = this.getAttribute('data-target');
        const input = document.getElementById(targetId);
        const icon = this.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'fas fa-eye-slash';
            this.setAttribute('title', 'Ocultar senha');
        } else {
            input.type = 'password';
            icon.className = 'fas fa-eye';
            this.setAttribute('title', 'Mostrar senha');
        }
    });
});

// Preview da imagem antes de enviar
const fileInput = document.getElementById('fileInput');
const profileImage = document.getElementById('profileImage');

if (fileInput && profileImage) {
    fileInput.addEventListener('change', function(e) {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            
            // Validar tamanho (5MB máximo)
            if (file.size > 5 * 1024 * 1024) {
                alert('A imagem deve ter no máximo 5MB!');
                this.value = '';
                return;
            }
            
            // Validar tipo
            const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                alert('Por favor, selecione uma imagem (JPG, PNG, GIF)!');
                this.value = '';
                return;
            }
            
            // Fazer preview
            const reader = new FileReader();
            reader.onload = function(e) {
                profileImage.src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    });
}

// Validar senhas antes de enviar
const passwordForm = document.getElementById('passwordForm');
if (passwordForm) {
    passwordForm.addEventListener('submit', function(e) {
        const novaSenha = document.getElementById('newPassword').value;
        const confirmarSenha = document.getElementById('confirmPassword').value;
        
        if (novaSenha !== confirmarSenha) {
            e.preventDefault();
            showAlert('A nova senha e a confirmação não coincidem!', 'error');
            return false;
        }
        
        if (novaSenha.length < 6) {
            e.preventDefault();
            showAlert('A senha deve ter pelo menos 6 caracteres!', 'error');
            return false;
        }
        
        return true;
    });
}

// Função para mostrar alertas
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `msg msg-${type}`;
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'error' ? 'exclamation-triangle' : 'info-circle'}"></i>
        <span>${message}</span>
    `;
    
    // Inserir após o header
    const header = document.querySelector('.perfil-header');
    header.parentNode.insertBefore(alertDiv, header.nextSibling);
    
    // Remover após 5 segundos
    setTimeout(() => {
        alertDiv.style.opacity = '0';
        alertDiv.style.transform = 'translateX(-20px)';
        setTimeout(() => alertDiv.remove(), 300);
    }, 5000);
}

// Animar elementos na entrada
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach((form, index) => {
        form.style.animationDelay = `${index * 0.1}s`;
    });
});

// Adicionar efeito de hover na foto
const avatar = document.querySelector('.perfil-header img, .avatar-placeholder');
if (avatar) {
    avatar.addEventListener('mouseenter', function() {
        this.style.transform = 'scale(1.05)';
    });
    
    avatar.addEventListener('mouseleave', function() {
        this.style.transform = 'scale(1)';
    });
}

// Melhorar UX do file input
if (fileInput) {
    fileInput.addEventListener('focus', function() {
        this.style.borderColor = 'var(--primary)';
        this.style.boxShadow = '0 0 0 3px rgba(37, 99, 235, 0.1)';
    });
    
    fileInput.addEventListener('blur', function() {
        this.style.borderColor = '';
        this.style.boxShadow = '';
    });
}
</script>
</body>
</html>