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

const MAX_PHOTO_DIMENSION = 1600;

/**
 * Redimensiona (se maior que MAX_PHOTO_DIMENSION) e regrava a imagem via GD.
 * Isso também descarta metadados EXIF (incluindo geolocalização), já que o
 * GD nunca copia esses dados ao regravar — importante numa rede social onde
 * uma foto pode revelar sem querer onde a pessoa mora ou está.
 * Retorna true se conseguiu gravar em $destPath; false se a extensão GD não
 * está disponível ou a imagem não pôde ser lida (o chamador deve then usar
 * o arquivo original como está).
 */
function reencode_photo_stripping_metadata(string $srcPath, string $mime, string $destPath): bool
{
    if (!function_exists('imagecreatefromjpeg')) {
        return false;
    }

    $image = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($srcPath),
        'image/png' => @imagecreatefrompng($srcPath),
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($srcPath) : false,
        default => false,
    };
    if (!$image) {
        return false;
    }

    $width = imagesx($image);
    $height = imagesy($image);
    $maxSide = max($width, $height);

    if ($maxSide > MAX_PHOTO_DIMENSION) {
        $scale = MAX_PHOTO_DIMENSION / $maxSide;
        $resized = imagescale($image, (int)round($width * $scale), (int)round($height * $scale));
        if ($resized !== false) {
            imagedestroy($image);
            $image = $resized;
        }
    }

    if ($mime === 'image/png') {
        imagesavealpha($image, true);
    }

    $ok = match ($mime) {
        'image/jpeg' => imagejpeg($image, $destPath, 85),
        'image/png' => imagepng($image, $destPath, 6),
        'image/webp' => function_exists('imagewebp') ? imagewebp($image, $destPath, 85) : false,
        default => false,
    };

    imagedestroy($image);
    return $ok;
}

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

    $reencoded = reencode_photo_stripping_metadata($file['tmp_name'], $mime, $dest);
    if (!$reencoded && !move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Não foi possível salvar o arquivo.');
    }

    return 'uploads/photos/' . $filename;
}

function current_base_url(): string
{
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['SERVER_PORT'] ?? null) === '443';
    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host;
}

/**
 * Envia o e-mail de recuperação de senha via mail() nativo do PHP — funciona
 * out-of-the-box na maioria dos planos cPanel (Locaweb inclusa), sem precisar
 * de credenciais de SMTP externo. Falha silenciosamente (não lança exceção):
 * quem chama nunca deve revelar ao usuário se o envio funcionou ou não.
 */
function send_password_reset_email(string $toEmail, string $displayName, string $resetUrl): void
{
    $subject = 'Redefinir sua senha — Reserva';
    $body = "Olá, {$displayName}.\n\n"
        . "Pediram a redefinição da senha da sua conta no Reserva. Se foi você, "
        . "clique no link abaixo (válido por " . PASSWORD_RESET_TTL_MINUTES . " minutos):\n\n"
        . $resetUrl . "\n\n"
        . "Se você não pediu isso, pode ignorar este e-mail — sua senha continua a mesma.\n";

    $headers = "From: Reserva <no-reply@" . preg_replace('/^www\./', '', $_SERVER['HTTP_HOST'] ?? 'localhost') . ">\r\n"
        . "Content-Type: text/plain; charset=utf-8";

    @mail($toEmail, $subject, $body, $headers);
}

function only_digits(string $value): string
{
    return preg_replace('/\D/', '', $value) ?? '';
}

/**
 * Valida o CPF pelo algoritmo oficial dos dois dígitos verificadores.
 * Recebe o CPF já só com dígitos (ver only_digits()).
 */
function is_valid_cpf(string $cpf): bool
{
    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }

    for ($pos = 9; $pos <= 10; $pos++) {
        $sum = 0;
        for ($i = 0; $i < $pos; $i++) {
            $sum += (int)$cpf[$i] * ($pos + 1 - $i);
        }
        $digit = ($sum * 10) % 11;
        if ($digit === 10) {
            $digit = 0;
        }
        if ($digit !== (int)$cpf[$pos]) {
            return false;
        }
    }

    return true;
}

function is_valid_phone(string $phone): bool
{
    return strlen($phone) >= 10 && strlen($phone) <= 11;
}

const BRAZIL_STATES = [
    'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS',
    'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC',
    'SP', 'SE', 'TO',
];

const PROFILE_TYPE_LABEL = [
    'COUPLE' => 'Casal',
    'SINGLE_WOMAN' => 'Solteira',
    'SINGLE_MAN' => 'Solteiro',
];
