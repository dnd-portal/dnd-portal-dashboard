# Accountmigratie — broncontrole

## Onderzocht

- `C:\Users\toonv\Downloads\users.sql (1).zip` bevat een phpMyAdmin-export van het oude `users`-schema met 7 records en de velden `id`, `name`, `email`, `password_hash`, `role` en timestamps.
- `dnd-portal-player-refactor-input.zip` en `refactor-zip-inspect/` bevatten daarnaast alleen de SvelteKit-player en geanonimiseerde frontend-mocktabellen.
- `backend/database/migrations/001_initial_portal_schema.sql` t/m `008_approved_product_policies.sql` zijn het huidige schema en geen oud accountschema.
- De export bevat geen usernameveld, geen expliciete identiteit→huidige-user-mapping en geen adminrechtenmapping.

## Niet uitgevoerd

Er is uitsluitend een dry-run uitgevoerd op `dnd_portal_test_run_20261027120001`; er zijn 0 bronrecords geïmporteerd. Resultaat: 7 records, 0 emailconflicten, 7 ontbrekende usernamebronnen, 6 bcrypt-hashes compatibel met de huidige verifier en 1 hash waarvoor een wachtwoordreset nodig is. Geen persoonsgegevens, hashes of credentials zijn gelogd.

## Nodig om dit af te ronden

1. Een schema-export of DDL van het oude gebruikers-/accountmodel, inclusief kolomnamen, primaire sleutels en relaties.
2. Een usernamebron of expliciete username-mapping voor alle 7 accounts.
3. Een expliciete mapping van oude identiteit naar huidige `users`, inclusief de bron voor bestaande campaign memberships.
4. Een expliciete adminrechtenmapping; de oude `role`-kolom is niet voldoende autorisatiebewijs.

Actieve sessies, resetcodes, invitetokens en adminrollen worden niet gemigreerd. Adminrechten moeten per huidige bron expliciet worden aangeleverd en mogen niet uit naam, e-mail of oude roltekst worden afgeleid.
