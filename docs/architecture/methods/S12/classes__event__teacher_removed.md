# teacher_removed — Methoden-Doku
**Datei:** `classes/event/teacher_removed.php` · **LOC:** 87 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`teacher_removed` ist das Spiegelbild von `teacher_added`: ein Moodle-Event (`\core\event\base`), das das Entfernen einer Lehrkraft von einer Buchungsoption protokolliert. Keine eigene Persistenz (Logstore-Framework); `objecttable = 'booking_teachers'`. Kollaborateure: `\core\event\base`, `singleton_service` (Settings fuer URL), `moodle_url`. Konvention der Felder: `userid` = Akteur, `relateduserid` = entfernte Lehrkraft, `objectid` = optionid.

## Methoden

### `protected function init()` — protected
- **Zweck:** Setzt Event-Metadaten: `crud = 'd'` (delete), `edulevel = LEVEL_TEACHING`, `objecttable = 'booking_teachers'`. **Seiteneffekte:** nur `$this->data`. **Bewertung:** A — kanonischer Boilerplate; einziger inhaltlicher Unterschied zu `teacher_added` ist `crud = 'd'`.

### `public static function get_name()` — public static
- **Zweck:** Lokalisierter Event-Anzeigename. **Seiteneffekte:** `get_string('eventteacherremoved', 'mod_booking')`. **Rueckgabe:** string. **Bewertung:** A.

### `public function get_description()` — public
- **Zweck:** Englische Klartext-Beschreibung mit `userid`/`relateduserid`/`objectid` („removed ... from the booking option"). **Seiteneffekte:** keine. **Rueckgabe:** string. **Bewertung:** B — wie beim Gegenstueck: fest englisch, eingebettete Whitespaces im String-Literal.

### `public function get_url()` — public
- **Zweck:** Deeplink zur Option (`view.php`, `whichview=showonlyone`). **Seiteneffekte:** `$CFG`, `singleton_service::get_instance_of_booking_option_settings($this->objectid)`. **Rueckgabe:** `moodle_url`. **Bewertung:** B — Settings-Vollload nur fuer `cmid`, kein Null-Guard bei ungueltiger `objectid`.

## Bewertungs-Resümee
Funktional korrektes Delete-Event, praktisch identisch zu `teacher_added` (klassischer Copy-Paste-Zwilling). Gleiche kosmetische Schwaechen: nicht-lokalisierte Description, kopierte Docblocks („taecher added"/„report viewed"), kein `validate_data()`. Code-Duplikation zwischen den beiden Teacher-Events liesse sich durch eine gemeinsame abstrakte Basis reduzieren, ist aber im Moodle-Event-Modell unueblich. Klassen-Score **B / P3**.
