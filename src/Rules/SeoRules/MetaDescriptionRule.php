<?php

namespace MartinLechene\AuditSuite\Rules\SeoRules;

use MartinLechene\AuditSuite\Models\AuditResult;
use MartinLechene\AuditSuite\Rules\BaseRule;
use MartinLechene\AuditSuite\Services\AuditContext;

class MetaDescriptionRule extends BaseRule
{
    public function getName(): string
    {
        return 'seo.meta_description';
    }

    public function getDescription(): string
    {
        return 'Vérifie que toutes les pages ont une meta description';
    }

    public function getSeverity(): string
    {
        return 'medium';
    }

    public function getCategory(): string
    {
        return 'seo';
    }

    public function check(AuditContext $context): AuditResult
    {
        // Cette règle devrait analyser les vues Blade pour trouver les meta descriptions
        // Pour l'instant, on fait une vérification basique
        $viewsPath = resource_path('views');
        $missingPages = [];

        if (is_dir($viewsPath)) {
            $files = glob($viewsPath . '/**/*.blade.php');
            $checked = 0;
            
            foreach ($files as $file) {
                $content = file_get_contents($file);
                if (!preg_match('/meta.*description|@section\([\'"]meta.*description[\'"]\)/i', $content)) {
                    $missingPages[] = basename($file);
                }
                $checked++;
            }

            if ($checked === 0) {
                return AuditResult::warning($this->getName())
                    ->withMessage('Aucune vue Blade trouvée')
                    ->withScore(50);
            }
        }

        if (empty($missingPages)) {
            return AuditResult::passed($this->getName());
        }

        $score = max(0, 100 - (count($missingPages) * 5));
        
        return AuditResult::failed($this->getName())
            ->withEvidence([
                'missing_count' => count($missingPages),
                'pages' => array_slice($missingPages, 0, 10), // Limiter à 10 pour l'affichage
            ])
            ->withMessage(sprintf('%d page(s) sans meta description trouvée(s)', count($missingPages)))
            ->withScore($score);
    }

    public function getFix(): ?string
    {
        return 'Ajoutez une meta description (50-160 caractères) à chaque page dans vos vues Blade.';
    }

    public function getDocumentation(): ?string
    {
        return 'https://moz.com/learn/seo/meta-description';
    }
}

