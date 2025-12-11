<?php

namespace MartinLechene\AuditSuite\Rules\SeoRules;

use MartinLechene\AuditSuite\Models\AuditResult;
use MartinLechene\AuditSuite\Rules\BaseRule;
use MartinLechene\AuditSuite\Services\AuditContext;

class RobotsTxtRule extends BaseRule
{
    public function getName(): string
    {
        return 'seo.robots_txt';
    }

    public function getDescription(): string
    {
        return 'Vérifie la présence et la validité du fichier robots.txt';
    }

    public function getSeverity(): string
    {
        return 'low';
    }

    public function getCategory(): string
    {
        return 'seo';
    }

    public function check(AuditContext $context): AuditResult
    {
        $robotsPath = public_path('robots.txt');
        
        if (!file_exists($robotsPath)) {
            return AuditResult::failed($this->getName())
                ->withMessage('Le fichier robots.txt est absent')
                ->withScore(0);
        }

        $content = file_get_contents($robotsPath);
        
        if (empty(trim($content))) {
            return AuditResult::warning($this->getName())
                ->withMessage('Le fichier robots.txt est vide')
                ->withScore(50);
        }

        // Vérifications basiques
        $hasUserAgent = preg_match('/User-agent:/i', $content);
        $hasDisallow = preg_match('/Disallow:/i', $content) || preg_match('/Allow:/i', $content);
        
        if (!$hasUserAgent) {
            return AuditResult::warning($this->getName())
                ->withMessage('Le fichier robots.txt ne contient pas de directive User-agent')
                ->withScore(60);
        }

        return AuditResult::passed($this->getName())
            ->withEvidence(['file_size' => strlen($content)]);
    }

    public function getFix(): ?string
    {
        return 'Créez un fichier robots.txt dans le dossier public avec les directives appropriées.';
    }

    public function getDocumentation(): ?string
    {
        return 'https://developers.google.com/search/docs/crawling-indexing/robots/intro';
    }
}

