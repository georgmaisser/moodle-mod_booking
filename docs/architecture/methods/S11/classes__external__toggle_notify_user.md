# toggle_notify_user — Methoden-Doku
**Datei:** `classes/external/toggle_notify_user.php` · **LOC:** 98 · **Subsystem:** S11 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S11_external_api.md)

## Klassenueberblick
`toggle_notify_user` ist eine duenne `external_api`-Webservice-Klasse, die das Umschalten der Warteliste-Benachrichtigung („benachrichtige mich, wenn ein Platz frei wird") fuer einen Nutzer auf einer Buchungsoption an die Domaenenschicht delegiert. Sie haelt keinen Zustand und besitzt keine Persistenz; die eigentliche Arbeit erledigt `booking_option::toggle_notify_user()`. Kollaborateure: `external_api` (Parameter-Validierung), `mod_booking\booking_option`. Folgt dem Moodle-WS-Tripel `execute_parameters()` / `execute()` / `execute_returns()`.

## Methoden

### `public static function execute_parameters(): external_function_parameters` — public static
- **Zweck:** Beschreibt das Eingabeschema: `userid` (PARAM_INT) und `optionid` (PARAM_INT). **Seiteneffekte:** keine. **Rueckgabe:** `external_function_parameters`. **Bewertung:** A — Standard-Boilerplate.

### `public static function execute(int $userid, int $optionid): array` — public static
- **Zweck:** Validiert die Parameter und ruft `booking_option::toggle_notify_user($userid, $optionid)` auf, dessen Ergebnis-Array direkt zurueckgereicht wird. **Seiteneffekte:** keine eigenen; alle DB-Schreibvorgaenge (An-/Abmeldung der Notify-Liste) liegen in der delegierten Methode. **Rueckgabe:** `array` mit `status`/`optionid`/`error`. **Bewertung:** B — funktional korrekt, aber die Methode ruft weder `self::validate_context()` noch eine eigene `has_capability()`-Pruefung am Optionskontext auf; die Autorisierung haengt vollstaendig davon ab, dass `booking_option::toggle_notify_user()` Kontext und Rechte selbst absichert (im Gegensatz zu `update_bookingnotes`, das explizit prueft).

### `public static function execute_returns(): external_single_structure` — public static
- **Zweck:** Beschreibt das Rueckgabeschema: `status` (PARAM_INT, 1 = auf Liste, 0 = nicht), `optionid` (PARAM_INT), `error` (PARAM_RAW). **Seiteneffekte:** keine. **Rueckgabe:** `external_single_structure`. **Bewertung:** A. Hinweis: `use external_warnings;` und `use stdClass;` sind importiert, aber ungenutzt (toter Import).

## Bewertungs-Resümee
Minimaler, korrekter WS-Delegat ohne Eigenlogik. Einziger funktionaler Vorbehalt: keine Kontext-/Capability-Pruefung auf WS-Ebene, sodass die Sicherheit ausschliesslich in der delegierten Domaenenmethode liegen muss. Ungenutzte Imports sind kosmetisch. Klassen-Score **B / P3**.
