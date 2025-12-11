<?php

namespace MartinLechene\AuditSuite\Contracts;

use MartinLechene\AuditSuite\Models\AuditResult;
use MartinLechene\AuditSuite\Services\AuditContext;

interface AuditRule
{
    /**
     * Nom unique de la règle
     */
    public function getName(): string;

    /**
     * Description de la règle
     */
    public function getDescription(): string;

    /**
     * Sévérité : critical, high, medium, low, info
     */
    public function getSeverity(): string;

    /**
     * Catégorie : seo, security, performance, database, quality, infrastructure
     */
    public function getCategory(): string;

    /**
     * Exécute la vérification
     */
    public function check(AuditContext $context): AuditResult;

    /**
     * Suggestion de correction
     */
    public function getFix(): ?string;

    /**
     * Documentations et références
     */
    public function getDocumentation(): ?string;
}

