<?php
require_once __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['erro' => 'Método não permitido.'], 405);
}

require_csrf_header();
require_admin_api();

$input = read_json_body();
$commentId = (string)($input['comment_id'] ?? '');

if ($commentId === '') {
    json_response(['erro' => 'Comentário inválido.'], 400);
}

try {
    delete_comment($commentId);
} catch (RuntimeException $e) {
    json_response(['erro' => $e->getMessage()], 404);
}

json_response(['ok' => true]);
