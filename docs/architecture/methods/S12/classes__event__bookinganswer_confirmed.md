# bookinganswer_confirmed — Methoden-Doku
**Datei:** `classes/event/bookinganswer_confirmed.php` · **LOC:** 91 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`bookinganswer_confirmed` ist ein Moodle-Logevent (`\core\event\base`-Ableitung), das das Bestaetigen einer Buchungsantwort signalisiert. Es haelt keinen eigenen Zustand, sondern beschreibt nur die Standard-Event-Metadaten (crud/edulevel/objecttable) und Praesentations-Helfer (Name, Beschreibung, URL). Persistenz: indirekt ueber den Moodle-Logstore (`logstore_*`), `objecttable = booking_options`. Kollaborateure: Event-Manager (`\core\event\manager`), `get_string` (Sprachstrings `bookingoptionconfirmed[:description]`), `moodle_url`. Ausloeser ist der Buchungs-/Bestaetigungs-Pfad, der `::create(...)->trigger()` aufruft.

## Methoden

### `protected function init()` — protected
- **Zweck:** Setzt die Event-Basismetadaten: `crud = 'u'` (Update), `edulevel = LEVEL_PARTICIPATING`, `objecttable = 'booking_options'`. **Seiteneffekte:** Schreibt in `$this->data`. **Bewertung:** A — Standard-Boilerplate, korrekt.

### `public static function get_name()` — public static
- **Zweck:** Menschenlesbarer Eventname fuer Report-/Log-Oberflaechen. **Seiteneffekte:** `get_string('bookingoptionconfirmed', 'booking')`. **Rueckgabe:** string. **Bewertung:** A.

### `public function get_description()` — public
- **Zweck:** Beschreibungstext fuer den Log-Eintrag. **Seiteneffekte:** `get_string('bookingoptionconfirmed:description', 'mod_booking', $this->data)`. **Rueckgabe:** string. **Bewertung:** B — uebergibt das gesamte `$this->data`-Array als Platzhalter-Objekt; funktioniert nur, solange der Sprachstring ausschliesslich Keys referenziert, die in `data` existieren (z. B. `relateduserid`, `objectid`). Robuster waere ein gezielt zusammengestelltes Platzhalter-Objekt wie in den `notesedited`/`presencechanged`-Events.

### `public function get_url()` — public
- **Zweck:** Verlinkt auf die Buchungsinstanz-Ansicht. **Seiteneffekte:** keine. **Rueckgabe:** `\moodle_url('/mod/booking/view.php', ['id' => $this->contextinstanceid])`. **Bewertung:** A.

### `protected function validate_data()` — protected
- **Zweck:** Erzwingt im Debug-Modus, dass `relateduserid` gesetzt ist (der bestaetigte Teilnehmer). **Seiteneffekte:** `parent::validate_data()`; wirft `\coding_exception`, falls `relateduserid` fehlt. **Bewertung:** A — sinnvolle Pflichtfeldpruefung.

## Bewertungs-Resümee
Schlankes, korrektes Logevent ohne Eigenzustand. Einzige Schwaeche ist die `get_description`, die das komplette `data`-Array statt eines kuratierten Platzhalter-Objekts an `get_string` reicht (latente Kopplung an String-Platzhalter). Funktional unkritisch. Klassen-Score **B / P3**.
