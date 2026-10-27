# Bestaande-data-migratie

De gebruiker leverde `C:\Users\toonv\Downloads\users.sql (1).zip` aan. Dit is een aantoonbare oude phpMyAdmin-export met één `users`-tabel en 7 records. Het bevat geen usernameveld, relatie-export of expliciete adminmapping. `backend/database/migrations/001_initial_portal_schema.sql` blijft het huidige schema, geen bronhistorie.

Daarom is geen import uitgevoerd. De geïsoleerde dry-run op `dnd_portal_test_run_20261027120001` controleerde conflicts en hashcompatibiliteit zonder records te schrijven; zie `accountmigratie.md` voor de exacte uitkomst en resterende mappings.
