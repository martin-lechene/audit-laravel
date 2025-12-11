<?php

namespace MartinLechene\AuditSuite\Tests\Unit;

use MartinLechene\AuditSuite\Rules\SeoRules\MetaDescriptionRule;
use MartinLechene\AuditSuite\Services\RuleEngine;
use PHPUnit\Framework\TestCase;

class RuleEngineTest extends TestCase
{
    private RuleEngine $ruleEngine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ruleEngine = new RuleEngine();
    }

    public function test_can_register_rule(): void
    {
        $rule = new MetaDescriptionRule();
        $this->ruleEngine->registerRule($rule);

        $this->assertTrue($this->ruleEngine->hasRule('seo.meta_description'));
    }

    public function test_can_get_rule_by_name(): void
    {
        $rule = new MetaDescriptionRule();
        $this->ruleEngine->registerRule($rule);

        $retrieved = $this->ruleEngine->getRule('seo.meta_description');

        $this->assertNotNull($retrieved);
        $this->assertEquals('seo.meta_description', $retrieved->getName());
    }

    public function test_can_get_rules_by_category(): void
    {
        $rule = new MetaDescriptionRule();
        $this->ruleEngine->registerRule($rule);

        $rules = $this->ruleEngine->getRulesByCategory('seo');

        $this->assertCount(1, $rules);
        $this->assertEquals('seo.meta_description', $rules->first()->getName());
    }

    public function test_returns_empty_collection_for_unknown_category(): void
    {
        $rules = $this->ruleEngine->getRulesByCategory('unknown');

        $this->assertCount(0, $rules);
    }
}

