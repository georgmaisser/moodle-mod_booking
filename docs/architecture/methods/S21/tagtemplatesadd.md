# tagtemplatesadd — Methoden-Doku
**Datei:** `tagtemplatesadd.php` · **LOC:** 96 · **Subsystem:** S21 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S21_entry_scripts.md)

## Klassenueberblick
Prozeduraler Entry-Point (keine Klasse) zum Anlegen/Bearbeiten eines Tag-Templates (Tabelle `booking_tags`). Das Skript ist ein klassischer Controller: Login + Capability-Gate, dann ein dreigeteilter `mform`-Lebenszyklus (Cancel / Submit / Display). Persistenz: `booking_tags` (insert/update). Kollaborateure: `tagtemplatesadd_form` (Formular), `$DB`, `$OUTPUT`, `$PAGE`, `lib.php`/`locallib.php`. Aufgerufen aus `tagtemplates.php` (Liste), Redirect-Ziel ist ebenfalls `tagtemplates.php`.

## Request-/Permission-Flow
1. **Parameter:** `id` (cmid, `required_param` PARAM_INT), `tagid` (optional PARAM_INT) — bestimmt Edit (tagid gesetzt) vs. Add.
2. **Auth:** `get_course_and_cm_from_cmid($id)` → `require_course_login($course, false, $cm)`; `$PAGE->activityheader->disable()`.
3. **Kontext:** `context_module::instance($cm->id)` (wirft `badcontext` bei Fehlschlag) → `require_capability('mod/booking:updatebooking', $context)`. Schreibrecht ist somit korrekt gegen das Modul-Context geprueft.
4. **Page-Setup:** URL/Title/Heading/Navbar/Pagelayout=standard.
5. **Formularzweige:**
   - `is_cancelled()` → `redirect($urlredirect)` + `die()`.
   - `get_data()` → baut `stdClass $tag` (id, courseid=`$cm->course`, tag, text, textformat=FORMAT_HTML) und macht `update_record` falls `$tag->id != ''`, sonst `insert_record`; Redirect mit Erfolgsmeldung.
   - Else (Erstanzeige / fehlgeschlagene Validierung) → Header + Heading; bei Edit (`$tagid != ''`) Record aus `booking_tags` laden, `id` unset, `text` in `['text'=>..,'format'=>FORMAT_HTML]` umbauen, `set_data` + `display()`.
6. Abschluss `$OUTPUT->footer()`.

## Bewertung der Logik
- **Seiteneffekte:** DB-Insert/Update auf `booking_tags`, Redirects, HTML-Ausgabe.
- **Bewertung:** B — Capability-Gate sitzt am Modul-Kontext, der Submit-Pfad ist sauber. Schwaeche: `$tag->id = $data->tagid` und der Check `if ($tag->id != '')` mischen Leerstring- und Int-Semantik (PARAM_INT liefert int/0); bei Add ist `tagid` 0, was als `!= ''` true ist — funktioniert hier nur, weil das Add-Formular `tagid` leer laesst und `get_data()` den Hidden ggf. als leeren String liefert. Die Edit-Vorbelegung verlaesst sich darauf, dass `$DB->get_record('booking_tags', ['id'=>$tagid])` einen Record liefert (kein false-Guard), ist aber durch den Edit-Link aus der Liste praktisch sicher. Kein Output-Escaping-Problem (Form rendert via mform).

## Bewertungs-Resümee
Schlanker, korrekt abgesicherter CRUD-Controller fuer Tag-Templates. Einzig die Leerstring/Int-Mischung bei `tagid` ist stilistisch fragil, aber funktional unkritisch. Klassen-Score **B / P3**.
