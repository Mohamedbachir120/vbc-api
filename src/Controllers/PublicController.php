<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Card;
use App\Models\CardView;
use App\Support\ApiException;
use App\Support\ApiResponse;
use App\Support\QrCodeHelper;
use App\Support\VCardBuilder;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PublicController
{
    public function show(Request $request, Response $response, array $args): Response
    {
        $card = $this->findActiveCard($args['uuid']);

        $this->logView($request, $card);

        return ApiResponse::success($response, $card->toPublicArray());
    }

    public function qr(Request $request, Response $response, array $args): Response
    {
        $card = $this->findActiveCard($args['uuid']);
        $baseUrl = $_ENV['APP_BASE_URL'];
        $publicUrl = rtrim($baseUrl, '/') . '/api/public/card/' . $card->uuid;

        $dataUri = QrCodeHelper::dataUriFor($publicUrl);

        return ApiResponse::success($response, ['qrCode' => $dataUri]);
    }

    public function vcard(Request $request, Response $response, array $args): Response
    {
        $card = $this->findActiveCard($args['uuid']);

        $vcf = VCardBuilder::build($card);
        $filename = VCardBuilder::filenameFor($card);

        $response->getBody()->write($vcf);

        return $response
            ->withHeader('Content-Type', 'text/vcard; charset=utf-8')
            ->withHeader('Content-Disposition', "attachment; filename=\"$filename\"")
            ->withStatus(200);
    }

    private function findActiveCard(string $uuid): Card
    {
        $card = Card::where('uuid', $uuid)->where('is_active', true)->first();

        if (!$card) {
            throw ApiException::notFound('Card not found (or inactive)');
        }

        return $card;
    }

    private function logView(Request $request, Card $card): void
    {
        $serverParams = $request->getServerParams();

        CardView::create([
            'ip'        => $serverParams['REMOTE_ADDR'] ?? null,
            'userAgent' => $request->getHeaderLine('User-Agent') ?: null,
            'cardId'    => $card->id,
        ]);
    }
}
