# custom_field_changed — Methoden-Doku
**Datei:** `classes/event/custom_field_changed.php` · **LOC:** 72 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`custom_field_changed` ist ein Moodle-Event (`extends \core\event\base`), das signalisiert, dass Custom-Fields einer Buchung/Option geaendert wurden; der Observer (`classes/observer.php`) reagiert darauf u.a. mit Kalender-Updates. `crud='u'`, `objecttable='booking'`. Keine eigene Persistenz (Standard-Logstore). Kollaborateure: `\core\event\base`, `get_string()`, `\moodle_url`. Doc-Bloecke sind copy-paste-Reste („report viewed"/„David Bogner 2014") und beschreiben die Klasse nicht korrekt.

## Methoden

### `protected function init()` — protected
- **Zweck:** Event-Init; `crud='u'`, `edulevel=LEVEL_OTHER`, `objecttable='booking'`. **Seiteneffekte:** mutiert `$this->data`. **Rueckgabe:** void. **Bewertung:** A.

### `public static function get_name()` — public static
- **Zweck:** Lokalisierter Name via `get_string('customfieldchanged', 'mod_booking')`. **Seiteneffekte:** keine. **Rueckgabe:** string. **Bewertung:** A.

### `public function get_description()` — public
- **Zweck:** Beschreibungstext. **Seiteneffekte:** keine. **Rueckgabe:** string `"Custom fileds where changed"`. **Bewertung:** C — hartkodierter, nicht lokalisierter Text mit zwei Tippfehlern (`fileds`, `where`→`were`); enthaelt keinerlei Kontext (welche Option, welches Feld). Rein kosmetisch, kein Funktionsfehler.

### `public function get_url()` — public
- **Zweck:** Liefert die generische Custom-Field-Settings-URL (`/mod/booking/customfieldsettings.php`). **Seiteneffekte:** keine. **Rueckgabe:** `\moodle_url`. **Bewertung:** B — zeigt nur auf die globale Settings-Seite, nicht auf das konkret geaenderte Objekt; fuer Log-Drilldown wenig hilfreich, aber zulaessig.

## Bewertungs-Resümee
Korrektes, minimalistisches Event. Schwaechen sind ausschliesslich Doku-/Text-Qualitaet (stale Doc-Bloecke, Tippfehler in der Beschreibung, generische URL) — keine funktionalen Defekte. Klassen-Score **B / P3**.
