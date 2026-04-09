<?php
session_start();
include("../config.php");

// Verifica login
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'aluno') {
    header("Location: ../index.html");
    exit();
}

$aluno_id = $_SESSION['usuario_id'];
$aluno_nome = $_SESSION['usuario_nome'] ?? "Aluno";

// Verifica curso
if (!isset($_GET['curso_id'])) {
    die("Curso não informado.");
}
$curso_id = intval($_GET['curso_id']);

// Buscar informações do curso
$sql_curso = $conn->prepare("SELECT titulo FROM cursos WHERE id = ?");
$sql_curso->bind_param("i", $curso_id);
$sql_curso->execute();
$curso = $sql_curso->get_result()->fetch_assoc();
$curso_titulo = $curso['titulo'] ?? "Curso #" . $curso_id;

// Verifica se o aluno está inscrito neste curso
$sql = $conn->prepare("SELECT * FROM inscricoes WHERE curso_id=? AND aluno_id=?");
$sql->bind_param("ii", $curso_id, $aluno_id);
$sql->execute();
$res = $sql->get_result();
if ($res->num_rows == 0) {
    die("Você não está inscrito neste curso.");
}

// Enviar mensagem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['mensagem'])) {
    $mensagem = trim($_POST['mensagem']);
    if ($mensagem !== "") {
        $stmt = $conn->prepare("INSERT INTO mensagens (curso_id, usuario_id, mensagem, data_envio) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $curso_id, $aluno_id, $mensagem);
        $stmt->execute();
    }
}

