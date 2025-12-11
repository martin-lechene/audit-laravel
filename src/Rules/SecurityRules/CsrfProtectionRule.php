<?php

namespace MartinLechene\AuditSuite\Rules\SecurityRules;

use MartinLechene\AuditSuite\Models\AuditResult;
use MartinLechene\AuditSuite\Rules\BaseRule;
use MartinLechene\AuditSuite\Services\AuditContext;

class CsrfProtectionRule extends BaseRule
{
    public function getName(): string
    {
        return 'security.csrf_protection';
    }

    public function getDescription(): string
    {
        return 'Vérifie que la protection CSRF est activée';
    }

    public function getSeverity(): string
    {
        return 'critical';
    }

    public function getCategory(): string
    {
        return 'security';
    }

    public function check(AuditContext $context): AuditResult
    {
        $kernelPath = app_path('Http/Kernel.php');
        $bootstrapPath = bootstrap_path('app.php');
        
        $hasCsrfMiddleware = false;
        
        // Vérifier dans Kernel.php (Laravel < 11)
        if (file_exists($kernelPath)) {
            $kernelContent = file_get_contents($kernelPath);
            $hasCsrfMiddleware = str_contains($kernelContent, 'VerifyCsrfToken') || 
                                str_contains($kernelContent, 'csrf');
        }
        
        // Vérifier dans bootstrap/app.php (Laravel 11+)
        if (file_exists($bootstrapPath)) {
            $bootstrapContent = file_get_contents($bootstrapPath);
            $hasCsrfMiddleware = $hasCsrfMiddleware || 
                                str_contains($bootstrapContent, 'VerifyCsrfToken') ||
                                str_contains($bootstrapContent, 'csrf');
        }

        // Vérifier le middleware VerifyCsrfToken
        $csrfMiddlewarePath = app_path('Http/Middleware/VerifyCsrfToken.php');
        if (file_exists($csrfMiddlewarePath)) {
            $csrfContent = file_get_contents($csrfMiddlewarePath);
            $hasExcept = preg_match('/\$except\s*=\s*\[.*\]/s', $csrfContent);
            
            if ($hasExcept) {
                return AuditResult::warning($this->getName())
                    ->withMessage('Protection CSRF activée mais certaines routes sont exclues')
                    ->withScore(80);
            }
        }

        if (!$hasCsrfMiddleware) {
            return AuditResult::failed($this->getName())
                ->withMessage('Protection CSRF non détectée')
                ->withScore(0);
        }

        return AuditResult::passed($this->getName());
    }

    public function getFix(): ?string
    {
        return 'Assurez-vous que le middleware VerifyCsrfToken est activé dans votre application Laravel.';
    }

    public function getDocumentation(): ?string
    {
        return 'https://laravel.com/docs/csrf';
    }
}

