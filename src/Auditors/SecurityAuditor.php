<?php

namespace MartinLechene\AuditSuite\Auditors;

use MartinLechene\AuditSuite\Services\RuleEngine;

class SecurityAuditor extends BaseAuditor
{
    public function getName(): string
    {
        return 'Security Auditor';
    }

    public function getCategory(): string
    {
        return 'security';
    }

    public function getDescription(): string
    {
        return 'Audit Sécurité : headers, CSRF, SQL Injection, XSS, secrets, etc.';
    }
}

