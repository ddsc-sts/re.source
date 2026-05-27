<?php
session_start();
$pendente = $_SESSION['cadastro_pendente'] ?? null;

if (!$pendente) {
    echo "Sessão vazia — nenhum cadastro pendente";
} else {
    echo "Sessão OK!<br>";
    echo "E-mail: " . $pendente['email'] . "<br>";
    echo "Código: " . $pendente['codigo'] . "<br>";
    echo "Expira em: " . date('H:i:s', $pendente['expires_at']);
}