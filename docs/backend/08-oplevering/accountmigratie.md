# Accountmigratie — broncontrole

## Onderzocht

- `dnd-portal-player-refactor-input.zip` en `refactor-zip-inspect/` bevatten alleen de SvelteKit-player en geanonimiseerde frontend-mocktabellen.
- `backend/database/migrations/001_initial_portal_schema.sql` t/m `008_approved_product_policies.sql` zijn het huidige schema en geen oud accountschema.
- Er is geen legacy-DDL, productie-export, gebruikers-CSV, credential-export of hash-algoritmedocumentatie aanwezig.

## Niet uitgevoerd

Er is geen accountmigratie verzonnen of uitgevoerd. Zonder echte bron kan identiteit niet betrouwbaar worden behouden en kan hashcompatibiliteit niet veilig worden vastgesteld. Er zijn geen livegegevens gewijzigd en er is geen credential of hash gelogd.

## Nodig om dit af te ronden

1. Een schema-export of DDL van het oude gebruikers-/accountmodel, inclusief kolomnamen, primaire sleutels en relaties.
2. Een synthetisch of geanonimiseerd data-exportformaat met minimaal stabiele gebruikersidentiteit, displaynaam, e-mail/username en accountstatus.
3. Hashmetadata: algoritmenaam, cost/parameters, encoding en een gecontroleerde compatibiliteitsfixture; nooit plaintextwachtwoorden.
4. Een expliciete mapping van oude identiteit naar huidige `users`, inclusief de bron voor bestaande campaign memberships.

Actieve sessies, resetcodes, invitetokens en adminrollen worden niet gemigreerd. Adminrechten moeten per huidige bron expliciet worden aangeleverd en mogen niet uit naam, e-mail of oude roltekst worden afgeleid.
