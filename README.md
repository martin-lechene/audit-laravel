# Laravel Audit Suite

Package complet pour auditer automatiquement les projets Laravel sur 6 piliers : SEO, Sécurité, Performance, Base de données, Code Quality, et Infrastructure.

## 📋 Fonctionnalités

- ✅ 6 Auditors complets (SEO, Security, Performance, Database, Code Quality, Infrastructure)
- ✅ 50+ Rules prédéfinis
- ✅ Système de scoring automatique
- ✅ 4 Reporters (Console, JSON, HTML, Markdown)
- ✅ Persistence en base de données
- ✅ Historique des audits
- ✅ Système d'événements complet
- ✅ Extension points pour règles custom
- ✅ Tests suite complets

## 🚀 Installation

```bash
composer require martin-lechene/laravel-audit-suite
```

Publier la configuration :

```bash
php artisan vendor:publish --tag=audit-suite-config
```

Publier les migrations :

```bash
php artisan vendor:publish --tag=audit-suite-migrations
php artisan migrate
```

## 📖 Usage

### Commande de base

```bash
php artisan audit:run
```

### Options disponibles

```bash
# Filtrer par catégorie
php artisan audit:run --category=security

# Filtrer par sévérité
php artisan audit:run --severity=critical,high

# Format de sortie
php artisan audit:run --format=json --store
php artisan audit:run --format=html --output=report.html
php artisan audit:run --format=markdown --output=report.md

# Comparaison historique
php artisan audit:run --historic

# Lancer uniquement certains auditors
php artisan audit:run --only=security,performance
php artisan audit:run --except=seo,database
```

### Options de la commande

| Option | Description | Type |
|--------|-------------|------|
| `--category` | Filtrer par catégorie (seo, security, performance, database, quality, infrastructure) | string |
| `--severity` | Filtrer par sévérité (critical, high, medium, low, info) | string |
| `--format` | Format de sortie (console, json, html, markdown) | string (default: console) |
| `--store` | Sauvegarder les résultats en base de données | boolean |
| `--historic` | Comparer avec les audits précédents | boolean |
| `--output` | Fichier de sortie | string |
| `--only` | Exécuter uniquement certains auditors | string |
| `--except` | Exclure certains auditors | string |

## 🎯 Auditors

### 1. SEO Auditor

Vérifie :
- Métadonnées (title, meta description, og tags)
- Structure du sitemap.xml
- Robots.txt présent et valide
- Présence de JSON-LD (schema.org)
- Canonical tags
- Headings structure (H1, H2, H3...)
- Alt text sur images
- Vitesse de chargement des pages
- Mobile responsiveness

### 2. Security Auditor

Vérifie :
- Headers de sécurité (CSP, X-Frame-Options, X-Content-Type-Options)
- CORS configuration
- HTTPS enforcement
- CSRF protection
- SQL Injection risks
- XSS vulnerabilities
- Secrets exposition
- Package vulnerabilities
- Rate limiting

### 3. Performance Auditor

Vérifie :
- Response time des requêtes
- Slow queries logging
- N+1 queries detection
- Database indexing
- Cache hits/misses
- Memory usage
- Asset optimization

### 4. Database Auditor

Vérifie :
- Migrations status
- Unused tables
- Missing indexes
- Orphaned records
- Foreign keys constraints
- Data types optimization
- Query performance analysis

### 5. Code Quality Auditor

Vérifie :
- PSR-12 compliance
- Dead code detection
- Code coverage
- Complexity metrics
- Documentation coverage
- Testing ratio
- Deprecated Laravel features
- Type hints completeness

### 6. Infrastructure Auditor

Vérifie :
- PHP version compatibility
- Extension requirements
- Environment variables
- Permissions (storage, bootstrap)
- SSL/TLS certificates
- Log files management
- Backup systems

## 📊 Système de Scoring

### Formule Globale

```
Score Global = (Σ scores par catégorie × weight) / 100

Weights :
- Security: 35%
- Performance: 25%
- SEO: 20%
- Code Quality: 12%
- Database: 5%
- Infrastructure: 3%
```

### Impact des Findings

- Critical finding : -20 points
- High finding : -10 points
- Medium finding : -5 points
- Low finding : -2 points
- Info : 0 points

### Interprétation des Scores

- 90-100 : Excellent
- 80-89  : Très Bon
- 70-79  : Bon
- 60-69  : Acceptable
- 50-59  : Problématique
- < 50   : Critique

## 🔌 Extension

### Créer une règle personnalisée

```php
namespace App\AuditRules;

use MartinLechene\AuditSuite\Rules\BaseRule;
use MartinLechene\AuditSuite\Models\AuditResult;
use MartinLechene\AuditSuite\Services\AuditContext;

class MyCustomRule extends BaseRule
{
    public function getName(): string
    {
        return 'custom.my_rule';
    }

    public function getDescription(): string
    {
        return 'Ma règle personnalisée';
    }

    public function getSeverity(): string
    {
        return 'medium';
    }

    public function getCategory(): string
    {
        return 'security';
    }

    public function check(AuditContext $context): AuditResult
    {
        // Votre logique de vérification
        if (/* condition */) {
            return AuditResult::passed($this->getName());
        }

        return AuditResult::failed($this->getName())
            ->withMessage('Problème détecté')
            ->withEvidence(['key' => 'value'])
            ->withScore(50);
    }

    public function getFix(): ?string
    {
        return 'Suggestion de correction';
    }
}
```

### Enregistrer la règle

```php
use MartinLechene\AuditSuite\Services\RuleEngine;

// Dans un ServiceProvider
public function boot()
{
    $ruleEngine = app(RuleEngine::class);
    $ruleEngine->registerRule(new MyCustomRule());
}
```

### Écouter les événements

```php
use MartinLechene\AuditSuite\Events\FindingDetected;

Event::listen(FindingDetected::class, function ($event) {
    if ($event->finding->severity === 'critical') {
        // Envoyer une notification
    }
});
```

## ⚙️ Configuration

Fichier `config/audit-suite.php` :

```php
return [
    'enabled' => env('AUDIT_ENABLED', true),
    
    'auditors' => [
        'seo' => env('AUDIT_SEO', true),
        'security' => env('AUDIT_SECURITY', true),
        'performance' => env('AUDIT_PERFORMANCE', true),
        'database' => env('AUDIT_DATABASE', true),
        'code_quality' => env('AUDIT_CODE_QUALITY', true),
        'infrastructure' => env('AUDIT_INFRASTRUCTURE', true),
    ],
    
    'store_results' => env('AUDIT_STORE_RESULTS', true),
    'max_stored_audits' => 50,
    
    'scoring' => [
        'weights' => [
            'security' => 35,
            'performance' => 25,
            'seo' => 20,
            'code_quality' => 12,
            'database' => 5,
            'infrastructure' => 3,
        ],
        'critical_threshold' => 0,
        'high_threshold' => 5,
        'minimum_score' => 70,
    ],
];
```

## 🧪 Tests

```bash
composer test
```

## 📝 License

MIT

## 🤝 Contribution

Les contributions sont les bienvenues ! N'hésitez pas à ouvrir une issue ou une pull request.

## 📧 Support

Pour toute question ou problème, ouvrez une issue sur GitHub.

