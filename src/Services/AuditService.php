<?php

namespace MartinLechene\AuditSuite\Services;

use MartinLechene\AuditSuite\Contracts\AuditorContract;
use MartinLechene\AuditSuite\Events\AuditCompleted;
use MartinLechene\AuditSuite\Events\AuditFailed;
use MartinLechene\AuditSuite\Events\AuditStarted;
use MartinLechene\AuditSuite\Events\FindingDetected;
use MartinLechene\AuditSuite\Models\AuditSession;
use MartinLechene\AuditSuite\Models\Finding;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;

class AuditService
{
    private RuleEngine $ruleEngine;
    private CacheManager $cacheManager;
    private array $auditors = [];

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
     * Exécute un audit complet
     */
    public function runAudit(array $options = []): AuditSession
    {
        $session = $this->createSession($options);
        
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
                        $finding = $this->createFinding($session, $result);
                        $findings[] = $finding;
                        
                        Event::dispatch(new FindingDetected($finding, $session));
                    }
                    
                    $scores[$category][] = $result->getScore();
                }
            }

            $overallScore = $this->calculateOverallScore($scores);
            $this->completeSession($session, $findings, $overallScore);

            Event::dispatch(new AuditCompleted($session));

            return $session;
        } catch (\Throwable $e) {
            $session->update(['status' => 'failed']);
            Event::dispatch(new AuditFailed($session, $e));
            throw $e;
        }
    }

    private function createSession(array $options): AuditSession
    {
        return AuditSession::create([
            'project_name' => config('app.name', 'Laravel Project'),
            'environment' => app()->environment(),
            'started_at' => now(),
            'status' => 'running',
            'total_findings' => 0,
            'overall_score' => 0,
            'findings_by_severity' => [],
            'findings_by_category' => [],
        ]);
    }

    private function createFinding(AuditSession $session, $result): Finding
    {
        $rule = $this->ruleEngine->getRule($result->getRuleName());
        
        return Finding::create([
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
        ]);
    }

    private function completeSession(AuditSession $session, array $findings, float $overallScore): void
    {
        $findingsBySeverity = collect($findings)->groupBy('severity')->map->count()->toArray();
        $findingsByCategory = collect($findings)->groupBy('category')->map->count()->toArray();

        $session->update([
            'status' => 'completed',
            'completed_at' => now(),
            'total_findings' => count($findings),
            'overall_score' => $overallScore,
            'findings_by_severity' => $findingsBySeverity,
            'findings_by_category' => $findingsByCategory,
        ]);
    }

    private function getCategoriesToAudit(?string $only, ?string $except): array
    {
        $allCategories = ['seo', 'security', 'performance', 'database', 'code_quality', 'infrastructure'];

        if ($only) {
            $onlyCategories = explode(',', $only);
            return array_intersect($allCategories, array_map('trim', $onlyCategories));
        }

        if ($except) {
            $exceptCategories = explode(',', $except);
            return array_diff($allCategories, array_map('trim', $exceptCategories));
        }

        return array_filter($allCategories, function ($category) {
            return config("audit-suite.auditors.{$category}", true);
        });
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

