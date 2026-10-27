# Testresultaten

- **Geslaagd — `pnpm format:check`.**
- **Geslaagd — `pnpm check`.** Svelte-check: 0 errors, 0 warnings.
- **Geslaagd — `pnpm test`.** 10/10 bestaande unittests.
- **Geslaagd — `pnpm build`.** Productiebuild voltooid.
- **Geslaagd — PHP syntaxcontrole.** `CampaignService.php`, `public/index.php` en `AuthService.php`.
- **Geslaagd — campagne restore API runtime tegen geïsoleerde MariaDB.** In run `dnd_portal_test_run_4261620261026154112` slaagden login, create, archive, restore, opnieuw ophalen en stale-version `409`. De restorecode normaliseert ISO-timestamps naar UTC-DATETIME.
- **Niet uitgevoerd — echte browsercampagneflow.** De auth-browserflow is geen bewijs voor campagnes.
- **Niet uitgevoerd — InfinityFree.** Lokale checks zijn geen hostingvalidatie.

Daarom is de frontendkoppeling statisch gecontroleerd, maar nog niet functioneel afgerond verklaard.
