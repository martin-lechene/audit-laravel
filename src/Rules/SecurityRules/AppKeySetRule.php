<?php

namespace MartinLechene\AuditSuite\Rules\SecurityRules;

use MartinLechene\AuditSuite\Models\AuditResult;
use MartinLechene\AuditSuite\Rules\BaseRule;
use MartinLechene\AuditSuite\Services\AuditContext;

class AppKeySetRule extends BaseRule
{
    public function getName(): string
    {
        return 'security.app_key_set';
    }

    public function getDescription(): string
    {
        return 'Vérifie que la clé d\'application APP_KEY est définie';
    }

    public function getSeverity(): string
    {
        return 'critical';
    }

    public function getCategory(): string
    {
        return 'security';
    }

    public function check(AuditContext $context): AuditResult
    {
        $key = config('app.key', '');

        if (empty($key)) {
            return AuditResult::failed($this->getName())
                ->withMessage('APP_KEY n\'est pas définie — les données chiffrées sont à risque')
                ->withScore(0);
        }

        // Warn if the key looks like it was never regenerated (placeholder value)
        if (str_contains($key, 'SomeRandomString') || str_contains($key, 'example')) {
            return AuditResult::warning($this->getName())
                ->withMessage('APP_KEY semble utiliser une valeur par défaut — régénérez-la')
                ->withScore(30);
        }

        return AuditResult::passed($this->getName());
    }

    public function getFix(): ?string
    {
        return 'Exécutez php artisan key:generate pour générer une clé d\'application sécurisée.';
    }

    public function getDocumentation(): ?string
    {
        return 'https://laravel.com/docs/encryption#configuration';
    }
}
