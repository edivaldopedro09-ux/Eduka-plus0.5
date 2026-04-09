<?php
session_start();
require_once("config.php");
require_once("google_oauth_config.php");

$erro = "";
$redirectTo = isset($_SESSION['oauth_return_to']) && $_SESSION['oauth_return_to'] === 'login' ? 'login.php' : 'register.php';

if (isset($_GET['code'])) {
    $code = $_GET['code'];

    if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
        $erro = "❌ Erro de segurança: estado inválido!";
    } else {
        $tokenResponse = exchangeCodeForToken($code);

        if (isset($tokenResponse['access_token'])) {
            $accessToken = $tokenResponse['access_token'];
            $userInfo = getGoogleUserInfo($accessToken);

            if (isset($userInfo['email']) && isset($userInfo['name'])) {
                $email = $userInfo['email'];
                $nome = $userInfo['name'];
                $googleId = $userInfo['sub'] ?? ($userInfo['id'] ?? null);

                if (!$googleId) {
                    $erro = "❌ Não foi possível obter o identificador do Google.";
                } else {
                    $sql = "SELECT id, nome, tipo, google_id FROM usuarios WHERE email = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        $user = $result->fetch_assoc();

                        if (empty($user['google_id'])) {
                            $update = $conn->prepare("UPDATE usuarios SET google_id = ? WHERE id = ?");
                            $update->bind_param("si", $googleId, $user['id']);
                            $update->execute();
                        }

                        session_regenerate_id(true);
                        $_SESSION['usuario_id'] = $user['id'];
                        $_SESSION['usuario_nome'] = $user['nome'];
                        $_SESSION['usuario_tipo'] = $user['tipo'];
                        $_SESSION['login_tipo'] = 'google';

                        if ($user['tipo'] === "admin") {
                            header("Location: painel_admin/dashboard.php");
                        } elseif ($user['tipo'] === "professor") {
                            header("Location: painel_professor/dashboard.php");
                        } else {
                            header("Location: painel_aluno/dashboard.php");
                        }
                        exit();
                    }

                    $senhaAleatoria = bin2hex(random_bytes(16));
                    $hash = password_hash($senhaAleatoria, PASSWORD_DEFAULT);
                    $tipo = "aluno";

                    $insert = $conn->prepare("INSERT INTO usuarios (nome, email, senha, tipo, google_id) VALUES (?, ?, ?, ?, ?)");
                    $insert->bind_param("sssss", $nome, $email, $hash, $tipo, $googleId);

                    if ($insert->execute()) {
                        session_regenerate_id(true);
                        $novoId = $insert->insert_id;
                        $_SESSION['usuario_id'] = $novoId;
                        $_SESSION['usuario_nome'] = $nome;
                        $_SESSION['usuario_tipo'] = $tipo;
                        $_SESSION['login_tipo'] = 'google';
                        header("Location: painel_aluno/dashboard.php");
                        exit();
                    }

                    $erro = "❌ Erro ao criar a conta. Tente novamente.";
                }
            } else {
                $erro = "❌ Não foi possível obter os dados do Google.";
            }
        } else {
            $erro = "❌ Erro ao autenticar com o Google: " . ($tokenResponse['error'] ?? 'Desconhecido');
        }
    }
} elseif (isset($_GET['error'])) {
    $erro = "❌ Erro do Google: " . htmlspecialchars($_GET['error']);
}

if (!empty($erro)) {
    $_SESSION['google_error'] = $erro;
    unset($_SESSION['oauth_state'], $_SESSION['oauth_return_to']);
    header("Location: {$redirectTo}");
    exit();
}
?>
