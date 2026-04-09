<?php
session_start();
require_once("google_oauth_config.php");

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico - Google OAuth</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #333; margin-bottom: 30px; }
        .check { 
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .check.ok { 
            background: #d4edda;
            border-left: 4px solid #28a745;
            color: #155724;
        }
        .check.error { 
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            color: #721c24;
        }
        .check.warning { 
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            color: #856404;
        }
        .check-icon { font-size: 24px; }
        .check-content { flex: 1; }
        .check-content h3 { margin-bottom: 5px; font-size: 14px; }
        .check-content p { margin: 0; font-size: 12px; opacity: 0.8; }
        code { 
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
            font-size: 12px;
        }
        .action {
            margin-top: 30px;
            padding: 20px;
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            border-radius: 5px;
        }
        .action h2 { color: #1565c0; margin-bottom: 10px; }
        .action ol { margin-left: 20px; }
        .action li { margin: 8px 0; }
        .copy-code {
            background: #f4f4f4;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            font-family: monospace;
            font-size: 12px;
            word-break: break-all;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnóstico - Google OAuth</h1>

        <?php
        $checks = [];

        // 1. Verificar Client ID
        if (defined('GOOGLE_CLIENT_ID') && !empty(GOOGLE_CLIENT_ID) && GOOGLE_CLIENT_ID !== 'SEU_CLIENT_ID_AQUI.apps.googleusercontent.com') {
            $checks[] = ['status' => 'ok', 'icon' => '✓', 'title' => 'Client ID configurado', 'message' => 'Client ID: ' . substr(GOOGLE_CLIENT_ID, 0, 20) . '...'];
        } else {
            $checks[] = ['status' => 'error', 'icon' => '✗', 'title' => 'Client ID NÃO configurado', 'message' => 'Você precisa adicionar o Client ID em google_oauth_config.php'];
        }

        // 2. Verificar Client Secret
        if (defined('GOOGLE_CLIENT_SECRET') && !empty(GOOGLE_CLIENT_SECRET) && GOOGLE_CLIENT_SECRET !== 'SEU_CLIENT_SECRET_AQUI') {
            $checks[] = ['status' => 'ok', 'icon' => '✓', 'title' => 'Client Secret configurado', 'message' => 'Client Secret: ' . substr(GOOGLE_CLIENT_SECRET, 0, 10) . '...'];
        } else {
            $checks[] = ['status' => 'error', 'icon' => '✗', 'title' => 'Client Secret NÃO configurado', 'message' => 'Você precisa adicionar o Client Secret em google_oauth_config.php'];
        }

        // 3. Verificar Redirect URI
        $redirectUri = GOOGLE_REDIRECT_URI;
        $checks[] = ['status' => 'ok', 'icon' => 'ℹ', 'title' => 'Redirect URI configurado', 'message' => 'URI: ' . $redirectUri];

        // 4. Verificar se curl está ativado
        if (extension_loaded('curl')) {
            $checks[] = ['status' => 'ok', 'icon' => '✓', 'title' => 'Extensão CURL ativada', 'message' => 'PHP conseguirá fazer requisições ao Google'];
        } else {
            $checks[] = ['status' => 'error', 'icon' => '✗', 'title' => 'Extensão CURL NÃO ativada', 'message' => 'Se curl não estiver ativada, o login não funcionará'];
        }

        // 5. Verificar se arquivo callback existe
        if (file_exists('google_callback.php')) {
            $checks[] = ['status' => 'ok', 'icon' => '✓', 'title' => 'Arquivo google_callback.php existe', 'message' => 'Arquivo encontrado e pronto'];
        } else {
            $checks[] = ['status' => 'error', 'icon' => '✗', 'title' => 'Arquivo google_callback.php ausente', 'message' => 'Este arquivo é necessário para receber o callback do Google'];
        }

        // 6. Verificar se config.php existe
        if (file_exists('config.php')) {
            $checks[] = ['status' => 'ok', 'icon' => '✓', 'title' => 'Arquivo config.php existe', 'message' => 'Banco de dados conectado'];
        } else {
            $checks[] = ['status' => 'error', 'icon' => '✗', 'title' => 'Arquivo config.php ausente', 'message' => 'Arquivo de configuração do banco de dados não encontrado'];
        }

        // 7. Verificar HTTPS em produção
        if (strpos($redirectUri, 'https://localhost') === false && strpos($redirectUri, 'https://127.0.0.1') === false && strpos($redirectUri, 'http://localhost') === false && strpos($redirectUri, 'http://127.0.0.1') === false) {
            if (strpos($redirectUri, 'https://') === false) {
                $checks[] = ['status' => 'warning', 'icon' => '⚠', 'title' => 'HTTPS recomendado para produção', 'message' => 'Em produção, use sempre HTTPS: ' . str_replace('http://', 'https://', $redirectUri)];
            }
        }

        // Exibir checks
        foreach ($checks as $check) {
            echo "<div class='check {$check['status']}'>";
            echo "<div class='check-icon'>{$check['icon']}</div>";
            echo "<div class='check-content'>";
            echo "<h3>{$check['title']}</h3>";
            echo "<p>{$check['message']}</p>";
            echo "</div></div>";
        }
        ?>

        <div class="action">
            <h2>📋 Como Corrigir o Erro "redirect_uri_mismatch"</h2>
            <ol>
                <li>Acesse <a href="https://console.cloud.google.com/" target="_blank">Google Developers Console</a></li>
                <li>Selecione seu projeto "Eduka Plus"</li>
                <li>Vá para <strong>Credenciais</strong> no menu esquerdo</li>
                <li>Clique no projeto OAuth que está usando (aquele com ID que começa com números)</li>
                <li>Em "<strong>URIs de redirecionamento autorizados</strong>", procure por campos já existentes</li>
                <li>Se não houver exatamente este URI, clique em <strong>+ ADICIONAR URI</strong>:
                    <div class="copy-code"><?php echo htmlspecialchars('http://localhost/plantaforma/google_callback.php'); ?></div>
                </li>
                <li>Clique em <strong>SALVAR</strong></li>
                <li>Aguarde 1-2 minutos (às vezes o Google demora para atualizar)</li>
                <li>Tente fazer login com Google novamente</li>
            </ol>
        </div>

        <div class="action">
            <h2>🔑 Verificar suas Credenciais</h2>
            <p>Copie o Client ID e Client Secret <strong>EXATAMENTE COMO APARECEM</strong> no Google Console (não adicione espaços):</p>
            <p><strong>Seu Client ID atual:</strong></p>
            <div class="copy-code"><?php echo htmlspecialchars(GOOGLE_CLIENT_ID); ?></div>
            <p><strong>Seu Redirect URI atual:</strong></p>
            <div class="copy-code"><?php echo htmlspecialchars(GOOGLE_REDIRECT_URI); ?></div>
            <p style="margin-top: 15px; color: #d32f2f;"><strong>⚠️ Importante:</strong> Se copiou errado, volte ao arquivo <code>google_oauth_config.php</code> e corrija.</p>
        </div>

        <div style="margin-top: 40px; padding: 20px; background: #f9f9f9; border-radius: 5px; text-align: center;">
            <p style="margin-bottom: 10px;">Após fazer as correções acima:</p>
            <a href="register.php" style="display: inline-block; background: #4CAF50; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: bold;">← Voltar para Cadastro</a>
        </div>
    </div>
</body>
</html>
