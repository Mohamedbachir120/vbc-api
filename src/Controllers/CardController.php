<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Card;
use App\Support\ApiException;
use App\Support\ApiResponse;
use App\Support\QrCodeHelper;
use App\Support\Validator;
use App\Support\VCardBuilder;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CardController
{
    public function index(Request $request, Response $response): Response
    {
        $userId = (int) $request->getAttribute('userId');
        $baseUrl = $_ENV['APP_BASE_URL'];

        $cards = Card::where('user_id', $userId)->orderByDesc('created_at')->get();

        $data = $cards->map(fn (Card $card) => $card->toApiArray($baseUrl))->all();

        return ApiResponse::success($response, $data);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $card = $this->findOwnedCard($request, (int) $args['id']);
        $baseUrl = $_ENV['APP_BASE_URL'];

        return ApiResponse::success($response, $card->toApiArray($baseUrl));
    }

    public function store(Request $request, Response $response): Response
    {
        $userId = (int) $request->getAttribute('userId');
        $body = (array) ($request->getParsedBody() ?? []);

        (new Validator($body))
            ->required('name', 'Name')
            ->email('email')
            ->url('website', 'Website')
            ->hexColor('accent', 'Accent')
            ->hexColor('accentLight', 'Accent light')
            ->throwIfFails();

        $card = Card::create([
            'name'        => $body['name'],
            'role'        => $body['role'] ?? null,
            'company'     => $body['company'] ?? null,
            'website'     => $body['website'] ?? null,
            'email'       => $body['email'] ?? null,
            'phone'       => $body['phone'] ?? null,
            'location'    => $body['location'] ?? null,
            'accent'      => $body['accent'] ?? '#6366f1',
            'accentLight' => $body['accentLight'] ?? '#e0e7ff',
            'isActive'    => $body['isActive'] ?? true,
            'userId'      => $userId,
        ]);

        $baseUrl = $_ENV['APP_BASE_URL'];

        return ApiResponse::success($response, $card->toApiArray($baseUrl), 201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $card = $this->findOwnedCard($request, (int) $args['id']);
        $body = (array) ($request->getParsedBody() ?? []);

        (new Validator($body))
            ->email('email')
            ->url('website', 'Website')
            ->hexColor('accent', 'Accent')
            ->hexColor('accentLight', 'Accent light')
            ->throwIfFails();

        $map = [
            'name'        => 'name',
            'role'        => 'role',
            'company'     => 'company',
            'website'     => 'website',
            'email'       => 'email',
            'phone'       => 'phone',
            'location'    => 'location',
            'accent'      => 'accent',
            'accentLight' => 'accentLight',
            'isActive'    => 'isActive',
        ];

        foreach ($map as $field => $attribute) {
            if (array_key_exists($field, $body)) {
                $card->{$attribute} = $body[$field];
            }
        }

        $card->save();

        $baseUrl = $_ENV['APP_BASE_URL'];

        return ApiResponse::success($response, $card->toApiArray($baseUrl));
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        $card = $this->findOwnedCard($request, (int) $args['id']);
        $card->delete();

        return ApiResponse::noContent($response);
    }

    public function toggle(Request $request, Response $response, array $args): Response
    {
        $card = $this->findOwnedCard($request, (int) $args['id']);

        $card->isActive = !$card->isActive;
        $card->save();

        $baseUrl = $_ENV['APP_BASE_URL'];
        $data = $card->toApiArray($baseUrl);

        return ApiResponse::success($response, $data);
    }

    public function qr(Request $request, Response $response, array $args): Response
    {
        $card = $this->findOwnedCard($request, (int) $args['id']);
        $baseUrl = $_ENV['APP_BASE_URL'];
        $publicUrl = rtrim($baseUrl, '/') . '/api/public/card/' . $card->uuid;

        $dataUri = QrCodeHelper::dataUriFor($publicUrl);

        return ApiResponse::success($response, ['qrCode' => $dataUri]);
    }

    public function vcard(Request $request, Response $response, array $args): Response
    {
        $card = $this->findOwnedCard($request, (int) $args['id']);

        $vcf = VCardBuilder::build($card);
        $filename = VCardBuilder::filenameFor($card);

        $response->getBody()->write($vcf);

        return $response
            ->withHeader('Content-Type', 'text/vcard; charset=utf-8')
            ->withHeader('Content-Disposition', "attachment; filename=\"$filename\"")
            ->withStatus(200);
    }

    /**
     * Loads a card by id and guarantees it belongs to the authenticated user.
     * 404 if it doesn't exist at all, 403 if it exists but belongs to someone else.
     */
    private function findOwnedCard(Request $request, int $cardId): Card
    {
        $userId = (int) $request->getAttribute('userId');

        $card = Card::find($cardId);

        if (!$card) {
            throw ApiException::notFound('Card not found');
        }

        if ($card->userId !== $userId) {
            throw ApiException::forbidden('Forbidden');
        }

        return $card;
    }
}
