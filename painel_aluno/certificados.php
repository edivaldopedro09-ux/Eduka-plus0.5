<?php
session_start();
require_once("../config.php");

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'aluno') {
    header("Location: ../index.php");
    exit();
}

$aluno_id = $_SESSION['usuario_id'];

$sql = "SELECT c.titulo AS curso, cr.status, cr.data_emissao, c.id AS curso_id, cr.id AS certificado_id
        FROM certificados cr
        JOIN cursos c ON c.id = cr.curso_id
        WHERE cr.aluno_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $aluno_id);
$stmt->execute();
$result = $stmt->get_result();
$certificados = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Meus Certificados — Eduka Plus</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --primary: #2563eb;
    --primary-dark: #1d4ed8;
    --primary-light: #3b82f6;
    --secondary: #0ea5e9;
    --secondary-dark: #0284c7;
    --background: #ffffff;
    --surface: #f8fafc;
    --surface-light: #ffffff;
    --surface-dark: #f1f5f9;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --text-muted: #94a3b8;
    --border: #e2e8f0;
    --border-light: #f1f5f9;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --info: #0ea5e9;
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    --radius: 12px;
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
    line-height: 1.6;
}

/* Header Principal */
.main-header {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    color: white;
    padding: 2rem;
    margin-bottom: 2rem;
}

.header-content {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-title h1 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.header-title p {
    opacity: 0.9;
    font-size: 1rem;
}

.header-actions {
    display: flex;
    gap: 1rem;
}

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: var(--radius);
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    border: none;
    cursor: pointer;
}

.btn-primary {
    background: white;
    color: var(--primary);
}

.btn-primary:hover {
    background: var(--surface-dark);
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.btn-secondary {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.btn-secondary:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
}

/* Container Principal */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1.5rem 2rem;
}

/* Estatísticas */
.stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2.5rem;
}

.stat-card {
    background: var(--surface-light);
    border-radius: var(--radius);
    padding: 1.5rem;
    border: 1px solid var(--border);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
    border-color: rgba(37, 99, 235, 0.2);
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
    font-size: 1.5rem;
    background: rgba(37, 99, 235, 0.1);
    color: var(--primary);
}

.stat-card:nth-child(2) .stat-icon {
    background: rgba(245, 158, 11, 0.1);
    color: var(--warning);
}

.stat-card:nth-child(3) .stat-icon {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success);
}

.stat-content h3 {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text-secondary);
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
    color: var(--text-primary);
    line-height: 1;
}

.stat-description {
    font-size: 0.85rem;
    color: var(--text-muted);
}

/* Grid de Certificados */
.certificates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

@media (max-width: 768px) {
    .certificates-grid {
        grid-template-columns: 1fr;
    }
}

/* Card do Certificado */
.certificate-card {
    background: var(--surface-light);
    border-radius: var(--radius);
    border: 1px solid var(--border);
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.certificate-card:hover {
    transform: translateY(-8px);
    box-shadow: var(--shadow-lg);
    border-color: rgba(37, 99, 235, 0.2);
}

.certificate-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--border);
    background: linear-gradient(135deg, rgba(37, 99, 235, 0.05) 0%, rgba(14, 165, 233, 0.05) 100%);
}

.certificate-title {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.certificate-icon {
    width: 60px;
    height: 60px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.certificate-info h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
    line-height: 1.3;
}

.certificate-badge {
    display: inline-block;
    padding: 0.35rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.badge-issued {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success);
}

.badge-pending {
    background: rgba(245, 158, 11, 0.1);
    color: var(--warning);
}

.certificate-content {
    padding: 1.5rem;
}

.certificate-details {
    margin-bottom: 1.5rem;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--border-light);
}

.detail-item:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.detail-label {
    color: var(--text-secondary);
    font-weight: 500;
    font-size: 0.9rem;
}

.detail-value {
    font-weight: 600;
    color: var(--text-primary);
}

.detail-value.status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Informações de Pagamento */
.payment-info {
    background: var(--surface-dark);
    border-radius: var(--radius);
    padding: 1.25rem;
    margin-bottom: 1.5rem;
    border: 1px solid var(--border);
}

.payment-info h4 {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.payment-method {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border);
}

.payment-method:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.method-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: rgba(37, 99, 235, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary);
    font-size: 1.2rem;
    flex-shrink: 0;
}

.method-details h5 {
    font-size: 0.95rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
    color: var(--text-primary);
}

.method-details p {
    font-size: 0.85rem;
    color: var(--text-secondary);
    line-height: 1.4;
}

/* Botões de Ação */
.certificate-actions {
    display: flex;
    gap: 0.75rem;
    margin-top: 1.5rem;
}

.cert-btn {
    flex: 1;
    padding: 0.75rem;
    border-radius: 8px;
    border: 1px solid var(--border);
    background: var(--surface-dark);
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
    text-align: center;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    cursor: pointer;
}

.cert-btn:hover {
    background: rgba(37, 99, 235, 0.1);
    color: var(--primary);
    border-color: rgba(37, 99, 235, 0.3);
    transform: translateY(-2px);
}

