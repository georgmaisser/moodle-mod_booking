# reminder1_sent — Methoden-Doku
**Datei:** `classes/event/reminder1_sent.php` · **LOC:** 69 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`reminder1_sent` ist ein Moodle-Standard-Event (`extends \core\event\base`), das signalisiert, dass die *erste* Teilnehmer-Erinnerung fuer eine Buchungsoption an alle Bucher versendet wurde. Keine eigene Persistenz — das Event landet ueber das Moodle-Logging-Framework in `logstore_*`; `objecttable` verweist auf `booking_options`, `objectid` traegt die optionid. Reines Read-/Teaching-Level-Event ohne `validate_data`, ohne `get_url`, ohne `other`-Pflichtfelder. Kollaborateure: `get_string()` (Sprachstring `reminder1sent`), Logging-Pipeline, der ausloesende Erinnerungs-Task/Reminder-Versand.

## Methoden

### `protected function init()` — protected
- **Zweck:** Setzt die Event-Metadaten: `crud = 'r'` (read), `edulevel = LEVEL_TEACHING`, `objecttable = 'booking_options'`. **Seiteneffekte:** mutiert `$this->data`. **Bewertung:** A — Standard-Boilerplate, korrekt.

### `public static function get_name()` — public static
- **Zweck:** Liefert den lokalisierten Anzeigenamen des Events. **Seiteneffekte:** `get_string('reminder1sent', 'booking')`. **Rueckgabe:** string. **Bewertung:** A.

### `public function get_description()` — public
- **Zweck:** Liefert eine englische Klartextbeschreibung mit der `objectid` (optionid). **Seiteneffekte:** keine; liest `$this->objectid`. **Rueckgabe:** string. **Bewertung:** B — Beschreibung hartkodiert englisch (Konvention fuer `get_description` in Moodle ist akzeptiert, da nicht endnutzerseitig lokalisiert).

## Bewertungs-Resümee
Minimaler, konventionskonformer Event-Wrapper ohne Logik-Risiko. Kein `validate_data` (unkritisch, da nur `objectid` verwendet wird). Funktional unauffaellig. Klassen-Score **B / P3**.
