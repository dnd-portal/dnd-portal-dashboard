# Hostingcontrole

## Wat is toegevoegd

`backend/public/index.php` bevat een tijdelijke route `/api/_hosting-check`. Die route is standaard uitgeschakeld (`HOSTING_CHECK_ENABLED=0`) en vereist de header `X-Hosting-Check-Token`. Er is geen installatie- of migratie-endpoint en de controle maakt alleen de eigen tabel `__dnd_hosting_check` aan.

De route controleert na bewust inschakelen:

- JSON-respons en origin/CORS-configuratie;
- sessiestart en herkenning in een volgend request;
- PHP-versie en `pdo`, `pdo_mysql`, `json`, `session`, `password`;
- `password_hash`/`password_verify` en beschikbare algoritmen;
- PDO-verbinding, InnoDB, transacties en een self-referencing foreign key;
- HTTPS tijdens het request.

Interne-bestandenbescherming blijft bewust een handmatige servercontrole; de endpoint toont geen `phpinfo`, secrets of verbindingsgegevens.

## Uitgevoerde controles

- **Gefaald — echte hostingcontrole:** niet uitvoerbaar zonder InfinityFree-account, database en dashboardorigin.
- **Niet uitgevoerd — upload:** er is niets naar hosting geüpload.
- **Geslaagd — lokale PHP-syntax:** `php -l backend/public/index.php` gaf geen syntaxfouten; dit is geen runtime- of hostingtest.
- **Niet uitgevoerd — HTTPS/cookies op hosting:** lokale broncode kan dit niet bewijzen.
- **Niet uitgevoerd — databaseverbinding:** geen credentials gebruikt en geen database geraakt.

## Handmatige controle-instructies

1. Upload uitsluitend `backend/public/index.php` als publieke PHP-entrypoint. Houd `.env` buiten git en buiten de webroot als InfinityFree dat ondersteunt; anders blokkeer directe toegang met de hostinginstelling/.htaccess.
2. Zet server-side environmentwaarden uit `.env.example`; gebruik een lang willekeurig token. Zet `HOSTING_CHECK_ENABLED=1` pas vlak vóór de controle en vul exact de HTTPS-dashboardorigin in.
3. Test JSON vanaf de echte dashboardorigin met credentials en header. Gebruik bijvoorbeeld browserconsole:

   ```js
   fetch('/api/_hosting-check', { headers: { 'X-Hosting-Check-Token': 'TIJDELIJK_TOKEN' }, credentials: 'include' }).then(r => r.json())
   ```

4. Test sessie in twee afzonderlijke requests met dezelfde cookie jar: POST `action=session-start`, daarna POST `action=session-check`. De status moet `geslaagd` zijn.
5. Controleer het rapport (`GET` of POST zonder action). Noteer per check exact `geslaagd`, `gefaald` of `niet uitgevoerd`; kopieer geen secrets naar documentatie.
6. Controleer handmatig in de browser: `https://dashboard.example/...` en `/api/...` gebruiken HTTPS; cookies hebben `Secure`, `HttpOnly` en passend `SameSite`; `.env`, bronbestanden en directory listings geven geen inhoud terug.
7. Voer na afloop POST `action=cleanup` uit. Controleer in MySQL dat alleen `__dnd_hosting_check` is verwijderd en dat bestaande tabellen onaangeraakt zijn.
8. Zet `HOSTING_CHECK_ENABLED=0`, roteer/verwijder het tijdelijke token en verwijder de route van de upload als de controle klaar is.

Een lokale HTTP-test, indien later uitgevoerd, telt niet als hostingtest. Upload, origin, HTTPS, cookies en InfinityFree-PHP moeten expliciet worden vastgelegd voordat de controle als geslaagd kan worden beoordeeld.

