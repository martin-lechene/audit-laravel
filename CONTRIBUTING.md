# Contributing

Thank you for considering a contribution to `martin-lechene/audit-laravel`.

## Reporting bugs and requesting features

- Search the [existing issues](https://github.com/martin-lechene/audit-laravel/issues) first.
- Use the issue templates. For a bug, include PHP and Laravel versions, the command you ran and the output.
- For security problems, do not open an issue: see [SECURITY.md](SECURITY.md).

## Development setup

```bash
git clone https://github.com/martin-lechene/audit-laravel.git
cd audit-laravel
composer install
composer test
```

The test suite uses PHPUnit and [Orchestra Testbench](https://packages.tools/testbench). The CI runs it on
PHP 8.1 to 8.4 with Laravel 10, 11 and 12.

## Pull requests

1. Fork the repository and create a branch from `main` (`feat/...`, `fix/...`, `docs/...`).
2. Add or update tests for your change.
3. Make sure `composer test` passes and that the code follows PSR-12.
4. Update `CHANGELOG.md` under `[Unreleased]` and the README if behaviour or options change.
5. Use [Conventional Commits](https://www.conventionalcommits.org/) for commit messages
   (for example `fix: handle missing robots.txt`).
6. Open the pull request against `main` and fill in the template.

## Adding a rule

Rules extend `MartinLechene\AuditSuite\Rules\BaseRule`, return an `AuditResult`, and are registered in
`AuditSuiteServiceProvider::registerRules()`. Add the rule to the README tables and add a test.

## Code of conduct

By participating you agree to abide by the [Code of Conduct](CODE_OF_CONDUCT.md).
