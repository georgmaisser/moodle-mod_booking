# reminder2_sent — Methoden-Doku
**Datei:** `classes/event/reminder2_sent.php` · **LOC:** 69 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`reminder2_sent` ist das Schwester-Event zu `reminder1_sent` (`extends \core\event\base`) und signalisiert, dass die *zweite* Teilnehmer-Erinnerung fuer eine Buchungsoption versendet wurde. Identische Struktur: keine eigene Persistenz, Logging via Moodle-Framework, `objecttable = 'booking_options'`, `objectid` = optionid, Read-/Teaching-Level, kein `validate_data`/`get_url`. Kollaborateure: `get_string()` (`reminder2sent`), Logging-Pipeline, Reminder-Versand.

## Methoden

### `protected function init()` — protected
- **Zweck:** Setzt `crud = 'r'`, `edulevel = LEVEL_TEACHING`, `objecttable = 'booking_options'`. **Seiteneffekte:** mutiert `$this->data`. **Bewertung:** A.

### `public static function get_name()` — public static
- **Zweck:** Lokalisierter Anzeigename. **Seiteneffekte:** `get_string('reminder2sent', 'booking')`. **Rueckgabe:** string. **Bewertung:** A.

### `public function get_description()` — public
- **Zweck:** Englische Klartextbeschreibung mit `objectid` (optionid). **Seiteneffekte:** keine; liest `$this->objectid`. **Rueckgabe:** string. **Bewertung:** B — wie reminder1_sent, hartkodiert englisch.

## Bewertungs-Resümee
Exakte Kopie von `reminder1_sent` bis auf Klassenname und Sprachstring (`reminder1sent` -> `reminder2sent`, „first" -> „second"). Strukturelle Duplikation zweier Event-Klassen ist in Moodle-Events ueblich und unkritisch. Klassen-Score **B / P3**.
