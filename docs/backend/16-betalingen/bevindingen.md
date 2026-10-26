# Bevindingen

Het schema gebruikt `payment_requests`, `payment_obligations` en append-only `payment_entries`. Historie heeft RESTRICT foreign keys. Het bestaande saldo-model is:

`outstanding_cents = obligation.amount_cents - SUM(payment_entries.amount_cents WHERE status = 'confirmed')`.

De bestaande migration 004 bevat een verwijzing voor correcties, maar geen negatief bedrag of expliciete correctietype/status. Daarom wordt financiële historie niet verwijderd en wordt een correctie uitsluitend als traceerbare verwijzing opgeslagen; de precieze verrekenregel voor negatieve correcties is nog niet door het bestaande ontwerp bepaald.

De oude adminpagina’s gebruikten mockdata. De backend had geen verplichting-aanmaak- of detailendpoint en accepteerde onveilig brede URL-validatie.
