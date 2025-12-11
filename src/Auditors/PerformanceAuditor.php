<?php

namespace MartinLechene\AuditSuite\Auditors;

use MartinLechene\AuditSuite\Services\RuleEngine;

class PerformanceAuditor extends BaseAuditor
{
    public function getName(): string
    {
        return 'Performance Auditor';
    }

    public function getCategory(): string
    {
        return 'performance';
    }

    public function getDescription(): string
    {
        return 'Audit Performance : requêtes lentes, N+1, cache, mémoire, etc.';
    }
}

