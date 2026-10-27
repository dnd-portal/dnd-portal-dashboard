# Backend API-infrastructuur

## Status

- **Geslaagd:** compacte PHP-frontcontroller, router, JSON-responses, origin/CORS-baseline, safe healthcheck en centrale frontendclient.
- **Geslaagd:** bestaande mockdata en UI zijn niet uitgeschakeld.
- **Niet uitgevoerd:** InfinityFree-upload, echte databaseverbinding en productiecookiecontrole; de eerdere hostingblokkades blijven gelden.
- **Deels gebouwd:** auth, gebruikers, campagnes, sessies, polls, betalingen en dashboardservices bestaan; frontendmigratie en runtimevalidatie blijven open.

## Leesvolgorde

1. Folderstructuur en verantwoordelijkheden.
2. API-contracten en foutmodel.
3. Autorisatie, validatie en operationele regels.
4. Lokale/deploymentinstructies.
5. Conclusies en migratie van mock naar API.

