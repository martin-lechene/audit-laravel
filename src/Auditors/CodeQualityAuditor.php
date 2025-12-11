<?php

namespace MartinLechene\AuditSuite\Auditors;

use MartinLechene\AuditSuite\Services\RuleEngine;

class CodeQualityAuditor extends BaseAuditor
{
    public function getName(): string
    {
        return 'Code Quality Auditor';
    }

    public function getCategory(): string
    {
        return 'code_quality';
    }

    public function getDescription(): string
    {
        return 'Audit Qualité de code : PSR-12, code mort, complexité, tests, etc.';
    }
}

