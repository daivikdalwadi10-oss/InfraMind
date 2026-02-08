<?php

declare(strict_types=1);

namespace InfraMind\Services;

use GuzzleHttp\Client;
use InfraMind\Core\Logger;
use InfraMind\Exceptions\AuthorizationException;
use InfraMind\Exceptions\NotFoundException;
use InfraMind\Models\AiOutputType;
use InfraMind\Repositories\AiOutputRepository;
use InfraMind\Repositories\AnalysisRepository;
use InfraMind\Repositories\AuditLogRepository;
use InfraMind\Repositories\TeamRepository;

/**
 * AI report draft generation service.
 */
class AiReportService
{
    private AnalysisRepository $analysisRepository;
    private TeamRepository $teamRepository;
    private AiOutputRepository $aiOutputRepository;
    private AuditLogRepository $auditRepository;
    private Logger $logger;
    private Client $client;

    public function __construct()
    {
        $this->analysisRepository = new AnalysisRepository();
        $this->teamRepository = new TeamRepository();
        $this->aiOutputRepository = new AiOutputRepository();
        $this->auditRepository = new AuditLogRepository();
        $this->logger = Logger::getInstance();
        $this->client = new Client([
            'timeout' => 25,
        ]);
    }

    public function generateReportDraft(string $analysisId, string $userId, string $role): array
    {
        $analysis = $this->analysisRepository->findById($analysisId);
        if (!$analysis) {
            throw new NotFoundException('Analysis not found');
        }

        if ($role === 'EMPLOYEE') {
            throw new AuthorizationException('Only managers can generate reports');
        }

        if ($role === 'MANAGER' && !$this->teamRepository->isManagerOfEmployee($userId, $analysis->employeeId)) {
            throw new AuthorizationException('You can only generate reports for your teams');
        }

        if ($role === 'OWNER') {
            throw new AuthorizationException('Owners cannot access raw analyses');
        }

        $apiKey = $_ENV['GENKIT_API_KEY'] ?? '';
        if ($apiKey === '') {
            throw new \RuntimeException('GENKIT_API_KEY is not configured');
        }

        $prompt = $this->buildPrompt($analysis->toArray());
        $response = $this->client->post(
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent',
            [
                'query' => ['key' => $apiKey],
                'json' => [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                ],
            ]
        );

        $payload = json_decode((string) $response->getBody(), true);
        $text = $payload['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $draft = $this->parseDraft($text);

        $stored = $this->aiOutputRepository->create(
            $analysisId,
            AiOutputType::REPORT_DRAFT,
            $draft,
            'AI'
        );

        $this->auditRepository->log('AI_OUTPUT', $analysisId, 'REPORT_DRAFT_GENERATED', $userId, [
            'outputId' => $stored['id'] ?? null,
        ]);

        $this->logger->info("AI report draft generated for analysis: $analysisId");

        return $stored;
    }

    private function buildPrompt(array $analysis): string
    {
        $context = [
            'symptoms' => $analysis['symptoms'] ?? [],
            'signals' => $analysis['signals'] ?? [],
            'hypotheses' => $analysis['hypotheses'] ?? [],
            'environment' => $analysis['environmentContext'] ?? [],
            'timeline' => $analysis['timelineEvents'] ?? [],
            'dependencies' => $analysis['dependencyImpact'] ?? [],
            'risks' => $analysis['riskClassification'] ?? [],
        ];

        return "You are an incident report writer. Return JSON only.\n" .
            "Return a JSON object with keys: executiveSummary, rootCause, impact, resolution, preventionSteps.\n" .
            "No markdown, no commentary.\n" .
            "Context: " . json_encode($context);
    }

    private function parseDraft(string $text): array
    {
        $trimmed = trim($text);
        $decoded = json_decode($trimmed, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Failed to parse AI output as JSON object');
        }

        return [
            'executiveSummary' => (string) ($decoded['executiveSummary'] ?? ''),
            'rootCause' => (string) ($decoded['rootCause'] ?? ''),
            'impact' => (string) ($decoded['impact'] ?? ''),
            'resolution' => (string) ($decoded['resolution'] ?? ''),
            'preventionSteps' => (string) ($decoded['preventionSteps'] ?? ''),
        ];
    }
}
