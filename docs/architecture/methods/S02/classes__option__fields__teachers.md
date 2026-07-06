# teachers — Methoden-Doku
**Datei:** `classes/option/fields/teachers.php` · **LOC:** 239 · **Subsystem:** S02 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`teachers` ist der Field-Handler (erbt `field_base`) fuer die Lehrer-Zuordnung einer Buchungsoption. Es ist eine der inhaltsreicheren Field-Klassen: sie deckt Form-Definition, Set-Data (inkl. Webservice-Import-/Merge-Logik), Post-Save-Persistierung und einen Post-Change-Hook fuer Kalendereintraege ab. Die eigentliche DB-Arbeit (Zuordnungen in `booking_teachers`, Enrolment etc.) liegt im `teachers_handler`; diese Klasse orchestriert nur. Persistenz indirekt ueber `teachers_handler` und `calendar`. Kollaborateure: `teachers_handler`, `calendar`, `booking_option_settings`, `field_base`. POSTSAVE-Feld (braucht die Option-id), unter dem Teachers-Header; als Import-Alias gilt zusaetzlich `teacheremail`.

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Pflicht-Override; berechnet zusaetzlich das Diff der Lehrerliste fuer das Changes-Protokoll. **Seiteneffekte:** ruft `parent::prepare_save_field(...)` (Verwerfungs-Ergebnis), instanziiert `new teachers()` und ein Mock-`stdClass` mit `id = $formdata->id ?? 0`, delegiert an `check_for_changes($formdata, $instance, $mockclass, 'teachersforoption')`. **Rueckgabe:** das von `check_for_changes` gelieferte Changes-Array. **Bewertung:** B — der Rueckgabewert von `parent::prepare_save_field` wird bewusst verworfen (nur das Diff zaehlt); defensives `?? 0` beim id-Zugriff.

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Haengt das Lehrer-Auswahlelement an das Formular. **Seiteneffekte:** `global $CFG;` (deklariert, aber ungenutzt), `new teachers_handler($formdata['id'] ?? 0)` und `add_to_mform($mform)`. **Rueckgabe:** void. **Bewertung:** B — saubere Delegation; das ungenutzte `global $CFG` ist toter Code (kosmetisch).

### `public static function set_data(stdClass &$data, booking_option_settings $settings)` — public static
- **Zweck:** Befuellt die Formulardaten mit den aktuellen Lehrern bzw. — im Import-Pfad — mit aufgeloesten/zusammengefuehrten Lehrer-ids. **Seiteneffekte:** Bei normaler Bearbeitung (`empty($data->importing) && !empty($data->id)`): `new teachers_handler($data->id)->set_data($data)`. Sonst: `teachers_handler::get_teacherids_from_form($data, true)` (throwerror=true, damit Import bei unbekanntem Lehrer scheitert), optionaler Merge mit `$settings->teacherids` wenn `mergeparam > 1`, schreibt `$data->teachersforoption`. **Rueckgabe:** void (mutiert `$data`). **Bewertung:** C — verschachtelte Bedingungslogik mit subtiler Doppelpruefung: der `else`-Zweig wird nur bei `importing` ODER leerer id betreten, fragt darin aber erneut `!empty($data->importing)` ab; der Merge greift somit ausschliesslich im Import-Fall — fuer eine neue, nicht-importierte Option (id leer, importing leer) wird `get_teacherids_from_form` mit `throwerror=true` aufgerufen, was bei Form-Eingabe unerwartet werfen koennte. Funktional vom Aufrufkontext getragen, aber fragil.

### `public static function save_data(stdClass &$data, stdClass &$option): array` — public static
- **Zweck:** Persistiert die Lehrer-Zuordnung nach dem Option-Save. **Seiteneffekte:** `new teachers_handler($data->id)->save_from_form($data)` (DB-Writes, Enrolment, Notifications im Handler). **Rueckgabe:** stets leeres `$changes`-Array. **Bewertung:** B — duenner Persistenz-Adapter; das Changes-Protokoll wird hier nicht gefuellt (es entsteht in `prepare_save_field`/`changes_collected_action`).

### `public static function changes_collected_action(array $changes, object $data, object $newoption, object $originaloption)` — public static
- **Zweck:** Post-Change-Hook, der pro hinzugefuegtem/entferntem Lehrer Kalendereintraege synchronisiert. **Seiteneffekte:** Liest alt/neu-Lehrer-ids aus dem verschachtelten `$changes`-Pfad (`...\teachers]["changes"]["oldvalue|newvalue"]`), sammelt alle `optiondateid_<n>`-Keys aus `$data` via `preg_match`. Fuer entfernte Lehrer: `new calendar(cmid, optionid, teacherid, MOD_BOOKING_TYPETEACHERREMOVE)`. Fuer neue Lehrer: pro Optiondate ein `new calendar(cmid, optionid, teacherid, MOD_BOOKING_TYPEOPTIONDATE, optiondateid, 1)`. **Rueckgabe:** void (early return bei leerer `$optionid`). **Bewertung:** C — korrekt im Ergebnis, aber zwei Risiken: (1) die `calendar`-Konstruktion in geschachtelter Schleife (neue Lehrer x Optiondates) ist ein potenzieller N+1/Write-Verstaerker bei vielen Terminen; (2) starke Kopplung an einen string-basierten, voll-qualifizierten Array-Schluessel (`mod_booking\\option\\fields\\teachers`) — eine Klassenumbenennung bricht den Hook still (kein Fehler, nur ausbleibende Kalender-Sync).

### Triviale Properties
Sechs statische Konfigurations-Properties (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers = ['teacheremail']`, `$incompatiblefields`, Z.47–81).

## Bewertungs-Resümee
Inhaltlich die anspruchsvollste der dokumentierten Field-Klassen: orchestriert Lehrer-Persistenz, Import-Merge und Kalender-Sync. Schwachstellen sind die fragile, doppelt verschachtelte `set_data`-Import-Logik, der string-fragile Changes-Schluessel im Kalender-Hook und der potenzielle Write-Verstaerker bei vielen Optiondates. Kein Daten-Verlust, aber Wartungs- und Skalierungs-Risiken. Klassen-Score **C / P2**.
