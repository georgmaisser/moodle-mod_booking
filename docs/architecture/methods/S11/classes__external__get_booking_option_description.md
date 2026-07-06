# get_booking_option_description — Methoden-Doku
**Datei:** `classes/external/get_booking_option_description.php` · **LOC:** 119 · **Subsystem:** S11 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S11_external_api.md)

## Klassenueberblick
`get_booking_option_description` (extends `external_api`) liefert die Render-Daten (JSON) der Optionsbeschreibung plus den zu verwendenden Mustache-Templatenamen, typischerweise fuer asynchrones Nachladen der Detail-Ansicht. Persistenz: keine eigene; bezieht alle Daten ueber `singleton_service`-Caches und das Output-DTO. Kollaborateure: `singleton_service` (booking, user, option_settings, booking_answers), `output\bookingoption_description`. Zugriffsschutz: **nur** `validate_parameters` — kein `validate_context`/`require_capability` (siehe Bewertung).

## Methoden

### `public static function execute_parameters(): external_function_parameters` — public static
- **Zweck:** Deklariert `optionid` und `userid` (beide `PARAM_INT`, Pflicht). **Bewertung:** A.

### `public static function execute(int $optionid, int $userid): array` — public static
- **Zweck:** Baut das `bookingoption_description`-DTO (Variante `MOD_BOOKING_DESCRIPTION_WEBSITE`) fuer die Option und ggf. einen konkreten User und gibt es JSON-kodiert samt Templatename zurueck. **Seiteneffekte:** `validate_parameters`; `singleton_service::get_instance_of_booking_by_optionid`, `..._of_user` (nur falls `userid > 0`), `..._of_booking_option_settings`, `..._of_booking_answers`; `bookinganswer->user_status($userid)` zur Bestimmung von `$forbookeduser`; Instanzierung des Output-DTO; `json_encode`. Normalisiert `$data->invisible` von `MOD_BOOKING_OPTION_INVISIBLE` auf echtes `bool`. **Rueckgabe:** `['content' => <json>, 'template' => 'mod_booking/bookingoption_description']`. **Bewertung:** B — funktional sauber und cache-effizient (singleton_service), aber **ohne Kontext-/Capability-Pruefung**: jede beliebige `optionid`/`userid`-Kombination wird ausgeliefert (potenzielle Info-Disclosure inkl. fremder Buchungsstatus via `user_status($userid)`). `$userid` geht roh (nicht `$params['userid']`) in `user_status` — hier identisch, aber inkonsistent zur sonstigen `$params`-Nutzung.

### `public static function execute_returns(): external_single_structure` — public static
- **Zweck:** Beschreibt die Rueckgabe `{content: PARAM_RAW, template: PARAM_TEXT}`. **Bewertung:** A.

## Bewertungs-Resümee
Kompakter Render-Daten-Service, der die `singleton_service`-Caches sinnvoll nutzt. Hauptkritik: fehlende Kontext-/Rechtepruefung (der Buchungsstatus eines beliebigen Users kann ueber `userid` erfragt werden) und die inkonsistente Roh-`$userid`-Nutzung. Klassen-Score **B / P3**.
