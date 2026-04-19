<?php

namespace MartinLechene\AuditSuite\Reporters;

use MartinLechene\AuditSuite\Contracts\ReporterContract;
use MartinLechene\AuditSuite\Models\AuditSession;
use Illuminate\Support\Str;

class ConsoleReporter implements ReporterContract
{
    public function generate(AuditSession $session): string
    {
        $output = [];
        $output[] = "\n" . str_repeat('=', 80);
        $output[] = "  AUDIT REPORT - " . strtoupper($session->project_name);
        $output[] = str_repeat('=', 80);
        $output[] = "";
        $output[] = "Environment: " . $session->environment;
        $output[] = "Started at: " . $session->started_at->format('Y-m-d H:i:s');
        $output[] = "Completed at: " . ($session->completed_at?->format('Y-m-d H:i:s') ?? 'N/A');
        $output[] = "Status: " . strtoupper($session->status);
        $output[] = "";
        $output[] = "Overall Score: " . number_format($session->overall_score, 2) . "/100";
        $output[] = "Total Findings: " . $session->total_findings;
        $output[] = "";

        // Findings by severity
        if (!empty($session->findings_by_severity)) {
            $output[] = "Findings by Severity:";
            foreach ($session->findings_by_severity as $severity => $count) {
                $output[] = "  - " . strtoupper($severity) . ": " . $count;
            }
            $output[] = "";
        }

        // Findings by category
        if (!empty($session->findings_by_category)) {
            $output[] = "Findings by Category:";
            foreach ($session->findings_by_category as $category => $count) {
                $output[] = "  - " . ucfirst($category) . ": " . $count;
            }
            $output[] = "";
        }

        // Detailed findings (pre-sorted by AuditService)
        $findings = $session->findings;
        
        if ($findings->count() > 0) {
            $output[] = str_repeat('-', 80);
            $output[] = "DETAILED FINDINGS";
            $output[] = str_repeat('-', 80);
            $output[] = "";

            foreach ($findings as $finding) {
                $output[] = "[" . strtoupper($finding->severity) . "] " . $finding->title;
                $output[] = "  Rule: " . $finding->rule_name;
                $output[] = "  Category: " . ucfirst($finding->category);
                $output[] = "  Score: " . number_format($finding->score, 2) . "/100";
                
                if ($finding->description) {
                    $output[] = "  Description: " . Str::limit($finding->description, 100);
                }
                
                if ($finding->fix_suggestion) {
                    $output[] = "  Fix: " . Str::limit($finding->fix_suggestion, 100);
                }
                
                $output[] = "";
            }
        } else {
            $output[] = "No findings detected. Great job!";
            $output[] = "";
        }

        $output[] = str_repeat('=', 80);
        $output[] = "";

        return implode("\n", $output);
    }

    public function save(string $content, string $path): bool
    {
        return file_put_contents($path, $content) !== false;
    }
}

