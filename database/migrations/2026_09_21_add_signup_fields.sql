-- Migração: adiciona telefone, CPF (validação real de idade/identidade) e
-- cidade/UF ao cadastro. Rodar uma vez em bancos criados antes desta data
-- (phpMyAdmin > Importar, ou `mysql -u usuario -p nome_do_banco < este_arquivo.sql`).
--
-- phone, cpf e city/state são dados sigilosos: nunca exibidos em nenhuma
-- página pública nem no perfil de outros usuários — servem só para
-- verificação interna de identidade/idade (ver app/auth.php::register_user).
--
-- ATENÇÃO: como cpf e phone não têm valor válido conhecido para contas já
-- existentes, esta migração só funciona num banco sem linhas em `users`
-- (ex.: ambiente novo/de testes). Num banco de produção com contas reais,
-- adicione as colunas como NULL primeiro, faça a coleta retroativa dos
-- dados e só depois aplique NOT NULL + UNIQUE.

ALTER TABLE users
  ADD COLUMN phone VARCHAR(20) NOT NULL AFTER birth_date,
  ADD COLUMN cpf CHAR(11) NOT NULL AFTER phone,
  ADD COLUMN city VARCHAR(100) NOT NULL AFTER cpf,
  ADD COLUMN state CHAR(2) NOT NULL AFTER city,
  ADD UNIQUE KEY uniq_users_cpf (cpf);
