<?php

namespace MartinLechene\AuditSuite\Reporters;

use MartinLechene\AuditSuite\Contracts\ReporterContract;
use MartinLechene\AuditSuite\Models\AuditSession;

class JsonReporter implements ReporterContract
{
    public function generate(AuditSession $session): string
    {
        $findings = $session->findings()->orderBy('severity', 'desc')->orderBy('score', 'asc')->get();

        $data = [
            'project_name' => $session->project_name,
            'environment' => $session->environment,
            'started_at' => $session->started_at->toIso8601String(),
            'completed_at' => $session->completed_at?->toIso8601String(),
            'status' => $session->status,
            'overall_score' => $session->overall_score,
            'total_findings' => $session->total_findings,
            'findings_by_severity' => $session->findings_by_severity,
            'findings_by_category' => $session->findings_by_category,
            'findings' => $findings->map(function ($finding) {
                return [
                    'id' => $finding->id,
                    'category' => $finding->category,
                    'rule_name' => $finding->rule_name,
                    'severity' => $finding->severity,
                    'title' => $finding->title,
                    'description' => $finding->description,
                    'affected_items' => $finding->affected_items,
                    'fix_suggestion' => $finding->fix_suggestion,
                    'evidence' => $finding->evidence,
                    'score' => $finding->score,
                    'created_at' => $finding->created_at->toIso8601String(),
                ];
            })->toArray(),
        ];

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function save(string $content, string $path): bool
    {
        return file_put_contents($path, $content) !== false;
    }
}

