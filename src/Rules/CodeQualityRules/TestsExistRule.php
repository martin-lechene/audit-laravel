<?php

namespace MartinLechene\AuditSuite\Rules\CodeQualityRules;

use MartinLechene\AuditSuite\Models\AuditResult;
use MartinLechene\AuditSuite\Rules\BaseRule;
use MartinLechene\AuditSuite\Services\AuditContext;

class TestsExistRule extends BaseRule
{
    private const MIN_TEST_FILES = 5;

    public function getName(): string
    {
        return 'quality.tests_exist';
    }

    public function getDescription(): string
    {
        return 'Vérifie que des tests automatisés existent dans le projet';
    }

    public function getSeverity(): string
    {
        return 'medium';
    }

    public function getCategory(): string
    {
        return 'code_quality';
    }

    public function check(AuditContext $context): AuditResult
    {
        $testsPath = base_path('tests');

        if (!is_dir($testsPath)) {
            return AuditResult::failed($this->getName())
                ->withMessage('Aucun dossier tests/ trouvé')
                ->withScore(0);
        }

        $testFiles = array_merge(
            glob($testsPath . '/**/*Test.php') ?: [],
            glob($testsPath . '/*Test.php') ?: [],
            glob($testsPath . '/**/*Spec.php') ?: [],
        );

        $count = count(array_unique($testFiles));

        if ($count === 0) {
            return AuditResult::failed($this->getName())
                ->withMessage('Aucun fichier de test trouvé dans tests/')
                ->withScore(0);
        }

        if ($count < self::MIN_TEST_FILES) {
            return AuditResult::warning($this->getName())
                ->withMessage(sprintf('Seulement %d fichier(s) de test trouvé(s) (minimum recommandé: %d)', $count, self::MIN_TEST_FILES))
                ->withEvidence(['test_files_count' => $count])
                ->withScore(40);
        }

        return AuditResult::passed($this->getName())
            ->withEvidence(['test_files_count' => $count]);
    }

    public function getFix(): ?string
    {
        return 'Ajoutez des tests unitaires et des tests de fonctionnalité dans le dossier tests/. Visez une couverture d\'au moins 70%.';
    }

    public function getDocumentation(): ?string
    {
        return 'https://laravel.com/docs/testing';
    }
}
