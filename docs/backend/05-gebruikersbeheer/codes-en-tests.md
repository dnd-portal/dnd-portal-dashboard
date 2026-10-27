# Codeoverzichten en tests

Invite- en resetoverzichten horen de bestaande `DataTable` te gebruiken met gebruiker, aanmaaktijd, vervaltijd, afgeleide status en toegestane acties. Status wordt uitsluitend afgeleid uit `used_at`, `revoked_at` en `expires_at`. De ruwe code is alleen in het directe generatieresultaat zichtbaar; na reload staat `Hidden after creation`. Alleen een gedeelde CopyButton mag kopiëren en toont succes pas na een geslaagde clipboardactie.

Te testen met dynamische fixtures: edit/cancel, unieke identifiers, rolwijzigingen, laatste-adminregel, concurrente versieconflict, gewone gebruiker versus admin, disable, invite/reset uitgifte en intrekken, verlopen/gebruikte codes en clipboardfeedback. Geen vaste bestaande gebruikersnamen of IDs.

## Werkelijk uitgevoerd

- **Geslaagd:** PHP syntaxcheck op alle backendbestanden.
- **Geslaagd:** `pnpm check`, bestaande tests en production build.
- **Niet uitgevoerd:** MySQL-integratie- en autorisatietests met geïsoleerde database.
- **Niet uitgevoerd:** InfinityFree deploymenttests.

