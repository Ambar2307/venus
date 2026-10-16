<?php

function is_following(string $followerId, string $followedId): bool
{
    $stmt = db()->prepare('SELECT id FROM follows WHERE follower_id = ? AND followed_id = ?');
    $stmt->execute([$followerId, $followedId]);
    return (bool)$stmt->fetch();
}

/** Alterna seguir/deixar de seguir. Retorna o novo estado (true = passou a seguir). */
function toggle_follow(string $followerId, string $followedId): bool
{
    if (is_following($followerId, $followedId)) {
        $stmt = db()->prepare('DELETE FROM follows WHERE follower_id = ? AND followed_id = ?');
        $stmt->execute([$followerId, $followedId]);
        return false;
    }

    $stmt = db()->prepare('INSERT INTO follows (id, follower_id, followed_id) VALUES (?, ?, ?)');
    $stmt->execute([gen_uuid(), $followerId, $followedId]);
    return true;
}

/** Quantas pessoas seguem $userId. */
function count_followers(string $userId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM follows WHERE followed_id = ?');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

/** Quantas pessoas $userId segue. */
function count_following(string $userId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM follows WHERE follower_id = ?');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

/** Quantas visitas (visitantes únicos) o perfil de $userId já recebeu. */
function count_profile_visits(string $userId): int
{
    $stmt = db()->prepare('SELECT COUNT(DISTINCT visitor_id) FROM profile_visits WHERE visited_id = ?');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

/** Lista quem segue $userId (meus seguidores). */
function list_followers(string $userId): array
{
    $stmt = db()->prepare(
        "SELECT u.id, p.display_name, p.type, f.created_at
         FROM follows f
         JOIN users u ON u.id = f.follower_id
         JOIN profiles p ON p.user_id = u.id
         WHERE f.followed_id = ?
         ORDER BY f.created_at DESC"
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/** Lista quem $userId segue. */
function list_following(string $userId): array
{
    $stmt = db()->prepare(
        "SELECT u.id, p.display_name, p.type, f.created_at
         FROM follows f
         JOIN users u ON u.id = f.followed_id
         JOIN profiles p ON p.user_id = u.id
         WHERE f.follower_id = ?
         ORDER BY f.created_at DESC"
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/** Registra uma visita ao perfil (nunca para o próprio dono visitando a si mesmo). */
function log_profile_visit(string $visitorId, string $visitedId): void
{
    if ($visitorId === $visitedId) {
        return;
    }
    $stmt = db()->prepare(
        'INSERT INTO profile_visits (id, visitor_id, visited_id) VALUES (?, ?, ?)'
    );
    $stmt->execute([gen_uuid(), $visitorId, $visitedId]);
}

/** Últimos visitantes do perfil de $userId (mais recente por visitante). */
function list_recent_visitors(string $userId, int $limit = 30): array
{
    $stmt = db()->prepare(
        "SELECT u.id, p.display_name, p.type, MAX(pv.visited_at) AS last_visit
         FROM profile_visits pv
         JOIN users u ON u.id = pv.visitor_id
         JOIN profiles p ON p.user_id = u.id
         WHERE pv.visited_id = ?
         GROUP BY u.id, p.display_name, p.type
         ORDER BY last_visit DESC
         LIMIT " . (int)$limit
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/** True se $recommenderId já recomendou publicamente $recommendedId. */
function is_recommended(string $recommenderId, string $recommendedId): bool
{
    $stmt = db()->prepare('SELECT id FROM recommendations WHERE recommender_id = ? AND recommended_id = ?');
    $stmt->execute([$recommenderId, $recommendedId]);
    return (bool)$stmt->fetch();
}

/** Alterna recomendar/remover recomendação. Retorna o novo estado (true = passou a recomendar). */
function toggle_recommendation(string $recommenderId, string $recommendedId): bool
{
    if (is_recommended($recommenderId, $recommendedId)) {
        $stmt = db()->prepare('DELETE FROM recommendations WHERE recommender_id = ? AND recommended_id = ?');
        $stmt->execute([$recommenderId, $recommendedId]);
        return false;
    }

    $stmt = db()->prepare('INSERT INTO recommendations (id, recommender_id, recommended_id) VALUES (?, ?, ?)');
    $stmt->execute([gen_uuid(), $recommenderId, $recommendedId]);
    return true;
}

/** Quantas pessoas recomendam publicamente $userId — vira referência no perfil. */
function count_recommendations(string $userId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM recommendations WHERE recommended_id = ?');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

/** Lista quem recomendou $userId (mais recente primeiro). */
function list_recommenders(string $userId): array
{
    $stmt = db()->prepare(
        "SELECT u.id, p.display_name, p.type, r.created_at
         FROM recommendations r
         JOIN users u ON u.id = r.recommender_id
         JOIN profiles p ON p.user_id = u.id
         WHERE r.recommended_id = ?
         ORDER BY r.created_at DESC"
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/**
 * Perfis recomendados pra $userId: prioriza mesma cidade, depois mais
 * recentes, excluindo o próprio usuário, amigos já aceitos e quem já segue.
 */
function list_recommended_profiles(array $user, int $limit = 12): array
{
    $stmt = db()->prepare(
        "SELECT u.id, u.city, u.state, p.display_name, p.type, p.interest
         FROM users u
         JOIN profiles p ON p.user_id = u.id
         WHERE u.id != ?
           AND u.status = 'active'
           AND NOT EXISTS (
             SELECT 1 FROM follows f WHERE f.follower_id = ? AND f.followed_id = u.id
           )
           AND NOT EXISTS (
             SELECT 1 FROM friend_requests fr
             WHERE fr.status = 'ACCEPTED'
               AND ((fr.from_id = ? AND fr.to_id = u.id) OR (fr.from_id = u.id AND fr.to_id = ?))
           )
         ORDER BY (u.city = ?) DESC, u.created_at DESC
         LIMIT " . (int)$limit
    );
    $stmt->execute([$user['id'], $user['id'], $user['id'], $user['id'], $user['city']]);
    return $stmt->fetchAll();
}
