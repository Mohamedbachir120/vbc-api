<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\CardController;
use App\Controllers\CrmDashboardController;
use App\Controllers\LeadController;
use App\Controllers\PublicController;
use App\Controllers\PublicLeadController;
use App\Controllers\ReminderController;
use App\Controllers\StatsController;
use App\Controllers\UserController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
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

    // ===================== CRM (Phase 2) =====================

    // ---- Leads & pipeline (any authenticated user; visibility is scoped
    //      per-role inside LeadController) ----
    $app->group('/api/leads', function ($group) {
        $group->get('', [LeadController::class, 'index']);
        $group->post('', [LeadController::class, 'store']);
        $group->get('/{id}', [LeadController::class, 'show']);
        $group->put('/{id}', [LeadController::class, 'update']);
        $group->patch('/{id}/status', [LeadController::class, 'updateStatus']);
        $group->get('/{id}/interactions', [LeadController::class, 'interactions']);
        $group->post('/{id}/interactions', [LeadController::class, 'addInteraction']);

        // Admin-only
        $group->delete('/{id}', [LeadController::class, 'destroy'])
            ->add(new RoleMiddleware(['admin']));
        $group->patch('/{id}/assign', [LeadController::class, 'assign'])
            ->add(new RoleMiddleware(['admin']));
    })->add(AuthMiddleware::class);

    // ---- Agenda / reminders (own only, admin sees all) ----
    $app->group('/api/reminders', function ($group) {
        $group->get('', [ReminderController::class, 'index']);
        $group->post('', [ReminderController::class, 'store']);
        $group->patch('/{id}/complete', [ReminderController::class, 'complete']);
        $group->delete('/{id}', [ReminderController::class, 'destroy']);
    })->add(AuthMiddleware::class);

    // ---- CRM dashboard (own stats, admin sees everyone's) ----
    $app->group('/api/crm', function ($group) {
        $group->get('/dashboard', [CrmDashboardController::class, 'dashboard']);
    })->add(AuthMiddleware::class);

    // ---- Team management (admin only) ----
    $app->group('/api/users', function ($group) {
        $group->get('', [UserController::class, 'index']);
        $group->post('', [UserController::class, 'store']);
    })->add(new RoleMiddleware(['admin']))->add(AuthMiddleware::class);

    // ---- Public ad-campaign lead capture (no auth) ----
    $app->post('/api/leads/capture', [PublicLeadController::class, 'capture']);
};
