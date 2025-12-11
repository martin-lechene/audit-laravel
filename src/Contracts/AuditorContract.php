<?php

namespace MartinLechene\AuditSuite\Contracts;

use MartinLechene\AuditSuite\Models\AuditResult;
use MartinLechene\AuditSuite\Services\AuditContext;

interface AuditorContract
{
    /**
     * Nom de l'auditor
     */
    public function getName(): string;

    /**
     * Catégorie de l'auditor
     */
    public function getCategory(): string;

    /**
     * Description de l'auditor
     */
    public function getDescription(): string;

    /**
     * Exécute l'audit
     */
    public function audit(AuditContext $context): array;

    /**
     * Retourne les règles disponibles pour cet auditor
     */
    public function getRules(): array;
}

