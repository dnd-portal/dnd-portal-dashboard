# Definitief datamodel

## Doel en status

Dit document beschrijft het eerste productiegerichte MySQL/MariaDB-schema voor de PHP API. Het sluit aan op de actuele mock-entiteiten in `player/src/lib/database/tables/`, maar migreert die mockdata niet automatisch.

- Schema-ontwerp: geslaagd.
- SQL-migratiebestand: geslaagd, versiebeheerbaar maar niet uitgevoerd.
- Lokale compatibele MySQL/MariaDB-validatie: niet uitgevoerd; er is geen lokale databaseverbinding beschikbaar.
- Hostingdatabase gewijzigd: niet uitgevoerd.

## Leesvolgorde

1. [datadictionary.md](datadictionary.md)
2. [relaties.md](relaties.md)
3. [ontwerpbesluiten.md](ontwerpbesluiten.md)
4. [migratieplan.md](migratieplan.md)
5. [conclusies.md](conclusies.md)

SQL staat in [001_initial_portal_schema.sql](../../../backend/database/migrations/001_initial_portal_schema.sql). Alle versieerbare migraties staan uitsluitend onder `backend/database/migrations/`.

