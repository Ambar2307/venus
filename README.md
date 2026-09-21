# Reserva — starter real (Next.js + Prisma + Postgres)

Este é o começo funcional da plataforma: cadastro, login, feed com visibilidade
pública/amigos, curtidas e comentários com limite diário do plano Livre, pedidos
de amizade e chat interno restrito ao plano Exclusivo.

O que **não** está pronto ainda (próximos passos naturais):
- Upload real de imagem (hoje `POST /api/photos` espera uma `url` já hospedada — falta
  ligar num serviço de storage como Cloudflare R2/S3 e um passo de moderação automática).
- Fila de moderação de conteúdo.
- Cobrança recorrente (Stripe/Mercado Pago) alterando `user.plan` via webhook.
- Telas de Amigos, Chat e Planos no frontend (as rotas de API já existem e funcionam;
  falta a interface — dá pra reaproveitar o HTML/CSS do protótipo visual).
- Chat em tempo real (hoje as mensagens funcionam por request/response comum, sem WebSocket).

## Como rodar localmente

1. **Instalar dependências**
   ```bash
   npm install
   ```

2. **Banco de dados**: crie um Postgres (mais rápido: conta grátis no
   [Supabase](https://supabase.com) ou [Neon](https://neon.tech)) e copie a
   *connection string*.

3. **Variáveis de ambiente**
   ```bash
   cp .env.example .env
   ```
   Preencha `DATABASE_URL` com a connection string do passo 2, e gere um valor
   para `NEXTAUTH_SECRET` (ex.: `openssl rand -base64 32`).

4. **Criar as tabelas no banco**
   ```bash
   npx prisma migrate dev --name init
   ```

5. **Rodar o servidor**
   ```bash
   npm run dev
   ```
   Acesse `http://localhost:3000`.

6. **Ver os dados** (opcional, interface visual do banco):
   ```bash
   npx prisma studio
   ```

## Testando o fluxo manualmente

1. Vá em `/signup` e crie um perfil.
2. Faça login em `/login`.
3. Publique uma foto direto pela API (por enquanto não há tela pra isso):
   ```bash
   curl -X POST http://localhost:3000/api/photos \
     -H "Content-Type: application/json" \
     -H "Cookie: <copie o cookie de sessão do navegador>" \
     -d '{"url":"https://exemplo.com/foto.jpg","caption":"Teste","visibility":"PUBLIC"}'
   ```
4. Ela entra como `PENDING` — aprove manualmente no `prisma studio` (mude
   `moderationStatus` para `APPROVED`) pra ela aparecer no feed em `/`.

## Onde continuar

Este starter cobre a parte de regra de negócio que mais importa (limites diários,
visibilidade de foto por amizade, chat travado por plano). O próximo passo natural é
recriar a interface visual do protótipo (feed, perfil, amigos, chat, planos) puxando
dados dessas rotas de API — e ligar upload de imagem e cobrança de verdade.

Para continuar esse desenvolvimento com testes reais (instalar pacotes, subir banco,
rodar o app, iterar rápido), o ideal é abrir esta pasta no **Claude Code**, que consegue
executar comandos e testar o app de ponta a ponta — algo que este ambiente de chat não
consegue fazer por não ter acesso à internet.
