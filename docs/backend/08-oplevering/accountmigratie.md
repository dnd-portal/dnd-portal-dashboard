# Accountmigratie — broncontrole

## Onderzocht

- `C:\Users\toonv\Downloads\users.sql (1).zip` bevat een phpMyAdmin-export van het oude `users`-schema met 7 records en de velden `id`, `name`, `email`, `password_hash`, `role` en timestamps.
- `dnd-portal-player-refactor-input.zip` en `refactor-zip-inspect/` bevatten daarnaast alleen de SvelteKit-player en geanonimiseerde frontend-mocktabellen.
- `backend/database/migrations/001_initial_portal_schema.sql` t/m `008_approved_product_policies.sql` zijn het huidige schema en geen oud accountschema.
- De export bevat geen usernameveld, geen expliciete identiteit→huidige-user-mapping en geen adminrechtenmapping.

## Eerdere dry-run

Er is uitsluitend een dry-run uitgevoerd op `dnd_portal_test_run_20261027120001`; er zijn 0 bronrecords geïmporteerd. Resultaat: 7 records, 0 emailconflicten, 7 ontbrekende usernamebronnen, 6 bcrypt-hashes compatibel met de huidige verifier en alleen oud ID `1` waarvoor een wachtwoordreset nodig is. Geen persoonsgegevens, hashes of credentials zijn naar de repository-output gelogd.

## Voorstel voor beoordeling

`C:\Users\toonv\Downloads\dnd-account-migration-proposal.local.json` bevat lokaal buiten Git het concrete voorstel voor de 4 te behouden accounts. Het voorstel sorteert op oud ID, kent in de geïsoleerde proefimport deterministische nieuwe IDs 1–4 toe en maakt usernames met de vorm `<genormaliseerde-naam>-<oud-id>`. Daarmee blijven usernames uniek en herhaalbaar; oud IDs 1, 2 en 5 staan niet in de records.

Het definitieve voorstel behoudt 4 compatibele hashes ongewijzigd en bevat geen resetaccount. Oud IDs `1`, `2` en `5` worden uitgesloten; er wordt geen standaardwachtwoord voorgesteld. Voor alle records blijven `admin_mapping` en `campaign_membership_mapping` leeg. De code weigert email- en usernameconflicten expliciet en een tweede planning levert exact hetzelfde voorstel op.

## Nodig om dit af te ronden

1. Een schema-export of DDL van het oude gebruikers-/accountmodel, inclusief kolomnamen, primaire sleutels en relaties.
2. Een usernamebron of expliciete username-mapping voor alle 7 accounts.
3. Een expliciete mapping van oude identiteit naar huidige `users`, inclusief de bron voor bestaande campaign memberships.
4. Een expliciete adminrechtenmapping; de oude `role`-kolom is niet voldoende autorisatiebewijs.

Actieve sessies, resetcodes, invitetokens en adminrollen worden niet gemigreerd. Adminrechten moeten per huidige bron expliciet worden aangeleverd en mogen niet uit naam, e-mail of oude roltekst worden afgeleid.

## Definitief voorstel en proefimport

Oud IDs 1, 2 en 5 zijn uitgesloten. Het lokale voorstel buiten Git bevat exact vier
records met deterministische usernames in de vorm
`<genormaliseerde-naam>-<oud-id>` en een expliciete `legacy_id` naar
`proposed_new_id`-mapping. Er worden geen rollen, campagnememberships,
sessies, resetcodes of invitetokens toegekend.

De proefimport draaide uitsluitend op
`dnd_portal_migration_test_20261027153001` met het huidige schema. Resultaat:
`IMPORT_OK excluded_legacy_ids=1,2,5 inserted=4 reused=0 total_users=4 roles=0
sessions=0 reset_tokens=0 invites=0`. De tweede run gaf
`inserted=0 reused=4`; bestaande records en wachtwoordhashes worden exact
gevalideerd en nooit stilzwijgend overschreven. De integratietest controleerde
de zes overgenomen hashwaarden byte-voor-byte zonder ze te tonen of te loggen.

Conflicten op email of username worden expliciet geweigerd. De synthetische
planner-test controleert deterministische planning, beide conflictsoorten en
een synthetisch bcrypt-wachtwoord via dezelfde `password_verify`-primitief als
de huidige AuthService. Werkelijke oude wachtwoorden zijn niet getest omdat
die niet bekend zijn. Het bijgewerkte voorstel staat lokaal buiten Git op
`C:\Users\toonv\Downloads\dnd-account-migration-proposal.local.json`.
