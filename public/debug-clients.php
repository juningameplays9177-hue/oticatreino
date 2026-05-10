<?php
/**
 * Script de debug para verificar se a requisição está chegando
 * Acesse: https://oticahospitaldosoculos.com.br/debug-clients.php
 */

// Logar no arquivo diretamente
$logFile = __DIR__ . '/../storage/logs/laravel.log';
$message = date('Y-m-d H:i:s') . " - DEBUG: Script debug-clients.php acessado\n";
file_put_contents($logFile, $message, FILE_APPEND);

echo json_encode([
    'status' => 'ok',
    'message' => 'Script de debug executado',
    'log_file' => $logFile,
    'log_writable' => is_writable($logFile),
    'timestamp' => date('Y-m-d H:i:s'),
]);
