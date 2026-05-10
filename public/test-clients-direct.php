<?php
/**
 * Teste direto para verificar se o problema é do servidor ou do Laravel
 * Acesse: https://oticahospitaldosoculos.com.br/test-clients-direct.php
 */

header('Content-Type: application/json; charset=utf-8');

$result = [
    'status' => 'ok',
    'message' => 'Arquivo PHP acessível diretamente',
    'timestamp' => date('Y-m-d H:i:s'),
    'server' => [
        'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'N/A',
        'script_name' => __FILE__,
    ],
    'test' => [
        'file_exists_index' => file_exists(__DIR__ . '/index.php'),
        'file_exists_clients_php' => file_exists(__DIR__ . '/clients.php'),
        'is_dir_clients' => is_dir(__DIR__ . '/clients'),
        'is_dir_clients_exists' => file_exists(__DIR__ . '/clients'),
    ],
    'next_step' => 'Se este arquivo funciona, o problema está no Laravel ou nas rotas',
];

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
