# Rechten en tests

## Rechten

| Actie | Portaladmin | Campaign-DM | Speler |
|---|---:|---:|---:|
| campagne lezen | alle | eigen | eigen actieve membership |
| campagne/session beheren | alle | eigen campagne | nee |
| leden beheren | alle | eigen campagne | nee |
| eigen RSVP | ja | ja | ja |
| attendance vastleggen | ja | eigen campagne | nee |
| eigen beschikbaarheid | ja | ja | ja |
| andermans beschikbaarheid | ja | nee | nee |

## Testplan/status

Te testen met geïsoleerde database: handmatig gewijzigde ID’s, verlaten campagnes, admin/DM/speler-grenzen, RSVP versus attendance, deadline, cancelled/archive, DST-overgangen, onbekende availability en ontbrekende migratierelaties.

- **Geslaagd:** PHP syntaxchecks.
- **Niet uitgevoerd:** MySQL-integratie, concurrency, DST-runtime en InfinityFree smoke test.
- **Niet uitgevoerd:** volledige frontend API-migratie; de mock blijft voorlopig actief.

