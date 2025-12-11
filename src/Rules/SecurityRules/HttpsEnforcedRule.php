<?php

namespace MartinLechene\AuditSuite\Rules\SecurityRules;

use MartinLechene\AuditSuite\Models\AuditResult;
use MartinLechene\AuditSuite\Rules\BaseRule;
use MartinLechene\AuditSuite\Services\AuditContext;

class HttpsEnforcedRule extends BaseRule
{
    public function getName(): string
    {
        return 'security.https_enforced';
    }

    public function getDescription(): string
    {
        return 'Vérifie que HTTPS est forcé en production';
    }

    public function getSeverity(): string
    {
        return 'high';
    }

    public function getCategory(): string
    {
        return 'security';
    }

    public function check(AuditContext $context): AuditResult
    {
        $appUrl = config('app.url', '');
        $forceHttps = config('app.force_https', false);
        $isProduction = app()->environment('production');

        if ($isProduction && !$forceHttps && !str_starts_with($appUrl, 'https://')) {
            return AuditResult::failed($this->getName())
                ->withMessage('HTTPS n\'est pas forcé en production')
                ->withEvidence([
                    'app_url' => $appUrl,
                    'force_https' => $forceHttps,
                    'environment' => app()->environment(),
                ])
                ->withScore(0);
        }

        if ($isProduction && str_starts_with($appUrl, 'https://')) {
            return AuditResult::passed($this->getName())
                ->withEvidence(['app_url' => $appUrl]);
        }

        return AuditResult::warning($this->getName())
            ->withMessage('HTTPS non vérifié (environnement non-production)')
            ->withScore(70);
    }

    public function getFix(): ?string
    {
        return 'Configurez HTTPS en production en définissant APP_URL avec https:// et en activant le middleware de redirection HTTPS.';
    }

    public function getDocumentation(): ?string
    {
        return 'https://laravel.com/docs/security#enforcing-https';
    }
}

