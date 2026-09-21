<?php
require_once __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['erro' => 'Método não permitido.'], 405);
}

require_csrf_header();
$user = require_login_api();

$input = read_json_body();
$photoId = (string)($input['photo_id'] ?? '');
$text = trim((string)($input['text'] ?? ''));

if ($photoId === '' || $text === '') {
    json_response(['erro' => 'Comentário vazio.'], 400);
}
if (mb_strlen($text) > 500) {
    json_response(['erro' => 'Comentário muito longo (máx. 500 caracteres).'], 400);
}

$checagem = pode_comentar($user['id'], $user['plan']);
if (!$checagem['permitido']) {
    json_response(['erro' => $checagem['motivo'], 'upgrade' => true], 403);
}

$stmt = db()->prepare('INSERT INTO comments (id, user_id, photo_id, body) VALUES (?, ?, ?, ?)');
$stmt->execute([gen_uuid(), $user['id'], $photoId, $text]);
registrar_comentario($user['id']);

json_response([
    'ok' => true,
    'autor' => $user['display_name'],
    'texto' => $text,
]);
