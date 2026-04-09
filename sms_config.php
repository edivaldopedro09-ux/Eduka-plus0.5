<?php
// Configurações de envio de SMS via Twilio
// Preencha com suas credenciais Twilio e número de envio em formato E.164

define('TWILIO_ACCOUNT_SID', 'SEU_ACCOUNT_SID_AQUI');
define('TWILIO_AUTH_TOKEN', 'SEU_AUTH_TOKEN_AQUI');
define('TWILIO_FROM_NUMBER', '+244929057372');

define('SMS_PROVIDER', 'twilio');

function sendSms($to, $message) {
    if (!extension_loaded('curl')) {
        return ['success' => false, 'error' => 'A extensão CURL não está ativada no PHP.'];
    }

    if (SMS_PROVIDER !== 'twilio') {
        return ['success' => false, 'error' => 'Provedor de SMS configurado não suportado.'];
    }

    if (empty(TWILIO_ACCOUNT_SID) || empty(TWILIO_AUTH_TOKEN) || empty(TWILIO_FROM_NUMBER)) {
        return ['success' => false, 'error' => 'Credenciais Twilio não configuradas.'];
    }

    $url = sprintf('https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json', TWILIO_ACCOUNT_SID);
    $data = http_build_query([
        'From' => TWILIO_FROM_NUMBER,
        'To' => $to,
        'Body' => $message,
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_USERPWD, TWILIO_ACCOUNT_SID . ':' . TWILIO_AUTH_TOKEN);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['success' => false, 'error' => 'Erro CURL: ' . $curlError];
    }

    $result = json_decode($response, true);
    if ($httpCode >= 200 && $httpCode < 300) {
        return ['success' => true, 'data' => $result];
    }

    $errorMessage = $result['message'] ?? ($result['error_message'] ?? 'Erro desconhecido na API Twilio.');
    return ['success' => false, 'error' => $errorMessage];
}
?>