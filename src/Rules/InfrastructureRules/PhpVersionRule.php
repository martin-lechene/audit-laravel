<?php

namespace MartinLechene\AuditSuite\Rules\InfrastructureRules;

use MartinLechene\AuditSuite\Models\AuditResult;
use MartinLechene\AuditSuite\Rules\BaseRule;
use MartinLechene\AuditSuite\Services\AuditContext;

class PhpVersionRule extends BaseRule
{
    public function getName(): string
    {
        return 'infrastructure.php_version';
    }

    public function getDescription(): string
    {
        return 'Vérifie la version PHP';
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
        $currentVersion = PHP_VERSION;
        $requiredVersion = '8.1.0';
        
        // Vérifier dans composer.json
        $composerPath = base_path('composer.json');
        if (file_exists($composerPath)) {
            $composer = json_decode(file_get_contents($composerPath), true);
            $phpRequirement = $composer['require']['php'] ?? null;
            
            if ($phpRequirement) {
                // Extraire la version minimale (simplifié)
                if (preg_match('/(\d+\.\d+)/', $phpRequirement, $matches)) {
                    $requiredVersion = $matches[1] . '.0';
                }
            }
        }

        if (version_compare($currentVersion, $requiredVersion, '<')) {
            return AuditResult::failed($this->getName())
                ->withMessage(sprintf('Version PHP %s requise, version actuelle: %s', $requiredVersion, $currentVersion))
                ->withEvidence([
                    'current_version' => $currentVersion,
                    'required_version' => $requiredVersion,
                ])
                ->withScore(0);
        }

        // Vérifier si la version est récente (8.1+)
        if (version_compare($currentVersion, '8.1.0', '>=')) {
            return AuditResult::passed($this->getName())
                ->withEvidence([
                    'current_version' => $currentVersion,
                    'required_version' => $requiredVersion,
                ]);
        }

        return AuditResult::warning($this->getName())
            ->withMessage(sprintf('Version PHP %s détectée, considérez une mise à jour', $currentVersion))
            ->withScore(70);
    }

    public function getFix(): ?string
    {
        return 'Mettez à jour PHP vers la version 8.1 ou supérieure.';
    }

    public function getDocumentation(): ?string
    {
        return 'https://www.php.net/supported-versions.php';
    }
}

