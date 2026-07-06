# booking_action — Methoden-Doku
**Datei:** `classes/bo_actions/booking_action.php` · **LOC:** 128 · **Subsystem:** S04 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S04_*.md)

## Klassenueberblick
`booking_action` ist die abstrakte Basisklasse aller bo_action-Typen (`bookotheroptions`, `cancelbooking`, `executerestscript`, `userprofilefield`). Eine bo_action ist eine „After-Action", die nach einer Buchung/Storno einer Buchungsoption ausgefuehrt wird. Die Klasse haelt keinen eigenen Zustand; sie buendelt drei geteilte Verhalten: Namensaufloesung ueber die Sprachdatei (`get_name_of_action`), Persistenz einer einzelnen Aktion in das `json`-Feld der Buchungsoption (`save_action`) und das Default-`apply_action`-No-op-Contract, das Subklassen ueberschreiben. Persistenz: kein eigener Table — Aktionen leben serialisiert unter `jsonobject->boactions[<id>]` im `booking_options.json`-Feld, geschrieben via `booking_option::update`. Kollaborateure: `singleton_service` (Option-Settings), `booking_option::update`, `context_module`, Konsument `actions_info` (Discovery/Ausfuehrung).

## Methoden

### `public static function get_name_of_action()` — public static
- **Zweck:** Liefert den lokalisierten Anzeigenamen der konkreten Aktionsklasse. **Seiteneffekte:** keine; nutzt `get_called_class()` (Late Static Binding), zerlegt den FQCN und holt den letzten Namensteil als Sprachstring-Key via `get_string($classname, 'mod_booking')`. **Rueckgabe:** lokalisierter String. **Bewertung:** A — kompakter, korrekter LSB-Helfer; setzt voraus, dass ein Sprachstring mit dem Klassen-Shortname existiert.

### `public static function save_action(stdClass &$data)` — public static
- **Zweck:** Serialisiert eine einzelne Aktion (`$data`) in das `boactions`-Array innerhalb des Options-JSON und persistiert das gesamte JSON ueber `booking_option::update`. **Seiteneffekte:** liest Settings via `singleton_service::get_instance_of_booking_option_settings($data->optionid)`; mutiert `$data` per Referenz (vergibt `$data->id`, entfernt `optionid`/`cmid`); ruft `booking_option::update($newdata, $context)` mit `importing = true` → DB-Schreibzugriff und Cache-Invalidierung der Option. **Rueckgabe:** void. **Bewertung:** C — die ID-Vergabe `$data->id = count((array)$jsonobject->boactions) + 1` ist kollisionsanfaellig: nach dem Loeschen einer mittleren Aktion (z.B. ids {1,3}, count=2 → neue id 3) ueberschreibt der neue Eintrag eine bestehende Aktion (Datenverlust). Defensives `(array)`-Casting kompensiert inkonsistente JSON-Form (Objekt vs. Array), zeigt aber die Fragilitaet des serialisierten Modells.

### `public function set_defaults(stdClass &$data, stdClass $record)` — public
- **Zweck:** Kopiert alle Properties eines gespeicherten Aktions-Records in das Formular-Daten-Objekt (Vorbelegung des Edit-Formulars). **Seiteneffekte:** mutiert `$data` per Referenz (flache Property-Kopie). **Rueckgabe:** void. **Bewertung:** B — trivialer Copy-Loop; ueberschreibt vorhandene Keys ohne Schutz, was hier aber gewollt ist. Subklassen koennen die Methode fuer feldspezifisches Mapping ueberschreiben.

### `public function apply_action(stdClass $actiondata, int $userid = 0)` — public
- **Zweck:** Default-Implementierung des Aktions-Contracts; tut nichts. **Seiteneffekte:** keine. **Rueckgabe:** `int` Status (0 = nichts tun / weitere Aktionen erlauben, 1 = Abbruch aller folgenden Aktionen) — hier immer 0. **Bewertung:** A — sauberer No-op-Default, den jede konkrete Aktionsklasse ueberschreibt.

## Bewertungs-Resümee
Schlanke, gut strukturierte Basisklasse mit klarem Contract (`apply_action`-Status-Rueckgabe steuert die Aktions-Kette). Hauptschwaeche ist die ID-Vergabe in `save_action` (`count+1`), die nach Loeschungen bestehende Aktionen ueberschreiben kann — ein realer Datenverlust-Pfad. Ansonsten funktional unkritisch. Klassen-Score **B / P3**.
