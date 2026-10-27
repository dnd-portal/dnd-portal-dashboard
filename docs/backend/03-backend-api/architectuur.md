# Folderstructuur en verantwoordelijkheden

```text
backend/
  public/
    index.php                 # enige PHP-frontcontroller
    api/.htaccess              # directe endpointbestanden blokkeren
  src/
    Config.php                 # environment-only configuratie
    Http/                      # kleine request-, response- en routerlaag
    HealthService.php          # niet-gevoelige runtime-check
    Auth/                       # volgende stap: sessie/CSRF/identiteit
    Domain/                     # kleine services per feature, geen framework
    Database/                   # PDO-verbinding en concrete querymodules
    Validation/                 # inputregels en normalisatie
  migrations/                  # reviewbare SQL; nooit publiek uitvoeren
  tests/                       # PHP unit/contract tests
  .env.example                 # placeholders; echte .env nooit committen
  router.php                   # alleen lokale PHP built-in server
player/src/lib/api/
  client.ts                    # enige frontend HTTP-client
  index.ts                     # publieke TypeScript API-surface
```

`public/index.php` routeert alleen `/api/v1/*`. Domeinlogica hoort niet in de frontcontroller. Repositories worden alleen toegevoegd wanneer een concrete service ze nodig heeft; geen generieke CRUD-laag. Health, auth en de inmiddels toegevoegde concrete domeinservices worden via deze frontcontroller aangeboden; runtimevalidatie vereist nog MySQL.

## Publieke deploymentindeling

Voorkeursindeling: document root bevat de statische frontendbuild en `api/` als PHP-ingang; `src/`, `.env`, migrations en tests staan buiten de document root. Dit is op InfinityFree nog **niet gecontroleerd**. Als InfinityFree alleen één document root toestaat, plaats uitsluitend `public/index.php` en `public/api/.htaccess` publiek, en blokkeer directe toegang tot `src/`, `.env`, migrations en tests met hostingregels. Upload geen projectroot als webroot zonder handmatige HTTP-controle.

