<?php
ob_start();
session_start();
require_once("config.php");

$mensagem = "";
$certificado = null;
$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" || isset($_GET['codigo'])) {
    $codigo = isset($_POST['codigo']) ? trim($_POST['codigo']) : (isset($_GET['codigo']) ? trim($_GET['codigo']) : "");
    
    if (!empty($codigo)) {
        $sql = "SELECT cr.id, cr.codigo_autenticacao, cr.status, cr.data_emissao,
                       u.nome AS aluno, u.email AS aluno_email,
                       c.titulo AS curso, c.descricao AS curso_descricao,
                       p.nome AS professor, p.email AS professor_email,
                       c.imagem AS curso_imagem
                FROM certificados cr
                JOIN usuarios u ON u.id = cr.aluno_id
                JOIN cursos c ON c.id = cr.curso_id
                JOIN usuarios p ON p.id = c.professor_id
                WHERE cr.codigo_autenticacao = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $codigo);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows > 0) {
            $certificado = $res->fetch_assoc();
        } else {
            $erro = "Certificado não encontrado. Verifique o código e tente novamente.";
        }
    } elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
        $erro = "Por favor, insira um código de certificado.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Validar Certificado - Eduka Plus Angola</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Verifique a autenticidade dos certificados emitidos pela Eduka Plus Angola">
    <link rel="stylesheet" href="css/inicio.css">
    <link rel="stylesheet" href="css/verificar_certificado.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="logo">
            <img src="imagens/logo.jpg" alt="">
            <span>Eduka Plus</span>
            <span class="logo-sub">Angola</span>
        </div>
        <nav id="menu">
            <a href="index.php"><i class="fas fa-home"></i> Início</a>
            <a href="cursos.php"><i class="fas fa-book"></i> Cursos</a>
            <a href="verificar_certificado.php" class="active"><i class="fas fa-certificate"></i> Validar Certificado</a>
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <?php if ($_SESSION['usuario_tipo'] === 'admin'): ?>
                    <a href="painel_admin/dashboard.php" class="btn-login">
                        <i class="fas fa-user-cog"></i> Admin
                    </a>
                <?php elseif ($_SESSION['usuario_tipo'] === 'professor'): ?>
                    <a href="painel_professor/dashboard.php" class="btn-login">
                        <i class="fas fa-chalkboard-teacher"></i> Professor
                    </a>
                <?php else: ?>
                    <a href="painel_aluno/dashboard.php" class="btn-login">
                        <i class="fas fa-user"></i> Aluno
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <a href="login.php" class="btn-login"><i class="fas fa-sign-in-alt"></i> Login</a>
                <a href="register.php" class="btn-cadastro"><i class="fas fa-user-plus"></i> Cadastro</a>
            <?php endif; ?>
        </nav>
        <div class="hamburger" onclick="toggleMenu()">
            <div></div><div></div><div></div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero certificado-hero">
        <div class="hero-content">
            <div class="hero-badge">
                <i class="fas fa-shield-check"></i>
                <span>Sistema Verificado</span>
            </div>
            <h1>Verifique a <span class="highlight">Autenticidade</span> do Seu Certificado</h1>
            <p>Confirme a validade dos certificados emitidos pela Eduka Plus Angola com nosso sistema seguro de verificação</p>
            
            <div class="hero-features">
                <div class="feature">
                    <i class="fas fa-bolt"></i>
                    <span>Verificação Instantânea</span>
                </div>
                <div class="feature">
                    <i class="fas fa-shield-alt"></i>
                    <span>100% Seguro</span>
                </div>
                <div class="feature">
                    <i class="fas fa-database"></i>
                    <span>Registro Permanente</span>
                </div>
            </div>
        </div>
        <div class="hero-image">
            <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Certificado Eduka Plus">
        </div>
    </section>

    <!-- Verificação Section -->
    <section id="verificacao" class="section">
        <div class="section-header">
            <h2>Verificar Certificado</h2>
            <p>Insira o código do certificado para validar sua autenticidade</p>
        </div>

        <div class="verificacao-container">
            <div class="verificacao-form-card">
                <div class="card-header">
                    <h3><i class="fas fa-search"></i> Verificação</h3>
                </div>
                
                <?php if (!empty($erro)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $erro; ?></span>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="verificacao-form">
                    <div class="form-group">
                        <label for="codigo">
                            <i class="fas fa-hashtag"></i> Código do Certificado
                        </label>
                        <input 
                            type="text" 
                            id="codigo" 
                            name="codigo" 
                            placeholder="Digite o código do certificado" 
                            required
                            value="<?php echo isset($_POST['codigo']) ? htmlspecialchars($_POST['codigo']) : (isset($_GET['codigo']) ? htmlspecialchars($_GET['codigo']) : ''); ?>"
                        >
                        <small class="form-hint">O código está localizado na parte inferior do certificado</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-full">
                        <i class="fas fa-check-circle"></i> Verificar Agora
                    </button>
                </form>
                
                <div class="verificacao-info">
                    <h4><i class="fas fa-info-circle"></i> Como encontrar o código?</h4>
                    <div class="info-tips">
                        <div class="tip">
                            <i class="fas fa-certificate"></i>
                            <p>No rodapé do certificado digital</p>
                        </div>
                        <div class="tip">
                            <i class="fas fa-envelope"></i>
                            <p>No e-mail de confirmação</p>
                        </div>
                        <div class="tip">
                            <i class="fas fa-file-pdf"></i>
                            <p>No arquivo PDF do certificado</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($certificado): ?>
            <div class="resultado-card">
                <div class="card-header <?php echo $certificado['status'] == 'pago' ? 'valido' : 'pendente'; ?>">
                    <div class="status-indicator">
                        <?php if ($certificado['status'] == 'pago'): ?>
                            <i class="fas fa-check-circle"></i>
                        <?php else: ?>
                            <i class="fas fa-clock"></i>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h3>
                            <?php echo $certificado['status'] == 'pago' ? 'Certificado Válido' : 'Certificado Pendente'; ?>
                        </h3>
                        <p>Código: <?php echo htmlspecialchars($certificado['codigo_autenticacao']); ?></p>
                    </div>
                </div>
                
                <div class="resultado-content">
                    <div class="certificado-detalhes">
                        <div class="detalhes-section">
                            <h4><i class="fas fa-user-graduate"></i> Informações do Aluno</h4>
                            <div class="detalhes-grid">
                                <div class="detalhe">
                                    <span class="label">Nome:</span>
                                    <span class="value"><?php echo htmlspecialchars($certificado['aluno']); ?></span>
                                </div>
                                <div class="detalhe">
                                    <span class="label">Email:</span>
                                    <span class="value"><?php echo htmlspecialchars($certificado['aluno_email']); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="detalhes-section">
                            <h4><i class="fas fa-book"></i> Informações do Curso</h4>
                            <div class="detalhes-grid">
                                <div class="detalhe">
                                    <span class="label">Curso:</span>
                                    <span class="value"><?php echo htmlspecialchars($certificado['curso']); ?></span>
                                </div>
                                <div class="detalhe">
                                    <span class="label">Instrutor:</span>
                                    <span class="value"><?php echo htmlspecialchars($certificado['professor']); ?></span>
                                </div>
                                <div class="detalhe">
                                    <span class="label">Data de Emissão:</span>
                                    <span class="value"><?php echo date('d/m/Y', strtotime($certificado['data_emissao'])); ?></span>
                                </div>
                                <div class="detalhe">
                                    <span class="label">Status:</span>
                                    <span class="status-badge <?php echo $certificado['status'] == 'pago' ? 'status-pago' : 'status-pendente'; ?>">
                                        <?php echo $certificado['status'] == 'pago' ? 'Emitido' : 'Pendente'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($certificado['status'] == 'pago'): ?>
                        <div class="certificado-actions">
                            <a href="certificado.php?codigo=<?php echo urlencode($certificado['codigo_autenticacao']); ?>" 
                               target="_blank" class="btn btn-primary">
                                <i class="fas fa-download"></i> Baixar Certificado
                            </a>
                            <button onclick="compartilharCertificado()" class="btn btn-secondary">
                                <i class="fas fa-share-alt"></i> Compartilhar
                            </button>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div>
                                <strong>Certificado Pendente</strong>
                                <p>Este certificado está pendente de pagamento. Entre em contato com o suporte para mais informações.</p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Recursos Section -->
    <section class="section recursos-section">
        <div class="section-header">
            <h2>Garantia de <span class="highlight">Autenticidade</span></h2>
            <p>Nosso sistema oferece múltiplas camadas de segurança</p>
        </div>
        
        <div class="grid grid-4">
            <div class="card recurso-card">
                <div class="card-icon">
                    <i class="fas fa-shield-check"></i>
                </div>
                <h4>Verificação Segura</h4>
                <p>Sistema criptografado que garante a autenticidade de cada certificado emitido</p>
            </div>
            
            <div class="card recurso-card">
                <div class="card-icon">
                    <i class="fas fa-database"></i>
                </div>
                <h4>Registro Permanente</h4>
                <p>Todos os certificados são registrados em nossa base de dados segura com backup</p>
            </div>
            
            <div class="card recurso-card">
                <div class="card-icon">
                    <i class="fas fa-qrcode"></i>
                </div>
                <h4>Código QR Único</h4>
                <p>Cada certificado possui um código QR exclusivo para verificação rápida</p>
            </div>
            
            <div class="card recurso-card">
                <div class="card-icon">
                    <i class="fas fa-history"></i>
                </div>
                <h4>Histórico Completo</h4>
                <p>Registro de todas as verificações realizadas para cada certificado</p>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="cta-container">
            <div class="cta-content">
                <h2>Precisa Emitir um Certificado?</h2>
                <p>Conclua seus cursos e obtenha certificados reconhecidos pelo mercado</p>
                <div class="cta-buttons">
                    <?php if (isset($_SESSION['usuario_id'])): ?>
                        <a href="cursos.php" class="btn btn-primary">
                            <i class="fas fa-book"></i> Ver Cursos
                        </a>
                        <a href="painel_aluno/certificados.php" class="btn btn-outline">
                            <i class="fas fa-certificate"></i> Meus Certificados
                        </a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Criar Conta
                        </a>
                        <a href="cursos.php" class="btn btn-outline">
                            <i class="fas fa-book"></i> Ver Cursos
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-section footer-main">
                <div class="footer-logo">
                    <img src="imagens/logo.jpg" alt="">
                    <div>
                        <h3>Eduka Plus</h3>
                        <span class="logo-sub">Angola</span>
                    </div>
                </div>
                <p>Transformando vidas através da educação online de qualidade</p>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            
            <div class="footer-section">
                <h4>Links Rápidos</h4>
                <a href="index.php"><i class="fas fa-home"></i> Início</a>
                <a href="cursos.php"><i class="fas fa-book"></i> Cursos</a>
                <a href="verificar_certificado.php"><i class="fas fa-certificate"></i> Validar Certificado</a>
                <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
            </div>
            
            <div class="footer-section">
                <h4>Suporte</h4>
                <a href="ajuda.php"><i class="fas fa-question-circle"></i> Ajuda</a>
                <a href="faq.php"><i class="fas fa-comments"></i> FAQ</a>
                <a href="contacto.php"><i class="fas fa-envelope"></i> Contato</a>
                <a href="termos.php"><i class="fas fa-file-contract"></i> Termos</a>
            </div>
            
            <div class="footer-section">
                <h4>Contato</h4>
                <p><i class="fas fa-envelope"></i> certificados@edukaplus.co.ao</p>
                <p><i class="fas fa-phone"></i> +244 923 456 789</p>
                <p><i class="fas fa-map-marker-alt"></i> Luanda, Angola</p>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>© <?php echo date("Y"); ?> Eduka Plus Angola — Todos os direitos reservados.</p>
            <p class="footer-copy">Sistema de verificação de certificados</p>
        </div>
    </footer>

    <!-- Botão WhatsApp Fixo -->
    <a href="https://wa.me/244958922590" target="_blank" class="whatsapp-btn">
        <i class="fab fa-whatsapp"></i>
        <span class="whatsapp-tooltip">Dúvidas sobre certificados?</span>
    </a>

    <script>
    function toggleMenu() {
        document.getElementById('menu').classList.toggle('active');
    }
    
    function compartilharCertificado() {
        const codigo = '<?php echo $certificado ? $certificado['codigo_autenticacao'] : ''; ?>';
        const url = `${window.location.origin}${window.location.pathname}?codigo=${codigo}`;
        
        if (navigator.share) {
            navigator.share({
                title: 'Certificado Eduka Plus - Verificação',
                text: 'Verifique a autenticidade deste certificado',
                url: url
            });
        } else {
            navigator.clipboard.writeText(url).then(() => {
                alert('Link copiado para a área de transferência!');
            });
        }
    }
    
    // Auto-focus no campo de código
    document.addEventListener('DOMContentLoaded', function() {
        const codigoInput = document.getElementById('codigo');
        if (codigoInput && !codigoInput.value) {
            codigoInput.focus();
        }
        
        // Fechar menu ao clicar em um link no mobile
        document.querySelectorAll('#menu a').forEach(link => {
            link.addEventListener('click', () => {
                if(window.innerWidth <= 768) {
                    document.getElementById('menu').classList.remove('active');
                }
            });
        });
        
        // Animar elementos ao rolar
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animated');
                }
            });
        }, observerOptions);
        
        // Observar cards
        document.querySelectorAll('.recurso-card, .verificacao-form-card, .resultado-card').forEach(card => {
            observer.observe(card);
        });
    });
    </script>
</body>
</html>