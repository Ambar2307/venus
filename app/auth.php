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
    $displayName = trim((string)($input['display_name'] ?? ''));
    $type = (string)($input['type'] ?? '');
    $interest = trim((string)($input['interest'] ?? ''));
    $description = trim((string)($input['description'] ?? ''));

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

    $pdo = db();

    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        throw new RuntimeException('Já existe uma conta com este e-mail.');
    }

    $userId = gen_uuid();
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO users (id, email, password_hash, birth_date, plan, status)
             VALUES (?, ?, ?, ?, "FREE", "active")'
        );
        $stmt->execute([$userId, $email, $passwordHash, $birthDate]);

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
function attempt_login(string $email, string $password): bool
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || $user['status'] !== 'active') {
        return false;
    }
    if (!password_verify($password, $user['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    return true;
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
