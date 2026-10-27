# API-contracten

Prefix: `/api/v1`. JSON UTF-8. Succes: `{ "data": ... }`; lijsten: `{ "data": [...], "meta": {"page":1,"page_size":25,"total":0} }`. Geen `SELECT *` naar JSON; elk endpoint gebruikt een expliciete allowlist.

## Infrastructuur en auth

| Methode | Endpoint | Auth | Contract |
|---|---|---|---|
| GET | `/health` | publiek | `data.status`, `data.checks`; geen secrets, DB-DSN of versies die fingerprinting onnodig maken |
| GET | `/auth/csrf` | sessie optioneel | `{data:{token:string}}`; token is alleen voor CSRF, niet login |
| GET | `/auth/me` | login | profielallowlist: `id,display_name,email,username,account_status,version,roles`; nooit credentials |
| POST | `/auth/login` | publiek + origin | body `identifier,password`; 200 `me`, generieke 401 bij fout; rate limit; sessie roteren |
| POST | `/auth/logout` | login + CSRF | 204 of `{data:null}`; revoket huidige sessie |
| POST | `/auth/register` | invite + CSRF | `invite_code,email?,username,password,display_name`; activeert bestaand profiel; 201 profielallowlist |
| POST | `/auth/password-reset/request` | publiek + origin | `identifier`; altijd generiek 202, geen account-enumeratie |
| POST | `/auth/password-reset/complete` | publiek + resetcode | `token,new_password`; 204; token direct verbruiken |

## Domeincontracten (te implementeren per volgende stap)

| Resource | Endpoints | Belangrijkste body/response |
|---|---|---|
| Users | `GET /users`, `GET/PATCH /users/{id}`, `POST /users/{id}/disable` | filters `status,role,query`; profielallowlist inclusief `version`; admin only |
| Campaigns/memberships | `GET/POST /campaigns`, `GET/PATCH /campaigns/{id}`, `GET/POST/PATCH /campaigns/{id}/members` | `name,slug,type,location,timezone`; DM/admin writes volgens matrix |
| Sessions/attendance | `GET/POST /campaigns/{id}/sessions`, `GET/PATCH /sessions/{id}`, `PATCH /sessions/{id}/participants/{userId}` | session fields plus aparte `rsvp_status` en `attendance_status` |
| Availability | `GET /availability`, `PUT /availability/{date}` | `status=available|unavailable` of geen rij = onbekend |
| Polls | `GET/POST /campaigns/{id}/polls`, `GET/PATCH /polls/{id}`, `PUT /polls/{id}/vote` | options met IDs; vote bevat `option_id`, nooit antwoordtekst als foreign key |
| Payments | `GET/POST /campaigns/{id}/payment-requests`, `GET /payment-requests/{id}`, `PUT /obligations/{id}`, `POST /obligations/{id}/entries` | cents + currency; totalen berekend; geen betalingsgeheimen |
| Dashboard | `GET /dashboard` | server-berekende counts en kleine samenvattingen; geen dubbele totalen in DB |

## Algemene regels

- Datums: ISO `YYYY-MM-DD`; instanties: ISO 8601 UTC met `Z`.
- Paginering: default 25, maximum 100; `page` begint bij 1. Alleen gedocumenteerde sorteringen (`created_at`, `name`, `session_date`, `status`) via vaste server-side map; nooit kolomnaam of ORDER BY rechtstreeks uit request.
- Filters zijn allowlisted per resource en worden prepared gebonden.
- Private responses: `Cache-Control: no-store`, geen publieke CDN-cache.
- Mutaties vereisen `Origin` gelijk aan geconfigureerde dashboardorigin, SameSite/secure cookie en CSRF-token. Geen CSRF-bypass voor JSON.
- `409 conflict` bij version mismatch; client moet opnieuw laden. Geen blind overschrijven.
- Requestvelden worden strikt getypeerd, getrimd, begrensd en onbekende velden geweigerd.

