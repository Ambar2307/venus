-- Migração: seguidores, visitas de perfil e notificações de curtida/comentário.
-- Rodar uma vez em bancos criados antes desta data
-- (phpMyAdmin > Importar, ou `mysql -u usuario -p nome_do_banco < este_arquivo.sql`).

CREATE TABLE IF NOT EXISTS follows (
  id CHAR(36) PRIMARY KEY,
  follower_id CHAR(36) NOT NULL,
  followed_id CHAR(36) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_follow (follower_id, followed_id),
  FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (followed_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS profile_visits (
  id CHAR(36) PRIMARY KEY,
  visitor_id CHAR(36) NOT NULL,
  visited_id CHAR(36) NOT NULL,
  visited_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (visitor_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (visited_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_profile_visits_visited (visited_id, visited_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notifications (
  id CHAR(36) PRIMARY KEY,
  user_id CHAR(36) NOT NULL,
  actor_id CHAR(36) NOT NULL,
  type ENUM('LIKE','COMMENT') NOT NULL,
  photo_id CHAR(36) NOT NULL,
  comment_snippet VARCHAR(140) NULL,
  read_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (photo_id) REFERENCES photos(id) ON DELETE CASCADE,
  INDEX idx_notifications_user (user_id, read_at, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
