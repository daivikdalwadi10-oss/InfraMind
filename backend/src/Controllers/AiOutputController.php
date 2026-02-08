<?php

declare(strict_types=1);

namespace InfraMind\Controllers;

use InfraMind\Core\Request;
use InfraMind\Core\Response;
use InfraMind\Core\Logger;
use InfraMind\Exceptions\ValidationException;
use InfraMind\Repositories\AiOutputRepository;
use InfraMind\Repositories\AnalysisRepository;
use InfraMind\Repositories\AuditLogRepository;
use InfraMind\Repositories\TeamRepository;
use InfraMind\Models\AiOutputStatus;
use InfraMind\Validators\AiOutputValidator;

class AiOutputController
{
    private AiOutputRepository $aiOutputRepository;
    private AnalysisRepository $analysisRepository;
    private TeamRepository $teamRepository;
    private AuditLogRepository $auditRepository;
    private Logger $logger;

    public function __construct()
    {
        $this->aiOutputRepository = new AiOutputRepository();
        $this->analysisRepository = new AnalysisRepository();
        $this->teamRepository = new TeamRepository();
        $this->auditRepository = new AuditLogRepository();
        $this->logger = Logger::getInstance();
    }

    /**
     * PATCH /ai/outputs/:id
     */
    public function update(Request $request, string $id): Response
    {
        try {
            $user = $request->getUser();
            $data = $request->getAll();
            AiOutputValidator::validateUpdate($data);

            $output = $this->aiOutputRepository->findById($id);
            if (!$output) {
                return (new Response(404))->error('AI output not found');
            }

            $analysis = $this->analysisRepository->findById($output['analysisId']);
            if (!$analysis) {
                return (new Response(404))->error('Analysis not found');
            }

            if ($user->role === 'OWNER') {
                return (new Response(403))->error('Owners cannot access raw analyses');
            }

            if ($user->role === 'EMPLOYEE' && $analysis->employeeId !== $user->sub) {
                return (new Response(403))->error('You can only manage your own analyses');
            }

            if ($user->role === 'MANAGER' && !$this->teamRepository->isManagerOfEmployee($user->sub, $analysis->employeeId)) {
                return (new Response(403))->error('You can only manage analyses for your teams');
            }

            $status = AiOutputStatus::from($data['status']);
            $payload = $data['payload'] ?? null;

            $this->aiOutputRepository->updateStatus($id, $status, $payload);
            $this->auditRepository->log('AiOutput', $id, 'STATUS_UPDATED', $user->sub, [
                'status' => $status->value,
            ]);

            return (new Response(200))->success(['id' => $id, 'status' => $status->value]);
        } catch (ValidationException $e) {
            return (new Response(422))->error('Validation failed', 422, $e->getErrors());
        } catch (\Exception $e) {
            $this->logger->error('AI output update error: ' . $e->getMessage());
            return (new Response(400))->error($e->getMessage());
        }
    }
}
