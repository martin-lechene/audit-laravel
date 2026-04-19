<?php

namespace MartinLechene\AuditSuite\Services;

use MartinLechene\AuditSuite\Contracts\AuditorContract;
use MartinLechene\AuditSuite\Events\AuditCompleted;
use MartinLechene\AuditSuite\Events\AuditFailed;
use MartinLechene\AuditSuite\Events\AuditStarted;
use MartinLechene\AuditSuite\Events\FindingDetected;
use MartinLechene\AuditSuite\Models\AuditSession;
use MartinLechene\AuditSuite\Models\Finding;
use Illuminate\Support\Facades\Event;

class AuditService
{
    private RuleEngine $ruleEngine;
    private CacheManager $cacheManager;
    private array $auditors = [];

    /** @var array<string, int> Severity sort order (lower = more severe) */
    private const SEVERITY_ORDER = [
        'critical' => 0,
        'high' => 1,
        'medium' => 2,
        'low' => 3,
        'info' => 4,
    ];

    public function __construct(RuleEngine $ruleEngine, CacheManager $cacheManager)
    {
        $this->ruleEngine = $ruleEngine;
        $this->cacheManager = $cacheManager;
    }

    /**
     * Enregistre un auditor
     */
    public function registerAuditor(AuditorContract $auditor): void
    {
        $this->auditors[$auditor->getCategory()] = $auditor;
    }

    /**
     * Exécute un audit complet.
     *
     * @param array{only?: string|null, except?: string|null, store?: bool} $options
     */
    public function runAudit(array $options = []): AuditSession
    {
        $store = (bool) ($options['store'] ?? false);
        $session = $this->createSession($store);

        Event::dispatch(new AuditStarted($session));

        try {
            $context = new AuditContext([
                'project_path' => base_path(),
                'config' => config()->all(),
            ]);

            $only = $options['only'] ?? null;
            $except = $options['except'] ?? null;
            $categories = $this->getCategoriesToAudit($only, $except);

            $findings = [];
            $scores = [];

            foreach ($categories as $category) {
                if (!isset($this->auditors[$category])) {
                    continue;
                }

                $auditor = $this->auditors[$category];
                $results = $auditor->audit($context);

                foreach ($results as $result) {
                    if (!$result->isPassed()) {
                        $finding = $this->buildFinding($session, $result, $store);
                        $findings[] = $finding;

                        Event::dispatch(new FindingDetected($finding, $session));
                    }

                    $scores[$category][] = $result->getScore();
                }
            }

            $overallScore = $this->calculateOverallScore($scores);
            $this->completeSession($session, $findings, $overallScore, $store);

            Event::dispatch(new AuditCompleted($session));

            return $session;
        } catch (\Throwable $e) {
            $session->status = 'failed';
            if ($store && $session->exists) {
                $session->save();
            }
            Event::dispatch(new AuditFailed($session, $e));
            throw $e;
        }
    }

    /**
     * Creates a session model. Persisted to DB only when $store is true.
     */
    private function createSession(bool $store): AuditSession
    {
        $attributes = [
            'project_name' => config('app.name', 'Laravel Project'),
            'environment' => app()->environment(),
            'started_at' => now(),
            'status' => 'running',
            'total_findings' => 0,
            'overall_score' => 0,
            'findings_by_severity' => [],
            'findings_by_category' => [],
        ];

        if ($store) {
            return AuditSession::create($attributes);
        }

        return new AuditSession($attributes);
    }

    /**
     * Builds a Finding model. Persisted to DB only when $store is true.
     */
    private function buildFinding(AuditSession $session, $result, bool $store): Finding
    {
        $rule = $this->ruleEngine->getRule($result->getRuleName());

        $attributes = [
            'audit_session_id' => $session->id,
            'category' => $rule ? $rule->getCategory() : 'unknown',
            'rule_name' => $result->getRuleName(),
            'severity' => $rule ? $rule->getSeverity() : 'medium',
            'title' => $rule ? $rule->getDescription() : $result->getRuleName(),
            'description' => $result->getMessage() ?? $rule?->getDescription() ?? '',
            'affected_items' => $result->getEvidence() ?? [],
            'fix_suggestion' => $rule?->getFix(),
            'evidence' => $result->getEvidence(),
            'score' => $result->getScore(),
        ];

        if ($store && $session->exists) {
            return Finding::create($attributes);
        }

        return new Finding($attributes);
    }

    /**
     * Finalises the session, sorts findings and attaches them as an eager-loaded
     * relation so reporters work regardless of whether results were persisted.
     */
    private function completeSession(AuditSession $session, array $findings, float $overallScore, bool $store): void
    {
        $findingsBySeverity = collect($findings)->groupBy('severity')->map->count()->toArray();
        $findingsByCategory = collect($findings)->groupBy('category')->map->count()->toArray();

        $session->status = 'completed';
        $session->completed_at = now();
        $session->total_findings = count($findings);
        $session->overall_score = $overallScore;
        $session->findings_by_severity = $findingsBySeverity;
        $session->findings_by_category = $findingsByCategory;

        // Pre-sort findings so reporters don't need DB ordering queries.
        $sorted = $this->sortFindings($findings);
        $session->setRelation('findings', collect($sorted));

        if ($store && $session->exists) {
            $session->save();
        }
    }

    /**
     * Sorts findings by severity (critical first) then by score ascending.
     *
     * @param  Finding[]  $findings
     * @return Finding[]
     */
    private function sortFindings(array $findings): array
    {
        usort($findings, function (Finding $a, Finding $b) {
            $aOrder = self::SEVERITY_ORDER[$a->severity] ?? 5;
            $bOrder = self::SEVERITY_ORDER[$b->severity] ?? 5;

            if ($aOrder !== $bOrder) {
                return $aOrder - $bOrder;
            }

            return $a->score <=> $b->score;
        });

        return $findings;
    }

    private function getCategoriesToAudit(?string $only, ?string $except): array
    {
        $allCategories = ['seo', 'security', 'performance', 'database', 'code_quality', 'infrastructure'];

        if ($only) {
            $onlyCategories = explode(',', $only);
            return array_values(array_intersect($allCategories, array_map('trim', $onlyCategories)));
        }

        if ($except) {
            $exceptCategories = explode(',', $except);
            return array_values(array_diff($allCategories, array_map('trim', $exceptCategories)));
        }

        return array_values(array_filter($allCategories, function ($category) {
            return config("audit-suite.auditors.{$category}", true);
        }));
    }

    private function calculateOverallScore(array $scores): float
    {
        $weights = config('audit-suite.scoring.weights', [
            'security' => 35,
            'performance' => 25,
            'seo' => 20,
            'code_quality' => 12,
            'database' => 5,
            'infrastructure' => 3,
        ]);

        $totalScore = 0;
        $totalWeight = 0;

        foreach ($scores as $category => $categoryScores) {
            if (empty($categoryScores)) {
                continue;
            }

            $categoryAverage = array_sum($categoryScores) / count($categoryScores);
            $weight = $weights[$category] ?? 0;

            $totalScore += $categoryAverage * $weight;
            $totalWeight += $weight;
        }

        return $totalWeight > 0 ? $totalScore / $totalWeight : 0;
    }
}

