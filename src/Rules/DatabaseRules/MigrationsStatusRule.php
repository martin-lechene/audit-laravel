<?php

namespace MartinLechene\AuditSuite\Rules\DatabaseRules;

use MartinLechene\AuditSuite\Models\AuditResult;
use MartinLechene\AuditSuite\Rules\BaseRule;
use MartinLechene\AuditSuite\Services\AuditContext;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class MigrationsStatusRule extends BaseRule
{
    public function getName(): string
    {
        return 'database.migrations_status';
    }

    public function getDescription(): string
    {
        return 'Vérifie le statut des migrations';
    }

    public function getSeverity(): string
    {
        return 'high';
    }

    public function getCategory(): string
    {
        return 'database';
    }

    public function check(AuditContext $context): AuditResult
    {
        try {
            // Vérifier si la table migrations existe
            if (!DB::getSchemaBuilder()->hasTable('migrations')) {
                return AuditResult::warning($this->getName())
                    ->withMessage('La table migrations n\'existe pas encore')
                    ->withScore(50);
            }

            // Compter les migrations
            $migrationsCount = DB::table('migrations')->count();
            $migrationsPath = database_path('migrations');
            $filesCount = count(glob($migrationsPath . '/*.php'));

            if ($migrationsCount === 0 && $filesCount > 0) {
                return AuditResult::failed($this->getName())
                    ->withMessage('Des fichiers de migration existent mais aucune n\'a été exécutée')
                    ->withScore(0);
            }

            if ($filesCount > $migrationsCount) {
                return AuditResult::warning($this->getName())
                    ->withMessage(sprintf('%d migrations en attente', $filesCount - $migrationsCount))
                    ->withEvidence([
                        'migrations_executed' => $migrationsCount,
                        'migrations_files' => $filesCount,
                    ])
                    ->withScore(70);
            }

            return AuditResult::passed($this->getName())
                ->withEvidence([
                    'migrations_executed' => $migrationsCount,
                    'migrations_files' => $filesCount,
                ]);
        } catch (\Throwable $e) {
            return AuditResult::warning($this->getName())
                ->withMessage('Impossible de vérifier les migrations: ' . $e->getMessage())
                ->withScore(50);
        }
    }

    public function getFix(): ?string
    {
        return 'Exécutez php artisan migrate pour appliquer les migrations en attente.';
    }

    public function getDocumentation(): ?string
    {
        return 'https://laravel.com/docs/migrations';
    }
}

