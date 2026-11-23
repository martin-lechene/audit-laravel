# Security Policy

## Supported versions

This package is in beta and has not been tagged yet. Security fixes are applied to the `main` branch
and will be released in the next tagged version.

| Version | Supported |
|---------|-----------|
| `main` / latest tag | Yes |
| Older tags | No |

## Reporting a vulnerability

Please do **not** open a public issue for security problems.

Report them privately, by one of these means:

- Email: [contact@doganddev.eu](mailto:contact@doganddev.eu) with the subject `[SECURITY] audit-laravel`.
- GitHub private vulnerability reporting, from the "Security" tab of the repository, if it is enabled.

Please include:

- a description of the issue and its impact,
- the affected version or commit,
- steps to reproduce, or a proof of concept,
- any suggested fix.

You can expect an acknowledgement within 5 working days. We will keep you informed of the progress and
credit you in the changelog once the fix is released, unless you prefer to stay anonymous.

## Scope

This package reads the configuration and the files of the Laravel project it is installed in, and can write
reports to disk and audit results to the database. Please do not include real secrets (`.env` values, keys,
tokens) in your reports.
