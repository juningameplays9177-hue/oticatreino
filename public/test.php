<?php
// Arquivo de teste para verificar se o servidor está funcionando
header('Content-Type: text/html; charset=utf-8');
echo "<h1>Teste de Acesso ao Servidor</h1>";
echo "<p>Se você está vendo esta mensagem, o servidor está funcionando corretamente.</p>";
echo "<p>Data/Hora: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>PHP Version: " . phpversion() . "</p>";
phpinfo();
