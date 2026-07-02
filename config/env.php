<?php

/** Carrega um arquivo .env sem sobrescrever variaveis do servidor. */
function loadEnv(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (str_starts_with($line, 'export ')) {
            $line = trim(substr($line, 7));
        }

        if (!str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = array_map('trim', explode('=', $line, 2));
        if ($name === '' || !preg_match('/^[A-Z_][A-Z0-9_]*$/i', $name)) {
            continue;
        }

        if (getenv($name) !== false || array_key_exists($name, $_ENV)) {
            continue;
        }

        $first = $value[0] ?? '';
        $last = $value !== '' ? $value[strlen($value) - 1] : '';
        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
            $value = substr($value, 1, -1);
            if ($first === '"') {
                $value = stripcslashes($value);
            }
        } else {
            $value = preg_replace('/\s+#.*$/', '', $value) ?? $value;
        }

        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
        putenv($name . '=' . $value);
    }
}

/** Retorna uma variavel de ambiente convertendo escalares comuns. */
function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return match (strtolower((string) $value)) {
        'true', '(true)' => true,
        'false', '(false)' => false,
        'null', '(null)' => null,
        'empty', '(empty)' => '',
        default => $value,
    };
}
