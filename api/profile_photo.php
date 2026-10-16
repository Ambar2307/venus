<?php
require_once __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['erro' => 'Método não permitido.'], 405);
}

require_csrf_header();
$user = require_login_api();

$input = read_json_body();
$slot = (string)($input['slot'] ?? '');
$photoId = isset($input['photo_id']) && $input['photo_id'] !== '' ? (string)$input['photo_id'] : null;

try {
    set_profile_photo_slot($user['id'], $slot, $photoId);
} catch (RuntimeException $e) {
    json_response(['erro' => $e->getMessage()], 400);
}

json_response(['ok' => true]);
