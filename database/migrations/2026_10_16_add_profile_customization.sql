-- Migração: avatar e fotos de capa escolhidas pelo usuário, e controle de
-- quando o nome de exibição foi alterado pela última vez (1x por ano).
-- Rodar uma vez em bancos criados antes desta data
-- (phpMyAdmin > SQL, cole e execute dentro do banco do site).

ALTER TABLE profiles
  ADD COLUMN avatar_photo_id CHAR(36) NULL AFTER description,
  ADD COLUMN cover_photo_1_id CHAR(36) NULL AFTER avatar_photo_id,
  ADD COLUMN cover_photo_2_id CHAR(36) NULL AFTER cover_photo_1_id,
  ADD COLUMN display_name_changed_at DATETIME NULL AFTER display_name,
  ADD CONSTRAINT fk_profiles_avatar_photo FOREIGN KEY (avatar_photo_id) REFERENCES photos(id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_profiles_cover_photo_1 FOREIGN KEY (cover_photo_1_id) REFERENCES photos(id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_profiles_cover_photo_2 FOREIGN KEY (cover_photo_2_id) REFERENCES photos(id) ON DELETE SET NULL;
