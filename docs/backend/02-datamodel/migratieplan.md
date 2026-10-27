# Migratieplan

## Voorbereiding en dry-run

1. Maak een volledige MySQL-dump inclusief routines/permissions waar beschikbaar en bewaar die buiten de webroot. Test herstel in een lege lokale database.
2. Maak een read-only extract van de mockdata; gebruik geen productiegegevens in fixtures, git of rapporten.
3. Controleer IDs, lege strings, dubbele genormaliseerde email/usernames, ontbrekende membership/session/poll/payment-relaties en ongeldige dates/statuswaarden.
4. Run de SQL in een tijdelijke database met `FOREIGN_KEY_CHECKS=1`; vergelijk row counts en foreign-keyrapporten. Breek af bij elke collision of ontbrekende relatie.
5. Pas pas na goedkeuring de migratie toe via een operatorverbinding. Registreer versie en SHA-256 in `schema_migrations`.

## Concrete bronmapping

- `users` en `user`: bestaande numerieke IDs blijven behouden waar ze geldig zijn. De mockgebruiker met wachtwoord `test` krijgt geen productiecredential; maak credential aan via een eenmalige invite/resetflow.
- `campaigns`: bestaande IDs 1–6 blijven behouden en krijgen `type='campaign'`; `location` en slug komen uit de mockrijen.
- `memberships` → `campaign_memberships`; `userId`/`campaignId` worden FKs. Onbekende IDs worden gerapporteerd en niet stilzwijgend aangemaakt.
- `sessions`: bestaande session-ID’s 1–9 blijven behouden als `game_sessions` wanneer de campagne-relatie geldig is.
- `oneShots`: de bron gebruikt `id=7`, terwijl `sessions` al `id=7` heeft. Maak campaign ID 7 met `type='one_shot'`; map de one-shot als `game_sessions.id=10` (of de eerstvolgende vrije ID) en leg de mapping vast in het migratierapport. De member-array wordt `session_participants`.
- `inviteCodes`: code nooit importeren; importeer alleen een hash, gekoppeld aan de bestaande user indien eenduidig, en forceer heruitgifte als de broncode niet veilig kan worden gehasht.
- `polls`/`pollResponses`: maak `poll_options` eerst en map antwoordteksten naar option-ID’s. Onbekende antwoordteksten blokkeren de migratie.
- `paymentRequests`/`paymentRequestUsers`: parse bedragen uitsluitend wanneer exact naar cents te converteren; payment URL is geen betalingsbewijs. Maak obligations en alleen expliciet bevestigde entries.
- `availability`: elke datum/user-combinatie wordt een rij `unavailable`; ontbrekende datums blijven onbekend.
- `organizerDashboard`: niet migreren; dit is afgeleide dashboarddata.

## Dubbele en ontbrekende identifiers

- **Status: niet uitgevoerd:** de actuele mockdata is niet als productie-import uitgevoerd.
- Controleer duplicates na normalisatie op email/username; een collision moet handmatig worden opgelost vóór import.
- Controleer dat alle membership-, participant-, poll-, payment- en creator-IDs bestaan. Rapportage bevat aantallen en bron-ID’s, maar geen productiegegevens.

## InfinityFree zonder SSH

Gebruik alleen de door InfinityFree aangeboden phpMyAdmin/MySQL-interface, na handmatige backup. Importeer het SQL-bestand in gecontroleerde delen als uploadlimieten dat vereisen. Voer geen SQL uit via een publieke PHP-route en zet `schema_migrations` alleen bij succesvolle uitvoering bij. Bij fout: stop, bewaar foutmelding en herstel vanuit dump; niet opnieuw seeden.

## Herstel

Voor deze eerste additive schema-aanleg is herstel primair: restore de vooraf gemaakte dump naar een gecontroleerde database en wijs de applicatie pas daarna om. Niet `DROP DATABASE`, niet automatisch resetten en niet bestaande tabellen overschrijven.

