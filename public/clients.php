<?php
/**
 * Arquivo de redirecionamento para resolver problema de 403 no LiteSpeed
 * Este arquivo redireciona /clients.php para a rota correta do Laravel
 */

// Redirecionar para a rota correta do Laravel
header('Location: /clients', true, 301);
exit;
