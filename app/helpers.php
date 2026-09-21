<?php

/** Escapa texto para saída segura em HTML. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(?string $token): bool
{
    return is_string($token) && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/** Para chamadas via fetch(): valida o header X-CSRF-Token. */
function require_csrf_header(): void
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verify_csrf($token)) {
        json_response(['erro' => 'Sessão inválida, recarregue a página.'], 403);
    }
}

function flash(string $key, ?string $value = null)
{
    if ($value !== null) {
        $_SESSION['flash'][$key] = $value;
        return null;
    }
    $val = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $val;
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function read_json_body(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '{}', true);
    return is_array($data) ? $data : [];
}

const ALLOWED_PHOTO_TYPES = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];

/**
 * Valida e move um upload de foto para uploads/photos, com nome aleatório.
 * Retorna o caminho relativo salvo no banco, ou lança RuntimeException.
 */
function save_uploaded_photo(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Falha no envio do arquivo.');
    }

    $config = load_config();
    if ($file['size'] > $config['max_upload_bytes']) {
        throw new RuntimeException('Arquivo maior que o limite permitido.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset(ALLOWED_PHOTO_TYPES[$mime])) {
        throw new RuntimeException('Formato de imagem não suportado (use JPG, PNG ou WEBP).');
    }

    $extension = ALLOWED_PHOTO_TYPES[$mime];
    $filename = gen_uuid() . '.' . $extension;
    $destDir = __DIR__ . '/../uploads/photos';
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }

    $dest = $destDir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Não foi possível salvar o arquivo.');
    }

    return 'uploads/photos/' . $filename;
}

const PROFILE_TYPE_LABEL = [
    'COUPLE' => 'Casal',
    'SINGLE_WOMAN' => 'Solteira',
    'SINGLE_MAN' => 'Solteiro',
];
