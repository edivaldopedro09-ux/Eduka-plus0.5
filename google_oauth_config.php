<?php
// Configurações do Google OAuth 2.0
// Após obter as credenciais em https://console.cloud.google.com, preencha abaixo:

define('GOOGLE_CLIENT_ID', '811725114546-sehu9p1qs8hdi5i81uui5vjo6efeff12.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');
define('GOOGLE_REDIRECT_URI', 'http://localhost/plantaforma/google_callback.php');

// URL para iniciar o login
define('GOOGLE_AUTH_URL', 'https://accounts.google.com/o/oauth2/auth');
define('GOOGLE_TOKEN_URL', 'https://oauth2.googleapis.com/token');
define('GOOGLE_USER_URL', 'https://openidconnect.googleapis.com/v1/userinfo');

function generateRandomToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

function getGoogleLoginUrl($returnTo = 'register') {
    $state = generateRandomToken();
    $_SESSION['oauth_state'] = $state;
    $_SESSION['oauth_return_to'] = in_array($returnTo, ['login', 'register'], true) ? $returnTo : 'register';

    $params = [
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'state' => $state
    ];

    return GOOGLE_AUTH_URL . '?' . http_build_query($params);
}

function exchangeCodeForToken($code) {
    $params = [
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code',
        'code' => $code
    ];

    $ch = curl_init(GOOGLE_TOKEN_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    curl_setopt($ch, CURLOPT_FAILONERROR, false);
    $response = curl_exec($ch);

    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['error' => $error];
    }

    curl_close($ch);
    return json_decode($response, true);
}

function getGoogleUserInfo($accessToken) {
    $ch = curl_init(GOOGLE_USER_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
    curl_setopt($ch, CURLOPT_FAILONERROR, false);
    $response = curl_exec($ch);

    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['error' => $error];
    }

    curl_close($ch);
    return json_decode($response, true);
}
?>
