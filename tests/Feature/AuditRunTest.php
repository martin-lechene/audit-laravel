<?php

namespace MartinLechene\AuditSuite\Tests\Feature;

use MartinLechene\AuditSuite\Services\AuditService;
use MartinLechene\AuditSuite\Tests\TestCase;

class AuditRunTest extends TestCase
{
    public function test_run_audit_returns_a_scored_session_without_persisting(): void
    {
        $session = $this->app->make(AuditService::class)->runAudit(['only' => 'security']);

        $this->assertFalse($session->exists);
        $this->assertSame('completed', $session->status);
        $this->assertGreaterThanOrEqual(0, $session->overall_score);
        $this->assertLessThanOrEqual(100, $session->overall_score);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('formats')]
    public function test_audit_command_writes_a_report(string $format): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'audit_' . uniqid() . '.' . $format;

        $this->artisan('audit:run', ['--only' => 'security', '--format' => $format, '--output' => $path])
            ->assertExitCode(0);

        $this->assertFileExists($path);
        $this->assertNotSame('', (string) file_get_contents($path));

        if ($format === 'json') {
            $this->assertIsArray(json_decode((string) file_get_contents($path), true));
        }

        @unlink($path);
    }

    public static function formats(): array
    {
        return [['json'], ['html'], ['markdown']];
    }
    public function test_audit_command_fails_when_disabled(): void
    {
        config(['audit-suite.enabled' => false]);

        $this->artisan('audit:run')->assertExitCode(1);
    }
}
