<?php

namespace MartinLechene\AuditSuite\Auditors;

use MartinLechene\AuditSuite\Services\RuleEngine;

class SeoAuditor extends BaseAuditor
{
    public function getName(): string
    {
        return 'SEO Auditor';
    }

    public function getCategory(): string
    {
        return 'seo';
    }

    public function getDescription(): string
    {
        return 'Audit SEO : métadonnées, sitemap, robots.txt, schema markup, etc.';
    }
}

