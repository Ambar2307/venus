<?php
require_once __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['erro' => 'Método não permitido.'], 405);
}

require_csrf_header();
$user = require_login_api();

$input = read_json_body();
$photoId = (string)($input['photo_id'] ?? '');
$reason = trim((string)($input['reason'] ?? ''));

if ($photoId === '' || $reason === '') {
    json_response(['erro' => 'Conte, em poucas palavras, o motivo da denúncia.'], 400);
}
if (mb_strlen($reason) > 500) {
    json_response(['erro' => 'Motivo muito longo (máx. 500 caracteres).'], 400);
}

$stmt = db()->prepare('SELECT id, user_id FROM photos WHERE id = ?');
$stmt->execute([$photoId]);
$foto = $stmt->fetch();

if (!$foto) {
    json_response(['erro' => 'Foto não encontrada.'], 404);
}
if ($foto['user_id'] === $user['id']) {
    json_response(['erro' => 'Você não pode denunciar sua própria foto.'], 400);
}

$stmt = db()->prepare(
    'INSERT INTO reports (id, reporter_id, photo_id, reported_user_id, reason)
     VALUES (?, ?, ?, ?, ?)'
);
$stmt->execute([gen_uuid(), $user['id'], $photoId, $foto['user_id'], $reason]);

json_response(['ok' => true]);
