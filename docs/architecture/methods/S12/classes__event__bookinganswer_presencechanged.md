# bookinganswer_presencechanged — Methoden-Doku
**Datei:** `classes/event/bookinganswer_presencechanged.php` · **LOC:** 109 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`bookinganswer_presencechanged` ist ein Moodle-Logevent (`\core\event\base`), das das Aendern des Praesenzstatus einer Buchungsantwort signalisiert (Teacher-Aktion; kann downstream u. a. Zertifikatslogik anstossen). Es transportiert alten/neuen Praesenz-Code in `other` (`presenceold`/`presencenew`) und uebersetzt diese in `get_description` in Klartextlabels. Persistenz: Moodle-Logstore, `objecttable = booking_answers`. Kollaborateure: Event-Manager, `singleton_service::get_instance_of_user`, `booking::get_array_of_possible_presence_statuses`, `get_string` (`presencechanged`, `presencechangedinfo`), `moodle_url`.

## Methoden

### `protected function init()` — protected
- **Zweck:** Event-Basismetadaten: `crud = 'c'`, `edulevel = LEVEL_TEACHING`, `objecttable = 'booking_answers'`. **Seiteneffekte:** Schreibt in `$this->data`. **Bewertung:** B — `crud='c'` (Create) fuer eine Statusaenderung ist semantisch ein Update (`'u'`); deskriptiv, ohne Funktionswirkung.

### `public static function get_name()` — public static
- **Zweck:** Menschenlesbarer Eventname. **Seiteneffekte:** `get_string('presencechanged', 'booking')`. **Rueckgabe:** string. **Bewertung:** A.

### `public function get_description()` — public
- **Zweck:** Beschreibungstext „Praesenz von X von alt auf neu geaendert"; loest `relateduser`-Namen auf und mappt Praesenz-Codes auf Labels. **Seiteneffekte:** Liest `other.presenceold`/`other.presencenew`/`relateduserid`; `singleton_service::get_instance_of_user(...)`; `booking::get_array_of_possible_presence_statuses()`; `get_string('presencechangedinfo', 'mod_booking', $a)`. **Rueckgabe:** string. **Bewertung:** C — `$possiblepresences[$data->presenceold]` und `[$data->presencenew]` indizieren das Status-Array ohne Existenzpruefung; ein im Log persistierter, mittlerweile entfernter/ungueltiger Praesenz-Code (oder fehlendes `other`) erzeugt einen „Undefined array key"-Warning. Zudem ungeprueftes `other`-Payload wie bei `notesedited`.

### `public function get_url()` — public
- **Zweck:** Verlinkt auf die Buchungsinstanz-Ansicht. **Seiteneffekte:** keine. **Rueckgabe:** `\moodle_url('/mod/booking/view.php', ['id' => $this->contextinstanceid])`. **Bewertung:** A.

### `protected function validate_data()` — protected
- **Zweck:** Erzwingt gesetzte `relateduserid`. **Seiteneffekte:** `parent::validate_data()`; wirft `\coding_exception` bei Fehlen. **Bewertung:** A — prueft jedoch nicht das von `get_description` benoetigte `other`-Payload (`presenceold`/`presencenew`).

## Bewertungs-Resümee
Korrektes Praesenz-Audit-Event mit Alt/Neu-Diff und Label-Aufloesung. Schwaechen: `crud='c'` fuer eine Aenderung sowie die ungesicherten Array-Zugriffe in `get_description` (`$possiblepresences[...]` und `other.*`), die bei veralteten/fehlenden Codes PHP-Warnings ausloesen koennen. Funktional unkritisch (nur Log-Rendering). Klassen-Score **B / P3**.
