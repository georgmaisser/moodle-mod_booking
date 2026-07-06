# bookinganswer_movedupfromwaitinglist — Methoden-Doku
**Datei:** `classes/event/bookinganswer_movedupfromwaitinglist.php` · **LOC:** 84 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`bookinganswer_movedupfromwaitinglist` ist ein Moodle-Logevent (`\core\event\base`), das das Nachruecken eines Users von der Warteliste in die regulaere Buchung signalisiert. Zustandslos; baut in `get_description` ein gezieltes Platzhalter-Objekt aus `userid`/`relateduserid`/`objectid`. Persistenz: Moodle-Logstore, `objecttable = booking_answers`. Kollaborateure: Event-Manager, `get_string` (`bookingoptionmovedupfromwaitinglist[desc]`), `moodle_url`.

## Methoden

### `protected function init()` — protected
- **Zweck:** Event-Basismetadaten: `crud = 'c'` (Create — eine neue, regulaere Buchungsantwort entsteht), `edulevel = LEVEL_PARTICIPATING`, `objecttable = 'booking_answers'`. **Seiteneffekte:** Schreibt in `$this->data`. **Bewertung:** A.

### `public static function get_name()` — public static
- **Zweck:** Menschenlesbarer Eventname. **Seiteneffekte:** `get_string('bookingoptionmovedupfromwaitinglist', 'booking')`. **Rueckgabe:** string. **Bewertung:** A.

### `public function get_description()` — public
- **Zweck:** Beschreibungstext mit kuratiertem Platzhalter-Array (`userid`, `relateduserid`, `objectid`). **Seiteneffekte:** `get_string('bookingoptionmovedupfromwaitinglistdesc', 'mod_booking', $data)`. **Rueckgabe:** string. **Bewertung:** B — liest `relateduserid` direkt aus `$this->data['relateduserid']` statt ueber den Magic-Getter `$this->relateduserid`; funktional aequivalent, aber inkonsistent zur eigenen `validate_data` (die `$this->relateduserid` prueft).

### `public function get_url()` — public
- **Zweck:** Verlinkt auf die Buchungsinstanz-Ansicht. **Seiteneffekte:** keine. **Rueckgabe:** `\moodle_url('/mod/booking/view.php', ['id' => $this->contextinstanceid])`. **Bewertung:** A.

### `protected function validate_data()` — protected
- **Zweck:** Erzwingt gesetzte `relateduserid` (der nachgerueckte User). **Seiteneffekte:** `parent::validate_data()`; wirft `\coding_exception` bei Fehlen. **Bewertung:** A.

## Bewertungs-Resümee
Korrektes Logevent mit gezieltem Platzhalter-Objekt; `crud='c'` ist hier sachlich begruendbar (Nachruecken = neuer aktiver Buchungs-Datensatz). Geringe Inkonsistenz: Direktzugriff `$this->data['relateduserid']` in `get_description` vs. Magic-Getter in `validate_data`. Funktional unkritisch. Klassen-Score **B / P3**.
