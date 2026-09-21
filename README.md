# Reserva

Rede social de encontros para casais e solteiros(as): feed de fotos com
visibilidade pública ou só-para-amigos, curtidas e comentários limitados no
plano Livre, pedidos de amizade e chat interno exclusivo para assinantes.

Stack: **PHP puro + MySQL**, sem framework e sem dependência de Node/Composer
em produção — feito para rodar em hospedagem compartilhada comum (cPanel),
como a Locaweb.

## Estrutura

```
index.php, login.php, signup.php, logout.php,
perfil.php, amigos.php, chat.php, planos.php   → páginas
admin/moderacao.php                             → aprovar/rejeitar fotos pendentes (só para usuários com is_admin=1)
api/                                            → endpoints JSON usados via fetch() (curtir, comentar, amizade, chat, moderação)
app/                                            → lógica (auth, banco, limites, visibilidade de fotos) — bloqueado por .htaccess
database/schema.sql                             → schema MySQL
assets/                                         → CSS e JS estáticos (sem build step)
uploads/photos/                                 → fotos enviadas pelos usuários (.htaccess impede execução de scripts aqui)
templates/                                      → cabeçalho/rodapé HTML reaproveitados pelas páginas
```

## Regras de negócio já implementadas

- Cadastro com verificação de maioridade (18+) e senha com hash (`password_hash`).
- Feed com fotos aprovadas por moderação (`moderation_status`); toda foto nova
  entra como `PENDING` até ser aprovada manualmente no banco.
- Visibilidade "só amigos": a URL da foto só é enviada ao navegador se o
  `viewer` for o dono ou amigo aceito (`app/friends.php`) — checado sempre no
  servidor, nunca confiando no cliente.
- Limite diário do plano Livre — 3 curtidas e 3 comentários por dia
  (`app/limits.php`, tabela `daily_usage`); plano Exclusivo é ilimitado.
- Só o plano Exclusivo pode marcar fotos como "somente amigos"; no plano
  Livre a visibilidade é sempre forçada para pública.
- Chat interno (`api/messages.php`, `chat.php`) restrito ao plano Exclusivo,
  com atualização por polling (sem WebSocket — compatível com hospedagem
  compartilhada, que normalmente não permite processos persistentes).
- CSRF: todo formulário tem token de sessão; toda chamada `fetch()` envia o
  header `X-CSRF-Token`.
- Upload de foto: valida tipo real do arquivo (`finfo`, não a extensão),
  limita tamanho, salva com nome aleatório; a pasta `uploads/` tem
  `.htaccess` que impede qualquer arquivo enviado de ser executado como
  script, mesmo que alguém envie um `.php` disfarçado de imagem.
- **Painel de moderação** (`admin/moderacao.php`): usuários com
  `is_admin=1` veem um link "Moderação" no menu e podem aprovar ou
  rejeitar cada foto pendente com um clique, com preview da imagem.
  Nenhuma conta é admin por padrão — veja "Promovendo o primeiro
  administrador" abaixo.

## O que falta (próximos passos naturais)

- **Cobrança recorrente** (ex.: Mercado Pago, mais comum no Brasil que
  Stripe): a página `planos.php` já mostra a comparação Livre × Exclusivo,
  falta ligar o botão "Assinar" a um checkout de verdade que, via webhook,
  atualize `users.plan` e `users.plan_valid_until`.
- **Redimensionar/otimizar imagens no upload** (a extensão `gd` do PHP,
  presente na maioria dos planos cPanel, permite isso) para não depender
  do tamanho enviado pelo usuário.
- Nota de segurança: como as fotos ficam em `uploads/photos/` com nome
  aleatório mas publicamente servidas pelo Apache, uma foto "só amigos"
  não aparece pra quem não é amigo pela interface — mas quem descobrir a
  URL exata (nome de arquivo aleatório de 36 caracteres) consegue abrir o
  arquivo direto. Isso é aceitável pra um MVP, mas se o sigilo das fotos
  reservadas for crítico, o passo seguinte é servir esses arquivos por um
  script PHP que reforça a checagem de amizade antes de entregar o arquivo
  (mais lento, mas sem depender só do nome ser difícil de adivinhar).

## Como publicar na Locaweb (hospedagem compartilhada / cPanel)

1. **Banco de dados**: no cPanel, crie um banco MySQL e um usuário com
   acesso total a ele (`Bancos de Dados MySQL®` → `Adicionar Banco de Dados`
   e `Adicionar Usuário`). Importe `database/schema.sql` pelo phpMyAdmin
   (aba "Importar").
2. **Configuração**: copie `app/config.example.php` para `app/config.php` e
   preencha `db_host` (geralmente `localhost`), `db_name`, `db_user`,
   `db_pass` com os dados criados no passo 1, e gere um `session_secret`
   aleatório (`openssl rand -hex 32`, ou qualquer gerador de string longa).
3. **Upload dos arquivos**: envie todo o conteúdo deste projeto (via FTP ou
   o Gerenciador de Arquivos do cPanel) para `public_html` (ou a subpasta
   do domínio/subdomínio onde o site vai rodar).
4. **Permissão de escrita**: garanta que a pasta `uploads/photos/` tenha
   permissão de escrita para o PHP (normalmente `755` já basta em cPanel;
   se der erro de upload, tente `775`).
5. **PHP**: confirme na seção "Selecionar Versão do PHP" do cPanel que está
   em PHP 8.x com as extensões `pdo_mysql`, `mbstring`, `fileinfo` e `gd`
   ativadas (todas vêm habilitadas por padrão nos planos comuns).
6. Acesse o domínio — a página inicial (`index.php`) já é o feed.

Nenhum passo de build é necessário: não há `npm install`, não há
`composer install`, é só enviar os arquivos `.php` e o servidor já entende.

### Promovendo o primeiro administrador

Nenhuma conta nasce administradora. Depois de criar seu próprio perfil pelo
`/signup.php`, promova-o pelo phpMyAdmin (aba "SQL") ou pela linha de comando:

```sql
UPDATE users SET is_admin = 1 WHERE email = 'seu-email@exemplo.com';
```

A partir daí, um link "Moderação" aparece no menu dessa conta, em
`/admin/moderacao.php`, para aprovar ou rejeitar as fotos pendentes.

## Rodando localmente para desenvolver

Requisitos: PHP 8.1+ com `pdo_mysql`, e um MySQL/MariaDB.

```bash
mysql -u root -e "CREATE DATABASE reserva CHARACTER SET utf8mb4;"
mysql -u root reserva < database/schema.sql
cp app/config.example.php app/config.php   # ajuste db_user/db_pass
php -S localhost:8080
```

Acesse `http://localhost:8080`. Crie um perfil em `/signup.php`, faça login,
publique uma foto pelo formulário do feed — ela entra como `PENDING`. Promova
sua conta a admin (`UPDATE users SET is_admin=1 WHERE email='...'`) e aprove
pelo painel em `/admin/moderacao.php` para ela aparecer no feed.

## Histórico

A primeira versão deste projeto foi prototipada em Next.js/Prisma/Postgres
(preservada no histórico do git). Ela foi substituída por esta versão em
PHP puro para poder rodar em hospedagem compartilhada comum, sem exigir um
processo Node.js persistente.
