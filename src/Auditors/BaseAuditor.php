<?php

namespace MartinLechene\AuditSuite\Auditors;

use MartinLechene\AuditSuite\Contracts\AuditorContract;
use MartinLechene\AuditSuite\Services\AuditContext;
use MartinLechene\AuditSuite\Services\RuleEngine;
use Illuminate\Support\Facades\Log;

abstract class BaseAuditor implements AuditorContract
{
    protected RuleEngine $ruleEngine;

    public function __construct(RuleEngine $ruleEngine)
    {
        $this->ruleEngine = $ruleEngine;
    }

    public function audit(AuditContext $context): array
    {
        $rules = $this->ruleEngine->getRulesByCategory($this->getCategory());
        $results = [];

        foreach ($rules as $rule) {
            try {
                $result = $rule->check($context);
                $results[] = $result;
            } catch (\Throwable $e) {
                Log::error("Error executing rule {$rule->getName()}: " . $e->getMessage());
            }
        }

        return $results;
    }

    public function getRules(): array
    {
        return $this->ruleEngine->getRulesByCategory($this->getCategory())->toArray();
    }
}

