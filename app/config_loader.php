<?php

function load_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $path = __DIR__ . '/config.php';
    if (!file_exists($path)) {
        http_response_code(500);
        die(
            'Configuração ausente: copie app/config.example.php para app/config.php ' .
            'e preencha os dados do banco de dados.'
        );
    }

    $config = require $path;
    return $config;
}
