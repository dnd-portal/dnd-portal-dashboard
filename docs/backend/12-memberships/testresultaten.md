# Testresultaten

- **Geslaagd — `pnpm format:check`.**
- **Geslaagd — `pnpm check`.** Svelte-check: 0 errors, 0 warnings.
- **Geslaagd — `pnpm build`.**
- **Geslaagd — PHP syntaxcontrole:** `CampaignService.php` en `public/index.php`.
- **Niet uitgevoerd — echte membership-integratietest:** toevoegen, wijzigen, vertrek, heractivatie, dubbele requests en campagnegrenzen zijn nog niet tegen MariaDB uitgevoerd.
- **Niet uitgevoerd — browsermembershiptest:** de auth-browserflow bewijst deze feature niet.
- **Niet uitgevoerd — InfinityFree:** geen hostingvalidatie.

Een poging om `pnpm test` vanuit de repositoryroot uit te voeren faalde omdat daar geen `package.json` staat; de frontendchecks zijn vanuit `player/` wel uitgevoerd.
# Testresultaten

- **Geslaagd:** `pnpm format:check`.
- **Geslaagd:** `pnpm check` zonder fouten of waarschuwingen.
- **Geslaagd:** zelfstandige admin-concurrencymatrix gebruikt echte MariaDB-data en laat runner-fixtures ongemoeid.
- **Niet geslaagd:** echte memberships-browserflow. `POST /api/v1/campaigns/1/members` gaf 201, maar `GET /api/v1/campaigns/1/members` gaf daarna `{"data":[]}`.
- **Geslaagd:** de drie bestaande auth-browserflows bleven groen in de volledige runner.
- **Geslaagd — regressie:** nieuwe runner `dnd_portal_test_run_3212420261026195511` controleerde POST-response, directe opslag en GET van hetzelfde membership.
- **Geslaagd — browser:** memberships add → reload → role change → end → reactivate; volledige Playwright-run `4 passed (15.4s)`.
- **Oorzaak:** de GET-route gebruikte `basename()` en castte het laatste padsegment `members` naar campagne-ID `0`.
- **Herstel:** GET-members-route parseert nu expliciet segment 3; POST retourneert de werkelijk opgeslagen allowlist-rij en valideert campagne/gebruiker/opslag.
