<?php

namespace MartinLechene\AuditSuite\Tests\Feature;

use MartinLechene\AuditSuite\Services\AuditService;
use MartinLechene\AuditSuite\Services\RuleEngine;
use MartinLechene\AuditSuite\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    public function test_config_is_merged(): void
    {
        $this->assertTrue(config('audit-suite.enabled'));
        $this->assertSame(35, config('audit-suite.scoring.weights.security'));
    }

    public function test_fifteen_rules_are_registered(): void
    {
        $rules = $this->app->make(RuleEngine::class)->getAllRules();

        $this->assertCount(15, $rules);
        $this->assertTrue($this->app->make(RuleEngine::class)->hasRule('security.debug_mode'));
    }

    public function test_audit_service_is_bound_as_singleton(): void
    {
        $this->assertSame(
            $this->app->make(AuditService::class),
            $this->app->make(AuditService::class)
        );
    }

    public function test_audit_command_is_registered(): void
    {
        $this->assertArrayHasKey('audit:run', $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->all());
    }
}
