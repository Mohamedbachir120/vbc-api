<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\CardController;
use App\Controllers\PublicController;
use App\Controllers\StatsController;
use App\Middleware\AuthMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app) {
    // ---- Utility ----
    $app->get('/health', function (Request $request, Response $response) {
        $response->getBody()->write(json_encode([
            'status'    => 'ok',
            'timestamp' => (new DateTime())->format(DATE_ATOM),
            'uptime'    => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
            'env'       => $_ENV['APP_ENV'] ?? 'development',
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    });

    // ---- Authentication ----
    $app->group('/api/auth', function ($group) {
        $group->post('/register', [AuthController::class, 'register']);
        $group->post('/login', [AuthController::class, 'login']);
        $group->get('/me', [AuthController::class, 'me'])->add(AuthMiddleware::class);
    });

    // ---- Card Management (all require auth) ----
    $app->group('/api/cards', function ($group) {
        $group->get('', [CardController::class, 'index']);
        $group->post('', [CardController::class, 'store']);
        $group->get('/{id}', [CardController::class, 'show']);
        $group->put('/{id}', [CardController::class, 'update']);
        $group->delete('/{id}', [CardController::class, 'destroy']);
        $group->patch('/{id}/toggle', [CardController::class, 'toggle']);
        $group->get('/{id}/qr', [CardController::class, 'qr']);
        $group->get('/{id}/vcard', [CardController::class, 'vcard']);
    })->add(AuthMiddleware::class);

    // ---- Statistics (require auth) ----
    $app->group('/api/stats', function ($group) {
        $group->get('/dashboard', [StatsController::class, 'dashboard']);
    })->add(AuthMiddleware::class);

    // ---- Public (no auth) ----
    $app->group('/api/public/card', function ($group) {
        $group->get('/{uuid}', [PublicController::class, 'show']);
        $group->get('/{uuid}/qr', [PublicController::class, 'qr']);
        $group->get('/{uuid}/vcard', [PublicController::class, 'vcard']);
    });
};
