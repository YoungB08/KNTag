<?php

declare(strict_types=1);

use KNCMS\Http\Routes;
use KNCMS\KNCMS;
use KNCMS\Auth\JWT;
use KNCMS\Database\DB;

if (!function_exists('json_in')) {
    function json_in(): array
    {
        $raw = file_get_contents('php://input');
        if (!$raw) return [];
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
}

if (!function_exists('json_out')) {
    function json_out(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('str_starts_with_polyfill')) {
    function str_starts_with_polyfill(string $haystack, string $needle): bool
    {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

if (!function_exists('get_bearer_token')) {
    function get_bearer_token(): ?string
    {
        $candidates = [];

        // 1) Apache/XAMPP
        if (function_exists('getallheaders')) {
            $h = getallheaders();
            if (is_array($h)) {
                foreach ($h as $k => $v) {
                    $candidates[strtolower((string)$k)] = $v;
                }
            }
        }

        // 2) Nginx/FastCGI style
        foreach ($_SERVER as $k => $v) {
            if (is_string($k) && str_starts_with_polyfill($k, 'HTTP_')) {
                $hk = strtolower(str_replace('_', '-', substr($k, 5)));
                $candidates[$hk] = $v;
            }
        }

        $auth = $candidates['authorization'] ?? null;
        if (is_string($auth) && preg_match('/Bearer\s+(.+)/i', $auth, $m)) {
            return trim($m[1]);
        }

        // 3) debug param
        if (!empty($_GET['token'])) return (string)$_GET['token'];

        return null;
    }
}

if (!function_exists('auth_user_id')) {
    function auth_user_id(): int
    {
        $token = get_bearer_token();
        if (!$token) json_out(['ok' => false], 401);

        $secret = $_ENV['JWT_SECRET'] ?? 'changeme';
        $payload = JWT::decode($token, $secret);

        if (!is_array($payload) || empty($payload['sub'])) {
            json_out(['ok' => false], 401);
        }

        return (int)$payload['sub'];
    }
}

if (!function_exists('ensure_bio')) {
    function ensure_bio(int $userId): array
    {
        $bio = KNCMS::get_row("SELECT * FROM bios WHERE user_id=:uid LIMIT 1", ['uid' => $userId]);
        if (is_array($bio) && !empty($bio)) return $bio;

        $bioId = KNCMS::insert_id(
            "INSERT INTO bios(user_id,bg_style,font_style,bg_custom) VALUES(:uid,'soft','inter','')",
            ['uid' => $userId]
        );

        return [
            'id' => (int)$bioId,
            'user_id' => $userId,
            'bg_style' => 'soft',
            'font_style' => 'inter',
            'bg_custom' => '',
        ];
    }
}

if (!function_exists('default_blocks')) {
    function default_blocks(): array
    {
        return [
            ['type' => 'avatar',   'data' => ['dataUrl' => '']],
            ['type' => 'name',     'data' => ['text' => 'Guest User']],
            ['type' => 'nickname', 'data' => ['text' => '@kn.biocard']],
            ['type' => 'role',     'data' => ['text' => 'Bio & NFC Card Designer']],
            ['type' => 'jobs',     'data' => ['text' => 'NFC Card, Personal Branding, Web Design']],
            ['type' => 'intro',    'data' => ['text' => 'Thiết kế Bio page & thẻ NFC cho creator, freelancer và doanh nghiệp nhỏ.']],
        ];
    }
}

if (!function_exists('validate_blocks')) {
    function validate_blocks($blocks): array
    {
        $errors = [];
        if (!is_array($blocks)) return ['blocks' => ['invalid']];
        if (count($blocks) > 60) return ['blocks' => ['too_many']];

        foreach ($blocks as $i => $b) {
            if (!is_array($b)) {
                $errors["blocks.$i"][] = 'invalid';
                continue;
            }
            $type = (string)($b['type'] ?? '');
            if ($type === '' || strlen($type) > 32) $errors["blocks.$i.type"][] = 'invalid';

            if (!array_key_exists('data', $b) || !is_array($b['data'])) {
                $errors["blocks.$i.data"][] = 'invalid';
            }
        }
        return $errors;
    }
}

if (!function_exists('ensure_core_blocks_payload')) {
    function ensure_core_blocks_payload(array $blocks): array
    {
        if (count($blocks) === 0) return default_blocks();

        $need = ['avatar', 'name', 'nickname', 'role', 'jobs', 'intro'];
        $seen = [];
        foreach ($blocks as $b) {
            $t = (string)($b['type'] ?? '');
            if ($t !== '') $seen[$t] = true;
        }

        $defs = default_blocks();
        $prepend = [];

        foreach ($need as $t) {
            if (!isset($seen[$t])) {
                foreach ($defs as $d) {
                    if ($d['type'] === $t) {
                        $prepend[] = $d;
                        break;
                    }
                }
            }
        }

        return $prepend ? array_merge($prepend, $blocks) : $blocks;
    }
}

if (!function_exists('normalize_bio_settings')) {
    function normalize_bio_settings(array $bioIn, array $bioRow): array
    {
        // accept both snake_case and camelCase from FE
        $bg = (string)($bioIn['bg_style'] ?? $bioIn['bgStyle'] ?? ($bioRow['bg_style'] ?? 'soft'));
        $font = (string)($bioIn['font_style'] ?? $bioIn['fontStyle'] ?? ($bioRow['font_style'] ?? 'inter'));

        $allowBg = ['white', 'soft', 'dark'];
        $allowFont = ['inter', 'poppins', 'dm-sans'];

        if (!in_array($bg, $allowBg, true)) $bg = 'soft';
        if (!in_array($font, $allowFont, true)) $font = 'inter';

        return [$bg, $font];
    }
}

/**
 * GET /api/bio/layout
 */
Routes::get('/api/bio/layout', function () {
    $userId = auth_user_id();
    $bio = ensure_bio($userId);

    $rows = KNCMS::get_list(
        "SELECT type, position, data_json
         FROM bio_blocks
         WHERE bio_id=:bid
         ORDER BY position ASC, id ASC",
        ['bid' => (int)$bio['id']]
    );

    $blocks = array_map(function ($r) {
        return [
            'type' => (string)$r['type'],
            'position' => (int)$r['position'],
            'data' => json_decode((string)$r['data_json'], true) ?: [],
        ];
    }, is_array($rows) ? $rows : []);

    // nếu DB chưa có block => trả core default để FE không bị mất
    if (count($blocks) === 0) {
        $blocks = array_map(function ($b, $idx) {
            $b['position'] = $idx;
            return $b;
        }, default_blocks(), array_keys(default_blocks()));
    }

    json_out([
        'ok' => true,
        'bio' => [
            'bg_style'   => (string)($bio['bg_style'] ?? 'soft'),
            'font_style' => (string)($bio['font_style'] ?? 'inter'),
            'bg_custom'  => (string)($bio['bg_custom'] ?? ''),
        ],
        'blocks' => $blocks,
    ]);
});

/**
 * PUT /api/bio/layout
 */
Routes::put('/api/bio/layout', function () {
    $userId = auth_user_id();
    $bio = ensure_bio($userId);

    $in = json_in();
    $bioIn = is_array($in['bio'] ?? null) ? (array)$in['bio'] : [];
    $blocks = $in['blocks'] ?? [];

    // ---- avatar/bg input compatibility (FE may send avt/bg separately) ----
    $avatarRaw = $bioIn['avatar'] ?? $bioIn['avt'] ?? ($in['avatar'] ?? null) ?? ($in['avt'] ?? null);
    $avatarDataUrl = '';
    if (is_string($avatarRaw)) {
        $avatarDataUrl = trim($avatarRaw);
    } elseif (is_array($avatarRaw)) {
        $avatarDataUrl = trim((string)($avatarRaw['dataUrl'] ?? $avatarRaw['url'] ?? ''));
    }
    if (strlen($avatarDataUrl) > 800000) $avatarDataUrl = ''; // block oversized base64

    // accept bg_custom from multiple keys
    if (!isset($bioIn['bg_custom'])) {
        $bioIn['bg_custom'] = (string)($bioIn['bgCustom'] ?? $bioIn['bg'] ?? $bioIn['background'] ?? ($in['bg'] ?? '') ?? ($in['background'] ?? ''));
    }

    if ($avatarDataUrl !== '') {
        // merge into blocks payload so it gets persisted to bio_blocks
        if (!is_array($blocks)) $blocks = [];
        $found = false;
        foreach ($blocks as $i => $b) {
            if (is_array($b) && (string)($b['type'] ?? '') === 'avatar') {
                if (!isset($blocks[$i]['data']) || !is_array($blocks[$i]['data'])) $blocks[$i]['data'] = [];
                $blocks[$i]['data']['dataUrl'] = $avatarDataUrl;
                $found = true;
                break;
            }
        }
        if (!$found) {
            array_unshift($blocks, ['type' => 'avatar', 'data' => ['dataUrl' => $avatarDataUrl]]);
        }
    }
    // ---- end compatibility ----

    // validate: nếu blocks rỗng -> OK (server tự default)
    $errs = validate_blocks($blocks);
    if ($errs && !(is_array($blocks) && count($blocks) === 0)) {
        json_out(['ok' => false, 'error' => 'VALIDATION_ERROR', 'fields' => $errs], 422);
    }

    [$bg, $font] = normalize_bio_settings($bioIn, $bio);

    // bg_custom
    $bgCustom = (string)($bioIn['bg_custom'] ?? ($bio['bg_custom'] ?? ''));
    if (strlen($bgCustom) > 800000) $bgCustom = ''; // chặn base64 quá to

    $blocks = is_array($blocks) ? $blocks : [];
    $blocks = ensure_core_blocks_payload($blocks);

    $pdo = DB::pdo();
    $debug = (($_ENV['APP_DEBUG'] ?? '') === 'true');

    try {
        $pdo->beginTransaction();

        // update bio settings + bg_custom
        $stmt = $pdo->prepare(
            "UPDATE bios
             SET bg_style=:bg, font_style=:font, bg_custom=:bgc
             WHERE id=:bid AND user_id=:uid"
        );
        $stmt->execute([
            ':bg' => $bg,
            ':font' => $font,
            ':bgc' => $bgCustom,
            ':bid' => (int)$bio['id'],
            ':uid' => $userId,
        ]);

        // reset blocks
        $stmt = $pdo->prepare("DELETE FROM bio_blocks WHERE bio_id=:bid");
        $stmt->execute([':bid' => (int)$bio['id']]);

        // insert blocks
        $stmt = $pdo->prepare(
            "INSERT INTO bio_blocks (bio_id, type, position, data_json)
             VALUES (:bid, :type, :pos, :json)"
        );

        $pos = 0;
        foreach ($blocks as $b) {
            $type = (string)($b['type'] ?? '');
            $data = (array)($b['data'] ?? []);

            if ($type === '' || strlen($type) > 32) continue;

            $json = json_encode($data, JSON_UNESCAPED_UNICODE);
            if ($json === false) $json = '{}';

            $stmt->execute([
                ':bid' => (int)$bio['id'],
                ':type' => $type,
                ':pos' => $pos++,
                ':json' => $json,
            ]);
        }

        $pdo->commit();
        json_out(['ok' => true]);
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();

        $resp = ['ok' => false, 'error' => 'SERVER_ERROR', 'message' => 'Save layout failed'];
        if ($debug) $resp['detail'] = $e->getMessage();

        json_out($resp, 500);
    }
});
