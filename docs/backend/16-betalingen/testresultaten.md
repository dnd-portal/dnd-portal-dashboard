# Testresultaten

## Definitieve lokale uitvoering

- Nieuwe geïsoleerde MariaDB-testdatabase met migraties vanaf nul en tweede ronde met uitsluitend `SKIP`: geslaagd.
- Betaalverzoek en verplichting: geslaagd.
- Deelbetaling 4000 cent, restbetaling 6000 cent en saldo 10000/10000: geslaagd.
- Reversal 1000 cent, behoud van oorspronkelijke entry en overschrijdingsblokkade: geslaagd.
- Eigen speleroverzicht toont alleen eigen verplichting en 9000 cent betaald: geslaagd.
- Speler kan geen betaling registreren: geslaagd, 403.
- Duplicate external reference: geslaagd, 409; database bevatte slechts één entry.
- Database-uniciteit voor `(obligation_id, external_reference)`: migratie 007 toegepast en tweede run gaf SKIP.
- Echte browserflow inclusief herladen, restbetaling, correctie en eigen overzicht: geslaagd.
- `pnpm check`: geslaagd, 0 errors en 0 warnings.
- `pnpm build`: geslaagd.
- PHP-lint gewijzigde betaalbestanden vanuit repository-root: geslaagd.

- `pnpm format:check`: geslaagd.
- `pnpm check`: geslaagd, 0 errors en 0 warnings.
- `pnpm build`: geslaagd.

De eerste PHP-lintaanroep werd vanuit de frontendmap uitgevoerd en kon de relatieve bestanden niet vinden; dit is geen PHP-syntaxresultaat. Een correcte PHP-lint vanuit de repository-root moet nog worden uitgevoerd.

## Nog open

- gelijktijdige financiële registratie met twee overlappende HTTP-processen; de transactionele locking is aanwezig, maar deze afzonderlijke concurrencytest is nog niet uitgevoerd.
- expliciete browserdekking voor onveilige URL-schema’s.

Er zijn geen InfinityFree-tests uitgevoerd.
