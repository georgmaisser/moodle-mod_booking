# optiondates_teacher_deleted — Methoden-Doku
**Datei:** `classes/event/optiondates_teacher_deleted.php` · **LOC:** 81 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`optiondates_teacher_deleted` ist das Gegenstueck zu `optiondates_teacher_added`: ein Moodle-Logevent (`\core\event\base`), das feuert, wenn eine Lehrkraft von einem einzelnen Optionstermin im Teaching-Journal entfernt wird. Keine eigene Persistenz; fachliches `objecttable` ist `booking_optiondates_teachers`. Gleicher Daten-Vertrag wie das Added-Pendant: `objectid` = optionid, `relateduserid` = entfernte Lehrkraft, `userid` = ausloesender User, `other['cmid']` = Course-Module-Id. Kollaborateure: Trigger im Teacher-Journal-/Optiondates-Pfad, `get_string()`, `moodle_url`.

## Methoden

### `protected function init()` — protected
- **Zweck:** Deklariert die Event-Metadaten. **Seiteneffekte:** Setzt `data['crud']='d'` (delete), `data['edulevel']=LEVEL_TEACHING`, `data['objecttable']='booking_optiondates_teachers'`. **Bewertung:** A.

### `public static function get_name()` — public static
- **Zweck:** Lokalisierter Anzeigename. **Seiteneffekte:** `get_string('optiondatesteacherdeleted','mod_booking')`. **Rueckgabe:** string. **Bewertung:** A.

### `public function get_description()` — public
- **Zweck:** Englische Logbeschreibung („Teacher ... was removed from one specific date ...") mit `relateduserid`, `objectid`, `userid`. **Seiteneffekte:** keine. **Rueckgabe:** string. **Bewertung:** B — konventionskonform, abhaengig von korrekt gesetzten Trigger-Feldern.

### `public function get_url()` — public
- **Zweck:** URL zum Optiondates-Teachers-Report der Instanz. **Seiteneffekte:** liest `other['cmid']` und `objectid`. **Rueckgabe:** `\moodle_url('/mod/booking/optiondates_teachers_report.php', ['cmid'=>..., 'optionid'=>...])`. **Bewertung:** B — wie beim Added-Event auf `other['cmid']` angewiesen.

## Bewertungs-Resümee
Exakter struktureller Zwilling von `optiondates_teacher_added`, nur mit `crud='d'` und anderem String-Key. Konventionskonform, keine eigene Logik. Klassen-Score **B / P3**.
