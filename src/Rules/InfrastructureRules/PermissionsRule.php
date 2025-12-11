<?php

namespace MartinLechene\AuditSuite\Rules\InfrastructureRules;

use MartinLechene\AuditSuite\Models\AuditResult;
use MartinLechene\AuditSuite\Rules\BaseRule;
use MartinLechene\AuditSuite\Services\AuditContext;

class PermissionsRule extends BaseRule
{
    public function getName(): string
    {
        return 'infrastructure.permissions';
    }

    public function getDescription(): string
    {
        return 'Vérifie les permissions des dossiers storage et bootstrap/cache';
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
        $issues = [];
        
        // Utiliser les helpers Laravel (disponibles globalement)
        $storagePath = \storage_path();
        $bootstrapCachePath = \bootstrap_path('cache');

        // Vérifier storage
        if (is_dir($storagePath)) {
            $storageWritable = is_writable($storagePath);
            if (!$storageWritable) {
                $issues[] = 'storage';
            }
        }

        // Vérifier bootstrap/cache
        if (is_dir($bootstrapCachePath)) {
            $cacheWritable = is_writable($bootstrapCachePath);
            if (!$cacheWritable) {
                $issues[] = 'bootstrap/cache';
            }
        }

        if (!empty($issues)) {
            return AuditResult::failed($this->getName())
                ->withMessage('Dossiers non accessibles en écriture: ' . implode(', ', $issues))
                ->withEvidence(['issues' => $issues])
                ->withScore(0);
        }

        return AuditResult::passed($this->getName())
            ->withEvidence([
                'storage_writable' => true,
                'bootstrap_cache_writable' => true,
            ]);
    }

    public function getFix(): ?string
    {
        return 'Exécutez: chmod -R 775 storage bootstrap/cache (ou équivalent selon votre système).';
    }

    public function getDocumentation(): ?string
    {
        return 'https://laravel.com/docs/installation#permissions';
    }
}

    