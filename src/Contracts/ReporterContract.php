<?php

namespace MartinLechene\AuditSuite\Contracts;

use MartinLechene\AuditSuite\Models\AuditSession;

interface ReporterContract
{
    /**
     * Génère un rapport pour une session d'audit
     */
    public function generate(AuditSession $session): string;

    /**
     * Sauvegarde le rapport dans un fichier
     */
    public function save(string $content, string $path): bool;
}

