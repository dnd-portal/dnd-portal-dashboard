# Bevindingen

Het oude `AdminOverview` las rechtstreeks de browser-mockdatabase. Ook de admin-sidebar telde mockarrays en de adminlayout laadde altijd `getDatabase()`. Dit gaf geen bewijs over echte autorisatie of API-uitval.

De backend had al een dashboardendpoint, maar gaf slechts drie compacte velden terug en miste RSVP-deelnemers, beheercounts en activiteit. De nieuwe queries hergebruiken dezelfde membership-, poll-participant- en eigen betalingsregels als de detailoverzichten.

Niet-gemigreerde adminpagina’s gebruiken nog de bestaande demo-context voor presentatie. Die context is niet langer de bron voor het dashboard of de navigatietotalen; deze resterende pagina’s moeten in volgende verticale stappen expliciet worden gemigreerd en mogen niet als productiegegevens worden geïnterpreteerd.
