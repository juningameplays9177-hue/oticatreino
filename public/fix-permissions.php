<?php
/**
 * Script para verificar e corrigir permissões do Laravel
 * Execute via SSH ou acesse: https://oticahospitaldosoculos.com.br/fix-permissions.php
 */

header('Content-Type: text/plain; charset=utf-8');

$basePath = dirname(__DIR__);
$results = [];

// Verificar e criar diretórios necessários
$directories = [
    'storage/logs',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'bootstrap/cache',
];

foreach ($directories as $dir) {
    $fullPath = $basePath . '/' . $dir;
    
    if (!is_dir($fullPath)) {
        if (mkdir($fullPath, 0755, true)) {
            $results[] = "✅ Criado: $dir";
        } else {
            $results[] = "❌ Erro ao criar: $dir";
        }
    } else {
        $results[] = "✅ Existe: $dir";
    }
    
    // Verificar permissões
    if (is_dir($fullPath)) {
        $perms = substr(sprintf('%o', fileperms($fullPath)), -4);
        $writable = is_writable($fullPath);
        $readable = is_readable($fullPath);
        
        $results[] = "   Permissões: $perms | Writable: " . ($writable ? 'SIM' : 'NÃO') . " | Readable: " . ($readable ? 'SIM' : 'NÃO');
        
        // Tentar corrigir permissões
        if (!$writable) {
            if (chmod($fullPath, 0755)) {
                $results[] = "   ✅ Permissões corrigidas para 0755";
            } else {
                $results[] = "   ❌ Erro ao corrigir permissões";
            }
        }
    }
}

// Verificar arquivo de log
$logFile = $basePath . '/storage/logs/laravel.log';
if (!file_exists($logFile)) {
    if (touch($logFile)) {
        chmod($logFile, 0644);
        $results[] = "✅ Arquivo laravel.log criado";
    } else {
        $results[] = "❌ Erro ao criar laravel.log";
    }
} else {
    $results[] = "✅ Arquivo laravel.log existe";
    $writable = is_writable($logFile);
    $results[] = "   Writable: " . ($writable ? 'SIM' : 'NÃO');
    if (!$writable) {
        if (chmod($logFile, 0644)) {
            $results[] = "   ✅ Permissões corrigidas para 0644";
        }
    }
}

// Verificar .env
$envFile = $basePath . '/.env';
if (file_exists($envFile)) {
    $results[] = "✅ Arquivo .env existe";
} else {
    $results[] = "⚠️ Arquivo .env NÃO existe - isso pode causar problemas!";
}

echo "=== VERIFICAÇÃO DE PERMISSÕES ===\n\n";
echo implode("\n", $results);
echo "\n\n=== FIM ===\n";
