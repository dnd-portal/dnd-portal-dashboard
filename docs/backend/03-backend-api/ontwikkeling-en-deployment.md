# Lokale ontwikkeling, deployment en controles

## Lokaal

1. `Copy-Item backend/.env.example backend/.env` en vul alleen lokale waarden in.
2. Start backend vanuit `backend/`: `php -S 127.0.0.1:8099 -t public router.php`.
3. Start frontend vanuit `player/`: `pnpm dev -- --port 5173`.
4. Vite proxyt relatieve `/api/*`-requests naar `VITE_BACKEND_URL` of `http://127.0.0.1:8099`; credentials blijven aan.
5. Controleer `GET http://127.0.0.1:8099/api/v1/health`. Zonder `pdo_mysql` is de status degraded; dat is geen hostingbewijs.

## InfinityFree

Upload de statische frontendbuild en de publieke PHP-ingang volgens de handmatig te bevestigen document-rootindeling. Zet `APP_ALLOWED_ORIGIN` op de echte HTTPS-origin. Gebruik geen SvelteKit serveradapter en geen publiek migratie- of installatiescript. Controleer na upload expliciet dat `.env`, `src/`, `migrations/` en `tests/` niet via HTTP leesbaar zijn. De vorige hostingcontrole blijft open; versie/extensies, cookies, routing en database zijn nog niet bevestigd.

## Uitgevoerde controles

- **Geslaagd:** `php -l` op alle gewijzigde PHP-bestanden.
- **Geslaagd:** TypeScript/Svelte-check, bestaande unit tests en build worden na wijziging opnieuw uitgevoerd.
- **Niet uitgevoerd:** daadwerkelijke API-runtimecheck met MySQL; lokale MySQL/MariaDB is niet beschikbaar.
- **Niet uitgevoerd:** InfinityFree upload, HTTPS, cookies, CORS vanaf echte origin en webroot-isolatie.

## Mock naar API

Per volgende feature: voeg eerst één service + concrete queries + response-allowlist + tests toe; voeg daarna één clientfunctie toe; migreer pas daarna één Svelte-load/formulier van `getDatabase()` naar `api.get/post/patch`. Houd de mock als fallback/testfixture totdat de echte endpoint en foutstates bewezen zijn. Niet meerdere domeinen tegelijk half migreren.

