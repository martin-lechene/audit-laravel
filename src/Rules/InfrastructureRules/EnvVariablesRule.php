<?php

namespace MartinLechene\AuditSuite\Rules\InfrastructureRules;

use MartinLechene\AuditSuite\Models\AuditResult;
use MartinLechene\AuditSuite\Rules\BaseRule;
use MartinLechene\AuditSuite\Services\AuditContext;

class EnvVariablesRule extends BaseRule
{
    /** @var string[] Required environment variables for every environment */
    private const REQUIRED = ['APP_KEY', 'APP_URL', 'APP_ENV'];

    /** @var string[] Additional variables required in production */
    private const REQUIRED_PRODUCTION = ['DB_CONNECTION', 'DB_HOST', 'DB_DATABASE'];

    public function getName(): string
    {
        return 'infrastructure.env_variables';
    }

    public function getDescription(): string
    {
        return 'Vérifie que les variables d\'environnement essentielles sont définies';
    }

    public function getSeverity(): string
    {
        return 'high';
    }

    public function getCategory(): string
    {
        return 'infrastructure';
    }

    public function check(AuditContext $context): AuditResult
    {
        $missing = [];
        $required = self::REQUIRED;

        if (app()->environment('production')) {
            $required = array_merge($required, self::REQUIRED_PRODUCTION);
        }

        foreach ($required as $var) {
            if (empty(env($var))) {
                $missing[] = $var;
            }
        }

        if (!empty($missing)) {
            return AuditResult::failed($this->getName())
                ->withMessage('Variables d\'environnement manquantes ou vides: ' . implode(', ', $missing))
                ->withEvidence(['missing' => $missing])
                ->withScore(max(0, 100 - count($missing) * 20));
        }

        return AuditResult::passed($this->getName())
            ->withEvidence(['checked' => $required]);
    }

    public function getFix(): ?string
    {
        return 'Assurez-vous que toutes les variables listées sont définies dans votre fichier .env.';
    }

    public function getDocumentation(): ?string
    {
        return 'https://laravel.com/docs/configuration#environment-configuration';
    }
}
