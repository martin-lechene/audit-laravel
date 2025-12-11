<?php

namespace MartinLechene\AuditSuite\Models;

class AuditResult
{
    private string $ruleName;
    private string $status; // passed, failed, warning
    private float $score; // 0-100
    private ?array $evidence = null;
    private ?string $message = null;

    private function __construct(string $ruleName, string $status, float $score)
    {
        $this->ruleName = $ruleName;
        $this->status = $status;
        $this->score = $score;
    }

    public static function passed(string $ruleName): self
    {
        return new self($ruleName, 'passed', 100);
    }

    public static function failed(string $ruleName): self
    {
        return new self($ruleName, 'failed', 0);
    }

    public static function warning(string $ruleName): self
    {
        return new self($ruleName, 'warning', 50);
    }

    public function withScore(float $score): self
    {
        $this->score = max(0, min(100, $score));
        return $this;
    }

    public function withEvidence(array $evidence): self
    {
        $this->evidence = $evidence;
        return $this;
    }

    public function withMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function getRuleName(): string
    {
        return $this->ruleName;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getScore(): float
    {
        return $this->score;
    }

    public function getEvidence(): ?array
    {
        return $this->evidence;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function isPassed(): bool
    {
        return $this->status === 'passed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isWarning(): bool
    {
        return $this->status === 'warning';
    }
}

