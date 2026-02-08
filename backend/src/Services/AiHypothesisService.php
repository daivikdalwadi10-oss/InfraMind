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
 * AI hypothesis generation service.
 */
class AiHypothesisService
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
            'timeout' => 20,
        ]);
    }

    public function generateHypotheses(string $analysisId, string $userId, string $role): array
    {
        $analysis = $this->analysisRepository->findById($analysisId);
        if (!$analysis) {
            throw new NotFoundException('Analysis not found');
        }

        if ($role === 'EMPLOYEE' && $analysis->employeeId !== $userId) {
            throw new AuthorizationException('You can only generate hypotheses for your own analyses');
        }
        if ($role === 'MANAGER' && !$this->teamRepository->isManagerOfEmployee($userId, $analysis->employeeId)) {
            throw new AuthorizationException('You can only generate hypotheses for your teams');
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
        $hypotheses = $this->parseHypotheses($text);

        $stored = $this->aiOutputRepository->create(
            $analysisId,
            AiOutputType::HYPOTHESES,
            ['hypotheses' => $hypotheses],
            'AI'
        );

        $this->auditRepository->log('AI_OUTPUT', $analysisId, 'HYPOTHESES_GENERATED', $userId, [
            'outputId' => $stored['id'] ?? null,
        ]);

        $this->logger->info("AI hypotheses generated for analysis: $analysisId");

        return $stored;
    }

    private function buildPrompt(array $analysis): string
    {
        $context = [
            'symptoms' => $analysis['symptoms'] ?? [],
            'signals' => $analysis['signals'] ?? [],
            'environment' => $analysis['environmentContext'] ?? [],
            'timeline' => $analysis['timelineEvents'] ?? [],
            'dependencies' => $analysis['dependencyImpact'] ?? [],
            'risks' => $analysis['riskClassification'] ?? [],
        ];

        return "You are a systems engineering analyst. Generate hypotheses as JSON only.\n" .
            "Return a JSON array of objects with fields: text (string), confidence (number 0-100), evidence (array of strings).\n" .
            "No markdown, no extra keys, no explanations.\n" .
            "Input context: " . json_encode($context);
    }

    private function parseHypotheses(string $text): array
    {
        $trimmed = trim($text);
        $decoded = json_decode($trimmed, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Failed to parse AI output as JSON array');
        }

        $normalized = [];
        foreach ($decoded as $item) {
            if (!is_array($item)) {
                continue;
            }
            $normalized[] = [
                'text' => (string) ($item['text'] ?? ''),
                'confidence' => (int) ($item['confidence'] ?? 0),
                'evidence' => array_values(array_filter($item['evidence'] ?? [], fn ($e) => is_string($e) && $e !== '')),
            ];
        }

        return $normalized;
    }
}
