<?php

namespace MartinLechene\AuditSuite\Rules\PerformanceRules;

use MartinLechene\AuditSuite\Models\AuditResult;
use MartinLechene\AuditSuite\Rules\BaseRule;
use MartinLechene\AuditSuite\Services\AuditContext;

class CacheConfigRule extends BaseRule
{
    private const PRODUCTION_UNSUITABLE = ['array', 'null'];

    public function getName(): string
    {
        return 'performance.cache_config';
    }

    public function getDescription(): string
    {
        return 'Vérifie que le driver de cache est adapté à l\'environnement';
    }

    public function getSeverity(): string
    {
        return 'medium';
    }

    public function getCategory(): string
    {
        return 'performance';
    }

    public function check(AuditContext $context): AuditResult
    {
        $driver = config('cache.default', 'file');
        $isProduction = app()->environment('production');

        if ($isProduction && in_array($driver, self::PRODUCTION_UNSUITABLE, true)) {
            return AuditResult::failed($this->getName())
                ->withMessage("Le driver de cache \"{$driver}\" n'est pas adapté à la production")
                ->withEvidence(['cache_driver' => $driver])
                ->withScore(0);
        }

        if (!$isProduction && in_array($driver, self::PRODUCTION_UNSUITABLE, true)) {
            return AuditResult::warning($this->getName())
                ->withMessage("Le driver de cache \"{$driver}\" est utilisé")
                ->withEvidence(['cache_driver' => $driver])
                ->withScore(60);
        }

        return AuditResult::passed($this->getName())
            ->withEvidence(['cache_driver' => $driver]);
    }

    public function getFix(): ?string
    {
        return 'Configurez un driver de cache persistant (redis, memcached, database) pour la production.';
    }

    public function getDocumentation(): ?string
    {
        return 'https://laravel.com/docs/cache#configuration';
    }
}