.cert-btn.primary {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

.cert-btn.primary:hover {
    background: var(--primary-dark);
    border-color: var(--primary-dark);
}

.cert-btn.success {
    background: rgba(16, 185, 129, 0.1);
    border-color: rgba(16, 185, 129, 0.2);
    color: var(--success);
}

.cert-btn.success:hover {
    background: rgba(16, 185, 129, 0.2);
    border-color: var(--success);
}

/* Formulário de Upload */
.upload-form {
    background: var(--surface-dark);
    border-radius: var(--radius);
    padding: 1.25rem;
    margin-top: 1rem;
    border: 1px solid var(--border);
}

.upload-form h4 {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.file-input {
    margin-bottom: 1rem;
}

.file-input input[type="file"] {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    background: var(--surface-light);
    font-family: 'Inter', sans-serif;
}

/* Empty State */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    text-align: center;
    background: var(--surface-light);
    border-radius: var(--radius);
    border: 2px dashed var(--border);
    grid-column: 1 / -1;
}

.empty-icon {
    font-size: 3.5rem;
    color: var(--text-muted);
    margin-bottom: 1.5rem;
    opacity: 0.5;
}

.empty-state h3 {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.75rem;
}

.empty-state p {
    color: var(--text-secondary);
    margin-bottom: 2rem;
    max-width: 400px;
}

/* Footer */
.main-footer {
    margin-top: 3rem;
    padding-top: 2rem;
    border-top: 1px solid var(--border-light);
    text-align: center;
    color: var(--text-secondary);
}

.footer-links {
    display: flex;
    justify-content: center;
    gap: 2rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.footer-links a {
    color: var(--text-secondary);
    text-decoration: none;
    transition: color 0.3s ease;
    font-size: 0.95rem;
    font-weight: 500;
}

.footer-links a:hover {
    color: var(--primary);
}

.copyright {
    font-size: 0.9rem;
    margin-top: 0.5rem;
}

.version {
    display: inline-block;
    background: var(--surface-dark);
    color: var(--text-secondary);
    padding: 0.4rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    margin-top: 1rem;
    font-weight: 500;
}

/* Responsividade */
@media (max-width: 768px) {
    .header-content {
        flex-direction: column;
        text-align: center;
        gap: 1.5rem;
    }
    
    .header-actions {
        flex-direction: column;
        width: 100%;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
    
    .stats-cards {
        grid-template-columns: 1fr;
    }
    
    .certificate-actions {
        flex-direction: column;
    }
}

@media (max-width: 480px) {
    .detail-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
    
    .payment-method {
        flex-direction: column;
    }
}
</style>
</head>
<body>

<!-- Header Principal -->
<header class="main-header">
    <div class="header-content">
        <div class="header-title">
            <h1><i class="fas fa-award"></i> Meus Certificados</h1>
            <p>Gerencie e visualize todos os seus certificados conquistados</p>
        </div>
        <div class="header-actions">
            <a href="meus_cursos.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i>
                <span>Voltar aos Cursos</span>
            </a>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </div>
    </div>
</header>

<!-- Conteúdo Principal -->
<main class="container">
    <?php
    // Calcular estatísticas
    $total_certificados = count($certificados);
    $certificados_emitidos = 0;
    $certificados_pendentes = 0;
    
    foreach($certificados as $cert) {
        if($cert['status'] == 'pago') {
            $certificados_emitidos++;
        } else {
            $certificados_pendentes++;
        }
    }
    ?>
    
    <!-- Estatísticas -->
    <div class="stats-cards">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-award"></i>
            </div>
            <div class="stat-content">
                <h3>Certificados Solicitados</h3>
                <div class="stat-value"><?php echo $total_certificados; ?></div>
                <p class="stat-description">Total de certificados solicitados</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <h3>Certificados Emitidos</h3>
                <div class="stat-value"><?php echo $certificados_emitidos; ?></div>
                <p class="stat-description">Certificados disponíveis para download</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <h3>Pendentes</h3>
                <div class="stat-value"><?php echo $certificados_pendentes; ?></div>
                <p class="stat-description">Aguardando processamento</p>
            </div>
        </div>
    </div>

    <!-- Grid de Certificados -->
    <div class="certificates-grid">
        <?php if(empty($certificados)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-award"></i>
                </div>
                <h3>Nenhum certificado solicitado</h3>
                <p>Você ainda não solicitou certificados. Conclua seus cursos para solicitar seu primeiro certificado.</p>
                <a href="meus_cursos.php" class="btn btn-primary" style="margin-top: 1rem;">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Ver Meus Cursos</span>
                </a>
            </div>
        <?php else: ?>
            <?php foreach($certificados as $cert): 
                $status = $cert['status'];
                $data_emissao = !empty($cert['data_emissao']) ? date("d/m/Y H:i", strtotime($cert['data_emissao'])) : null;
                
                // Determinar badge e status
                if($status == 'pago') {
                    $statusClass = 'badge-issued';
                    $statusText = 'Emitido';
                    $statusIcon = 'fas fa-check-circle';
                } else {
                    $statusClass = 'badge-pending';
                    $statusText = ucfirst($status);
                    $statusIcon = 'fas fa-clock';
                }
            ?>
            <div class="certificate-card">
                <div class="certificate-header">
                    <div class="certificate-title">
                        <div class="certificate-icon">
                            <i class="fas fa-award"></i>
                        </div>
                        <div class="certificate-info">
                            <h3><?php echo htmlspecialchars($cert['curso']); ?></h3>
                            <span class="certificate-badge <?php echo $statusClass; ?>">
                                <i class="<?php echo $statusIcon; ?>"></i> <?php echo $statusText; ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="certificate-content">
                    <div class="certificate-details">
                        <div class="detail-item">
                            <span class="detail-label">Status do Certificado</span>
                            <span class="detail-value status">
                                <i class="<?php echo $statusIcon; ?>"></i> <?php echo $statusText; ?>
                            </span>
                        </div>
                        
                        <?php if($data_emissao): ?>
                        <div class="detail-item">
                            <span class="detail-label">Data de Emissão</span>
                            <span class="detail-value">
                                <i class="far fa-calendar-check"></i> <?php echo $data_emissao; ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="detail-item">
                            <span class="detail-label">Valor do Certificado</span>
                            <span class="detail-value">
                                <i class="fas fa-money-bill-wave"></i> 500,00 KZ
                            </span>
                        </div>
                    </div>
                    
                    <?php if($status != 'pago'): ?>
                    <div class="payment-info">
                        <h4><i class="fas fa-credit-card"></i> Informações de Pagamento</h4>
                        
                        <div class="payment-method">
                            <div class="method-icon">
                                <i class="fas fa-university"></i>
                            </div>
                            <div class="method-details">
                                <h5>Transferência Bancária</h5>
                                <p>
                                    <strong>Banco:</strong> ATLANTICO<br>
                                    <strong>Conta:</strong> 300089688 10 001<br>
                                    <strong>IBAN:</strong> 0055.0000.0008.9688.1016.5<br>
                                    <strong>Titular:</strong> Edivaldo dos Santos Pedro
                                </p>
                            </div>
                        </div>
                        
                        <div class="payment-method">
                            <div class="method-icon">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <div class="method-details">
                                <h5>Multicaixa Express</h5>
                                <p>
                                    <strong>Número:</strong> +244 936 863 110<br>
                                    <strong>Referência:</strong> Certificado #<?php echo $cert['certificado_id']; ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="payment-method">
                            <div class="method-icon">
                                <i class="fas fa-globe"></i>
                            </div>
                            <div class="method-details">
                                <h5>PayPay Ao</h5>
                                <p>
                                    <strong>Número:</strong> 929057372<br>
                                    <strong>Nome:</strong> Eduka Plus Angola
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="upload-form">
                        <h4><i class="fas fa-upload"></i> Enviar Comprovativo</h4>
                        <form action="upload_comprovativo.php" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="certificado_id" value="<?= $cert['certificado_id'] ?>">
                            
                            <div class="file-input">
                                <input type="file" name="comprovativo" accept="image/*,.pdf,.doc,.docx" required>
                            </div>
                            
                            <button type="submit" class="cert-btn success" style="width: 100%;">
                                <i class="fas fa-paper-plane"></i>
                                <span>Enviar Comprovativo</span>
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                    
                    <div class="certificate-actions">
                        <?php if($status == 'pago'): ?>
                            <a href="gerar_certificado.php?curso_id=<?= $cert['curso_id'] ?>" class="cert-btn primary">
                                <i class="fas fa-download"></i>
                                <span>Baixar Certificado</span>
                            </a>
                        <?php else: ?>
                            <a href="meus_cursos.php" class="cert-btn">
                                <i class="fas fa-graduation-cap"></i>
                                <span>Ver Curso</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<!-- Footer -->
<footer class="main-footer">
    <div class="footer-links">
        <a href="ajuda.php">Central de Ajuda</a>
        <a href="privacidade.php">Política de Privacidade</a>
        <a href="termos.php">Termos de Uso</a>
        <a href="contato.php">Fale Conosco</a>
    </div>
    <div class="copyright">
        © <?php echo date("Y"); ?> Eduka Plus Angola — Transformando a educação angolana.
    </div>
    <div class="version">Versão 3.0.0</div>
</footer>

<script>
// Efeito de hover nos cards
document.querySelectorAll('.certificate-card').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-8px)';
        this.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
        this.style.boxShadow = 'none';
    });
});

// Efeito de hover nos botões
document.querySelectorAll('.btn, .cert-btn').forEach(btn => {
    btn.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-2px)';
    });
    
    btn.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
    });
});

// Validação do formulário
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const fileInput = this.querySelector('input[type="file"]');
        if (fileInput && !fileInput.value) {
            e.preventDefault();
            alert('Por favor, selecione um arquivo para enviar.');
            fileInput.style.borderColor = 'var(--danger)';
            fileInput.focus();
        }
    });
});
</script>
</body>
</html>