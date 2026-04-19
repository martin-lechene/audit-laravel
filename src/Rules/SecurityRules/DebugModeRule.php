<?php

namespace MartinLechene\AuditSuite\Rules\SecurityRules;

use MartinLechene\AuditSuite\Models\AuditResult;
use MartinLechene\AuditSuite\Rules\BaseRule;
use MartinLechene\AuditSuite\Services\AuditContext;

class DebugModeRule extends BaseRule
{
    public function getName(): string
    {
        return 'security.debug_mode';
    }

    public function getDescription(): string
    {
        return 'Vérifie que le mode debug est désactivé en production';
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
        $debug = config('app.debug', false);
        $isProduction = app()->environment('production');

        if ($isProduction && $debug) {
            return AuditResult::failed($this->getName())
                ->withMessage('APP_DEBUG est activé en production — les traces d\'erreur sont exposées')
                ->withEvidence(['app.debug' => true, 'environment' => 'production'])
                ->withScore(0);
        }

        if (!$isProduction && $debug) {
            return AuditResult::warning($this->getName())
                ->withMessage('APP_DEBUG est activé (environnement non-production)')
                ->withScore(80);
        }

        return AuditResult::passed($this->getName())
            ->withEvidence(['app.debug' => false]);
    }

    public function getFix(): ?string
    {
        return 'Définissez APP_DEBUG=false dans votre fichier .env de production.';
    }

    public function getDocumentation(): ?string
    {
        return 'https://laravel.com/docs/configuration#debug-mode';
    }
}
