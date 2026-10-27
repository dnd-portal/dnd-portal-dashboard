# Migratiemapping en dry-run

- `campaigns.ts` IDs 1–6 blijven campagne-ID’s; `type='campaign'`.
- `oneShots.ts` wordt een campaign met `type='one_shot'`; de bestaande ID-collision met session 7 blijft opgelost via de eerder vastgelegde mapping: one-shot campaign 7 en nieuwe vrije game-session-ID.
- `memberships.ts` wordt `campaign_memberships`; actieve leden krijgen `left_at=NULL`.
- `sessions.ts` wordt `game_sessions`; `members` wordt vooraf gevalideerd en daarna `session_participants`. Geen arraykolom.
- `availability.ts` wordt per user/date genormaliseerd naar `user_availability` met status `unavailable`; niet aanwezige datums blijven onbekend.

Voor echte data: maak eerst een volledige backup, voer een read-only dry-run uit op IDs, ontbrekende campagnes/users, dubbele slugs en ongeldige datums, vergelijk aantallen en foreign keys, en stop bij iedere ontbrekende relatie. Pas daarna de additive migraties toe. Geen publieke migratieroute en geen database-reset. Zonder SSH: phpMyAdmin na backup, in gecontroleerde delen, met `schema_migrations`-registratie.

