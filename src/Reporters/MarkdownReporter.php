<?php

namespace MartinLechene\AuditSuite\Reporters;

use MartinLechene\AuditSuite\Contracts\ReporterContract;
use MartinLechene\AuditSuite\Models\AuditSession;

class MarkdownReporter implements ReporterContract
{
    public function generate(AuditSession $session): string
    {
        $output = [];
        $output[] = "# Audit Report - " . $session->project_name;
        $output[] = "";
        $output[] = "**Environment:** " . $session->environment;
        $output[] = "**Started at:** " . $session->started_at->format('Y-m-d H:i:s');
        $output[] = "**Completed at:** " . ($session->completed_at?->format('Y-m-d H:i:s') ?? 'N/A');
        $output[] = "**Status:** " . strtoupper($session->status);
        $output[] = "";
        $output[] = "## Summary";
        $output[] = "";
        $output[] = "- **Overall Score:** " . number_format($session->overall_score, 2) . "/100";
        $output[] = "- **Total Findings:** " . $session->total_findings;
        $output[] = "";

        // Findings by severity
        if (!empty($session->findings_by_severity)) {
            $output[] = "### Findings by Severity";
            $output[] = "";
            foreach ($session->findings_by_severity as $severity => $count) {
                $output[] = "- **" . strtoupper($severity) . ":** " . $count;
            }
            $output[] = "";
        }

        // Findings by category
        if (!empty($session->findings_by_category)) {
            $output[] = "### Findings by Category";
            $output[] = "";
            foreach ($session->findings_by_category as $category => $count) {
                $output[] = "- **" . ucfirst($category) . ":** " . $count;
            }
            $output[] = "";
        }

        // Detailed findings
        $findings = $session->findings()->orderBy('severity', 'desc')->orderBy('score', 'asc')->get();
        
        if ($findings->count() > 0) {
            $output[] = "## Detailed Findings";
            $output[] = "";

            foreach ($findings as $finding) {
                $output[] = "### [" . strtoupper($finding->severity) . "] " . $finding->title;
                $output[] = "";
                $output[] = "- **Rule:** `" . $finding->rule_name . "`";
                $output[] = "- **Category:** " . ucfirst($finding->category);
                $output[] = "- **Score:** " . number_format($finding->score, 2) . "/100";
                $output[] = "";
                
                if ($finding->description) {
                    $output[] = "**Description:**";
                    $output[] = "";
                    $output[] = $finding->description;
                    $output[] = "";
                }
                
                if ($finding->fix_suggestion) {
                    $output[] = "**Fix Suggestion:**";
                    $output[] = "";
                    $output[] = $finding->fix_suggestion;
                    $output[] = "";
                }

                if (!empty($finding->evidence)) {
                    $output[] = "**Evidence:**";
                    $output[] = "";
                    $output[] = "```json";
                    $output[] = json_encode($finding->evidence, JSON_PRETTY_PRINT);
                    $output[] = "```";
                    $output[] = "";
                }
                
                $output[] = "---";
                $output[] = "";
            }
        } else {
            $output[] = "## Results";
            $output[] = "";
            $output[] = "✅ No findings detected. Great job!";
            $output[] = "";
        }

        return implode("\n", $output);
    }

    public function save(string $content, string $path): bool
    {
        return file_put_contents($path, $content) !== false;
    }
}

