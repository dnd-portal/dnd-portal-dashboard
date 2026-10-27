# Campagnes, sessies en beschikbaarheid

- **Geslaagd:** backendservice voor campagnes, one-shots, leden, sessies, deelnemers, RSVP, attendance en beschikbaarheid.
- **Geslaagd:** one-shots blijven `campaigns.type='one_shot'`; er zijn geen deelnemersarrays.
- **Geslaagd:** archiveren/cancel-status is niet-destructief; historische participantrecords blijven bestaan.
- **Niet uitgevoerd:** MySQL-integratie en InfinityFree-validatie; lokaal ontbreekt MySQL/MariaDB.
- **Bewust beperkt:** bestaande Svelte-schermen blijven tijdelijk mockgedreven als veilige fallback; de API-contractlaag is klaar voor gefaseerde omzetting.

## Leesvolgorde

1. [implementatie.md](implementatie.md)
2. [migratie.md](migratie.md)
3. [rechten-en-tests.md](rechten-en-tests.md)
4. [conclusies.md](conclusies.md)

