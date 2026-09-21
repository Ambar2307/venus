<?php

function idade_minima_18(string $birthDate): bool
{
    try {
        $nascimento = new DateTime($birthDate);
    } catch (Exception $e) {
        return false;
    }
    $hoje = new DateTime();
    return $nascimento->diff($hoje)->y >= 18;
}

/**
 * Cria um novo usuário (plano Livre) com perfil associado.
 * Retorna o id do usuário criado, ou lança RuntimeException com a mensagem de erro.
 */
function register_user(array $input): string
{
    $email = trim((string)($input['email'] ?? ''));
    $password = (string)($input['password'] ?? '');
    $birthDate = (string)($input['birth_date'] ?? '');
    $phone = only_digits((string)($input['phone'] ?? ''));
    $cpf = only_digits((string)($input['cpf'] ?? ''));
    $city = trim((string)($input['city'] ?? ''));
    $state = strtoupper(trim((string)($input['state'] ?? '')));
    $displayName = trim((string)($input['display_name'] ?? ''));
    $type = (string)($input['type'] ?? '');
    $interest = trim((string)($input['interest'] ?? ''));
    $description = trim((string)($input['description'] ?? ''));
    $termsAccepted = !empty($input['terms_accepted']);

    if (!$termsAccepted) {
        throw new RuntimeException('É preciso aceitar os Termos de Uso e a Política de Privacidade.');
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Informe um e-mail válido.');
    }
    if (strlen($password) < 8) {
        throw new RuntimeException('A senha precisa ter pelo menos 8 caracteres.');
    }
    if ($displayName === '') {
        throw new RuntimeException('Informe um nome fictício.');
    }
    if (!in_array($type, ['COUPLE', 'SINGLE_WOMAN', 'SINGLE_MAN'], true)) {
        throw new RuntimeException('Tipo de perfil inválido.');
    }
    if ($birthDate === '' || !idade_minima_18($birthDate)) {
        throw new RuntimeException('É necessário ter 18 anos ou mais para se cadastrar.');
    }
    if (!is_valid_phone($phone)) {
        throw new RuntimeException('Informe um telefone válido, com DDD.');
    }
    if (!is_valid_cpf($cpf)) {
        throw new RuntimeException('Informe um CPF válido — ele é usado só para confirmar sua idade e não é exibido no seu perfil.');
    }
    if ($city === '') {
        throw new RuntimeException('Informe sua cidade.');
    }
    if (!in_array($state, BRAZIL_STATES, true)) {
        throw new RuntimeException('Selecione um estado (UF) válido.');
    }

    $pdo = db();

    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        throw new RuntimeException('Já existe uma conta com este e-mail.');
    }

    $stmt = $pdo->prepare('SELECT id FROM users WHERE cpf = ?');
    $stmt->execute([$cpf]);
    if ($stmt->fetch()) {
        throw new RuntimeException('Já existe uma conta cadastrada com este CPF.');
    }

    $userId = gen_uuid();
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO users (id, email, password_hash, birth_date, phone, cpf, city, state, plan, status, terms_accepted_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, "FREE", "active", NOW())'
        );
        $stmt->execute([$userId, $email, $passwordHash, $birthDate, $phone, $cpf, $city, $state]);

        $stmt = $pdo->prepare(
            'INSERT INTO profiles (id, user_id, display_name, type, interest, description)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([gen_uuid(), $userId, $displayName, $type, $interest, $description]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw new RuntimeException('Não foi possível criar o perfil. Tente novamente.');
    }

    return $userId;
}

/** Tenta autenticar; em sucesso inicia a sessão e retorna true. */
const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_LOCKOUT_MINUTES = 15;

function attempt_login(string $email, string $password): bool
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || $user['status'] !== 'active') {
        return false;
    }

    if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
        return false;
    }

    if (!password_verify($password, $user['password_hash'])) {
        register_failed_login($user['id'], (int)$user['failed_login_count']);
        return false;
    }

    if ($user['failed_login_count'] > 0 || $user['locked_until']) {
        $pdo->prepare('UPDATE users SET failed_login_count = 0, locked_until = NULL WHERE id = ?')
            ->execute([$user['id']]);
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    return true;
}

function register_failed_login(string $userId, int $currentCount): void
{
    $newCount = $currentCount + 1;
    $pdo = db();

    if ($newCount >= LOGIN_MAX_ATTEMPTS) {
        $lockedUntil = date('Y-m-d H:i:s', time() + LOGIN_LOCKOUT_MINUTES * 60);
        $pdo->prepare('UPDATE users SET failed_login_count = 0, locked_until = ? WHERE id = ?')
            ->execute([$lockedUntil, $userId]);
        return;
    }

    $pdo->prepare('UPDATE users SET failed_login_count = ? WHERE id = ?')
        ->execute([$newCount, $userId]);
}

