# bookinganswer_waitingforconfirmation — Methoden-Doku
**Datei:** `classes/event/bookinganswer_waitingforconfirmation.php` · **LOC:** 95 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`bookinganswer_waitingforconfirmation` ist ein Moodle-Logevent (`\core\event\base`), das ausgeloest wird, wenn eine Buchungsantwort den Status „wartet auf Bestaetigung" annimmt (Warteliste-/Bestaetigungs-Flow). Keine eigene Persistenz; Bezug ueber `objecttable = 'booking_answers'`/`objectid`. Log-URL zeigt auf `view.php`. Kollaborateure: Moodle-Event-API, `get_string`.

## Methoden

### `protected function init()` — protected
- **Zweck:** Setzt `crud = 'c'`, `edulevel = LEVEL_PARTICIPATING`, `objecttable = 'booking_answers'`. **Seiteneffekte:** Mutiert `$this->data`. **Bewertung:** A.

### `public static function get_name()` — public static
- **Zweck:** Name ueber `get_string('bookinganswerwaitingforconfirmation', 'booking')`. **Seiteneffekte:** keine. **Rueckgabe:** string. **Bewertung:** B — verwendet die Legacy-Komponente `'booking'` statt `'mod_booking'`; funktioniert in Moodle wegen des Component-Alias, ist aber inkonsistent zu `get_description` (`'mod_booking'`).

### `public function get_description()` — public
- **Zweck:** Baut `$data` (`userid`, `relateduserid` = `data['relateduserid']`, `objectid`) und gibt `get_string('bookinganswerwaitingforconfirmationdesc', 'mod_booking', $data)` zurueck. **Seiteneffekte:** keine. **Rueckgabe:** string. **Bewertung:** B — liest `relateduserid` ueber `$this->data['relateduserid']` statt ueber die Magic-Property `$this->relateduserid`; funktioniert, weil base den Wert in `data` haelt, ist aber unueblich.

### `public function get_url()` — public
- **Zweck:** Log-URL `/mod/booking/view.php?id=<contextinstanceid>`. **Seiteneffekte:** keine. **Rueckgabe:** `moodle_url`. **Bewertung:** A.

### `protected function validate_data()` — protected
- **Zweck:** Verlangt nach `parent::validate_data()` ein gesetztes `relateduserid`. **Seiteneffekte:** wirft `coding_exception`. **Rueckgabe:** void. **Bewertung:** A.

## Bewertungs-Resümee
Einfaches create-Event fuer den Warteliste-/Bestaetigungs-Status. Funktional korrekt; kosmetische Inkonsistenzen (Komponente `'booking'` vs. `'mod_booking'`, direkter `data[]`-Zugriff statt Magic-Property). Klassen-Score **B / P3**.
