<?php

namespace MartinLechene\AuditSuite\Auditors;

use MartinLechene\AuditSuite\Services\RuleEngine;

class DatabaseAuditor extends BaseAuditor
{
    public function getName(): string
    {
        return 'Database Auditor';
    }

    public function getCategory(): string
    {
        return 'database';
    }

    public function getDescription(): string
    {
        return 'Audit Base de données : migrations, index, foreign keys, etc.';
    }
}

