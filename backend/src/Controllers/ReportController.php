<?php

declare(strict_types=1);

namespace InfraMind\Controllers;

use InfraMind\Core\Request;
use InfraMind\Core\Response;
use InfraMind\Services\ReportService;
use InfraMind\Core\Logger;

/**
 * Report controller.
 */
class ReportController
{
    private ReportService $reportService;
    private Logger $logger;

    public function __construct()
    {
        $this->reportService = new ReportService();
        $this->logger = Logger::getInstance();
    }

    /**
     * POST /reports
     */
    public function create(Request $request): Response
    {
        try {
            $user = $request->getUser();
            if ($user->role !== 'MANAGER') {
                return (new Response(403))->error('Only managers can create reports');
            }

            $data = $request->getAll();

            if (!isset($data['analysisId'])) {
                return (new Response(400))->error('analysisId required');
            }
            if (!isset($data['executiveSummary'])) {
                return (new Response(400))->error('executiveSummary required');
            }
            if (!isset($data['rootCause'])) {
                return (new Response(400))->error('rootCause required');
            }
            if (!isset($data['impact'])) {
                return (new Response(400))->error('impact required');
            }
            if (!isset($data['resolution'])) {
                return (new Response(400))->error('resolution required');
            }
            if (!isset($data['preventionSteps'])) {
                return (new Response(400))->error('preventionSteps required');
            }

            $report = $this->reportService->createReport(
                $data['analysisId'],
                $data['executiveSummary'],
                $data['rootCause'],
                $data['impact'],
                $data['resolution'],
                $data['preventionSteps'],
                (bool) ($data['aiAssisted'] ?? false),
                $user->sub,
            );
            return (new Response(201))->success($report, 'Report created');
        } catch (\Exception $e) {
            $this->logger->error('Report creation error: ' . $e->getMessage());
            return (new Response(400))->error($e->getMessage());
        }
    }

    /**
     * GET /reports/:id
     */
    public function get(Request $request, string $id): Response
    {
        try {
            $user = $request->getUser();
            $report = $this->reportService->getReport($id, $user->sub, $user->role);
            return (new Response(200))->success($report);
        } catch (\Exception $e) {
            $this->logger->error('Report fetch error: ' . $e->getMessage());
            return (new Response(404))->error($e->getMessage());
        }
    }

    /**
     * GET /reports/:id/full
     */
    public function getFull(Request $request, string $id): Response
    {
        try {
            $user = $request->getUser();
            $data = $this->reportService->getReportWithAnalysis($id, $user->sub, $user->role);
            return (new Response(200))->success($data);
        } catch (\Exception $e) {
            $this->logger->error('Report fetch error: ' . $e->getMessage());
            return (new Response(404))->error($e->getMessage());
        }
    }

    /**
     * GET /reports
     */
    public function list(Request $request): Response
    {
        try {
            $user = $request->getUser();
            $limit = min((int) $request->getQuery('limit', 50), 100);
            $offset = (int) $request->getQuery('offset', 0);

            if ($user->role === 'MANAGER') {
                $reports = $this->reportService->listReportsForManager($user->sub, $limit, $offset);
            } elseif ($user->role === 'OWNER') {
                $reports = $this->reportService->listAllReports($limit, $offset);
            } else {
                return (new Response(403))->error('Insufficient permissions');
            }

            return (new Response(200))->success($reports);
        } catch (\Exception $e) {
            $this->logger->error('Report list error: ' . $e->getMessage());
            return (new Response(400))->error($e->getMessage());
        }
    }
}
