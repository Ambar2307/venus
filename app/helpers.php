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
    $subject = 'Redefinir sua senha — Clube do Swing';
    $body = "Olá, {$displayName}.\n\n"
        . "Pediram a redefinição da senha da sua conta no Clube do Swing. Se foi você, "
        . "clique no link abaixo (válido por " . PASSWORD_RESET_TTL_MINUTES . " minutos):\n\n"
        . $resetUrl . "\n\n"
        . "Se você não pediu isso, pode ignorar este e-mail — sua senha continua a mesma.\n";

    $headers = "From: Clube do Swing <no-reply@" . preg_replace('/^www\./', '', $_SERVER['HTTP_HOST'] ?? 'localhost') . ">\r\n"
        . "Content-Type: text/plain; charset=utf-8";

    @mail($toEmail, $subject, $body, $headers);
}

function only_digits(string $value): string
{
    return preg_replace('/\D/', '', $value) ?? '';
}

/**
 * Mascara qualquer trecho de texto que pareça um número de telefone
 * (8 a 13 dígitos seguidos, com ou sem parênteses/traço/espaço entre eles),
 * trocando cada dígito por "*". Usado pra impedir troca de contato nos
 * comentários das fotos — é uma heurística, não detecção perfeita, então
 * prefere errar mascarando demais a deixar passar um telefone de verdade.
 */
function mask_phone_numbers(string $text): string
{
    return preg_replace_callback(
        '/(?:\(?\d{2,3}\)?[\s.-]*)?\d{4,5}[\s.-]*\d{4}/',
        function (array $m): string {
            $digitos = only_digits($m[0]);
            if (strlen($digitos) < 8 || strlen($digitos) > 13) {
                return $m[0];
            }
            return preg_replace('/\d/', '*', $m[0]);
        },
        $text
    ) ?? $text;
}

/** Tempo decorrido desde $datetime em formato curto ("agora", "12 min", "3 h", "5 d", ou a data). */
function tempo_relativo(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60) {
        return 'agora';
    }
    if ($diff < 3600) {
        return (int)floor($diff / 60) . ' min';
    }
    if ($diff < 86400) {
        return (int)floor($diff / 3600) . ' h';
    }
    if ($diff < 2592000) {
        return (int)floor($diff / 86400) . ' d';
    }
    return date('d/m/Y', strtotime($datetime));
}

/** Gera as iniciais (até 2 letras) de um nome, para o círculo de avatar. */
function initials(string $name): string
{
    $parts = array_filter(preg_split('/\s+/', trim($name)) ?: []);
    if (!$parts) {
        return '?';
    }
    $first = mb_substr((string)reset($parts), 0, 1);
    $last = count($parts) > 1 ? mb_substr((string)end($parts), 0, 1) : '';
    return mb_strtoupper($first . $last);
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

const INTEREST_OPTIONS = [
    'Homem',
    'Mulher',
    'Casal (H e M)',
    'Casal (H e H)',
    'Casal (M e M)',
    'Travesti',
    'Mulher Trans',
    'Homem Trans',
];

/** Municípios do Brasil por UF (27 estados, 5.570 cidades) — carregado uma vez por request. */
function brazil_cities(): array
{
    static $cidades = null;
    if ($cidades === null) {
        $cidades = require __DIR__ . '/data/cidades_brasil.php';
    }
    return $cidades;
}

/** Lista de cidades de uma UF, ou lista vazia se a UF não existir. */
function cities_for_state(string $uf): array
{
    return brazil_cities()[strtoupper($uf)] ?? [];
}

/** Ícones de linha simples (24x24, herdam a cor do texto) usados no menu lateral. */
function nav_icon(string $nome): string
{
    $paths = [
        'home' => '<path d="M4 11.5 12 4l8 7.5"/><path d="M6 10v9h12v-9"/><path d="M10 19v-5h4v5"/>',
        'search' => '<circle cx="11" cy="11" r="6.5"/><path d="m20 20-4.3-4.3"/>',
        'users' => '<circle cx="9" cy="8.5" r="3"/><path d="M3.5 19c0-3 2.5-5 5.5-5s5.5 2 5.5 5"/><circle cx="17" cy="9.5" r="2.3"/><path d="M15.2 14.2c2.4.3 4.3 2.1 4.3 4.8"/>',
        'chat' => '<path d="M4 5h16v11H9l-5 4V5Z"/>',
        'bell' => '<path d="M6 10a6 6 0 0 1 12 0v5l1.5 3h-15L6 15Z"/><path d="M10 21a2 2 0 0 0 4 0"/>',
        'user' => '<circle cx="12" cy="8.5" r="3.5"/><path d="M5 20c0-3.6 3.1-6.5 7-6.5s7 2.9 7 6.5"/>',
        'gem' => '<path d="m4 8 3.5-4h9L20 8l-8 11.5L4 8Z"/><path d="M4 8h16"/><path d="m9 4 3 4-3 11.5"/><path d="m15 4-3 4 3 11.5"/>',
        'shield' => '<path d="M12 3.5 19 6v6c0 4.5-3 7.5-7 8.5-4-1-7-4-7-8.5V6l7-2.5Z"/>',
        'logout' => '<path d="M15 4H6v16h9"/><path d="M10 12h10"/><path d="m17 8 4 4-4 4"/>',
        'login' => '<path d="M9 4h9v16H9"/><path d="M14 12H4"/><path d="m7 8-4 4 4 4"/>',
        'user-plus' => '<circle cx="9" cy="8.5" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6.5 6.5-6.5s6.5 2.9 6.5 6.5"/><path d="M18 8v6"/><path d="M15 11h6"/>',
        'plus' => '<circle cx="12" cy="12" r="9.5"/><path d="M12 8v8"/><path d="M8 12h8"/>',
        'photo' => '<rect x="3.5" y="5" width="17" height="14" rx="1.5"/><circle cx="8.5" cy="10" r="1.5"/><path d="m5 17 4.5-4.5 3 3L17 11l2.5 2.5"/>',
        'star' => '<path d="m12 3 2.6 5.9 6.4.6-4.8 4.3 1.4 6.3L12 16.9 6.4 20.1l1.4-6.3-4.8-4.3 6.4-.6L12 3Z"/>',
        'eye' => '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="2.8"/>',
    ];
    $path = $paths[$nome] ?? '';
    return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
        . 'stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}
