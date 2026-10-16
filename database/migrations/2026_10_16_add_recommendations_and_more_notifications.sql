-- Migração: recomendações públicas entre usuários + notificações também para
-- seguir, pedido de amizade e recomendação (antes só existiam para curtida
-- e comentário). Rodar uma vez em bancos criados antes desta data
-- (phpMyAdmin > SQL, dentro do banco do site).

CREATE TABLE IF NOT EXISTS recommendations (
  id CHAR(36) PRIMARY KEY,
  recommender_id CHAR(36) NOT NULL,
  recommended_id CHAR(36) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_recommendation (recommender_id, recommended_id),
  FOREIGN KEY (recommender_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (recommended_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- photo_id vira opcional porque notificações de seguir/pedido de
-- amizade/recomendação não têm foto associada.
ALTER TABLE notifications
  MODIFY COLUMN photo_id CHAR(36) NULL,
  MODIFY COLUMN type ENUM('LIKE','COMMENT','FOLLOW','FRIEND_REQUEST','RECOMMEND') NOT NULL;
