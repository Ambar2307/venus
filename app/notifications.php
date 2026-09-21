<?php

/** Cria uma notificação de curtida/comentário. Nunca notifica a si mesmo. */
function create_notification(
    string $recipientId,
    string $actorId,
    string $type,
    string $photoId,
    ?string $commentSnippet = null
): void {
    if ($recipientId === $actorId) {
        return;
    }
    $stmt = db()->prepare(
        'INSERT INTO notifications (id, user_id, actor_id, type, photo_id, comment_snippet)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([gen_uuid(), $recipientId, $actorId, $type, $photoId, $commentSnippet]);
}

function count_unread_notifications(string $userId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function list_notifications(string $userId, int $limit = 30): array
{
    $stmt = db()->prepare(
        "SELECT n.*, p.display_name AS actor_name, ph.file_path AS photo_file_path
         FROM notifications n
         JOIN profiles p ON p.user_id = n.actor_id
         JOIN photos ph ON ph.id = n.photo_id
         WHERE n.user_id = ?
         ORDER BY n.created_at DESC
         LIMIT " . (int)$limit
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function mark_all_notifications_read(string $userId): void
{
    $stmt = db()->prepare('UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL');
    $stmt->execute([$userId]);
}
