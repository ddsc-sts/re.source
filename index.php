<?php
// index.php — Ponto de entrada alternativo na raiz do projeto
// Garante que http://localhost:8080/re.source/ funcione mesmo sem mod_rewrite ativo

// Simplesmente inclui o front controller em public/index.php
require_once __DIR__ . '/public/index.php';
