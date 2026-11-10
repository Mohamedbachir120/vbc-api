<?php

declare(strict_types=1);

use App\Database;
use App\Middleware\CorsMiddleware;
use App\Support\ApiException;
use Dotenv\Dotenv;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpMethodNotAllowedException;

require __DIR__ . '/../vendor/autoload.php';

// ---- Environment ----
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// ---- Database (Eloquent / illuminate-database, MySQL) ----
Database::boot();

// ---- Slim App ----
$app = AppFactory::create();
$app->addBodyParsingMiddleware();

// ---- Routes ----
(require __DIR__ . '/../routes/api.php')($app);

$app->addRoutingMiddleware();

// ---- Centralized error handling -> matches the documented error_shape ----
$errorMiddleware = $app->addErrorMiddleware(
    (bool) ($_ENV['APP_ENV'] === 'development'),
    true,
    true
);

$errorMiddleware->setDefaultErrorHandler(function (
    Request $request,
    Throwable $exception,
    bool $displayErrorDetails
) use ($app): Response {
    $response = $app->getResponseFactory()->createResponse();

    if ($exception instanceof ApiException) {
        $status = $exception->getStatus();
        $code = $exception->getErrorCode();
        $message = $exception->getMessage();
        $details = $exception->getDetails();
    } elseif ($exception instanceof HttpNotFoundException) {
        $status = 404;
        $code = 'NOT_FOUND';
        $message = 'Route not found';
        $details = null;
    } elseif ($exception instanceof HttpMethodNotAllowedException) {
        $status = 405;
        $code = 'BAD_REQUEST';
        $message = 'Method not allowed';
        $details = null;
    } else {
        $status = 500;
        $code = 'INTERNAL_ERROR';
        $message = $displayErrorDetails ? $exception->getMessage() : 'Something went wrong';
        $details = null;
    }

    $payload = [
        'success' => false,
        'code'    => $code,
        'message' => $message,
    ];

    if ($details !== null) {
        $payload['details'] = $details;
    }

    $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_SLASHES));

    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus($status);
});

// Added last so it is the OUTERMOST middleware: it must see every response,
// including the ones ErrorMiddleware builds for 404/405/500, or the browser
// blocks those error bodies from reaching the frontend's catch handler.
$app->add(CorsMiddleware::class);

$app->run();
