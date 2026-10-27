# Foutformaat en autorisatie

## Foutformaat

```json
{"error":{"code":"validation_failed","message":"Request validation failed.","details":{"fields":{"username":"invalid"}}}}
```

Vaste codes: `bad_request` 400, `unauthorized` 401, `forbidden` 403, `not_found` 404, `method_not_allowed` 405, `conflict` 409, `validation_failed` 422, `rate_limited` 429, `csrf_failed` 419, `server_error` 500, `service_unavailable` 503. Productie geeft geen stack traces, SQL, paths, tokens of secrets terug. `request_id` kan als responseheader en serverlogcorrelatie worden toegevoegd.

## Matrix

| Actie | Publiek | Ingelogde gebruiker | Campaign DM | Portaladmin |
|---|---:|---:|---:|---:|
| health, login, reset request | ja | ja | ja | ja |
| me, logout, eigen password/availability/RSVP/vote | nee | eigen account | ja | ja |
| registratie met geldige invite | invite | invite | invite | invite |
| eigen campagne lezen | nee | lid | ja | ja |
| campagne/sessie/poll beheren | nee | nee | eigen campagne | ja |
| attendance en payment obligations beheren | nee | eigen gegevens | eigen campagne | ja |
| users, roles, disable, audit | nee | nee | nee | ja |

Rechten worden server-side bepaald uit `account_status`, `user_roles` en `campaign_memberships`. Frontend routeguards zijn geen beveiligingsgrens.

## Validatie

Email/username worden genormaliseerd en afzonderlijk uniek behandeld; lege strings worden NULL. Passwords krijgen minimale lengte/sterkte en worden alleen met `password_hash` verwerkt. IDs zijn positieve integers. Cents zijn niet-negatief en currency is drie hoofdletters. Statuswaarden komen uit vaste sets. Resource ownership wordt na lookup gecontroleerd, niet alleen op URL-ID.

