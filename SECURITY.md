# Security Policy

Security reports are taken seriously. Please do not disclose a suspected vulnerability in a public issue, discussion, pull request or social-media post.

## Supported versions

| Version                | Supported |
| ---------------------- | --------- |
| Latest `4.8.x` release | Yes       |
| Older versions         | No        |

Only the latest production release receives security fixes. Upgrade before reporting an issue that has already been resolved in a newer release.

## Reporting a vulnerability

Use GitHub's private vulnerability-reporting flow:

[Report a vulnerability privately](https://github.com/dnd-portal/dnd-portal-dashboard/security/advisories/new)

If private reporting is unavailable, contact a repository maintainer privately before sharing technical details. Do not include secrets, access tokens, passwords, session cookies, invitation codes, reset codes or production user data in public channels.

Include as much of the following information as safely possible:

- A clear description of the issue and its potential impact
- The affected version, route or component
- Reproduction steps or a minimal proof of concept
- Required account role or environmental conditions
- Suggested mitigations, if known
- Whether the issue may already have been disclosed elsewhere

## What to expect

A maintainer will review the report, confirm whether it can be reproduced and coordinate remediation before public disclosure. Please allow a reasonable amount of time for investigation and deployment.

Do not test against accounts, campaigns or data that you do not own or have explicit permission to use. Avoid actions that may degrade the production service, destroy data or affect other users.

## Scope

Security reports may cover:

- Authentication, authorization and session handling
- Invitation and password-reset flows
- CSRF, injection and unintended data exposure
- Rate-limit bypasses
- Deployment or configuration behavior that exposes private runtime data

General bugs, styling issues and feature requests belong in the public [issue tracker](https://github.com/dnd-portal/dnd-portal-dashboard/issues).
