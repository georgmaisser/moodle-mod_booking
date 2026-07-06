# optiondates_teacher_added — Methoden-Doku
**Datei:** `classes/event/optiondates_teacher_added.php` · **LOC:** 81 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`optiondates_teacher_added` ist ein Standard-Moodle-Logevent (`\core\event\base`), das ausgeloest wird, sobald einer einzelnen Optionstermin-Zeile im Teaching-Journal eine Lehrkraft zugeordnet wird. Es traegt keine eigene Persistenz, sondern wird ueber den Moodle-Event-/Log-Store verarbeitet; das fachliche `objecttable` ist `booking_optiondates_teachers`. Konventions-Vertrag: `objectid` = optionid, `relateduserid` = hinzugefuegte Lehrkraft, `userid` = ausloesender User, `other['cmid']` = Course-Module-Id (fuer die URL). Kollaborateure: Event-Dispatcher in den Teacher-Journal-/Optiondates-Pfaden (Trigger) sowie `get_string()` und `moodle_url`.

## Methoden

### `protected function init()` — protected
- **Zweck:** Deklariert die statischen Event-Metadaten. **Seiteneffekte:** Setzt `data['crud']='c'` (create), `data['edulevel']=LEVEL_TEACHING` und `data['objecttable']='booking_optiondates_teachers'`. **Bewertung:** A — kanonischer Boilerplate, korrekt.

### `public static function get_name()` — public static
- **Zweck:** Liefert den lokalisierten Anzeigenamen des Events. **Seiteneffekte:** `get_string('optiondatesteacheradded','mod_booking')`. **Rueckgabe:** string. **Bewertung:** A.

### `public function get_description()` — public
- **Zweck:** Baut die englische Klartext-Logbeschreibung mit `relateduserid`, `objectid` und `userid`. **Seiteneffekte:** keine. **Rueckgabe:** string (hartcodiert englisch, nicht lokalisiert — Moodle-Konvention fuer `get_description`). **Bewertung:** B — folgt der Konvention; greift auf `relateduserid`/`objectid` zu, die der Trigger korrekt setzen muss.

### `public function get_url()` — public
- **Zweck:** Verweist auf den Optiondates-Teachers-Report der betroffenen Instanz. **Seiteneffekte:** liest `other['cmid']` und `objectid`. **Rueckgabe:** `\moodle_url('/mod/booking/optiondates_teachers_report.php', ['cmid'=>..., 'optionid'=>...])`. **Bewertung:** B — funktional korrekt, setzt aber stillschweigend voraus, dass der Trigger `other['cmid']` immer mitgibt; fehlt es, wuerde der Array-Zugriff bei der URL-Erzeugung eine Notice/Undefined-Key-Situation erzeugen (Standard-Event-Risiko, nicht klassenspezifisch behoben).

## Bewertungs-Resümee
Schlanke, konventionskonforme Event-Klasse ohne eigene Logik oder DB-Zugriff. Einzige Annahme: der ausloesende Code liefert `objectid`, `relateduserid`, `userid` und `other['cmid']` korrekt. Funktional unkritisch. Klassen-Score **B / P3**.
