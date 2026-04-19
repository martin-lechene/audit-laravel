<?php

namespace MartinLechene\AuditSuite\Rules\SeoRules;

use MartinLechene\AuditSuite\Models\AuditResult;
use MartinLechene\AuditSuite\Rules\BaseRule;
use MartinLechene\AuditSuite\Services\AuditContext;

class SitemapRule extends BaseRule
{
    public function getName(): string
    {
        return 'seo.sitemap';
    }

    public function getDescription(): string
    {
        return 'Vérifie la présence d\'un fichier sitemap XML';
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
        $candidates = [
            public_path('sitemap.xml'),
            public_path('sitemap_index.xml'),
            public_path('sitemap-index.xml'),
        ];

        $found = null;
        foreach ($candidates as $path) {
            if (file_exists($path)) {
                $found = $path;
                break;
            }
        }

        if ($found === null) {
            return AuditResult::failed($this->getName())
                ->withMessage('Aucun fichier sitemap.xml trouvé dans le dossier public/')
                ->withScore(0);
        }

        $content = (string) file_get_contents($found);

        if (!str_contains($content, '<urlset') && !str_contains($content, '<sitemapindex')) {
            return AuditResult::warning($this->getName())
                ->withMessage('Le fichier sitemap.xml semble invalide (balise <urlset> ou <sitemapindex> absente)')
                ->withEvidence(['path' => basename($found)])
                ->withScore(40);
        }

        return AuditResult::passed($this->getName())
            ->withEvidence(['path' => basename($found), 'size' => strlen($content)]);
    }

    public function getFix(): ?string
    {
        return 'Générez un sitemap.xml et placez-le dans le dossier public/. Des packages comme spatie/laravel-sitemap peuvent automatiser cela.';
    }

    public function getDocumentation(): ?string
    {
        return 'https://developers.google.com/search/docs/crawling-indexing/sitemaps/overview';
    }
}
