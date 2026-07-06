# select_booking_manager — Methoden-Doku
**Datei:** `classes/booking_rules/conditions/select_booking_manager.php` · **LOC:** 171 · **Subsystem:** S06 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S06_booking_rules.md)

## Klassenueberblick
`select_booking_manager` implementiert `booking_rule_condition` und selektiert genau einen Nutzer: den Buchungs-Manager der betroffenen Booking-Instanz. Der Manager ist in `booking.bookingmanager` als **Username** gespeichert; die Condition joint daher ueber `booking` und `user` (`u.username = b.bookingmanager`). Die Condition ist parameterlos (kein eigenes Form-Feld ausser einer statischen Beschreibung); `rulejson` enthaelt nur den `conditionname`. Lebenszyklus wie die anderen Conditions (Form/Save/Defaults/Execute), aber datenfrei. Kollaborateure: `$DB`-SQL-Helfer, `MoodleQuickForm`, `lib.php`.

## Methoden

### `public function can_be_combined_with_bookingruletype(string $bookingruletype): bool` — public
- **Zweck:** Kombinierbarkeit mit jedem Regeltyp. **Seiteneffekte:** keine. **Rueckgabe:** konstant `true`. **Bewertung:** A.

### `public function set_conditiondata(stdClass $record)` — public
- **Zweck:** Laedt Condition-Daten aus DB-Record. **Seiteneffekte:** delegiert an `set_conditiondata_from_json`. **Bewertung:** A.

### `public function set_conditiondata_from_json(string $json)` — public
- **Zweck:** Speichert das rohe JSON. **Seiteneffekte:** setzt `$this->rulejson = $json` (kein Decode noetig, da keine Datenfelder). **Bewertung:** A — bewusst minimal, kein Decode/Guard noetig.

### `public function add_condition_to_mform(MoodleQuickForm &$mform, ?array &$ajaxformdata = null)` — public
- **Zweck:** Fuegt nur ein statisches Beschreibungs-Element hinzu (keine Eingabe). **Seiteneffekte:** mutiert `$mform`. **Bewertung:** A.

### `public function get_name_of_condition($localized = true)` — public
- **Zweck:** Anzeigename. **Seiteneffekte:** `get_string($this->conditionnamestringid, 'mod_booking')`. **Rueckgabe:** Name. **Bewertung:** A.

### `public function save_condition(stdClass &$data): void` — public
- **Zweck:** Schreibt nur `conditionname` ins `rulejson`. **Seiteneffekte:** `json_decode`/`json_encode`, mutiert `$data->rulejson`; ungenutztes `global $DB`. **Bewertung:** B — funktional korrekt; `global $DB` ist tot.

### `public function set_defaults(stdClass &$data, stdClass $record)` — public
- **Zweck:** Setzt nur `bookingruleconditiontype` als Default. **Seiteneffekte:** mutiert `$data`. **Bewertung:** A.

### `public function execute(stdClass &$sql, array &$params): void` — public
- **Zweck:** Injiziert JOINs auf `booking b` (ueber `b.id = bo.bookingid`) und `user u` (ueber `u.username = b.bookingmanager`), baut den `uniqueid`-Vorspaltenausdruck (`bo.id`/`u.id`/optional `bod.id`) und exponiert `u.id AS userid`; optionale Einschraenkung auf einen `userid` (`userid2`). **Seiteneffekte:** mutiert `$sql->select`/`->from`/`->where` und `$params`; `$DB->sql_concat`. **Bewertung:** B — sauber parametrisiert (keine String-Interpolation von Nutzerwerten); der JOIN ueber `username` ist konzeptionell fragil (Username als Fremdschluessel statt userid): ist `b.bookingmanager` leer/NULL oder zeigt auf einen nicht (mehr) existierenden/umbenannten Account, liefert die Condition keinen Empfaenger — kein Fehler, aber stille Nicht-Zustellung. Wie bei den Schwester-Conditions Param-Kollisionsrisiko (`userid2`) bei Mehrfach-Conditions.

## Bewertungs-Resümee
Schlankste der Conditions: datenfrei, gut parametrisiert, klar. Hauptrisiko ist konzeptionell (Join ueber `username` statt `userid` -> stille Nicht-Zustellung bei leerem/umbenanntem Manager), nicht ein direkter Code-Bug; dazu ein totes `global $DB`. Klassen-Score **B / P3**.
