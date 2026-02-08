<?php

declare(strict_types=1);

namespace InfraMind\Controllers;

use InfraMind\Core\Request;
use InfraMind\Core\Response;
use InfraMind\Services\AnalysisService;
use InfraMind\Repositories\TeamRepository;
use InfraMind\Repositories\AiOutputRepository;
use InfraMind\Services\AiHypothesisService;
use InfraMind\Services\AiReportService;
use InfraMind\Validators\AnalysisValidator;
use InfraMind\Exceptions\ValidationException;
use InfraMind\Core\Logger;

/**
 * Analysis controller.
 */
class AnalysisController
{
    private AnalysisService $analysisService;
    private TeamRepository $teamRepository;
    private AiOutputRepository $aiOutputRepository;
    private AiHypothesisService $aiHypothesisService;
    private AiReportService $aiReportService;
    private Logger $logger;

    public function __construct()
    {
        $this->analysisService = new AnalysisService();
        $this->teamRepository = new TeamRepository();
        $this->aiOutputRepository = new AiOutputRepository();
        $this->aiHypothesisService = new AiHypothesisService();
        $this->aiReportService = new AiReportService();
        $this->logger = Logger::getInstance();
    }

    /**
     * POST /analyses
     */
    public function create(Request $request): Response
    {
        try {
            $user = $request->getUser();
            if ($user->role !== 'EMPLOYEE') {
                return (new Response(403))->error('Only employees can create analyses');
            }

            $data = $request->getAll();
            AnalysisValidator::validateCreate($data);

            $analysis = $this->analysisService->createAnalysis(
                $user->sub,
                $data['taskId'],
                $data['analysisType'],
            );

            return (new Response(201))->success($analysis->toArray(), 'Analysis created');
        } catch (ValidationException $e) {
            return (new Response(422))->error('Validation failed', 422, $e->getErrors());
        } catch (\Exception $e) {
            $this->logger->error('Analysis creation error: ' . $e->getMessage());
            return (new Response(400))->error($e->getMessage());
        }
    }

    /**
     * POST /analyses/manager
     */
    public function createAssigned(Request $request): Response
    {
        try {
            $user = $request->getUser();
            if ($user->role !== 'MANAGER') {
                return (new Response(403))->error('Only managers can create assigned analyses');
            }

            $data = $request->getAll();
            AnalysisValidator::validateManagerCreate($data);

            $analysis = $this->analysisService->createAssignedAnalysis(
                $user->sub,
                $data['title'],
                $data['analysisType'],
                $data['assignedTo'],
                $data['teamId'] ?? null,
                $data['taskDescription'] ?? null,
            );

            return (new Response(201))->success($analysis->toArray(), 'Analysis created');
        } catch (ValidationException $e) {
            return (new Response(422))->error('Validation failed', 422, $e->getErrors());
        } catch (\Exception $e) {
            $this->logger->error('Manager analysis creation error: ' . $e->getMessage());
            return (new Response(400))->error($e->getMessage());
        }
    }

    /**
     * GET /analyses/:id
     */
    public function get(Request $request, string $id): Response
    {
        try {
            $user = $request->getUser();
            if (!$user) {
                return (new Response(401))->error('Not authenticated');
            }

            if ($user->role === 'OWNER') {
                return (new Response(403))->error('Owners cannot view raw analyses');
            }

            $analysis = $this->analysisService->getAnalysis($id);
            if ($user->role === 'EMPLOYEE' && $analysis->employeeId !== $user->sub) {
                return (new Response(403))->error('You can only view your own analyses');
            }
            if ($user->role === 'MANAGER' && !$this->teamRepository->isManagerOfEmployee($user->sub, $analysis->employeeId)) {
                return (new Response(403))->error('You can only view analyses for your teams');
            }
            return (new Response(200))->success($analysis->toArray());
        } catch (\Exception $e) {
            return (new Response(404))->error($e->getMessage());
        }
    }

    /**
     * PUT /analyses/:id
     */
    public function update(Request $request, string $id): Response
    {
        try {
            $user = $request->getUser();
            if ($user->role !== 'EMPLOYEE') {
                return (new Response(403))->error('Only employees can update analyses');
            }

            $data = $request->getAll();
            AnalysisValidator::validateUpdate($data);

            $analysis = $this->analysisService->updateAnalysisContent(
                $id,
                $user->sub,
                $data['symptoms'] ?? [],
                $data['signals'] ?? [],
                $data['hypotheses'] ?? [],
                $data['environmentContext'] ?? [],
                $data['timelineEvents'] ?? [],
                $data['dependencyImpact'] ?? [],
                $data['riskClassification'] ?? [],
                (int) ($data['readinessScore'] ?? 0),
            );

            return (new Response(200))->success($analysis->toArray(), 'Analysis updated');
        } catch (ValidationException $e) {
            return (new Response(422))->error('Validation failed', 422, $e->getErrors());
        } catch (\Exception $e) {
            $this->logger->error('Analysis update error: ' . $e->getMessage());
            return (new Response(400))->error($e->getMessage());
        }
    }

