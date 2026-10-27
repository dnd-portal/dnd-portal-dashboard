# Bestaande-data-migratie

De lokale repository bevat geen aantoonbaar oud/pre-V1 schema, export of legacy-DDL waarop een synthetische migratie kan worden gebaseerd. `backend/database/migrations/001_initial_portal_schema.sql` is het huidige schema, geen bronhistorie.

Daarom is geen migratie verzonnen of uitgevoerd. Zodra een echte oude schema-export beschikbaar is, moet eerst een veld- en relatie-mapping worden opgesteld en daarna een geïsoleerde dry-run met row counts, foreign keys en financiële saldi worden uitgevoerd.
