# instancetemplate — Methoden-Doku
**Datei:** `classes/external/instancetemplate.php` · **LOC:** 96 · **Subsystem:** S11 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S11_external_api.md)

## Klassenueberblick
`instancetemplate` ist eine Moodle-External-API-Funktion (`mod_booking_instancetemplate`), die ein gespeichertes Booking-Instanz-Template aus `booking_instancetemplate` laedt und als JSON-Rohstring (`PARAM_RAW`) zurueckliefert. Keine Instanz-Persistenz — reine statische WS-Klasse (`extends external_api`). Kollaborateure: `$DB`, `mod_booking\permissions` (Capability-Gate `has_capability_anywhere`). Drei Standard-Slots: `execute_parameters` / `execute` / `execute_returns`.

## Methoden

### `public static function execute_parameters(): external_function_parameters` — public static
- **Zweck:** Deklariert den einzigen Parameter `id` (`PARAM_INT`) = Template-id. **Seiteneffekte:** keine. **Rueckgabe:** `external_function_parameters`. **Bewertung:** A — kanonisch.

### `public static function execute(int $id): array` — public static
- **Zweck:** Validiert Parameter, prueft das Capability-Gate und liest den Template-Record. **Seiteneffekte:** `self::validate_parameters(...)`; `permissions::has_capability_anywhere()` — bei `false` `\moodle_exception('nopermissions','error')`; `$DB->get_record('booking_instancetemplate', ['id' => $id], '*', IGNORE_MISSING)`. **Rueckgabe:** Array `id`/`name`/`template` (`$template->name`, `$template->template`). **Bewertung:** C — (1) `IGNORE_MISSING` liefert bei nicht existierender id `false`; der direkte Zugriff `$template->name`/`$template->template` wirft dann „Attempt to read property on bool" statt einer sauberen WS-Fehlermeldung (Z.75–81). (2) Kein `validate_context(...)` — das Gate ist nur das site-weite `has_capability_anywhere`, kein Modul-Kontext; fuer einen reinen Template-Read vertretbar, aber nicht praezise.

### `public static function execute_returns(): external_single_structure` — public static
- **Zweck:** Beschreibt die Rueckgabe: `id` (`PARAM_INT`), `name` (`PARAM_TEXT`), `template` (`PARAM_RAW`, JSON-serialisierte Template-Daten). **Seiteneffekte:** keine. **Bewertung:** A.

## Bewertungs-Resümee
Schlanke Read-only-WS-Klasse mit Capability-Gate (`has_capability_anywhere`), was sie gegenueber `optiontemplate` deutlich aufwertet. Einziger funktionaler Mangel: der ungesicherte Zugriff auf den `IGNORE_MISSING`-Record kann bei ungueltiger id einen PHP-Fehler statt einer kontrollierten Exception erzeugen. Klassen-Score **C / P3**.
