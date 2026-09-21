<?php
/**
 * Cria 5 perfis fictícios de teste (uso local/QA — NUNCA rodar em produção).
 *
 * Usa os mesmos dados/validações de app/auth.php::register_user(), então
 * qualquer regra de negócio nova (CPF, telefone, cidade/UF etc.) é aplicada
 * automaticamente aqui também. As fotos NÃO são as imagens enviadas pelo
 * usuário no pedido original — eram previews com marca d'água da Shutterstock,
 * sem licença para uso fora do próprio site da Shutterstock — em vez disso,
 * este script gera um avatar simples (iniciais sobre fundo colorido) por
 * perfil, só para o feed ter algo pra exibir durante os testes.
 *
 * Uso:
 *   cp app/config.example.php app/config.php   # se ainda não configurado
 *   php database/seed_test_profiles.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/config_loader.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/auth.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die("Este script só pode ser rodado via linha de comando (CLI).\n");
}

/** Gera um avatar placeholder simples (iniciais sobre fundo colorido). */
function gerar_avatar_placeholder(string $iniciais, string $corHex): string
{
    $tamanho = 600;
    $img = imagecreatetruecolor($tamanho, $tamanho);

    [$r, $g, $b] = sscanf($corHex, '#%02x%02x%02x');
    $fundo = imagecolorallocate($img, $r, $g, $b);
    imagefill($img, 0, 0, $fundo);

    $branco = imagecolorallocate($img, 255, 255, 255);
    $fonte = 5;
    $largura = imagefontwidth($fonte) * strlen($iniciais) * 6;
    $altura = imagefontheight($fonte) * 6;
    imagestring($img, $fonte, (int)(($tamanho - $largura) / 2), (int)(($tamanho - $altura) / 2), $iniciais, $branco);

    $destDir = __DIR__ . '/../uploads/photos';
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }
    $filename = gen_uuid() . '.png';
    imagepng($img, $destDir . '/' . $filename, 6);
    imagedestroy($img);

    return 'uploads/photos/' . $filename;
}

$perfis = [
    [
        'display_name' => 'Casal Aurora',
        'type' => 'COUPLE',
        'interest' => 'Casais e mulheres solteiras',
        'description' => 'Perfil fictício de teste — casal, gosta de jantares e viagens.',
        'birth_date' => '1990-04-12',
        'email' => 'casal.aurora@example.com',
        'password' => 'TesteAurora#1',
        'phone' => '11987650001',
        'cpf' => '11144477735',
        'city' => 'São Paulo',
        'state' => 'SP',
        'avatar_iniciais' => 'CA',
        'avatar_cor' => '#8a3f52',
    ],
    [
        'display_name' => 'Enzo Cardoso',
        'type' => 'SINGLE_MAN',
        'interest' => 'Casais e mulheres solteiras',
        'description' => 'Perfil fictício de teste — homem solteiro, gosta de vinhos e música ao vivo.',
        'birth_date' => '1988-09-30',
        'email' => 'enzo.cardoso@example.com',
        'password' => 'TesteEnzo#2',
        'phone' => '21987650002',
        'cpf' => '22255588846',
        'city' => 'Rio de Janeiro',
        'state' => 'RJ',
        'avatar_iniciais' => 'EC',
        'avatar_cor' => '#3f5a8a',
    ],
    [
        'display_name' => 'Bia Duarte',
        'type' => 'SINGLE_WOMAN',
        'interest' => 'Casais e homens solteiros',
        'description' => 'Perfil fictício de teste — mulher solteira, adora praia e trilhas.',
        'birth_date' => '1995-01-18',
        'email' => 'bia.duarte@example.com',
        'password' => 'TesteBia#3',
        'phone' => '31987650003',
        'cpf' => '33366699957',
        'city' => 'Belo Horizonte',
        'state' => 'MG',
        'avatar_iniciais' => 'BD',
        'avatar_cor' => '#3f8a5e',
    ],
    [
        'display_name' => 'Nina Salgado',
        'type' => 'SINGLE_WOMAN',
        'interest' => 'Casais',
        'description' => 'Perfil fictício de teste — mulher solteira, gosta de arte e boa gastronomia.',
        'birth_date' => '1992-07-05',
        'email' => 'nina.salgado@example.com',
        'password' => 'TesteNina#4',
        'phone' => '41987650004',
        'cpf' => '44477711107',
        'city' => 'Curitiba',
        'state' => 'PR',
        'avatar_iniciais' => 'NS',
        'avatar_cor' => '#8a6a3f',
    ],
    [
        'display_name' => 'Lara Ventura',
        'type' => 'SINGLE_WOMAN',
        'interest' => 'Casais e mulheres solteiras',
        'description' => 'Perfil fictício de teste — mulher solteira, apaixonada por fotografia.',
        'birth_date' => '1993-11-22',
        'email' => 'lara.ventura@example.com',
        'password' => 'TesteLara#5',
        'phone' => '51987650005',
        'cpf' => '55588822200',
        'city' => 'Porto Alegre',
        'state' => 'RS',
        'avatar_iniciais' => 'LV',
        'avatar_cor' => '#6a3f8a',
    ],
];

$pdo = db();
$criados = [];

foreach ($perfis as $perfil) {
    try {
        $userId = register_user([
            'email' => $perfil['email'],
            'password' => $perfil['password'],
            'birth_date' => $perfil['birth_date'],
            'phone' => $perfil['phone'],
            'cpf' => $perfil['cpf'],
            'city' => $perfil['city'],
            'state' => $perfil['state'],
            'display_name' => $perfil['display_name'],
            'type' => $perfil['type'],
            'interest' => $perfil['interest'],
            'description' => $perfil['description'],
            'terms_accepted' => true,
        ]);
    } catch (RuntimeException $e) {
        echo "Pulando \"{$perfil['display_name']}\": {$e->getMessage()}\n";
        continue;
    }

    $filePath = gerar_avatar_placeholder($perfil['avatar_iniciais'], $perfil['avatar_cor']);

    $stmt = $pdo->prepare(
        "INSERT INTO photos (id, user_id, file_path, caption, visibility, moderation_status)
         VALUES (?, ?, ?, ?, 'PUBLIC', 'APPROVED')"
    );
    $stmt->execute([gen_uuid(), $userId, $filePath, 'Foto de teste (avatar placeholder)']);

    $criados[] = $perfil;
    echo "Criado: {$perfil['display_name']} <{$perfil['email']}> (senha: {$perfil['password']})\n";
}

echo "\n" . count($criados) . " perfil(is) de teste criado(s).\n";
