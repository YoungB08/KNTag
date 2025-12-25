<?php
declare(strict_types=1);

require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
use KNCMS\KNCMS;
use KNCMS\Http\Routes;
KNCMS::boot(PROJECT_ROOT);
/* ========= WEB ROUTES ========= */
Routes::get('/', function () {
    require PROJECT_ROOT . '/views/dashboard.php';
});

Routes::get('/home', function () {
    require PROJECT_ROOT . '/views/dashboard.php';
});

Routes::get('/auth', function () {
    require PROJECT_ROOT . '/views/auth.php';
});
Routes::get('/Auth', function () {
    require PROJECT_ROOT . '/views/auth.php';
});
Routes::get('/app', function () {
    require PROJECT_ROOT . '/views/app.php';
});

Routes::get('/bio', function () {
    require PROJECT_ROOT . '/views/bio.php';
});
Routes::get('/api/bio/view', function () {
});

Routes::get('/info', function () {
    require PROJECT_ROOT . '/views/info.php';
});
Routes::post('/info', function () {
    require PROJECT_ROOT . '/views/info.php';
});
Routes::get('/card/list', function () {
    require PROJECT_ROOT . '/views/card_list.php';
});
Routes::get('/payment', function () {
    require PROJECT_ROOT . '/views/pay_banking.php';
});

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
if (preg_match('~^/@([a-zA-Z0-9._-]+)$~', $uri, $m)) {
    $_GET['u'] = $m[1];
    require PROJECT_ROOT . '/views/viewer.php';
    exit;
}
/* ========= API ROUTES ========= */
require PROJECT_ROOT . '/api/Auth.php';

require PROJECT_ROOT . '/api/bio.php';
require PROJECT_ROOT . '/api/viewer.php';

/* ========= DASHBOARD API ROUTES ========= */
require PROJECT_ROOT . '/api/dashboard.php';

/* =========== Sercurity Middleware =========== */
require PROJECT_ROOT . '/src/KNCMS/Security.php';

register_auth_routes();
Routes::dispatch();
