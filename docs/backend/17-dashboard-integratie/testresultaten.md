# Testresultaten

## Statisch gecontroleerd

- PHP syntaxcontrole van de gewijzigde dashboardservice: moet vanuit repository-root worden uitgevoerd.
- Frontend format-, Svelte-check- en buildcontrole: opnieuw uitvoeren na deze wijzigingen.

## Lokale runtime-resultaten (2026-10-26)

- Verse database `dnd_portal_test_run_3056020261026213941`: migraties 001 t/m 007 geslaagd.
- Tweede migratieronde: alle zeven migraties gaven `SKIP`; bestaande database en volume zijn behouden.
- Speler-dashboard: na 9000 cent netto betaling op 10000 cent verplichting werd exact 1000 cent openstaand getoond; admin-totalen waren niet aanwezig in de spelerresponse.
- Admin-dashboard: admin-totalen en beperkte auditactiviteit werden via de echte API ontvangen.
- Duplicate payment reference: HTTP 409 en directe databasecontrole bevestigde één entry.
- Gecombineerde runner: backendbetalings- en dashboardchecks geslaagd; browser eindigde op 8 geslaagde tests en één bestaande pollfixture-fout vóór poll-login. De betaalbrowserflow zelf slaagde.
- Definitieve runner: auth/access 6/6, poll 2/2, payment/dashboard 2/2 browsertests; dashboard admin/speler reload- en privacychecks geslaagd.
- Pollfixture-oorzaak: login-rate limiter per testserver-IP werd door één gecombineerde browserbatch gedeeld. De runner voert browsergroepen nu met fixture-reset ertussen uit; productie-rate-limiting blijft actief.
- Frontend `pnpm check` en `pnpm build`: geslaagd.
- PHP-lint van PaymentService, DashboardService en payment_validations: geslaagd.

## Functioneel nog uit te voeren

- Lege gebruiker zonder campagnes, poll of verplichting.
- Speler met beperkte campagnes versus portaladmin.
- Sessie- en RSVP-totalen met deelnemers buiten de toegestane campagne.
- Open poll zonder deelname en poll met bestaande stem.
- Deelbetaling die het eigen saldo verlaagt.
- API-uitval: echte foutstatus zonder mockdata.
- Navigatiecounts tegen dezelfde gecontroleerde fixtures als de overzichten.
- Dashboardbrowserflow voor admin en speler na herstel van de bestaande pollfixture.

Er zijn geen InfinityFree-tests uitgevoerd.
