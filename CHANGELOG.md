# Changelog

All notable changes to D&D Portal Dashboard are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project uses [Semantic Versioning](https://semver.org/spec/v2.0.0.html) for public releases.

## [Unreleased]

### Planned

- Continued campaign, session and organizer workflow improvements

## [4.8.21] - 2026-10-29

### Added

- Invitation-based account registration with personal, single-use codes
- Secure account login and persistent database-backed sessions
- Password-reset codes, personal reset links and a public reset flow
- Player dashboard for campaigns, sessions, availability, polls and payments
- Global multi-select polls with organizer result views
- Administrative interfaces for users, invitations, campaigns, sessions, payments, activity and rate limits
- Responsive player and admin layouts for desktop and mobile devices
- Full-screen accessible mobile navigation with focus restoration and Escape support
- Local `portal` commands for development, status, logs, preview and deployment packaging
- Browser regression coverage for authentication, responsive layouts and production-like HTTPS flows

### Changed

- Reworked production configuration loading for hosting environments where `putenv()` is unavailable
- Improved mobile headers, breadcrumbs, tables, polls and administration layouts
- Made player and administration logos link to their respective dashboards
- Hardened the audited InfinityFree deployment-package workflow

### Security

- Added CSRF protection to state-changing API requests
- Added secure, HTTP-only, same-site session cookies in production
- Added login and invitation rate limiting
- Disabled detailed production error output
- Excluded runtime configuration, credentials, production SQL, logs and release artifacts from version control

[Unreleased]: https://github.com/dnd-portal/dnd-portal-dashboard/compare/v4.8.21...HEAD
[4.8.21]: https://github.com/dnd-portal/dnd-portal-dashboard/releases/tag/v4.8.21
