<?php

declare(strict_types=1);

namespace InfraMind\Controllers;

use InfraMind\Core\Request;
use InfraMind\Core\Response;
use InfraMind\Services\AnalysisService;
use InfraMind\Validators\AnalysisValidator;
use InfraMind\Exceptions\ValidationException;
use InfraMind\Core\Logger;

/**
 * Analysis controller.
 */
class AnalysisController
{
    private AnalysisService $analysisService;
    private Logger $logger;

    public function __construct()
    {
        $this->analysisService = new AnalysisService();
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
     * GET /analyses/:id
     */
    public function get(Request $request, string $id): Response
    {
        try {
            $analysis = $this->analysisService->getAnalysis($id);
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
                $analyses = $this->analysisService->getAnalysesForReview($limit, $offset);
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
