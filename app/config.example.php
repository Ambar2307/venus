<?php
// Copie este arquivo para app/config.php e preencha com os dados do seu banco
// MySQL (painel da Locaweb: cPanel > Bancos de Dados MySQL).

return [
    'db_host' => 'localhost',
    'db_name' => 'reserva',
    'db_user' => 'usuario_do_banco',
    'db_pass' => 'senha_do_banco',

    // Gere uma string aleatória longa (ex.: `openssl rand -hex 32`) e não reutilize
    // entre ambientes. Usada para assinar o cookie de sessão.
    'session_secret' => 'troque-por-uma-string-aleatoria-longa',

    // Tamanho máximo de upload de foto, em bytes (padrão: 5 MB).
    'max_upload_bytes' => 5 * 1024 * 1024,
];
