<?php

namespace MartinLechene\AuditSuite\Reporters;

use MartinLechene\AuditSuite\Contracts\ReporterContract;
use MartinLechene\AuditSuite\Models\AuditSession;

class HtmlReporter implements ReporterContract
{
    public function generate(AuditSession $session): string
    {
        $findings = $session->findings()->orderBy('severity', 'desc')->orderBy('score', 'asc')->get();
        
        $severityColors = [
            'critical' => '#dc3545',
            'high' => '#fd7e14',
            'medium' => '#ffc107',
            'low' => '#17a2b8',
            'info' => '#6c757d',
        ];

        $html = '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Report - ' . htmlspecialchars($session->project_name) . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
        .summary-card { background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid #007bff; }
        .summary-card h3 { margin: 0 0 10px 0; font-size: 14px; color: #666; }
        .summary-card .value { font-size: 24px; font-weight: bold; color: #333; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 3px; font-size: 12px; font-weight: bold; }
        .finding { margin: 20px 0; padding: 15px; border-left: 4px solid #ddd; background: #f8f9fa; border-radius: 4px; }
        .finding-title { font-size: 18px; font-weight: bold; margin-bottom: 10px; }
        .finding-meta { color: #666; font-size: 14px; margin: 5px 0; }
        .finding-description { margin: 10px 0; }
        .finding-fix { background: #e7f3ff; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .evidence { background: #f1f1f1; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 12px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Audit Report - ' . htmlspecialchars($session->project_name) . '</h1>
        
        <div class="summary">
            <div class="summary-card">
                <h3>Environment</h3>
                <div class="value">' . htmlspecialchars($session->environment) . '</div>
            </div>
            <div class="summary-card">
                <h3>Overall Score</h3>
                <div class="value">' . number_format($session->overall_score, 2) . '/100</div>
            </div>
            <div class="summary-card">
                <h3>Total Findings</h3>
                <div class="value">' . $session->total_findings . '</div>
            </div>
            <div class="summary-card">
                <h3>Status</h3>
                <div class="value">' . strtoupper($session->status) . '</div>
            </div>
        </div>

        <p><strong>Started at:</strong> ' . $session->started_at->format('Y-m-d H:i:s') . '</p>
        <p><strong>Completed at:</strong> ' . ($session->completed_at?->format('Y-m-d H:i:s') ?? 'N/A') . '</p>';

        if (!empty($session->findings_by_severity)) {
            $html .= '<h2>Findings by Severity</h2><table><thead><tr><th>Severity</th><th>Count</th></tr></thead><tbody>';
            foreach ($session->findings_by_severity as $severity => $count) {
                $color = $severityColors[$severity] ?? '#6c757d';
                $html .= '<tr><td><span class="badge" style="background: ' . $color . '; color: white;">' . strtoupper($severity) . '</span></td><td>' . $count . '</td></tr>';
            }
            $html .= '</tbody></table>';
        }

        if (!empty($session->findings_by_category)) {
            $html .= '<h2>Findings by Category</h2><table><thead><tr><th>Category</th><th>Count</th></tr></thead><tbody>';
            foreach ($session->findings_by_category as $category => $count) {
                $html .= '<tr><td>' . ucfirst($category) . '</td><td>' . $count . '</td></tr>';
            }
            $html .= '</tbody></table>';
        }

        if ($findings->count() > 0) {
            $html .= '<h2>Detailed Findings</h2>';
            foreach ($findings as $finding) {
                $color = $severityColors[$finding->severity] ?? '#6c757d';
                $html .= '<div class="finding" style="border-left-color: ' . $color . ';">
                    <div class="finding-title">
                        <span class="badge" style="background: ' . $color . '; color: white;">' . strtoupper($finding->severity) . '</span> ' . htmlspecialchars($finding->title) . '
                    </div>
                    <div class="finding-meta">
                        <strong>Rule:</strong> <code>' . htmlspecialchars($finding->rule_name) . '</code><br>
                        <strong>Category:</strong> ' . ucfirst($finding->category) . '<br>
                        <strong>Score:</strong> ' . number_format($finding->score, 2) . '/100
                    </div>';
                
                if ($finding->description) {
                    $html .= '<div class="finding-description"><strong>Description:</strong><br>' . nl2br(htmlspecialchars($finding->description)) . '</div>';
                }
                
                if ($finding->fix_suggestion) {
                    $html .= '<div class="finding-fix"><strong>Fix Suggestion:</strong><br>' . nl2br(htmlspecialchars($finding->fix_suggestion)) . '</div>';
                }

                if (!empty($finding->evidence)) {
                    $html .= '<div class="evidence"><strong>Evidence:</strong><br><pre>' . htmlspecialchars(json_encode($finding->evidence, JSON_PRETTY_PRINT)) . '</pre></div>';
                }
                
                $html .= '</div>';
            }
        } else {
            $html .= '<h2>Results</h2><p>✅ No findings detected. Great job!</p>';
        }

        $html .= '</div></body></html>';

        return $html;
    }

    public function save(string $content, string $path): bool
    {
        return file_put_contents($path, $content) !== false;
    }
}

