# teacher_added — Methoden-Doku
**Datei:** `classes/event/teacher_added.php` · **LOC:** 87 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`teacher_added` ist ein Moodle-Event (`\core\event\base`) das das Hinzufuegen einer Lehrkraft zu einer Buchungsoption protokolliert. Es ist ein reiner Logging-/Observer-Trigger ohne eigene Persistenz: Der Event-Record landet ueber das Moodle-Logstore-Framework in `logstore_*`-Tabellen; `objecttable` zeigt auf `booking_teachers`. Kollaborateure: `\core\event\base` (Basis, `create()`/`trigger()`), `singleton_service` (Settings-Lookup fuer die URL), `moodle_url`. Konvention: Der ausloesende Code setzt `userid` (Akteur), `relateduserid` (hinzugefuegte Lehrkraft) und `objectid` (optionid).

## Methoden

### `protected function init()` — protected
- **Zweck:** Setzt die Event-Metadaten: `crud = 'c'` (create), `edulevel = LEVEL_TEACHING`, `objecttable = 'booking_teachers'`. **Seiteneffekte:** Schreibt nur in `$this->data`. **Bewertung:** A — kanonischer Event-Boilerplate.

### `public static function get_name()` — public static
- **Zweck:** Liefert den lokalisierten Anzeigenamen des Events. **Seiteneffekte:** `get_string('eventteacheradded', 'mod_booking')`. **Rueckgabe:** string. **Bewertung:** A.

### `public function get_description()` — public
- **Zweck:** Liefert eine englische Klartext-Beschreibung mit `userid`, `relateduserid` und `objectid`. **Seiteneffekte:** keine. **Rueckgabe:** string (mehrzeilig mit eingebetteter Einrueckung im Heredoc-artigen String-Literal). **Bewertung:** B — fest englischer Text (nicht lokalisiert, branchentypisch fuer Event-Descriptions); die wortwoertlich uebernommenen Zeilenumbrueche/Tabs landen 1:1 in der Logbeschreibung.

### `public function get_url()` — public
- **Zweck:** Liefert die Deeplink-URL zur betroffenen Option (`view.php` mit `whichview=showonlyone`). **Seiteneffekte:** liest `$CFG`, `singleton_service::get_instance_of_booking_option_settings($this->objectid)`. **Rueckgabe:** `moodle_url`. **Bewertung:** B — laedt die kompletten Option-Settings nur um `cmid` zu erhalten; bei fehlendem/ungueltigem `objectid` ist das Settings-Objekt evtl. unvollstaendig (kein Guard). Akzeptabel fuer den selten aufgerufenen URL-Pfad.

## Bewertungs-Resümee
Standard-Event nach Moodle-Muster, fehlerfrei in der Funktion. Schwaechen rein kosmetisch: nicht-lokalisierte, mit Whitespace verunreinigte `get_description`, Docblock-Tippfehler („taecher", Klassen-Doc spricht von „report viewed"), Settings-Vollload in `get_url` ohne Null-Guard. Kein `validate_data()`, daher keine Pflichtfeld-Garantie fuer `objectid`/`relateduserid`. Klassen-Score **B / P3**.
