# report_viewed — Methoden-Doku
**Datei:** `classes/event/report_viewed.php` · **LOC:** 71 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`report_viewed` (`extends \core\event\base`) signalisiert, dass ein User den Report der gebuchten Teilnehmer einer Buchungsoption angesehen hat. Aeltestes der dokumentierten Events (Copyright 2014 David Bogner, @since Moodle 2.7). Read-/Teaching-Level-Event ohne eigene Persistenz; `objecttable = 'booking_options'`, `objectid` = optionid. Im Unterschied zu den Reminder-Events liefert es zusaetzlich eine `get_url`, die auf den Report deep-linkt. Kollaborateure: `get_string('eventreportviewed', ...)`, `moodle_url`, Logging-Pipeline, `report.php`.

## Methoden

### `protected function init()` — protected
- **Zweck:** Setzt `crud = 'r'`, `edulevel = LEVEL_TEACHING`, `objecttable = 'booking_options'`. **Seiteneffekte:** mutiert `$this->data`. **Bewertung:** A.

### `public static function get_name()` — public static
- **Zweck:** Lokalisierter Anzeigename. **Seiteneffekte:** `get_string('eventreportviewed', 'mod_booking')`. **Rueckgabe:** string. **Bewertung:** A — nutzt voll qualifizierte Komponente `mod_booking` (statt nur `booking` wie die Reminder-Events; beide aequivalent).

### `public function get_description()` — public
- **Zweck:** Klartextbeschreibung mit `userid` und `objectid` (optionid). **Seiteneffekte:** keine; liest `$this->userid`, `$this->objectid`. **Rueckgabe:** string. **Bewertung:** B — englischer Festtext mit Zeilenumbruch/Einrueckung im String (kosmetisch).

### `public function get_url()` — public
- **Zweck:** Deep-Link auf den Report: `/mod/booking/report.php?id={contextinstanceid}&optionid={objectid}`. **Seiteneffekte:** keine; konstruiert `moodle_url` aus `$this->contextinstanceid` und `$this->objectid`. **Rueckgabe:** `\moodle_url`. **Bewertung:** A — setzt korrekt voraus, dass `contextinstanceid` die cmid traegt (Modul-Kontext), was fuer ein an einen booking-cm gebundenes Event gilt.

## Bewertungs-Resümee
Solides, vollstaendiges Read-Event mit nuetzlichem `get_url`-Deep-Link. Kein `validate_data`, aber da nur `userid`/`objectid`/`contextinstanceid` (Core-Felder) verwendet werden, unkritisch. Klassen-Score **B / P3**.
