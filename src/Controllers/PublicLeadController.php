<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Lead;
use App\Support\ApiResponse;
use App\Support\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Unauthenticated endpoint the public ad-campaign landing page posts to.
 * Someone clicks an ad -> lands on the French lead-capture form -> this
 * creates a lead with source=ads so it shows up directly in the pipeline.
 */
class PublicLeadController
{
    public function capture(Request $request, Response $response): Response
    {
        $body = (array) ($request->getParsedBody() ?? []);

        (new Validator($body))
            ->required('name', 'Nom')
            ->required('phone', 'Téléphone')
            ->email('email')
            ->in('propertyType', ['appartement', 'villa', 'terrain', 'local', 'bureau', 'autre'], 'Type de bien')
            ->integer('budgetMin', 'Budget min')
            ->integer('budgetMax', 'Budget max')
            ->throwIfFails();

        $lead = Lead::create([
            'name'         => $body['name'],
            'phone'        => $body['phone'],
            'email'        => $body['email'] ?? null,
            'budgetMin'    => $body['budgetMin'] ?? null,
            'budgetMax'    => $body['budgetMax'] ?? null,
            'location'     => $body['location'] ?? null,
            'propertyType' => $body['propertyType'] ?? null,
            'options'      => $body['options'] ?? null,
            'message'      => $body['message'] ?? null,
            'source'       => 'ads',
            'campaign'     => $body['campaign'] ?? ($body['utm_campaign'] ?? null),
            'status'       => 'prospect',
        ]);

        return ApiResponse::success($response, ['id' => $lead->id], 201);
    }
}
