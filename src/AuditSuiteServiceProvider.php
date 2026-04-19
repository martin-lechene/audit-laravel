<?php

namespace MartinLechene\AuditSuite;

use MartinLechene\AuditSuite\Auditors\CodeQualityAuditor;
use MartinLechene\AuditSuite\Auditors\DatabaseAuditor;
use MartinLechene\AuditSuite\Auditors\InfrastructureAuditor;
use MartinLechene\AuditSuite\Auditors\PerformanceAuditor;
use MartinLechene\AuditSuite\Auditors\SeoAuditor;
use MartinLechene\AuditSuite\Auditors\SecurityAuditor;
use MartinLechene\AuditSuite\Commands\AuditCommand;
use MartinLechene\AuditSuite\Rules\CodeQualityRules\Psr12ComplianceRule;
use MartinLechene\AuditSuite\Rules\CodeQualityRules\TestsExistRule;
use MartinLechene\AuditSuite\Rules\DatabaseRules\MigrationsStatusRule;
use MartinLechene\AuditSuite\Rules\InfrastructureRules\EnvVariablesRule;
use MartinLechene\AuditSuite\Rules\InfrastructureRules\PermissionsRule;
use MartinLechene\AuditSuite\Rules\InfrastructureRules\PhpVersionRule;
use MartinLechene\AuditSuite\Rules\PerformanceRules\CacheConfigRule;
use MartinLechene\AuditSuite\Rules\PerformanceRules\SlowQueriesRule;
use MartinLechene\AuditSuite\Rules\SecurityRules\AppKeySetRule;
use MartinLechene\AuditSuite\Rules\SecurityRules\CsrfProtectionRule;
use MartinLechene\AuditSuite\Rules\SecurityRules\DebugModeRule;
use MartinLechene\AuditSuite\Rules\SecurityRules\HttpsEnforcedRule;
use MartinLechene\AuditSuite\Rules\SeoRules\MetaDescriptionRule;
use MartinLechene\AuditSuite\Rules\SeoRules\RobotsTxtRule;
use MartinLechene\AuditSuite\Rules\SeoRules\SitemapRule;
use MartinLechene\AuditSuite\Services\AuditService;
use MartinLechene\AuditSuite\Services\CacheManager;
use MartinLechene\AuditSuite\Services\RuleEngine;
use Illuminate\Support\ServiceProvider;

class AuditSuiteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/audit-suite.php',
            'audit-suite'
        );

        $this->app->singleton(RuleEngine::class);
        $this->app->singleton(CacheManager::class);
        $this->app->singleton(AuditService::class, function ($app) {
            return new AuditService(
                $app->make(RuleEngine::class),
                $app->make(CacheManager::class)
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/audit-suite.php' => config_path('audit-suite.php'),
        ], 'audit-suite-config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'audit-suite-migrations');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->registerRules();
        $this->registerAuditors();

        if ($this->app->runningInConsole()) {
            $this->commands([
                AuditCommand::class,
            ]);
        }
    }

    private function registerRules(): void
    {
        $ruleEngine = $this->app->make(RuleEngine::class);

        // SEO Rules (3)
        $ruleEngine->registerRule(new MetaDescriptionRule());
        $ruleEngine->registerRule(new RobotsTxtRule());
        $ruleEngine->registerRule(new SitemapRule());

        // Security Rules (4)
        $ruleEngine->registerRule(new HttpsEnforcedRule());
        $ruleEngine->registerRule(new CsrfProtectionRule());
        $ruleEngine->registerRule(new DebugModeRule());
        $ruleEngine->registerRule(new AppKeySetRule());

        // Performance Rules (2)
        $ruleEngine->registerRule(new SlowQueriesRule());
        $ruleEngine->registerRule(new CacheConfigRule());

        // Database Rules (1)
        $ruleEngine->registerRule(new MigrationsStatusRule());

        // Code Quality Rules (2)
        $ruleEngine->registerRule(new Psr12ComplianceRule());
        $ruleEngine->registerRule(new TestsExistRule());

        // Infrastructure Rules (3)
        $ruleEngine->registerRule(new PhpVersionRule());
        $ruleEngine->registerRule(new PermissionsRule());
        $ruleEngine->registerRule(new EnvVariablesRule());
    }

    private function registerAuditors(): void
    {
        $auditService = $this->app->make(AuditService::class);
        $ruleEngine = $this->app->make(RuleEngine::class);

        $auditService->registerAuditor(new SeoAuditor($ruleEngine));
        $auditService->registerAuditor(new SecurityAuditor($ruleEngine));
        $auditService->registerAuditor(new PerformanceAuditor($ruleEngine));
        $auditService->registerAuditor(new DatabaseAuditor($ruleEngine));
        $auditService->registerAuditor(new CodeQualityAuditor($ruleEngine));
        $auditService->registerAuditor(new InfrastructureAuditor($ruleEngine));
    }
}

