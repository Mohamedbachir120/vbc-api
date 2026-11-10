<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Lead;
use App\Models\LeadInteraction;
use App\Models\User;
use App\Support\ApiException;
use App\Support\ApiResponse;
use App\Support\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class LeadController
{
    /**
     * Admins see every lead; a commercial only sees leads assigned to them.
     * Supports ?status=, ?source=, ?q= (search name/phone/email), ?assignedTo=
     * (admin only).
     */
    public function index(Request $request, Response $response): Response
    {
        $user = $this->currentUser($request);
        $params = $request->getQueryParams();

        $query = Lead::with('assignee')->orderByDesc('created_at');

        if (!$user->isAdmin()) {
            $query->where('assigned_to', $user->id);
        } elseif (!empty($params['assignedTo'])) {
            $query->where('assigned_to', (int) $params['assignedTo']);
        }

        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        if (!empty($params['source'])) {
            $query->where('source', $params['source']);
        }

        if (!empty($params['q'])) {
            $term = '%' . $params['q'] . '%';
            $query->where(function ($w) use ($term) {
                $w->where('name', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('email', 'like', $term);
            });
        }

        $leads = $query->get()->map(fn (Lead $lead) => $lead->toApiArray())->all();

        return ApiResponse::success($response, $leads);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $lead = $this->findVisibleLead($request, (int) $args['id']);

        return ApiResponse::success($response, $lead->toApiArray());
    }

    public function store(Request $request, Response $response): Response
    {
        $user = $this->currentUser($request);
        $body = (array) ($request->getParsedBody() ?? []);

        (new Validator($body))
            ->required('name', 'Name')
            ->email('email')
            ->in('propertyType', ['appartement', 'villa', 'terrain', 'local', 'bureau', 'autre'], 'Property type')
            ->integer('budgetMin', 'Budget min')
            ->integer('budgetMax', 'Budget max')
            ->throwIfFails();

        $lead = Lead::create([
            'name'         => $body['name'],
            'phone'        => $body['phone'] ?? null,
            'email'        => $body['email'] ?? null,
            'budgetMin'    => $body['budgetMin'] ?? null,
            'budgetMax'    => $body['budgetMax'] ?? null,
            'location'     => $body['location'] ?? null,
            'propertyType' => $body['propertyType'] ?? null,
            'options'      => $body['options'] ?? null,
            'message'      => $body['message'] ?? null,
            'source'       => 'manual',
            'status'       => 'prospect',
            'assignedTo'   => $body['assignedTo'] ?? ($user->isAdmin() ? null : $user->id),
        ]);

        return ApiResponse::success($response, $lead->toApiArray(), 201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $lead = $this->findVisibleLead($request, (int) $args['id']);
        $body = (array) ($request->getParsedBody() ?? []);

        (new Validator($body))
            ->email('email')
            ->in('propertyType', ['appartement', 'villa', 'terrain', 'local', 'bureau', 'autre'], 'Property type')
            ->integer('budgetMin', 'Budget min')
            ->integer('budgetMax', 'Budget max')
            ->throwIfFails();

        $map = [
            'name', 'phone', 'email', 'budgetMin', 'budgetMax',
            'location', 'propertyType', 'options', 'message',
        ];

        foreach ($map as $field) {
            if (array_key_exists($field, $body)) {
                $lead->{$field} = $body[$field];
            }
        }

        $lead->save();

        return ApiResponse::success($response, $lead->toApiArray());
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        // Deleting a lead is admin-only; RoleMiddleware on this route enforces that.
        $lead = Lead::find((int) $args['id']);

        if (!$lead) {
            throw ApiException::notFound('Lead not found');
        }

        $lead->delete();

        return ApiResponse::noContent($response);
    }

    public function updateStatus(Request $request, Response $response, array $args): Response
    {
        $lead = $this->findVisibleLead($request, (int) $args['id']);
        $body = (array) ($request->getParsedBody() ?? []);

        (new Validator($body))
            ->required('status', 'Status')
            ->in('status', Lead::STATUSES, 'Status')
            ->throwIfFails();

        $lead->status = $body['status'];
        $lead->save();

        return ApiResponse::success($response, $lead->toApiArray());
    }

    public function assign(Request $request, Response $response, array $args): Response
    {
        // Admin-only route (enforced by RoleMiddleware on the group).
        $lead = Lead::find((int) $args['id']);

        if (!$lead) {
            throw ApiException::notFound('Lead not found');
        }

        $body = (array) ($request->getParsedBody() ?? []);

        (new Validator($body))
            ->required('assignedTo', 'Assigned to')
            ->integer('assignedTo', 'Assigned to')
            ->throwIfFails();

        $assignee = User::find((int) $body['assignedTo']);

        if (!$assignee) {
            throw ApiException::validation('Validation failed', ['assignedTo' => 'User not found']);
        }

        $lead->assignedTo = $assignee->id;
        $lead->save();

        return ApiResponse::success($response, $lead->load('assignee')->toApiArray());
    }

    // --- Interactions (nested under a lead) ---

    public function interactions(Request $request, Response $response, array $args): Response
    {
        $lead = $this->findVisibleLead($request, (int) $args['id']);

        $items = $lead->interactions()->with('user')->get()
            ->map(fn (LeadInteraction $i) => $i->toApiArray())->all();

        return ApiResponse::success($response, $items);
    }

    public function addInteraction(Request $request, Response $response, array $args): Response
    {
        $user = $this->currentUser($request);
        $lead = $this->findVisibleLead($request, (int) $args['id']);
        $body = (array) ($request->getParsedBody() ?? []);

        (new Validator($body))
            ->required('content', 'Content')
            ->in('type', LeadInteraction::TYPES, 'Type')
            ->throwIfFails();

        $interaction = LeadInteraction::create([
            'leadId'  => $lead->id,
            'userId'  => $user->id,
            'type'    => $body['type'] ?? 'note',
            'content' => $body['content'],
        ]);

        return ApiResponse::success($response, $interaction->load('user')->toApiArray(), 201);
    }

    /**
     * Loads a lead the current user is allowed to see:
     * admins see everything, a commercial only their own assigned leads.
     */
    private function findVisibleLead(Request $request, int $leadId): Lead
    {
        $user = $this->currentUser($request);
        $lead = Lead::with('assignee')->find($leadId);

        if (!$lead) {
            throw ApiException::notFound('Lead not found');
        }

        if (!$user->isAdmin() && $lead->assignedTo !== $user->id) {
            throw ApiException::forbidden('Forbidden');
        }

        return $lead;
    }

    private function currentUser(Request $request): User
    {
        $attached = $request->getAttribute('user');

        if ($attached instanceof User) {
            return $attached;
        }

        $user = User::find((int) $request->getAttribute('userId'));

        if (!$user) {
            throw ApiException::unauthorized('Invalid or expired token');
        }

        return $user;
    }
}
