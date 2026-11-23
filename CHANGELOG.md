# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Community files: `SECURITY.md`, `CONTRIBUTING.md`, `CODE_OF_CONDUCT.md`, issue and pull request templates.
- Dependabot configuration for Composer and GitHub Actions.
- `.gitattributes` with `export-ignore` rules for development files.
- `composer test` script, package keywords and `support` metadata.
- Integration tests (Orchestra Testbench) covering the service provider and the `audit:run` command.

### Changed
- CI now runs a PHP x Laravel matrix (PHP 8.1 to 8.4, Laravel 10, 11 and 12).
- Development dependencies accept PHPUnit 10.5, 11 and 12 and Testbench 8, 9 and 10 so that the declared
  `php ^8.1` constraint is actually tested.
- Contact email is now `contact@doganddev.eu`.
- README rewritten to match the code (configuration keys actually used, scoring formula, beta install instructions).

### Fixed
- CI failure caused by `phpunit/phpunit ^12.5.22` requiring PHP 8.3 while the package declares `php ^8.1`.

## [0.1.0] - Unreleased

First public beta. Not yet tagged and not yet published on Packagist.

### Added
- `audit:run` Artisan command with console, JSON, HTML and Markdown reporters.
- 15 rules across 6 auditors: SEO, Security, Performance, Database, Code Quality and Infrastructure.
- Weighted scoring, optional persistence of audit sessions and findings, and audit events.

[Unreleased]: https://github.com/martin-lechene/audit-laravel/commits/main
