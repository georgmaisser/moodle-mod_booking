# enrollink_triggered — Methoden-Doku
**Datei:** `classes/event/enrollink_triggered.php` · **LOC:** 68 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`enrollink_triggered` ist ein Moodle-Event (`extends \core\event\base`), das ausgeloest wird, wenn ein Enrolment-Link (enrollink) verwendet/ausgeloest wurde. `crud='u'`, `edulevel=LEVEL_PARTICIPATING`, `objecttable='booking_options'`. Keine eigene Persistenz (Standard-Logstore). Kollaborateure: `\core\event\base`, `get_string()`. Doc-Block traegt einen stale `@since Moodle 2.7`-Rest.

## Methoden

### `protected function init()` — protected
- **Zweck:** Event-Init; `crud='u'`, `edulevel=LEVEL_PARTICIPATING`, `objecttable='booking_options'`. **Seiteneffekte:** mutiert `$this->data`. **Rueckgabe:** void. **Bewertung:** A.

### `public static function get_name()` — public static
- **Zweck:** Lokalisierter Name via `get_string('enrollinktriggered', 'booking')`. **Seiteneffekte:** keine. **Rueckgabe:** string. **Bewertung:** B — nutzt die Kurzform-Komponente `'booking'` statt `'mod_booking'`; in Moodle aequivalent aufgeloest, aber inkonsistent zum Plugin-Standard.

### `public function get_description()` — public
- **Zweck:** Lokalisierte Beschreibung via `get_string('enrollinktriggered:description', 'mod_booking', $this->data)`. **Seiteneffekte:** keine. **Rueckgabe:** string. **Bewertung:** B — uebergibt das gesamte `$this->data`-Array als Sprachstring-`$a`; das funktioniert nur, wenn der Sprachstring die Platzhalter exakt auf die `data`-Schluessel ({$a->objectid} etc.) abstimmt. Korrekt lokalisiert (im Gegensatz zu den hartkodierten Beschreibungen der Schwester-Events).

## Bewertungs-Resümee
Sauberes, vollstaendig lokalisiertes Event ohne Funktionsdefekte. Minimale Schwaechen: Komponenten-Kurzform `'booking'` in `get_name()` und stale `@since`-Doc. Klassen-Score **B / P3**.
