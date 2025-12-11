<?php

namespace MartinLechene\AuditSuite\Tests\Unit;

use MartinLechene\AuditSuite\Models\AuditResult;
use PHPUnit\Framework\TestCase;

class AuditResultTest extends TestCase
{
    public function test_can_create_passed_result(): void
    {
        $result = AuditResult::passed('test.rule');

        $this->assertTrue($result->isPassed());
        $this->assertFalse($result->isFailed());
        $this->assertFalse($result->isWarning());
        $this->assertEquals(100, $result->getScore());
        $this->assertEquals('test.rule', $result->getRuleName());
    }

    public function test_can_create_failed_result(): void
    {
        $result = AuditResult::failed('test.rule');

        $this->assertTrue($result->isFailed());
        $this->assertFalse($result->isPassed());
        $this->assertEquals(0, $result->getScore());
    }

    public function test_can_create_warning_result(): void
    {
        $result = AuditResult::warning('test.rule');

        $this->assertTrue($result->isWarning());
        $this->assertEquals(50, $result->getScore());
    }

    public function test_can_add_evidence(): void
    {
        $result = AuditResult::failed('test.rule')
            ->withEvidence(['key' => 'value']);

        $this->assertEquals(['key' => 'value'], $result->getEvidence());
    }

    public function test_can_add_message(): void
    {
        $result = AuditResult::failed('test.rule')
            ->withMessage('Test message');

        $this->assertEquals('Test message', $result->getMessage());
    }

    public function test_can_set_custom_score(): void
    {
        $result = AuditResult::failed('test.rule')
            ->withScore(75);

        $this->assertEquals(75, $result->getScore());
    }

    public function test_score_is_clamped_between_0_and_100(): void
    {
        $result1 = AuditResult::passed('test.rule')
            ->withScore(150);
        $this->assertEquals(100, $result1->getScore());

        $result2 = AuditResult::passed('test.rule')
            ->withScore(-50);
        $this->assertEquals(0, $result2->getScore());
    }
}

