# Wijzigingen

- Pollservice gebruikt expliciete responsevelden, versiecontrole en server-side campagne-/beheerrechten.
- Stemmen controleren nu expliciet `poll_participants`, dezelfde-poll-opties en single/multiple-regels. De pollrij wordt met `FOR UPDATE` vergrendeld, zodat sluiten en stemmen niet ongecontroleerd kruisen.
- Toegevoegd: poll detail, update/close, resultaten en participant toevoegen/verwijderen via API.
- Polloptie-ID’s blijven de verwijzing; labels zijn uitsluitend presentatie. Er is geen optie-deleteactie, zodat bestaande stemmen niet breken.
- Admin-pollroutes gebruiken de centrale API-client zonder mockfallback. De bestaande externe styling- en componentafspraken zijn behouden.
# Actuele wijzigingen

- `006_poll_multiple_vote_key.sql` maakt meerdere opties per gebruiker per poll mogelijk zonder de optie-ID’s te veranderen.
- Pollupdates accepteren alleen bestaande optie-ID’s en wijzigen labels zonder stemmen te verliezen; structurele wijzigingen blijven geblokkeerd.
- De centrale `PollDetail`-component is aangesloten op admin- en spelerpollroutes met echte API-aanroepen en zonder mockfallback.
- Backendvalidaties voor verkeerde pollopties, niet-deelnemers en onbevoegde beheerders zijn toegevoegd aan de integratierun.
- De browserfixture gebruikt een afzonderlijke polladmin/pollplayer-opzet; de browserflow is nog niet functioneel geslaagd en blijft openstaand.
- Fixture-isolatie is idempotent gemaakt: rollen, actieve memberships, wachtwoorden en test-rate-limitrecords worden alleen in de expliciete testdatabase hersteld.
- De admin-layout laat optionele dashboardcounts niet langer de geautoriseerde pagina verwijderen; autorisatie blijft uitsluitend gebaseerd op `/auth/me`.
- Browserrollen gebruiken afzonderlijke browsercontexts; de spelersessie deelt geen admincookie.
- De pollbrowserflow wacht op de concrete succesmelding en controleert daardoor ook dat de stemrequest werkelijk is verwerkt.

