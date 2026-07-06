# init_comments — Methoden-Doku
**Datei:** `classes/external/init_comments.php` · **LOC:** 89 · **Subsystem:** S11 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S11_external_api.md)

## Klassenueberblick
`init_comments` ist eine minimale, parameterlose `external_api`-Webservice-Klasse, die die Moodle-Kommentar-Engine fuer die Client-Seite initialisiert (`comment::init()` registriert die noetigen JS-/AMD-Hooks). Wird vom AMD-Modul `amd/src/init_comments.js` angestossen. Keine Persistenz; einziger Kollaborateur: Core-`comment` (`comment/lib.php`).

## Methoden

### `public static function execute_parameters(): external_function_parameters` — public static
- **Zweck:** Deklariert eine leere Parameterliste (Kommentar "Currently no params here."). **Seiteneffekte:** keine. **Rueckgabe:** leere `external_function_parameters`. **Bewertung:** A.

### `public static function execute(): array` — public static
- **Zweck:** Initialisiert die Kommentar-Engine. **Seiteneffekte:** `comment::init()` (registriert die Comment-JS-Requirements im aktuellen Page-Output); ein auskommentierter `validate_parameters`-Aufruf verbleibt als Relikt. **Rueckgabe:** `['status' => true]` (konstant). **Bewertung:** B — kein `require_login`/Kontext-Gate, aber `comment::init()` ist ein reiner clientseitiger Init ohne Datenzugriff, daher praktisch harmlos; `status` ist immer `true` (kein echtes Fehlersignal moeglich).

### `public static function execute_returns(): external_single_structure` — public static
- **Zweck:** Beschreibt die Rueckgabe (`status` PARAM_BOOL). **Seiteneffekte:** keine. **Rueckgabe:** `external_single_structure`. **Bewertung:** A.

## Bewertungs-Resümee
Trivialer, parameterloser WS-Endpoint zum Anstossen von `comment::init()`. Konstantes `status=true`, kein Auth-Gate noetig wegen fehlendem Datenzugriff. Funktional unkritisch. Klassen-Score **B / P3**.
