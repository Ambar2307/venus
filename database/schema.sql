-- Reserva — schema MySQL/MariaDB (compatível com hospedagem compartilhada cPanel)
-- Importar via phpMyAdmin ou `mysql -u usuario -p nome_do_banco < schema.sql`

SET NAMES utf8mb4;

-- phone, cpf e city/state são dados sigilosos: nunca exibidos em nenhuma
-- página pública nem no perfil de outros usuários (ver signup.php e
-- perfil.php) — servem só para verificação interna de identidade/idade.
CREATE TABLE IF NOT EXISTS users (
  id CHAR(36) PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  birth_date DATE NOT NULL,
  phone VARCHAR(20) NOT NULL,
  cpf CHAR(11) NOT NULL UNIQUE,
  city VARCHAR(100) NOT NULL,
  state CHAR(2) NOT NULL,
  plan ENUM('FREE','EXCLUSIVE') NOT NULL DEFAULT 'FREE',
  plan_valid_until DATETIME NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'active',
  is_admin TINYINT(1) NOT NULL DEFAULT 0,
  terms_accepted_at DATETIME NOT NULL,
  failed_login_count INT NOT NULL DEFAULT 0,
  locked_until DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS profiles (
  id CHAR(36) PRIMARY KEY,
  user_id CHAR(36) NOT NULL UNIQUE,
  display_name VARCHAR(80) NOT NULL,
  type ENUM('COUPLE','SINGLE_WOMAN','SINGLE_MAN') NOT NULL,
  interest VARCHAR(255) NULL,
  description TEXT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS photos (
  id CHAR(36) PRIMARY KEY,
  user_id CHAR(36) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  caption VARCHAR(500) NULL,
  visibility ENUM('PUBLIC','FRIENDS') NOT NULL DEFAULT 'PUBLIC',
  moderation_status ENUM('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_photos_feed (moderation_status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS likes (
  id CHAR(36) PRIMARY KEY,
  user_id CHAR(36) NOT NULL,
  photo_id CHAR(36) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_like (user_id, photo_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (photo_id) REFERENCES photos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS comments (
  id CHAR(36) PRIMARY KEY,
  user_id CHAR(36) NOT NULL,
  photo_id CHAR(36) NOT NULL,
  body VARCHAR(500) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (photo_id) REFERENCES photos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Contador de uso diário: é o que faz valer o limite de 3/dia do plano Livre.
CREATE TABLE IF NOT EXISTS daily_usage (
  user_id CHAR(36) NOT NULL,
  usage_date DATE NOT NULL,
  likes_count INT NOT NULL DEFAULT 0,
  comments_count INT NOT NULL DEFAULT 0,
  PRIMARY KEY (user_id, usage_date),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS friend_requests (
  id CHAR(36) PRIMARY KEY,
  from_id CHAR(36) NOT NULL,
  to_id CHAR(36) NOT NULL,
  status ENUM('PENDING','ACCEPTED','DECLINED') NOT NULL DEFAULT 'PENDING',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_request (from_id, to_id),
  FOREIGN KEY (from_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (to_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- part_a_id é sempre o menor UUID dos dois participantes (normalizado pela aplicação),
-- assim a mesma conversa é encontrada não importa quem inicia.
CREATE TABLE IF NOT EXISTS conversations (
  id CHAR(36) PRIMARY KEY,
  part_a_id CHAR(36) NOT NULL,
  part_b_id CHAR(36) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_conversation (part_a_id, part_b_id),
  FOREIGN KEY (part_a_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (part_b_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Recuperação de senha: token_hash guarda o SHA-256 do token (nunca o token
-- em texto puro), assim um vazamento do banco não permite redefinir senhas.
CREATE TABLE IF NOT EXISTS password_resets (
  id CHAR(36) PRIMARY KEY,
  user_id CHAR(36) NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_password_resets_token (token_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Denúncia de foto: reported_user_id é sempre o dono da foto, preenchido
-- pelo servidor a partir de photos.user_id — nunca confiar nisso vindo do cliente.
CREATE TABLE IF NOT EXISTS reports (
  id CHAR(36) PRIMARY KEY,
  reporter_id CHAR(36) NOT NULL,
  photo_id CHAR(36) NOT NULL,
  reported_user_id CHAR(36) NOT NULL,
  reason VARCHAR(500) NOT NULL,
  status ENUM('PENDING','REVIEWED') NOT NULL DEFAULT 'PENDING',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (photo_id) REFERENCES photos(id) ON DELETE CASCADE,
  FOREIGN KEY (reported_user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_reports_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS messages (
  id CHAR(36) PRIMARY KEY,
  conversation_id CHAR(36) NOT NULL,
  sender_id CHAR(36) NOT NULL,
  body VARCHAR(1000) NOT NULL,
  sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  read_at DATETIME NULL,
  FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_messages_conversation (conversation_id, sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
