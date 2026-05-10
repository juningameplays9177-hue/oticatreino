<?php
/**
 * Arquivo de diagnóstico para verificar problemas de 403
 * Acesse: https://oticahospitaldosoculos.com.br/diagnostico.php
 */

header('Content-Type: application/json; charset=utf-8');

$diagnostico = [
    'timestamp' => date('Y-m-d H:i:s'),
    'server' => [
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'N/A',
        'script_filename' => __FILE__,
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'N/A',
    ],
    'permissions' => [
        'file_readable' => is_readable(__FILE__),
        'file_writable' => is_writable(__FILE__),
        'directory_readable' => is_readable(__DIR__),
        'directory_writable' => is_writable(__DIR__),
    ],
    'laravel' => [
        'index_exists' => file_exists(__DIR__ . '/index.php'),
        'index_readable' => is_readable(__DIR__ . '/index.php'),
    ],
    'htaccess' => [
        'exists' => file_exists(__DIR__ . '/.htaccess'),
        'readable' => is_readable(__DIR__ . '/.htaccess'),
    ],
    'status' => 'ok',
    'message' => 'Arquivo de diagnóstico acessível',
];

echo json_encode($diagnostico, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
