# Wijzigingen

- PaymentService geeft expliciete allowlists terug voor requests, verplichtingen en berekende `paid_cents`/`outstanding_cents`.
- Toegevoegd: payment-request detail en verplichting aanmaken.
- Ontvangsten controleren server-side campagnebevoegdheid, positieve gehele centen, exacte valuta en dubbele externe referenties.
- Externe betaal-URL’s accepteren uitsluitend HTTPS-hosts zonder userinfo.
- Eigen verplichtingen blijven beperkt tot de ingelogde gebruiker.
- Admin-overzicht en detail gebruiken de centrale API-client; mockdata is voor deze routes geen fallback.
- Bestaande component- en SCSS-structuur is behouden; er is geen provider-, webhook- of bankintegratie toegevoegd.
