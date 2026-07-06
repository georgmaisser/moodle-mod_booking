# bookingoption_cancelled — Methoden-Doku
**Datei:** `classes/event/bookingoption_cancelled.php` · **LOC:** 77 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`bookingoption_cancelled` ist ein Moodle-Event (`\core\event\base`-Subklasse), das die Stornierung einer **gesamten** Buchungsoption (alle User betroffen) signalisiert. Nicht eigen-DB-persistent; landet im Standard-Logstore. CRUD-Typ `u` (update — die Option wird als storniert markiert, nicht geloescht), Edulevel `LEVEL_TEACHING`, `objecttable` = `booking_options`. Anders als die Buchungs-Events besitzt es **keine** `validate_data()` und verwendet `objectid` als Booking-Option-id. Kollaborateure: Sprachstring `bookingoptioncancelled`, `\core\event\base`.

## Methoden

### `protected function init()` — protected
- **Zweck:** Setzt Event-Metadaten (`crud='u'`, `edulevel=LEVEL_TEACHING`, `objecttable='booking_options'`). **Seiteneffekte:** Mutiert `$this->data`. **Rueckgabe:** void. **Bewertung:** A — `u` ist hier passend, da Stornierung als Status-Update modelliert ist.

### `public static function get_name()` — public static
- **Zweck:** Lokalisierter Anzeigename. **Seiteneffekte:** `get_string('bookingoptioncancelled', 'booking')`. **Rueckgabe:** string. **Bewertung:** A.

### `public function get_description()` — public
- **Zweck:** Liefert eine textuelle Beschreibung der Stornierung. **Seiteneffekte:** keine. **Rueckgabe:** string (hartkodiertes Englisch, interpoliert `userid`/`objectid`). **Bewertung:** B — nicht lokalisiert (festes Englisch im Gegensatz zu den `get_string`-basierten Buchungs-Events); funktional unkritisch fuer Logs.

### `public function get_url()` — public
- **Zweck:** Ziel-URL zur Aktivitaetsansicht. **Seiteneffekte:** keine. **Rueckgabe:** `\moodle_url` auf `/mod/booking/view.php?id={$this->contextinstanceid}`. **Bewertung:** A — korrekt `contextinstanceid` (cmid).

## Bewertungs-Resümee
Schlankes Storno-Event mit korrekter cmid-URL und passendem CRUD-Typ. Einzige Schwaeche ist die nicht lokalisierte, hartkodierte Beschreibung (Inkonsistenz zur uebrigen Event-Familie). Kein `validate_data()`, da keine `relateduserid` benoetigt wird (Storno gilt der gesamten Option). Keine funktionalen Befunde. Klassen-Score **B / P3**.
