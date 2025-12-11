<?php

namespace MartinLechene\AuditSuite\Rules\PerformanceRules;

use MartinLechene\AuditSuite\Models\AuditResult;
use MartinLechene\AuditSuite\Rules\BaseRule;
use MartinLechene\AuditSuite\Services\AuditContext;
use Illuminate\Support\Facades\DB;

class SlowQueriesRule extends BaseRule
{
    public function getName(): string
    {
        return 'performance.slow_queries';
    }

    public function getDescription(): string
    {
        return 'Détecte les requêtes SQL lentes';
    }

    public function getSeverity(): string
    {
        return 'medium';
    }

    public function getCategory(): string
    {
        return 'performance';
    }

    public function check(AuditContext $context): AuditResult
    {
        try {
            // Vérifier si le logging des requêtes lentes est activé
            $logPath = storage_path('logs/laravel.log');
            $slowQueries = [];
            
            if (file_exists($logPath)) {
                $logContent = file_get_contents($logPath);
                // Chercher des patterns de requêtes lentes dans les logs
                if (preg_match_all('/query.*took.*(\d+\.?\d*)\s*(ms|s)/i', $logContent, $matches)) {
                    $slowQueries = $matches[0];
                }
            }

            // Vérifier la configuration de slow query log
            $slowQueryTime = config('database.slow_query_time', 1000);
            
            if (count($slowQueries) > 10) {
                return AuditResult::failed($this->getName())
                    ->withMessage(sprintf('%d requêtes lentes détectées dans les logs', count($slowQueries)))
                    ->withEvidence([
                        'slow_queries_count' => count($slowQueries),
                        'slow_query_time_threshold' => $slowQueryTime,
                    ])
                    ->withScore(max(0, 100 - count($slowQueries)));
            }

            return AuditResult::passed($this->getName())
                ->withEvidence(['slow_queries_count' => count($slowQueries)]);
        } catch (\Throwable $e) {
            return AuditResult::warning($this->getName())
                ->withMessage('Impossible de vérifier les requêtes lentes: ' . $e->getMessage())
                ->withScore(50);
        }
    }

    public function getFix(): ?string
    {
        return 'Activez le logging des requêtes lentes et optimisez les requêtes qui prennent plus de 1 seconde.';
    }

    public function getDocumentation(): ?string
    {
        return 'https://laravel.com/docs/queries#monitoring-query-performance';
    }
}

