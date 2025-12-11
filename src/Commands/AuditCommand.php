<?php

namespace MartinLechene\AuditSuite\Commands;

use MartinLechene\AuditSuite\Reporters\ConsoleReporter;
use MartinLechene\AuditSuite\Reporters\HtmlReporter;
use MartinLechene\AuditSuite\Reporters\JsonReporter;
use MartinLechene\AuditSuite\Reporters\MarkdownReporter;
use MartinLechene\AuditSuite\Services\AuditService;
use Illuminate\Console\Command;

class AuditCommand extends Command
{
    protected $signature = 'audit:run 
                            {--category= : Filtrer par catégorie (seo, security, performance, database, quality, infrastructure)}
                            {--severity= : Filtrer par sévérité (critical, high, medium, low, info)}
                            {--format=console : Format de sortie (console, json, html, markdown)}
                            {--store : Sauvegarder les résultats en base de données}
                            {--historic : Comparer avec les audits précédents}
                            {--output= : Fichier de sortie}
                            {--fix : Appliquer les corrections automatiques}
                            {--only= : Exécuter uniquement certains auditors}
                            {--except= : Exclure certains auditors}';

    protected $description = 'Exécute un audit complet du projet Laravel';

    public function handle(AuditService $auditService): int
    {
        if (!config('audit-suite.enabled', true)) {
            $this->error('Audit suite is disabled in configuration.');
            return Command::FAILURE;
        }

        $this->info('Starting audit...');
        $this->newLine();

        $options = [
            'only' => $this->option('only'),
            'except' => $this->option('except'),
        ];

        try {
            $session = $auditService->runAudit($options);

            // Filtrer les findings si nécessaire pour l'affichage
            $findingsQuery = $session->findings();
            
            if ($this->option('category')) {
                $categories = explode(',', $this->option('category'));
                $findingsQuery->whereIn('category', array_map('trim', $categories));
            }

            if ($this->option('severity')) {
                $severities = explode(',', $this->option('severity'));
                $findingsQuery->whereIn('severity', array_map('trim', $severities));
            }

            // Les findings sont déjà chargés dans la session, on les utilise directement
            // Le filtrage ci-dessus n'affecte que l'affichage si nécessaire

            // Générer le rapport
            $format = $this->option('format') ?? 'console';
            $reporter = $this->getReporter($format);
            $report = $reporter->generate($session);

            // Afficher ou sauvegarder
            if ($format === 'console') {
                $this->line($report);
            } else {
                $outputPath = $this->option('output') ?? 
                    storage_path('audits/audit_' . $session->id . '.' . $format);
                
                $dir = dirname($outputPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }

                if ($reporter->save($report, $outputPath)) {
                    $this->info("Report saved to: {$outputPath}");
                } else {
                    $this->error("Failed to save report to: {$outputPath}");
                }
            }

            // Sauvegarder en base si demandé
            if ($this->option('store')) {
                // Déjà sauvegardé par AuditService
                $this->info("Results stored in database (Session ID: {$session->id})");
            }

            // Comparaison historique
            if ($this->option('historic')) {
                $this->showHistoricComparison($session);
            }

            // Résumé
            $this->newLine();
            $this->info("Audit completed!");
            $this->info("Overall Score: " . number_format($session->overall_score, 2) . "/100");
            $this->info("Total Findings: " . $session->total_findings);

            // Score interpretation
            $this->newLine();
            $this->displayScoreInterpretation($session->overall_score);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Audit failed: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    private function getReporter(string $format)
    {
        return match ($format) {
            'json' => new JsonReporter(),
            'html' => new HtmlReporter(),
            'markdown' => new MarkdownReporter(),
            default => new ConsoleReporter(),
        };
    }

    private function showHistoricComparison($currentSession)
    {
        $previousSession = \MartinLechene\AuditSuite\Models\AuditSession::where('id', '<', $currentSession->id)
            ->orderBy('id', 'desc')
            ->first();

        if (!$previousSession) {
            $this->warn('No previous audit found for comparison.');
            return;
        }

        $this->newLine();
        $this->info('Historic Comparison:');
        $this->table(
            ['Metric', 'Previous', 'Current', 'Change'],
            [
                [
                    'Overall Score',
                    number_format($previousSession->overall_score, 2),
                    number_format($currentSession->overall_score, 2),
                    $this->formatChange($currentSession->overall_score - $previousSession->overall_score),
                ],
                [
                    'Total Findings',
                    $previousSession->total_findings,
                    $currentSession->total_findings,
                    $this->formatChange($currentSession->total_findings - $previousSession->total_findings, true),
                ],
            ]
        );
    }

    private function formatChange(float $change, bool $inverted = false): string
    {
        $isPositive = $inverted ? $change < 0 : $change > 0;
        $sign = $isPositive ? '+' : '';
        $color = $isPositive ? 'green' : 'red';
        return "<fg={$color}>{$sign}" . number_format($change, 2) . "</>";
    }

    private function displayScoreInterpretation(float $score): void
    {
        $interpretation = match (true) {
            $score >= 90 => ['Excellent', 'green'],
            $score >= 80 => ['Très Bon', 'green'],
            $score >= 70 => ['Bon', 'yellow'],
            $score >= 60 => ['Acceptable', 'yellow'],
            $score >= 50 => ['Problématique', 'red'],
            default => ['Critique', 'red'],
        };

        $this->line("Interpretation: <fg={$interpretation[1]}>{$interpretation[0]}</>");
    }
}

