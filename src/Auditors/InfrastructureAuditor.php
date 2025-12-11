<?php

namespace MartinLechene\AuditSuite\Auditors;

use MartinLechene\AuditSuite\Services\RuleEngine;

class InfrastructureAuditor extends BaseAuditor
{
    public function getName(): string
    {
        return 'Infrastructure Auditor';
    }

    public function getCategory(): string
    {
        return 'infrastructure';
    }

    public function getDescription(): string
    {
        return 'Audit Infrastructure : PHP version, extensions, permissions, SSL, etc.';
    }
}

