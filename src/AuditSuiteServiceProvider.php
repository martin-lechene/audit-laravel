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
use MartinLechene\AuditSuite\Rules\DatabaseRules\MigrationsStatusRule;
use MartinLechene\AuditSuite\Rules\InfrastructureRules\PermissionsRule;
use MartinLechene\AuditSuite\Rules\InfrastructureRules\PhpVersionRule;
use MartinLechene\AuditSuite\Rules\PerformanceRules\SlowQueriesRule;
use MartinLechene\AuditSuite\Rules\SecurityRules\CsrfProtectionRule;
use MartinLechene\AuditSuite\Rules\SecurityRules\HttpsEnforcedRule;
use MartinLechene\AuditSuite\Rules\SeoRules\MetaDescriptionRule;
use MartinLechene\AuditSuite\Rules\SeoRules\RobotsTxtRule;
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

        // Enregistrer les services
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
        // Publier la configuration
        $this->publishes([
            __DIR__ . '/../config/audit-suite.php' => config_path('audit-suite.php'),
        ], 'audit-suite-config');

        // Publier les migrations
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'audit-suite-migrations');

        // Charger les migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Enregistrer les règles
        $this->registerRules();

        // Enregistrer les auditors
        $this->registerAuditors();

        // Enregistrer la commande
        if ($this->app->runningInConsole()) {
            $this->commands([
                AuditCommand::class,
            ]);
        }
    }

    private function registerRules(): void
    {
        $ruleEngine = $this->app->make(RuleEngine::class);

        // SEO Rules
        $ruleEngine->registerRule(new MetaDescriptionRule());
        $ruleEngine->registerRule(new RobotsTxtRule());

        // Security Rules
        $ruleEngine->registerRule(new HttpsEnforcedRule());
        $ruleEngine->registerRule(new CsrfProtectionRule());

        // Performance Rules
        $ruleEngine->registerRule(new SlowQueriesRule());

        // Database Rules
        $ruleEngine->registerRule(new MigrationsStatusRule());

        // Code Quality Rules
        $ruleEngine->registerRule(new Psr12ComplianceRule());

        // Infrastructure Rules
        $ruleEngine->registerRule(new PhpVersionRule());
        $ruleEngine->registerRule(new PermissionsRule());
    }

    private function registerAuditors(): void
    {
        $auditService = $this->app->make(AuditService::class);
        $ruleEngine = $this->app->make(RuleEngine::class);

        // Enregistrer les auditors
        $auditService->registerAuditor(new SeoAuditor($ruleEngine));
        $auditService->registerAuditor(new SecurityAuditor($ruleEngine));
        $auditService->registerAuditor(new PerformanceAuditor($ruleEngine));
        $auditService->registerAuditor(new DatabaseAuditor($ruleEngine));
        $auditService->registerAuditor(new CodeQualityAuditor($ruleEngine));
        $auditService->registerAuditor(new InfrastructureAuditor($ruleEngine));
    }
}

