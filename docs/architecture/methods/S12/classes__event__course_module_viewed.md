# course_module_viewed — Methoden-Doku
**Datei:** `classes/event/course_module_viewed.php` · **LOC:** 44 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`course_module_viewed` (mod_booking) ist eine duenne Subklasse des Core-Events `\core\event\course_module_viewed`. Sie erbt das gesamte Verhalten (Name, Beschreibung, URL, Validierung, Legacy-Log-Mapping) vom Core und ueberschreibt ausschliesslich `init()`, um das Event auf das Booking-Kursmodul zu binden. Sie wird ausgeloest, wenn ein User die Booking-Aktivitaet ansieht. Persistenz: keine eigene; `objecttable = booking`. Kollaborateure: `\core\event\course_module_viewed`, Trigger im View-Pfad (`view.php`).

## Methoden

### `protected function init()` — protected
- **Zweck:** Setzt die Event-Metadaten: `crud = 'r'` (read), `edulevel = LEVEL_PARTICIPATING`, `objecttable = 'booking'`. **Seiteneffekte:** mutiert `$this->data`. **Rueckgabe:** void. **Bewertung:** A — korrekte read-Klassifizierung; `objecttable = booking` bindet das generische Core-Event sauber an die Booking-Instanztabelle.

## Bewertungs-Resümee
Minimaler, idiomatischer Core-Event-Spezialfall — die korrekte Moodle-Art, ein `course_module_viewed` fuers eigene Modul bereitzustellen. Keine Schwaechen. Klassen-Score **B / P3** (gemaess CLASS_INDEX; intrinsisch eher A wegen Trivialitaet).
