# Conclusies

## Oordeel

De technische basis is bruikbaar voor een gefaseerde migratie, maar nog niet klaar voor productie. De bestaande Svelte-interface kan behouden blijven en een PHP API onder dezelfde hostname is verenigbaar met de gewenste richting. De huidige app is echter volledig mockgedreven en heeft geen echte sessie-, database- of deploymentlaag.

## Harde blokkades

- **InfinityFree-compatibiliteit is onbewezen:** zonder echte hostingcontrole zijn PHP-versie, extensies, HTTPS, cookies, routing en MySQL niet bevestigd.
- **Geen databasecontract:** de TypeScript-entiteiten zijn nog geen gevalideerd relationeel schema met InnoDB/foreign keys.
- **Geen productie-authenticatie:** registratie en login zijn demo-UI; Google en Discord blijven buiten scope.
- **Statische frontenddeployment moet expliciet worden gekozen:** `adapter-auto` bewijst geen passende InfinityFree-build; SvelteKit-serverfunctionaliteit mag niet worden verwacht.

## Voorwaarden voor de volgende stap

1. Voer de hostingcontrole uit op de echte dashboardorigin en leg elk resultaat vast.
2. Kies een concrete statische frontend-output en een uploadstructuur waarbij `/api/` naar PHP gaat.
3. Ontwerp daarna pas additive SQL-migraties en een schema voor de geïnventariseerde entiteiten.
4. Bouw vervolgens native PHP-authenticatie met sessies, CSRF en prepared PDO-statements; verplaats geen credentials naar de frontend.

Geen van deze voorwaarden mag worden vervangen door een lokale build of een fictieve InfinityFree-uitkomst.

