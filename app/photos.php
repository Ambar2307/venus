<?php

/**
 * Cria uma foto nova para o usuário. Entra como PENDING até moderação aprovar.
 * Só o plano Exclusivo pode marcar como "somente amigos" — no Livre, força PUBLIC.
 * Lança RuntimeException com mensagem amigável em caso de erro.
 */
function create_photo(array $user, array $file, string $caption, string $visibility): void
{
    $filePath = save_uploaded_photo($file);

    $visibilidadeFinal = ($visibility === 'FRIENDS' && is_exclusive($user)) ? 'FRIENDS' : 'PUBLIC';

    $stmt = db()->prepare(
        'INSERT INTO photos (id, user_id, file_path, caption, visibility, moderation_status)
         VALUES (?, ?, ?, ?, ?, "PENDING")'
    );
    $stmt->execute([gen_uuid(), $user['id'], $filePath, $caption !== '' ? $caption : null, $visibilidadeFinal]);
}

/**
 * Exclui uma foto do usuário (registro + arquivo no disco). Lança
 * RuntimeException se a foto não existir ou não pertencer a ele.
 */
function delete_photo(string $userId, string $photoId): void
{
    $stmt = db()->prepare('SELECT file_path FROM photos WHERE id = ? AND user_id = ?');
    $stmt->execute([$photoId, $userId]);
    $foto = $stmt->fetch();

    if (!$foto) {
        throw new RuntimeException('Foto não encontrada.');
    }

    $stmt = db()->prepare('DELETE FROM photos WHERE id = ?');
    $stmt->execute([$photoId]);

    $abs = __DIR__ . '/../' . $foto['file_path'];
    if (is_file($abs)) {
        @unlink($abs);
    }
}

/** Exclui um comentário (uso administrativo — a permissão é checada por quem chama). */
function delete_comment(string $commentId): void
{
    $stmt = db()->prepare('DELETE FROM comments WHERE id = ?');
    $stmt->execute([$commentId]);
    if ($stmt->rowCount() === 0) {
        throw new RuntimeException('Comentário não encontrado.');
    }
}

/** Todas as fotos (qualquer status de moderação) do próprio usuário, mais recentes primeiro. */
function fetch_own_photos(string $userId): array
{
    $stmt = db()->prepare(
        'SELECT * FROM photos WHERE user_id = ? ORDER BY created_at DESC'
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/** Busca o feed (fotos aprovadas) já resolvendo visibilidade, curtidas e comentários. */
function fetch_feed(?string $viewerId, int $limit = 30): array
{
    $stmt = db()->prepare(
        "SELECT p.*, pr.display_name, pr.type
         FROM photos p
         JOIN profiles pr ON pr.user_id = p.user_id
         WHERE p.moderation_status = 'APPROVED'
         ORDER BY p.created_at DESC
         LIMIT " . (int)$limit
    );
    $stmt->execute();
    $fotos = $stmt->fetchAll();

    if (!$fotos) {
        return [];
    }

    $ids = array_column($fotos, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $likesStmt = db()->prepare("SELECT photo_id, user_id FROM likes WHERE photo_id IN ($placeholders)");
    $likesStmt->execute($ids);
    $likesByPhoto = [];
    foreach ($likesStmt->fetchAll() as $like) {
        $likesByPhoto[$like['photo_id']][] = $like['user_id'];
    }

    $commentsStmt = db()->prepare(
        "SELECT c.id, c.photo_id, c.body, pr.display_name
         FROM comments c
         JOIN profiles pr ON pr.user_id = c.user_id
         WHERE c.photo_id IN ($placeholders)
         ORDER BY c.created_at ASC"
    );
    $commentsStmt->execute($ids);
    $commentsByPhoto = [];
    foreach ($commentsStmt->fetchAll() as $comment) {
        $commentsByPhoto[$comment['photo_id']][] = $comment;
    }

    foreach ($fotos as &$foto) {
        $visivel = pode_ver_foto($viewerId, $foto);
        $foto['visivel'] = $visivel;
        $foto['likes'] = $likesByPhoto[$foto['id']] ?? [];
        $foto['curtido_pelo_viewer'] = $viewerId ? in_array($viewerId, $foto['likes'], true) : false;
        $foto['comentarios'] = $commentsByPhoto[$foto['id']] ?? [];
    }
    unset($foto);

    return $fotos;
}
