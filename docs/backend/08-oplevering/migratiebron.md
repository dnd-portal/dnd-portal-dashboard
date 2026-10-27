# Bestaande-data-migratie

De lokale repository bevat geen aantoonbaar oud/pre-V1 schema, export of legacy-DDL waarop een accountmigratie kan worden gebaseerd. De beschikbare refactor-ZIP bevat alleen geanonimiseerde frontend-mocktabellen. `backend/database/migrations/001_initial_portal_schema.sql` is het huidige schema, geen bronhistorie.

Daarom is geen migratie verzonnen of uitgevoerd. Zie `accountmigratie.md` voor de exact benodigde bronbestanden en hashmetadata. Zodra die beschikbaar zijn, moet eerst een veld- en relatie-mapping worden opgesteld en daarna een geïsoleerde dry-run met synthetische data, row counts en foreign keys worden uitgevoerd.
