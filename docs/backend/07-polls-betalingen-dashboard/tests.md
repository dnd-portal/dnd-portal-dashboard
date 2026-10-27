# Tests

Te testen in een geïsoleerde database: verkeerde campaign/poll/option-combinaties, non-participant votes, gesloten/verlopen polls, single/multiple, option-labelwijziging na stemmen, admin/DM/spelergrenzen, cents/deelbetalingen/correcties, speler die zichzelf betaald probeert te markeren, externe URL-validatie, dashboard privacy en serverpaginering.

- **Geslaagd:** PHP syntaxchecks.
- **Geslaagd:** bestaande `pnpm check`, tests en build.
- **Niet uitgevoerd:** MySQL-integratie, transacties/concurrency, financiële fixtures en InfinityFree-tests.

