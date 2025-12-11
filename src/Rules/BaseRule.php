<?php

namespace MartinLechene\AuditSuite\Rules;

use MartinLechene\AuditSuite\Contracts\AuditRule;
use MartinLechene\AuditSuite\Services\AuditContext;

abstract class BaseRule implements AuditRule
{
    abstract public function getName(): string;
    abstract public function getDescription(): string;
    abstract public function getSeverity(): string;
    abstract public function getCategory(): string;
    abstract public function check(AuditContext $context): \MartinLechene\AuditSuite\Models\AuditResult;

    public function getFix(): ?string
    {
        return null;
    }

    public function getDocumentation(): ?string
    {
        return null;
    }
}

