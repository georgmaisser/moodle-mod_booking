# reminder_teacher_sent — Methoden-Doku
**Datei:** `classes/event/reminder_teacher_sent.php` · **LOC:** 68 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`reminder_teacher_sent` ist ein Moodle-Logevent (`\core\event\base`), das anzeigt, dass die Lehrkraft-Erinnerungs-Nachricht fuer eine Buchungsoption versandt wurde. Keine eigene Persistenz; fachliches `objecttable` ist `booking_options`, der Vertrag erwartet `objectid` = optionid. Im Gegensatz zu den anderen Events der Familie ueberschreibt diese Klasse `get_url()` NICHT — sie erbt das Default-Verhalten von `\core\event\base`. Kollaborateure: Reminder-Task/Messaging-Pfad (Trigger), `get_string()`.

## Methoden

### `protected function init()` — protected
- **Zweck:** Deklariert die Event-Metadaten. **Seiteneffekte:** Setzt `data['crud']='r'` (read), `data['edulevel']=LEVEL_TEACHING`, `data['objecttable']='booking_options'`. **Bewertung:** B — `crud='r'` fuer ein „Nachricht versandt"-Ereignis ist Auslegungssache (ein Versand ist eher eine Aktion als ein Lesezugriff), wird aber von Moodle akzeptiert.

### `public static function get_name()` — public static
- **Zweck:** Lokalisierter Anzeigename. **Seiteneffekte:** `get_string('reminderteachersent','mod_booking')`. **Rueckgabe:** string. **Bewertung:** A.

### `public function get_description()` — public
- **Zweck:** Englische Logbeschreibung inkl. `objectid` (optionid). **Seiteneffekte:** keine. **Rueckgabe:** string. **Bewertung:** A — schlank, hat keine ungeprueften `other`-Zugriffe.

### Geerbtes `get_url()`
Die Klasse definiert KEIN eigenes `get_url()`, womit die Default-Implementierung von `\core\event\base` greift (kein modulspezifisches Ziel). Bewusst minimal, da das Event keine kanonische Detailseite hat.

## Bewertungs-Resümee
Die schlankeste Event-Klasse der Datei-Gruppe: nur `init`, `get_name`, `get_description`, kein `get_url`-Override und keine `other`-Abhaengigkeit. Daher die geringste Fehlerflaeche. Einzige Diskussionsstelle ist `crud='r'`. Funktional unkritisch. Klassen-Score **B / P3**.