function logout_user(): void
{
    $_SESSION = [];
    session_destroy();
}

/** Usuário logado (com perfil), ou null. Resultado cacheado por request. */
function current_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }

    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        $user = null;
        return null;
    }

    $stmt = db()->prepare(
        'SELECT u.*, p.id AS profile_id, p.display_name, p.type, p.interest, p.description
         FROM users u LEFT JOIN profiles p ON p.user_id = u.id
         WHERE u.id = ?'
    );
    $stmt->execute([$userId]);
    $row = $stmt->fetch();

    if (!$row || $row['status'] !== 'active') {
        $user = null;
        return null;
    }

    $user = $row;
    return $user;
}

/** Para páginas: redireciona ao login se não autenticado. */
function require_login_page(): array
{
    $user = current_user();
    if (!$user) {
        redirect('/login.php');
    }
    return $user;
}

/** Para endpoints em api/: responde 401 em JSON se não autenticado. */
function require_login_api(): array
{
    $user = current_user();
    if (!$user) {
        json_response(['erro' => 'Não autenticado.'], 401);
    }
    return $user;
}

function is_exclusive(array $user): bool
{
    if ($user['plan'] !== 'EXCLUSIVE') {
        return false;
    }
    if (!$user['plan_valid_until']) {
        return false;
    }
    return strtotime($user['plan_valid_until']) > time();
}

/** Para endpoints em api/: responde 403 em JSON se não for plano Exclusivo. */
function require_exclusive_api(): array
{
    $user = require_login_api();
    if (!is_exclusive($user)) {
        json_response(['erro' => 'Recurso exclusivo do plano pago.', 'upgrade' => true], 403);
    }
    return $user;
}

function is_admin(array $user): bool
{
    return (bool)($user['is_admin'] ?? false);
}

/** Para páginas em admin/: redireciona ao feed se não for administrador. */
function require_admin_page(): array
{
    $user = require_login_page();
    if (!is_admin($user)) {
        redirect('/index.php');
    }
    return $user;
}

/** Para endpoints em api/: responde 403 em JSON se não for administrador. */
function require_admin_api(): array
{
    $user = require_login_api();
    if (!is_admin($user)) {
        json_response(['erro' => 'Restrito a administradores.'], 403);
    }
    return $user;
}

const PASSWORD_RESET_TTL_MINUTES = 60;

/**
 * Se o e-mail existir, gera um token de recuperação e envia por e-mail.
 * Sempre silencioso (sem lançar erro) se o e-mail não existir — quem chama
 * deve mostrar a mesma mensagem genérica nos dois casos, pra não revelar
 * quais e-mails têm conta cadastrada.
 */
function request_password_reset(string $email): void
{
    $stmt = db()->prepare(
        'SELECT u.id, u.email, p.display_name FROM users u
         JOIN profiles p ON p.user_id = u.id
         WHERE u.email = ? AND u.status = "active"'
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user) {
        return;
    }

    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiresAt = date('Y-m-d H:i:s', time() + PASSWORD_RESET_TTL_MINUTES * 60);

    $stmt = db()->prepare(
        'INSERT INTO password_resets (id, user_id, token_hash, expires_at) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([gen_uuid(), $user['id'], $tokenHash, $expiresAt]);

    $resetUrl = current_base_url() . '/redefinir-senha.php?token=' . $token;
    send_password_reset_email($user['email'], $user['display_name'], $resetUrl);
}

/** Retorna a linha de password_resets + o usuário associado, se o token for válido. */
function validate_reset_token(string $token): ?array
{
    if ($token === '') {
        return null;
    }
    $tokenHash = hash('sha256', $token);

    $stmt = db()->prepare(
        'SELECT pr.*, u.email FROM password_resets pr
         JOIN users u ON u.id = pr.user_id
         WHERE pr.token_hash = ? AND pr.used_at IS NULL AND pr.expires_at > NOW()'
    );
    $stmt->execute([$tokenHash]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/** Define a nova senha e invalida todos os tokens pendentes desse usuário. */
function consume_reset_token(string $resetId, string $userId, string $newPassword): void
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);
        $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL')
            ->execute([$userId]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw new RuntimeException('Não foi possível redefinir a senha. Tente novamente.');
    }
}
