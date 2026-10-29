# Contributing to D&D Portal Dashboard

Thank you for helping improve D&D Portal Dashboard. Focused issues and pull requests are welcome when they respect the project's security, privacy and production constraints.

## Before you begin

- Search existing issues before opening a new one.
- Use a clear, outcome-based issue title.
- Keep unrelated changes in separate issues and pull requests.
- Never use real production accounts, credentials or user data in development or tests.
- Report vulnerabilities privately according to [`SECURITY.md`](SECURITY.md).

## Local setup

Follow the [local development instructions](README.md#local-development) in the main README. Development uses isolated local configuration and databases.

Never point development scripts or tests at the production database.

## Making a change

1. Create a focused branch from the latest `main`.
2. Keep the implementation within the confirmed issue scope.
3. Follow the existing architecture and four-space formatting.
4. Add or update tests for changed behavior.
5. Check responsive behavior when changing user-interface code.
6. Update documentation when commands, configuration or user-visible behavior changes.

Use concise, imperative commit messages, for example:

```text
fix persistent session refresh
add password reset browser coverage
improve mobile administration tables
```

## Required checks

Run the relevant checks before opening a pull request:

```powershell
pnpm --dir player format:check
pnpm --dir player check
pnpm --dir player test
pnpm --dir player build
npm --prefix backend run format:check
```

Run the Playwright suite when changing authentication, navigation, responsive layouts or other browser behavior:

```powershell
pnpm --dir player test:browser
```

PHP files must also pass `php -l`, and `git diff --check` must report no whitespace errors.

## Pull requests

A pull request should explain:

- What changed
- Why the change is needed
- How it was tested
- Any remaining limitations or follow-up work
- Screenshots for meaningful visual changes

Do not claim a check passed unless it was actually executed. Clearly distinguish a failing check from a check that could not be run because a required test environment was unavailable.

## Sensitive and generated files

Do not commit:

- `.env` or `.env.infinityfree` files
- Passwords, tokens, invitation codes, reset codes or session cookies
- Production database exports or real user information
- HAR files, private logs, keys or certificates
- `node_modules`, build output or local release packages

The committed `.env.example` files contain development-only placeholders and are the only environment templates that belong in version control.

## Contribution terms

By submitting a contribution, you confirm that you have the right to submit it and grant the project owner a perpetual, worldwide, non-exclusive, royalty-free license to use, reproduce, modify and distribute the contribution as part of D&D Portal Dashboard.

The repository remains proprietary and is governed by [`LICENSE`](LICENSE).
