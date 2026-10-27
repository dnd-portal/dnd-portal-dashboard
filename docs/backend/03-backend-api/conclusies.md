# Conclusies

De minimale backendstructuur is aanwezig zonder een framework of half werkende domeinlaag. Er is één frontcontroller, een kleine router, uniforme JSON-fouten, origin-baseline, een veilige healthcheck en één centrale frontendclient. De mockdata blijft de huidige UI voeden.

Resterend:

- **Hard open:** InfinityFree-webroot, PHP-versie/extensies, MySQL-variant, HTTPS/cookies en echte `/api/`-routing zijn niet bevestigd.
- **Nog bouwen:** database-backed PHP session handler, CSRF-token endpoint, login/registratie/resetservices en concrete repositories.
- **Nog testen:** contracttests met MySQL/MariaDB, authorization tests, rate limiting en deployment smoke test.
- **Niet doen:** migraties, credentials of volledige domeinfeatures via deze infrastructuurstap publiceren.

