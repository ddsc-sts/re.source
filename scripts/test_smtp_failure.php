<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);

require dirname(__DIR__) . '/config/env.php';
foreach (['MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_FROM_ADDRESS'] as $key) {
    $_ENV[$key] = '';
    $_SERVER[$key] = '';
    putenv($key . '=');
}
require dirname(__DIR__) . '/config/mailer.php';

$startedAt = microtime(true);
$result = enviarEmailFluxo(
    'destino-inexistente@example.invalid',
    'Teste',
    'Teste de tolerancia SMTP',
    'SMTP indisponivel',
    'Este e-mail nao deve ser enviado.'
);
$elapsed = microtime(true) - $startedAt;

if ($result !== false || $elapsed > 2) {
    fwrite(STDERR, "[FALHA] O mailer nao retornou false imediatamente sem configuracao.\n");
    exit(1);
}

fwrite(STDOUT, sprintf("[OK] Falha SMTP tratada sem excecao em %.3f s.\n", $elapsed));
