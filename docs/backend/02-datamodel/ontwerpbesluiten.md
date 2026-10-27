# Ontwerpbesluiten

1. **Normalisatie:** de mock-arrays worden 1NF-tabellen; many-to-many-relaties krijgen koppeltabellen. Geen ID-arrays of komma-strings.
2. **Identiteit:** email wordt getrimd en casefolded; username wordt getrimd en casefolded volgens één vast Unicode/ASCII-beleid in de applicatielaag. Opslag gebruikt die genormaliseerde lookupwaarde. Lege strings worden NULL. Login zoekt beide kolommen; nul matches is ongeldig, één match is geldig, meer dan één match is een harde datafout en wordt geweigerd.
3. **Wachtwoorden en geheimen:** PHP `password_hash()`-output past in VARCHAR(255). Sessies, invites en resetcodes worden als SHA-256 hashes opgeslagen; ruwe waarden bestaan alleen in het directe response-/mailmoment.
4. **Sessies:** `auth_sessions` is de database-backed PHP session handler. Er is geen tweede PHP-file-sessionbron en geen JWT/localStorage-authenticatie.
5. **Rechten:** globale portaladmin en campagne-DM zijn onafhankelijk. Een DM krijgt niet automatisch portalbeheer.
6. **Tijd:** applicatie en databaseverbinding draaien UTC; campagne-timezone bepaalt lokale kalenderweergave. `session_date` blijft een DATE.
7. **Historie:** foreign keys restricten verwijderen. Audit en betalingen zijn append-only of statusgedreven. `version` ondersteunt `UPDATE ... WHERE version=?`.
8. **Bedragen:** integers in cents plus valuta. Geen floating point en geen berekende dashboardtotalen als kolom.
9. **Compatibiliteit:** `ENUM` houdt statussen database-side beperkt en is beschikbaar in MySQL/MariaDB; wijzigingen aan statuswaarden zijn latere expliciete migraties.

