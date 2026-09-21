<?php
require_once __DIR__ . '/app/bootstrap.php';

$photoId = (string)($_GET['id'] ?? '');
if ($photoId === '') {
    http_response_code(404);
    exit;
}

$stmt = db()->prepare('SELECT * FROM photos WHERE id = ?');
$stmt->execute([$photoId]);
$foto = $stmt->fetch();

if (!$foto) {
    http_response_code(404);
    exit;
}

$viewer = current_user();
$viewerId = $viewer['id'] ?? null;

$allowed = false;
if ($viewer && is_admin($viewer)) {
    $allowed = true;
} elseif ($viewerId !== null && $viewerId === $foto['user_id']) {
    $allowed = true;
} elseif ($foto['moderation_status'] === 'APPROVED') {
    $allowed = pode_ver_foto($viewerId, $foto);
}

if (!$allowed) {
    http_response_code(403);
    exit;
}

$path = __DIR__ . '/' . $foto['file_path'];
if (!is_file($path)) {
    http_response_code(404);
    exit;
}

$mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
    'jpg', 'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'webp' => 'image/webp',
    default => 'application/octet-stream',
};

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string)filesize($path));
// Foto pública: pode ficar em cache de borda/navegador. Reservada ou do
// próprio dono: só o navegador de quem pediu pode guardar, e por pouco tempo.
header($foto['visibility'] === 'PUBLIC' && $foto['moderation_status'] === 'APPROVED'
    ? 'Cache-Control: public, max-age=86400'
    : 'Cache-Control: private, max-age=60');
readfile($path);