    /**
     * POST /analyses/:id/submit
     */
    public function submit(Request $request, string $id): Response
    {
        try {
            $user = $request->getUser();
            if ($user->role !== 'EMPLOYEE') {
                return (new Response(403))->error('Only employees can submit analyses');
            }

            $analysis = $this->analysisService->submitAnalysis($id, $user->sub);
            return (new Response(200))->success($analysis->toArray(), 'Analysis submitted');
        } catch (\Exception $e) {
            $this->logger->error('Analysis submission error: ' . $e->getMessage());
            return (new Response(400))->error($e->getMessage());
        }
    }

    /**
     * POST /analyses/:id/review
     */
    public function review(Request $request, string $id): Response
    {
        try {
            $user = $request->getUser();
            if ($user->role !== 'MANAGER') {
                return (new Response(403))->error('Only managers can review analyses');
            }

            $data = $request->getAll();
            AnalysisValidator::validateReview($data);

            $analysis = $this->analysisService->reviewAnalysis(
                $id,
                $user->sub,
                $data['decision'],
                $data['feedback'] ?? null,
            );

            return (new Response(200))->success($analysis->toArray(), 'Analysis reviewed');
        } catch (ValidationException $e) {
            return (new Response(422))->error('Validation failed', 422, $e->getErrors());
        } catch (\Exception $e) {
            $this->logger->error('Analysis review error: ' . $e->getMessage());
            return (new Response(400))->error($e->getMessage());
        }
    }

    /**
     * POST /analyses/:id/ai/hypotheses
     */
    public function generateHypotheses(Request $request, string $id): Response
    {
        try {
            $user = $request->getUser();
            $output = $this->aiHypothesisService->generateHypotheses($id, $user->sub, $user->role);
            return (new Response(200))->success($output, 'AI hypotheses generated');
        } catch (\Exception $e) {
            $this->logger->error('AI hypothesis generation error: ' . $e->getMessage());
            return (new Response(400))->error($e->getMessage());
        }
    }

    /**
     * GET /analyses/:id/ai/outputs
     */
    public function listAiOutputs(Request $request, string $id): Response
    {
        try {
            $user = $request->getUser();
            $analysis = $this->analysisService->getAnalysis($id);

            if ($user->role === 'OWNER') {
                return (new Response(403))->error('Owners cannot access raw analyses');
            }

            if ($user->role === 'EMPLOYEE' && $analysis->employeeId !== $user->sub) {
                return (new Response(403))->error('You can only access your own analyses');
            }

            if ($user->role === 'MANAGER' && !$this->teamRepository->isManagerOfEmployee($user->sub, $analysis->employeeId)) {
                return (new Response(403))->error('You can only access analyses for your teams');
            }

            $outputs = $this->aiOutputRepository->listByAnalysis($id);
            return (new Response(200))->success($outputs);
        } catch (\Exception $e) {
            $this->logger->error('AI output list error: ' . $e->getMessage());
            return (new Response(400))->error($e->getMessage());
        }
    }

    /**
     * POST /analyses/:id/ai/report-draft
     */
    public function generateReportDraft(Request $request, string $id): Response
    {
        try {
            $user = $request->getUser();
            $output = $this->aiReportService->generateReportDraft($id, $user->sub, $user->role);
            return (new Response(200))->success($output, 'AI report draft generated');
        } catch (\Exception $e) {
            $this->logger->error('AI report draft error: ' . $e->getMessage());
            return (new Response(400))->error($e->getMessage());
        }
    }

    /**
     * GET /analyses
     */
    public function list(Request $request): Response
    {
        try {
            $user = $request->getUser();
            $limit = min((int) $request->getQuery('limit', 50), 100);
            $offset = (int) $request->getQuery('offset', 0);

            if ($user->role === 'EMPLOYEE') {
                $analyses = $this->analysisService->getAnalysesForEmployee($user->sub, $limit, $offset);
            } elseif ($user->role === 'MANAGER') {
                $analyses = $this->analysisService->getAnalysesForReview($user->sub, $limit, $offset);
            } else {
                return (new Response(403))->error('Insufficient permissions');
            }

            return (new Response(200))->success(array_map(fn($a) => $a->toArray(), $analyses));
        } catch (\Exception $e) {
            $this->logger->error('Analysis list error: ' . $e->getMessage());
            return (new Response(400))->error($e->getMessage());
        }
    }
}