// Buscar mensagens
$sql = $conn->prepare("
    SELECT m.*, u.nome, u.tipo, u.foto
    FROM mensagens m
    INNER JOIN usuarios u ON m.usuario_id = u.id
    WHERE m.curso_id=?
    ORDER BY m.data_envio ASC
");
$sql->bind_param("i", $curso_id);
$sql->execute();
$mensagens = $sql->get_result();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat da Turma — Eduka Plus Angola</title>
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
        --surface-dark: #f1f5f9;
        --text-primary: #1e293b;
        --text-secondary: #64748b;
        --text-muted: #94a3b8;
        --border: #e2e8f0;
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        --radius: 12px;
        --radius-lg: 16px;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        background: var(--background);
        color: var(--text-primary);
        height: 100vh;
        display: flex;
        flex-direction: column;
        line-height: 1.6;
    }

    /* Header */
    .chat-header {
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        padding: 1.25rem 2rem;
        color: white;
        box-shadow: var(--shadow-md);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
    }

    .header-left {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .back-btn {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: white;
        text-decoration: none;
        padding: 0.75rem 1.25rem;
        border-radius: var(--radius);
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
    }

    .back-btn:hover {
        background: rgba(255, 255, 255, 0.25);
        transform: translateX(-4px);
    }

    .course-info {
        display: flex;
        flex-direction: column;
    }

    .course-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: white;
    }

    .course-subtitle {
        font-size: 0.9rem;
        opacity: 0.9;
        margin-top: 0.25rem;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .user-avatar {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        border: 2px solid rgba(255, 255, 255, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 1rem;
        color: white;
        overflow: hidden;
    }

    .user-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .user-name {
        font-weight: 500;
        font-size: 0.95rem;
    }

    /* Container principal */
    .chat-container {
        flex: 1;
        display: flex;
        overflow: hidden;
        position: relative;
    }

    /* Sidebar de participantes */
    .participants-sidebar {
        width: 280px;
        background: var(--surface);
        border-right: 1px solid var(--border);
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
        overflow-y: auto;
    }

    .sidebar-header {
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--border-light);
    }

    .sidebar-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .sidebar-title i {
        color: var(--primary);
    }

    .participants-count {
        font-size: 0.85rem;
        color: var(--text-muted);
        margin-top: 0.25rem;
    }

    .participants-list {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .participant {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem;
        border-radius: var(--radius);
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .participant:hover {
        background: var(--surface-dark);
    }

    .participant-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.9rem;
        color: white;
        overflow: hidden;
    }

    .participant-professor {
        background: linear-gradient(135deg, var(--danger) 0%, #ef4444 100%);
    }

    .participant-aluno {
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    }

    .participant-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .participant-info {
        flex: 1;
    }

    .participant-name {
        font-weight: 500;
        font-size: 0.9rem;
        color: var(--text-primary);
    }

    .participant-role {
        font-size: 0.75rem;
        color: var(--text-muted);
        margin-top: 0.125rem;
    }

    .participant-status {
        width: 10px;
        height: 10px;
        border-radius: 50%;
    }

    .status-online {
        background: var(--success);
        box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
    }

    .status-offline {
        background: var(--text-muted);
    }

    /* Área de mensagens */
    .messages-area {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: var(--surface);
    }

    .messages-container {
        flex: 1;
        padding: 2rem;
        overflow-y: auto;
        background: linear-gradient(180deg, var(--background) 0%, var(--surface) 100%);
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    /* Mensagens */
    .message {
        display: flex;
        gap: 1rem;
        max-width: 80%;
        animation: messageIn 0.3s ease;
    }

    @keyframes messageIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .message.received {
        align-self: flex-start;
    }

    .message.sent {
        align-self: flex-end;
        flex-direction: row-reverse;
    }

    .message-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.9rem;
        color: white;
        flex-shrink: 0;
        overflow: hidden;
    }

    .message-professor .message-avatar {
        background: linear-gradient(135deg, var(--danger) 0%, #ef4444 100%);
    }

    .message-aluno .message-avatar {
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    }

    .message-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .message-content {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .message.received .message-content {
        align-items: flex-start;
    }

    .message.sent .message-content {
        align-items: flex-end;
    }

    .message-bubble {
        padding: 1rem 1.25rem;
        border-radius: var(--radius-lg);
        position: relative;
        max-width: 100%;
        word-wrap: break-word;
        box-shadow: var(--shadow-sm);
    }

    .message.received .message-bubble {
        background: var(--surface);
        border: 1px solid var(--border);
        color: var(--text-primary);
        border-top-left-radius: 4px;
    }

    .message.sent .message-bubble {
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        color: white;
        border-top-right-radius: 4px;
    }

    .message-text {
        line-height: 1.5;
        font-size: 0.95rem;
    }

    .message-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-top: 0.5rem;
    }

    .message-sender {
        font-weight: 600;
        font-size: 0.85rem;
    }

    .message.received .message-sender {
        color: var(--text-primary);
    }

    .message.sent .message-sender {
        color: rgba(255, 255, 255, 0.9);
    }

    .message-time {
        font-size: 0.75rem;
        opacity: 0.7;
    }

    .message.received .message-time {
        color: var(--text-muted);
    }

    .message.sent .message-time {
        color: rgba(255, 255, 255, 0.7);
    }

    /* Input de mensagem */
    .message-input-container {
        padding: 1.5rem 2rem;
        background: var(--surface);
        border-top: 1px solid var(--border);
        flex-shrink: 0;
    }

    .message-form {
        display: flex;
        gap: 1rem;
        align-items: flex-end;
    }

    .input-wrapper {
        flex: 1;
        position: relative;
    }

    .message-input {
        width: 100%;
        min-height: 50px;
        max-height: 150px;
        padding: 1rem 1rem 1rem 3rem;
        border: 2px solid var(--border);
        border-radius: var(--radius-lg);
        background: var(--surface);
        color: var(--text-primary);
        font-family: 'Inter', sans-serif;
        font-size: 0.95rem;
        resize: none;
        line-height: 1.5;
        transition: all 0.3s ease;
    }

    .message-input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    .input-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        font-size: 1.1rem;
    }

    .send-btn {
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        color: white;
        border: none;
        width: 50px;
        height: 50px;
        border-radius: var(--radius);
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    .send-btn:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(37, 99, 235, 0.3);
    }

    .send-btn:active {
        transform: translateY(0);
    }

    .input-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 0.75rem;
        padding-left: 3rem;
    }

    .action-btn {
        background: none;
        border: none;
        color: var(--text-muted);
        cursor: pointer;
        padding: 0.5rem;
        border-radius: 6px;
        transition: all 0.3s ease;
        font-size: 1.1rem;
    }

    .action-btn:hover {
        color: var(--primary);
        background: var(--surface-dark);
    }

    /* Indicador de novo usuário */
    .new-user-notice {
        text-align: center;
        margin: 1rem 0;
        position: relative;
    }

    .new-user-notice::before {
        content: '';
        position: absolute;
        left: 0;
        right: 0;
        top: 50%;
        height: 1px;
        background: var(--border);
    }

    .notice-text {
        display: inline-block;
        background: var(--surface);
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.85rem;
        color: var(--text-muted);
        border: 1px solid var(--border);
        position: relative;
        z-index: 1;
    }

    /* Welcome message */
    .welcome-message {
        text-align: center;
        padding: 2rem;
        background: var(--surface);
        border-radius: var(--radius);
        border: 1px solid var(--border);
        margin: 2rem auto;
        max-width: 600px;
    }

    .welcome-icon {
        font-size: 3rem;
        color: var(--primary);
        margin-bottom: 1rem;
    }

    .welcome-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
    }

    .welcome-text {
        color: var(--text-secondary);
        margin-bottom: 1.5rem;
    }

    /* Responsividade */
    @media (max-width: 1024px) {
        .participants-sidebar {
            width: 250px;
        }
    }

    @media (max-width: 768px) {
        .participants-sidebar {
            position: fixed;
            left: -100%;
            top: 0;
            bottom: 0;
            width: 280px;
            z-index: 1000;
            transition: left 0.3s ease;
            box-shadow: var(--shadow-lg);
        }

        .participants-sidebar.show {
            left: 0;
        }

        .chat-header {
            padding: 1rem;
        }

        .header-left {
            flex: 1;
        }

        .user-info {
            display: none;
        }

        .messages-container {
            padding: 1rem;
        }

        .message {
            max-width: 90%;
        }

        .message-input-container {
            padding: 1rem;
        }

        .toggle-sidebar-btn {
            display: block;
            background: none;
            border: none;
            color: white;
            font-size: 1.25rem;
            cursor: pointer;
            padding: 0.5rem;
        }
    }

    @media (max-width: 480px) {
        .message {
            max-width: 95%;
        }

        .message-avatar {
            width: 35px;
            height: 35px;
            font-size: 0.8rem;
        }

        .message-bubble {
            padding: 0.75rem 1rem;
        }

        .message-input {
            padding-left: 2.5rem;
            font-size: 0.9rem;
        }

        .input-icon {
            left: 0.75rem;
        }

        .input-actions {
            padding-left: 2.5rem;
        }
    }

    /* Scrollbar personalizada */
    ::-webkit-scrollbar {
        width: 8px;
    }

    ::-webkit-scrollbar-track {
        background: var(--surface-dark);
    }

    ::-webkit-scrollbar-thumb {
        background: var(--primary);
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: var(--primary-dark);
    }

    /* Animações */
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .chat-container {
        animation: fadeIn 0.3s ease;
    }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="chat-header">
        <div class="header-left">
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Voltar
            </a>
            <button class="toggle-sidebar-btn" id="toggleSidebar">
                <i class="fas fa-users"></i>
            </button>
            <div class="course-info">
                <div class="course-title"><?php echo htmlspecialchars($curso_titulo); ?></div>
                <div class="course-subtitle">Chat da Turma</div>
            </div>
        </div>

        <div class="user-info">
            <div class="user-avatar">
                <?php
                $nome = $_SESSION['usuario_nome'];
                $iniciais = strtoupper(substr($nome, 0, 1) . substr($nome, strpos($nome, ' ') + 1, 1));
                echo $iniciais;
                ?>
            </div>
            <div class="user-name"><?php echo htmlspecialchars($aluno_nome); ?></div>
        </div>
    </header>

    <!-- Container Principal -->
    <div class="chat-container">
        <!-- Sidebar de Participantes -->
        <aside class="participants-sidebar" id="participantsSidebar">
            <div class="sidebar-header">
                <h3 class="sidebar-title">
                    <i class="fas fa-users"></i>
                    Participantes
                </h3>
                <div class="participants-count" id="participantsCount">Carregando...</div>
            </div>

            <div class="participants-list" id="participantsList">
                <!-- Lista de participantes será carregada via JavaScript -->
            </div>

            <div style="margin-top: auto; padding-top: 1rem; border-top: 1px solid var(--border);">
                <div class="notice-text">
                    <i class="fas fa-info-circle"></i>
                    <?php echo count($cursos ?? []); ?> alunos no curso
                </div>
            </div>
        </aside>

        <!-- Área de Mensagens -->
        <div class="messages-area">
            <!-- Container de Mensagens -->
            <div class="messages-container" id="messagesContainer">
                <!-- Mensagem de boas-vindas -->
                <div class="welcome-message">
                    <div class="welcome-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h3 class="welcome-title">Bem-vindo ao chat da turma!</h3>
                    <p class="welcome-text">
                        Este é o espaço para tirar dúvidas, compartilhar ideias e interagir com colegas e professores.
                    </p>
                    <div style="color: var(--text-muted); font-size: 0.9rem;">
                        <i class="fas fa-lightbulb"></i>
                        Dica: Mantenha o respeito e foco no aprendizado.
                    </div>
                </div>

                <!-- Mensagens -->
                <?php 
                $last_date = null;
                while ($m = $mensagens->fetch_assoc()): 
                    $current_date = date("d/m/Y", strtotime($m['data_envio']));
                    $is_sent = $m['usuario_id'] == $aluno_id;
                    $user_type = $m['tipo'];
                    
                    // Mostrar separador de data se mudou
                    if ($current_date != $last_date):
                        $last_date = $current_date;
                ?>
                <div class="new-user-notice">
                    <span class="notice-text"><?php echo $current_date; ?></span>
                </div>
                <?php endif; ?>

                <div class="message <?php echo $is_sent ? 'sent' : 'received'; ?> message-<?php echo $user_type; ?>"
                     data-message-id="<?php echo $m['id']; ?>">
                    <div class="message-avatar">
                        <?php
                        $nome = $m['nome'];
                        $iniciais = strtoupper(substr($nome, 0, 1) . substr($nome, strpos($nome, ' ') + 1, 1));
                        echo $iniciais;
                        ?>
                    </div>
                    <div class="message-content">
                        <div class="message-bubble">
                            <div class="message-text"><?php echo nl2br(htmlspecialchars($m['mensagem'])); ?></div>
                        </div>
                        <div class="message-info">
                            <span class="message-sender"><?php echo htmlspecialchars($m['nome']); ?></span>
                            <span class="message-time">
                                <?php echo date("H:i", strtotime($m['data_envio'])); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>

            <!-- Input de Mensagem -->
            <div class="message-input-container">
                <form method="POST" class="message-form" id="messageForm">
                    <div class="input-wrapper">
                        <div class="input-icon">
                            <i class="fas fa-comment"></i>
                        </div>
                        <textarea class="message-input" name="mensagem" 
                                  placeholder="Digite sua mensagem..." 
                                  rows="1" required></textarea>
                    </div>
                    <button type="submit" class="send-btn">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
                <div class="input-actions">
                    <button type="button" class="action-btn" title="Anexar arquivo">
                        <i class="fas fa-paperclip"></i>
                    </button>
                    <button type="button" class="action-btn" title="Enviar imagem">
                        <i class="fas fa-image"></i>
                    </button>
                    <button type="button" class="action-btn" title="Emojis">
                        <i class="far fa-smile"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Elementos DOM
    const messagesContainer = document.getElementById('messagesContainer');
    const messageForm = document.getElementById('messageForm');
    const messageInput = document.querySelector('.message-input');
    const toggleSidebarBtn = document.getElementById('toggleSidebar');
    const participantsSidebar = document.getElementById('participantsSidebar');
    const participantsList = document.getElementById('participantsList');
    const participantsCount = document.getElementById('participantsCount');

    // Auto-expand textarea
    messageInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });

    // Auto-scroll to bottom
    function scrollToBottom() {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    // Inicializar scroll
    setTimeout(scrollToBottom, 100);

    // Carregar participantes (simulação)
    function loadParticipants() {
        const participants = [
            { name: "<?php echo $aluno_nome; ?>", role: "Aluno", status: "online", type: "aluno", isCurrentUser: true },
            { name: "Professor Silva", role: "Professor", status: "online", type: "professor", isCurrentUser: false },
            { name: "Maria Santos", role: "Aluno", status: "online", type: "aluno", isCurrentUser: false },
            { name: "João Pereira", role: "Aluno", status: "offline", type: "aluno", isCurrentUser: false },
            { name: "Ana Costa", role: "Aluno", status: "online", type: "aluno", isCurrentUser: false },
            { name: "Pedro Alves", role: "Aluno", status: "offline", type: "aluno", isCurrentUser: false },
        ];

        participantsList.innerHTML = '';
        participants.forEach(participant => {
            const avatarText = participant.name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
            
            const participantElement = document.createElement('div');
            participantElement.className = 'participant';
            participantElement.innerHTML = `
                <div class="participant-avatar participant-${participant.type} ${participant.isCurrentUser ? 'current-user' : ''}">
                    ${avatarText}
                </div>
                <div class="participant-info">
                    <div class="participant-name">${participant.name}</div>
                    <div class="participant-role">${participant.role}</div>
                </div>
                <div class="participant-status status-${participant.status}"></div>
            `;
            
            participantsList.appendChild(participantElement);
        });

        participantsCount.textContent = `${participants.length} participantes`;
    }

    // Carregar participantes
    loadParticipants();

    // Toggle sidebar mobile
    toggleSidebarBtn.addEventListener('click', () => {
        participantsSidebar.classList.toggle('show');
    });

    // Fechar sidebar ao clicar fora (mobile)
    document.addEventListener('click', (e) => {
        if (window.innerWidth <= 768) {
            if (!participantsSidebar.contains(e.target) && !toggleSidebarBtn.contains(e.target)) {
                participantsSidebar.classList.remove('show');
            }
        }
    });

    // Enviar mensagem com AJAX
    messageForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const message = messageInput.value.trim();
        if (!message) return;

        // Criar elemento de mensagem temporário
        const messageElement = document.createElement('div');
        messageElement.className = 'message sent message-aluno';
        messageElement.innerHTML = `
            <div class="message-avatar"><?php
                $nome = $_SESSION['usuario_nome'];
                $iniciais = strtoupper(substr($nome, 0, 1) . substr($nome, strpos($nome, ' ') + 1, 1));
                echo $iniciais;
            ?></div>
            <div class="message-content">
                <div class="message-bubble">
                    <div class="message-text">${message}</div>
                </div>
                <div class="message-info">
                    <span class="message-sender"><?php echo htmlspecialchars($aluno_nome); ?></span>
                    <span class="message-time">Agora</span>
                </div>
            </div>
        `;

        // Adicionar ao container
        messagesContainer.appendChild(messageElement);
        
        // Limpar input
        messageInput.value = '';
        messageInput.style.height = 'auto';
        
        // Scroll para baixo
        scrollToBottom();
        
        // Simular envio para servidor
        setTimeout(() => {
            // Aqui você faria a requisição AJAX real
            // fetch('chat.php', { method: 'POST', body: new FormData(this) })
            // .then(response => response.json())
            // .then(data => { ... });
            
            // Por enquanto, apenas submetemos o form normalmente
            this.submit();
        }, 100);
    });

    // Suporte para Enter (Shift+Enter para nova linha)
    messageInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            messageForm.dispatchEvent(new Event('submit'));
        }
    });

    // Atualizar status online (simulação)
    setInterval(() => {
        const statusIndicators = document.querySelectorAll('.participant-status');
        statusIndicators.forEach(indicator => {
            if (Math.random() > 0.7) {
                indicator.classList.toggle('status-online');
                indicator.classList.toggle('status-offline');
            }
        });
    }, 10000);

    // Verificar novas mensagens (simulação)
    let lastMessageCount = <?php echo $mensagens->num_rows; ?>;
    setInterval(() => {
        // Em um sistema real, você faria uma requisição AJAX para verificar novas mensagens
        // fetch(`check_messages.php?curso_id=<?php echo $curso_id; ?>&last=${lastMessageCount}`)
        // .then(response => response.json())
        // .then(data => {
        //     if (data.new_messages.length > 0) {
        //         // Adicionar novas mensagens
        //         data.new_messages.forEach(msg => {
        //             addMessage(msg);
        //         });
        //         lastMessageCount = data.total_messages;
        //         
        //         // Notificação sonora (opcional)
        //         if (!document.hasFocus()) {
        //             new Audio('notification.mp3').play();
        //         }
        //     }
        // });
    }, 5000); // Verificar a cada 5 segundos

    // Função para adicionar mensagem
    function addMessage(message) {
        const isSent = message.usuario_id == <?php echo $aluno_id; ?>;
        const userType = message.tipo;
        const nome = message.nome;
        const avatarText = nome.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
        const time = new Date(message.data_envio).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        const messageElement = document.createElement('div');
        messageElement.className = `message ${isSent ? 'sent' : 'received'} message-${userType}`;
        messageElement.innerHTML = `
            <div class="message-avatar">${avatarText}</div>
            <div class="message-content">
                <div class="message-bubble">
                    <div class="message-text">${message.mensagem}</div>
                </div>
                <div class="message-info">
                    <span class="message-sender">${nome}</span>
                    <span class="message-time">${time}</span>
                </div>
            </div>
        `;

        messagesContainer.appendChild(messageElement);
        scrollToBottom();
    }

    // Inicializar
    window.addEventListener('load', () => {
        // Focar no input
        messageInput.focus();
        
        // Verificar se há mensagens
        const messages = document.querySelectorAll('.message');
        if (messages.length === 0) {
            const welcome = document.querySelector('.welcome-message');
            welcome.style.display = 'block';
        }
    });

    // Ajustar altura da sidebar
    function adjustSidebarHeight() {
        if (window.innerWidth <= 768) {
            participantsSidebar.style.height = '100vh';
            participantsSidebar.style.top = '0';
        }
    }

    window.addEventListener('resize', adjustSidebarHeight);
    adjustSidebarHeight();
    </script>
</body>
</html>