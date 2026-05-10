<?php
/**
 * Teste simples para verificar se o Laravel está funcionando
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== INICIANDO TESTE ===\n";

// Teste 1: Verificar se o autoloader existe
echo "1. Verificando autoloader...\n";
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    echo "   ✅ Autoloader encontrado\n";
    require $autoloadPath;
} else {
    echo "   ❌ Autoloader NÃO encontrado em: $autoloadPath\n";
    exit(1);
}

// Teste 2: Verificar se o bootstrap existe
echo "2. Verificando bootstrap...\n";
$bootstrapPath = __DIR__ . '/../bootstrap/app.php';
if (file_exists($bootstrapPath)) {
    echo "   ✅ Bootstrap encontrado\n";
} else {
    echo "   ❌ Bootstrap NÃO encontrado em: $bootstrapPath\n";
    exit(1);
}

// Teste 3: Tentar carregar o Laravel
echo "3. Carregando Laravel...\n";
try {
    $app = require_once $bootstrapPath;
    echo "   ✅ Laravel carregado\n";
} catch (\Throwable $e) {
    echo "   ❌ Erro ao carregar Laravel: " . $e->getMessage() . "\n";
    echo "   Arquivo: " . $e->getFile() . "\n";
    echo "   Linha: " . $e->getLine() . "\n";
    exit(1);
}

// Teste 4: Verificar se consegue escrever no log
echo "4. Testando escrita de log...\n";
try {
    $logPath = __DIR__ . '/../storage/logs/laravel.log';
    $testMessage = date('Y-m-d H:i:s') . " - TESTE MANUAL\n";
    if (file_put_contents($logPath, $testMessage, FILE_APPEND)) {
        echo "   ✅ Log escrito com sucesso diretamente\n";
    } else {
        echo "   ❌ Erro ao escrever log diretamente\n";
    }
    
    // Tentar usar o sistema de logs do Laravel
    \Illuminate\Support\Facades\Log::info('TESTE: Laravel está funcionando!', [
        'timestamp' => date('Y-m-d H:i:s'),
        'test' => true,
    ]);
    echo "   ✅ Log escrito via Laravel\n";
} catch (\Throwable $e) {
    echo "   ❌ Erro ao escrever log: " . $e->getMessage() . "\n";
    echo "   Arquivo: " . $e->getFile() . "\n";
    echo "   Linha: " . $e->getLine() . "\n";
}

echo "\n=== TESTE CONCLUÍDO ===\n";
echo "Verifique o arquivo storage/logs/laravel.log\n";
