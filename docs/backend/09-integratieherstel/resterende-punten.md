# Resterende punten

1. Test InfinityFree apart: webrootafscherming, HTTPS-cookies, PHP-versie/extensies en productie-migratie.
2. Verwijder oude per-run testdatabases alleen met een expliciete operatoractie; de runner verwijdert ze bewust niet.
3. InfinityFree-validatie blijft open: productie-DB-locking, PHP-versie, HTTPS-cookieflags en webrootafscherming zijn lokaal niet bewezen.

Opruimen zonder testvolume te verwijderen: `docker compose -f backend/database/compose.test.yml down`. Gebruik `down -v` niet als standaardcommando.
