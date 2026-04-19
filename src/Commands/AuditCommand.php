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
                            {--category= : Filter by category (seo, security, performance, database, code_quality, infrastructure)}
                            {--severity= : Filter by severity (critical, high, medium, low, info)}
                            {--format=console : Output format (console, json, html, markdown)}
                            {--store : Persist results to the database}
                            {--historic : Compare with previous audits}
                            {--output= : Output file path}
                            {--only= : Run only these auditors (comma-separated)}
                            {--except= : Skip these auditors (comma-separated)}';

    protected $description = 'Run a full audit of the Laravel project';

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
            'store' => $this->option('store'),
        ];

        try {
            $session = $auditService->runAudit($options);

            // Apply optional display filters to the eager-loaded findings collection.
            $findings = $session->findings;

            if ($this->option('category')) {
                $categories = array_map('trim', explode(',', $this->option('category')));
                $findings = $findings->whereIn('category', $categories)->values();
            }

            if ($this->option('severity')) {
                $severities = array_map('trim', explode(',', $this->option('severity')));
                $findings = $findings->whereIn('severity', $severities)->values();
            }

            // If filters were applied, update the session relation so the reporter
            // outputs only the filtered subset.
            if ($this->option('category') || $this->option('severity')) {
                $session->setRelation('findings', $findings);
            }

            // Generate the report
            $format = $this->option('format') ?? 'console';
            $reporter = $this->getReporter($format);
            $report = $reporter->generate($session);

            if ($format === 'console') {
                $this->line($report);
            } else {
                $outputPath = $this->option('output') ??
                    storage_path('audits/audit_' . now()->format('YmdHis') . '.' . $format);

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

            if ($this->option('store')) {
                $this->info("Results stored in database (Session ID: {$session->id})");
            }

            if ($this->option('historic')) {
                $this->showHistoricComparison($session);
            }

            $this->newLine();
            $this->info("Audit completed!");
            $this->info("Overall Score: " . number_format($session->overall_score, 2) . "/100");
            $this->info("Total Findings: " . $session->total_findings);

            $this->newLine();
            $this->displayScoreInterpretation($session->overall_score);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Audit failed: " . $e->getMessage());
            if (app()->hasDebugModeEnabled()) {
                $this->error($e->getTraceAsString());
            }
            return Command::FAILURE;
        }
    }

    private function getReporter(string $format): \MartinLechene\AuditSuite\Contracts\ReporterContract
    {
        return match ($format) {
            'json' => new JsonReporter(),
            'html' => new HtmlReporter(),
            'markdown' => new MarkdownReporter(),
            default => new ConsoleReporter(),
        };
    }

    private function showHistoricComparison($currentSession): void
    {
        if (!$currentSession->exists) {
            $this->warn('Historic comparison requires --store to be enabled.');
            return;
        }

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
        $sign = $change > 0 ? '+' : '';
        $color = $isPositive ? 'green' : 'red';
        return "<fg={$color}>{$sign}" . number_format($change, 2) . "</>";
    }

    private function displayScoreInterpretation(float $score): void
    {
        [$label, $color] = match (true) {
            $score >= 90 => ['Excellent', 'green'],
            $score >= 80 => ['Very Good', 'green'],
            $score >= 70 => ['Good', 'yellow'],
            $score >= 60 => ['Acceptable', 'yellow'],
            $score >= 50 => ['Needs Attention', 'red'],
            default => ['Critical', 'red'],
        };

        $this->line("Interpretation: <fg={$color}>{$label}</>");
    }
}

