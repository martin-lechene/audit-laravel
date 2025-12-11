<?php

namespace MartinLechene\AuditSuite\Rules\CodeQualityRules;

use MartinLechene\AuditSuite\Models\AuditResult;
use MartinLechene\AuditSuite\Rules\BaseRule;
use MartinLechene\AuditSuite\Services\AuditContext;

class Psr12ComplianceRule extends BaseRule
{
    public function getName(): string
    {
        return 'quality.psr12_compliance';
    }

    public function getDescription(): string
    {
        return 'Vérifie la conformité PSR-12 du code';
    }

    public function getSeverity(): string
    {
        return 'low';
    }

    public function getCategory(): string
    {
        return 'code_quality';
    }

    public function check(AuditContext $context): AuditResult
    {
        // Vérifier si PHP_CodeSniffer ou PHP CS Fixer est configuré
        $composerPath = base_path('composer.json');
        $phpcsConfig = base_path('.phpcs.xml');
        $phpcsDist = base_path('.phpcs.xml.dist');
        $phpcsfixer = base_path('.php-cs-fixer.php');
        $phpcsfixerDist = base_path('.php-cs-fixer.dist.php');

        $hasPhpcs = file_exists($phpcsConfig) || file_exists($phpcsDist);
        $hasPhpcsfixer = file_exists($phpcsfixer) || file_exists($phpcsfixerDist);

        if ($hasPhpcs || $hasPhpcsfixer) {
            return AuditResult::passed($this->getName())
                ->withEvidence([
                    'phpcs_configured' => $hasPhpcs,
                    'phpcsfixer_configured' => $hasPhpcsfixer,
                ]);
        }

        // Vérifier dans composer.json
        if (file_exists($composerPath)) {
            $composer = json_decode(file_get_contents($composerPath), true);
            $devDependencies = $composer['require-dev'] ?? [];
            
            $hasCodeSniffer = isset($devDependencies['squizlabs/php_codesniffer']) ||
                             isset($devDependencies['php-cs-fixer/shim']);
            
            if ($hasCodeSniffer) {
                return AuditResult::warning($this->getName())
                    ->withMessage('Outils PSR-12 installés mais pas de fichier de configuration')
                    ->withScore(60);
            }
        }

        return AuditResult::failed($this->getName())
            ->withMessage('Aucun outil de vérification PSR-12 configuré')
            ->withScore(0);
    }

    public function getFix(): ?string
    {
        return 'Installez et configurez PHP_CodeSniffer ou PHP CS Fixer pour vérifier la conformité PSR-12.';
    }

    public function getDocumentation(): ?string
    {
        return 'https://www.php-fig.org/psr/psr-12/';
    }
}

