# Gebruikersbeheer

De bestaande user-detailpagina heeft nu een lokale draft-editor voor naam, email, username en portaalrollen. Opslaan gebruikt `PATCH /api/v1/users/{id}`; annuleren gooit uitsluitend de lokale draft weg. ID, registratiehistorie en credentialgegevens blijven read-only/afgeschermd.

- **Geslaagd:** server-side allowlist, rolcontrole, optimistic locking, laatste-admin-bescherming en disable-endpoint toegevoegd.
- **Geslaagd:** UI toont saving-, fout- en conflictstatus en gebruikt bestaande component-/SCSSstructuur.
- **Niet uitgevoerd:** echte MySQL-integratie, omdat geen lokale MySQL/MariaDB beschikbaar is.
- **Nog open:** volledige invite/reset DataTables moeten hun mockbronnen in een volgende stap naar de admin-endpoints migreren; de authcodes blijven nooit zichtbaar na reload.

