<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Lead;
use App\Models\Reminder;
use App\Support\ApiException;
use App\Support\ApiResponse;
use App\Support\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ReminderController
{
    /**
     * Each commercial sees their own reminders; an admin sees everyone's.
     * ?upcoming=1 hides done reminders and orders by due date.
     */
    public function index(Request $request, Response $response): Response
    {
        $userId = (int) $request->getAttribute('userId');
        $user = \App\Models\User::find($userId);
        $params = $request->getQueryParams();

        $query = Reminder::with('lead')->orderBy('due_at');

        if (!$user || !$user->isAdmin()) {
            $query->where('user_id', $userId);
        }

        if (!empty($params['upcoming'])) {
            $query->where('is_done', false);
        }

        $reminders = $query->get()->map(fn (Reminder $r) => $r->toApiArray())->all();

        return ApiResponse::success($response, $reminders);
    }

    public function store(Request $request, Response $response): Response
    {
        $userId = (int) $request->getAttribute('userId');
        $body = (array) ($request->getParsedBody() ?? []);

        (new Validator($body))
            ->required('leadId', 'Lead')
            ->integer('leadId', 'Lead')
            ->required('dueAt', 'Due date')
            ->dateTime('dueAt', 'Due date')
            ->throwIfFails();

        $lead = Lead::find((int) $body['leadId']);

        if (!$lead) {
            throw ApiException::validation('Validation failed', ['leadId' => 'Lead not found']);
        }

        $reminder = Reminder::create([
            'leadId' => $lead->id,
            'userId' => $userId,
            'dueAt'  => $body['dueAt'],
            'note'   => $body['note'] ?? null,
        ]);

        return ApiResponse::success($response, $reminder->load('lead')->toApiArray(), 201);
    }

    public function complete(Request $request, Response $response, array $args): Response
    {
        $reminder = $this->findOwnedReminder($request, (int) $args['id']);

        $reminder->isDone = true;
        $reminder->save();

        return ApiResponse::success($response, $reminder->toApiArray());
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        $reminder = $this->findOwnedReminder($request, (int) $args['id']);
        $reminder->delete();

        return ApiResponse::noContent($response);
    }

    private function findOwnedReminder(Request $request, int $id): Reminder
    {
        $userId = (int) $request->getAttribute('userId');
        $user = \App\Models\User::find($userId);

        $reminder = Reminder::with('lead')->find($id);

        if (!$reminder) {
            throw ApiException::notFound('Reminder not found');
        }

        if ((!$user || !$user->isAdmin()) && $reminder->userId !== $userId) {
            throw ApiException::forbidden('Forbidden');
        }

        return $reminder;
    }
}
