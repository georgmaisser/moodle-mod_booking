# bookingoption_freetobookagain — Methoden-Doku
**Datei:** `classes/event/bookingoption_freetobookagain.php` · **LOC:** 78 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`bookingoption_freetobookagain` ist ein Moodle-Standard-Event (`\core\event\base`), das signalisiert, dass eine zuvor ausgebuchte Option wieder buchbar ist (Platz frei geworden — durch Stornierung einer Antwort oder Erhoehung der Platzzahl). Anders als die created/deleted/updated-Events bezieht es sich auf `booking_answers` (`objecttable='booking_answers'`) und ist `edulevel=LEVEL_PARTICIPATING`, da es ein teilnehmerseitiges Ereignis ist; `crud='c'`. Keine eigene Persistenz. Kollaborateure: `get_string` (Sprachpaket `booking`), `moodle_url`. Konsument typischerweise Waitlist-/Notification-Rules.

## Methoden

### `protected function init()` — protected
- **Zweck:** Setzt Event-Metadaten (`crud='c'`, `edulevel=LEVEL_PARTICIPATING`, `objecttable='booking_answers'`). **Seiteneffekte:** Schreibt in `$this->data`. **Bewertung:** A — `LEVEL_PARTICIPATING` und `booking_answers` korrekt fuer ein teilnehmerseitiges Platz-frei-Ereignis.

### `public static function get_name()` — public static
- **Zweck:** Uebersetzter Anzeigename. **Seiteneffekte:** `get_string('bookingoptionfreetobookagain', 'booking')`. **Rueckgabe:** string. **Bewertung:** A.

### `public function get_description()` — public
- **Zweck:** Zweizeilige Klartext-Beschreibung mit Begruendung (Stornierung oder erhoehte Platzzahl). **Seiteneffekte:** keine; interpoliert `userid`/`objectid`. **Rueckgabe:** string. **Bewertung:** B — der mehrzeilige String mit eingebetteten Whitespaces wirkt im Log etwas unsauber (Einrueckungs-Whitespace landet in der Ausgabe), funktional unkritisch.

### `public function get_url()` — public
- **Zweck:** Link auf die Booking-Instanz-Ansicht. **Seiteneffekte:** `moodle_url('/mod/booking/view.php', ['id' => contextinstanceid])`. **Rueckgabe:** `\moodle_url`. **Bewertung:** A.

## Bewertungs-Resümee
Standard-Event mit korrekt abweichendem edulevel/objecttable fuer den Teilnehmer-Kontext. Einziger Schoenheitsfehler ist der eingerueckte Mehrzeilen-String in `get_description`. Keine funktionalen Befunde. Klassen-Score **B / P3**.
