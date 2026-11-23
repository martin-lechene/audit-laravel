# Laravel Audit Suite

[![Tests](https://github.com/martin-lechene/audit-laravel/actions/workflows/tests.yml/badge.svg)](https://github.com/martin-lechene/audit-laravel/actions/workflows/tests.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.1%20|%208.2%20|%208.3%20|%208.4-777bb4.svg)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-10%20|%2011%20|%2012-ff2d20.svg)](https://laravel.com)
[![Status: beta](https://img.shields.io/badge/status-beta-orange.svg)](CHANGELOG.md)

An audit package for Laravel projects. One Artisan command (`php artisan audit:run`) runs 15 checks across
**6 pillars** (SEO, Security, Performance, Database, Code Quality, Infrastructure), computes a weighted score
and prints or exports a report (console, JSON, HTML or Markdown).

> **Beta.** The package has not been tagged yet and is **not yet published on Packagist**. Install it from
> the GitHub repository as shown below. The API may change before `1.0`.

## Requirements

| Dependency | Version |
|------------|---------|
| PHP | 8.1, 8.2, 8.3 or 8.4 |
| Laravel (`illuminate/*`) | 10, 11 or 12 |

The CI runs the test suite on every supported PHP / Laravel combination.

## Installation

The package is not on Packagist yet, so declare the GitHub repository in your project's `composer.json`:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/martin-lechene/audit-laravel" }
    ]
}
```

Then require the development branch:

```bash
composer require martin-lechene/audit-laravel:dev-main
```

The service provider is auto-discovered. Optionally publish the configuration:

```bash
php artisan vendor:publish --tag=audit-suite-config
```

Audit results are only written to the database when you use `--store`. In that case run the migrations
(they are loaded by the package, publishing them is optional):

```bash
php artisan migrate
# or, to copy them into your project first:
php artisan vendor:publish --tag=audit-suite-migrations
```

## Quick start

```bash
php artisan audit:run
```

## Usage

```bash
# Only show findings of some categories / severities (the score still covers every audited category)
php artisan audit:run --category=security
php artisan audit:run --severity=critical,high

# Export as JSON / HTML / Markdown
php artisan audit:run --format=json --output=report.json
php artisan audit:run --format=html --output=report.html
php artisan audit:run --format=markdown --output=report.md

# Persist the results and compare with the previous stored audit
php artisan audit:run --store --historic

# Run only some auditors, or skip some
php artisan audit:run --only=security,performance
php artisan audit:run --except=seo,database
```

### Options

| Option | Description | Default |
|--------|-------------|---------|
| `--category` | Comma-separated categories to display (`seo`, `security`, `performance`, `database`, `code_quality`, `infrastructure`) | all |
| `--severity` | Comma-separated severities to display (`critical`, `high`, `medium`, `low`, `info`) | all |
| `--format` | `console`, `json`, `html` or `markdown` | `console` |
| `--output` | File path for non-console formats | `storage/audits/audit_<timestamp>.<format>` |
| `--store` | Persist the session and its findings to the database | off |
| `--historic` | Print a comparison with the previous stored audit (needs `--store`) | off |
| `--only` | Comma-separated auditors to run | all enabled |
| `--except` | Comma-separated auditors to skip | none |

The command exits with code `0` on success and `1` if the audit is disabled (`AUDIT_ENABLED=false`) or fails.
It does not fail based on the score or on the `scoring.*_threshold` settings.

## Auditors and rules

| Category | Rule id | Checks | Severity |
|----------|---------|--------|----------|
| SEO | `seo.meta_description` | Blade views contain a meta description | medium |
| SEO | `seo.robots_txt` | `robots.txt` is present and valid | low |
| SEO | `seo.sitemap` | `sitemap.xml` is present and valid | medium |
| Security | `security.https_enforced` | HTTPS is enforced in production | high |
| Security | `security.csrf_protection` | CSRF middleware is enabled | critical |
| Security | `security.debug_mode` | `APP_DEBUG` is off in production | critical |
| Security | `security.app_key_set` | `APP_KEY` is defined and not a placeholder | critical |
| Performance | `performance.slow_queries` | Slow queries detected in `storage/logs/laravel.log` | medium |
| Performance | `performance.cache_config` | Cache driver suits the environment | medium |
| Database | `database.migrations_status` | Migration files have all been run | high |
| Code Quality | `quality.psr12_compliance` | A PSR-12 tool (PHP_CodeSniffer / PHP CS Fixer) is configured | low |
| Code Quality | `quality.tests_exist` | Automated tests exist (at least 5 test files) | medium |
| Infrastructure | `infrastructure.php_version` | PHP version meets the project requirement | high |
| Infrastructure | `infrastructure.permissions` | `storage/` and `bootstrap/cache` are writable | high |
| Infrastructure | `infrastructure.env_variables` | `APP_KEY`, `APP_URL`, `APP_ENV` (and `DB_*` in production) are defined | high |

The rules read the project they are installed in (`base_path()`, `config()`, `env()`). Because `env()` returns
`null` outside the configuration files once `php artisan config:cache` has been run, the environment
variables rule is most accurate with an uncached configuration. Finding messages are currently written in
French.

## Scoring

Every rule returns a score between 0 and 100 (`passed` = 100, `warning` = 50 by default, `failed` = 0 by default,
or a custom value set by the rule). The score of a category is the average of its rules, and the overall score
is the weighted average of the categories that were audited:

```
overall = Σ (category_average × weight) / Σ weights
```

Default weights (`audit-suite.scoring.weights`): security 35, performance 25, seo 20, code_quality 12,
database 5, infrastructure 3.

Interpretation printed by the command:

| Score | Interpretation |
|-------|----------------|
| 90 and above | Excellent |
| 80 - 89 | Very Good |
| 70 - 79 | Good |
| 60 - 69 | Acceptable |
| 50 - 59 | Needs Attention |
| below 50 | Critical |

A rule that throws an exception is logged (`Log::error`) and skipped.

## Configuration

`config/audit-suite.php` (after publishing, or merged automatically):

| Key | Env variable | Default | Used by |
|-----|--------------|---------|---------|
| `enabled` | `AUDIT_ENABLED` | `true` | `audit:run` refuses to run when `false` |
| `auditors.seo` | `AUDIT_SEO` | `true` | Enables the auditor when `--only` / `--except` are not given |
| `auditors.security` | `AUDIT_SECURITY` | `true` | idem |
| `auditors.performance` | `AUDIT_PERFORMANCE` | `true` | idem |
| `auditors.database` | `AUDIT_DATABASE` | `true` | idem |
| `auditors.code_quality` | `AUDIT_CODE_QUALITY` | `true` | idem |
| `auditors.infrastructure` | `AUDIT_INFRASTRUCTURE` | `true` | idem |
| `scoring.weights.*` | - | see above | Overall score |
| `cache.ttl` | - | `3600` | `CacheManager` |

The following keys are declared in the configuration file but are **not read by the code yet** (reserved for
future versions): `store_results`, `max_stored_audits`, `scoring.critical_threshold`, `scoring.high_threshold`,
`scoring.minimum_score`, `notifications.*` (`SLACK_WEBHOOK_URL`, `AUDIT_EMAIL`, `DISCORD_WEBHOOK_URL`),
`reporting.*`, `cache.driver`. Persistence is controlled by the `--store` option only.

## Extending

### Custom rule

A rule extends `BaseRule` and its category must be one of the six categories above, otherwise no auditor runs it.

```php
namespace App\AuditRules;

use MartinLechene\AuditSuite\Models\AuditResult;
use MartinLechene\AuditSuite\Rules\BaseRule;
use MartinLechene\AuditSuite\Services\AuditContext;

class MyCustomRule extends BaseRule
{
    public function getName(): string        { return 'security.my_rule'; }
    public function getDescription(): string { return 'My custom rule'; }
    public function getSeverity(): string    { return 'medium'; }
    public function getCategory(): string    { return 'security'; }

    public function check(AuditContext $context): AuditResult
    {
        if (/* condition */ true) {
            return AuditResult::passed($this->getName());
        }

        return AuditResult::failed($this->getName())
            ->withMessage('Issue detected')
            ->withEvidence(['key' => 'value'])
            ->withScore(50);
    }

    public function getFix(): ?string
    {
        return 'How to fix this issue.';
    }
}
```

### Register the rule

```php
use MartinLechene\AuditSuite\Services\RuleEngine;

// In your AppServiceProvider
public function boot(): void
{
    $this->app->make(RuleEngine::class)->registerRule(new \App\AuditRules\MyCustomRule());
}
```

### Listen to events

```php
use Illuminate\Support\Facades\Event;
use MartinLechene\AuditSuite\Events\FindingDetected;

Event::listen(FindingDetected::class, function (FindingDetected $event) {
    if ($event->finding->severity === 'critical') {
        // Send a notification, trigger an alert, etc.
    }
});
```

Available events: `AuditStarted`, `AuditCompleted`, `AuditFailed`, `FindingDetected`
(namespace `MartinLechene\AuditSuite\Events`).

## Testing

```bash
composer install
composer test
```

The suite contains unit tests and Orchestra Testbench feature tests (service provider registration, `audit:run`
with every report format).

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Security

Please report vulnerabilities privately, as described in [SECURITY.md](SECURITY.md).

## Contributing

Contributions are welcome, see [CONTRIBUTING.md](CONTRIBUTING.md) and the
[Code of Conduct](CODE_OF_CONDUCT.md).

## License

MIT, see [LICENSE](LICENSE). Copyright Dog and Dev / Martin Lechene.

## Contact

[contact@doganddev.eu](mailto:contact@doganddev.eu) - [doganddev.eu](https://doganddev.eu)
