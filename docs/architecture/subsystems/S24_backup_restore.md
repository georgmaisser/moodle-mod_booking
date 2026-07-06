# S24 — backup_restore

## Zweck & Grenzen

Dieses Subsystem implementiert die Moodle-Backup-/Restore-API fuer `mod_booking`. Es definiert,
welche Daten beim Kurs-Backup, beim Import und insbesondere beim **Duplizieren** einer
Booking-Instanz exportiert (`backup_*`) bzw. importiert (`restore_*`) werden — inklusive der
zahlreichen mod_booking-spezifischen Nebentabellen (Optionen, Sessions/Optiondates, Lehrer,
Preise, Subbookings, History, Custom Fields) sowie optionaler Fremdplugin-Daten
(`local_entities`, `local_shopping_cart`).

Grenzen: Das Subsystem besteht ausschliesslich aus den von der Moodle-Backup-Engine
vorgegebenen Task-/Step-Klassen unter `backup/moodle2/`. Es enthaelt keine eigene UI und keine
Geschaeftslogik im engeren Sinne — es ist reine Struktur-/Mapping-Definition, die von der
Core-Backup-Engine (`backup_controller`/`restore_controller`) angesteuert wird. Semester und
Feiertage werden bewusst NICHT mitgesichert (site-weite Daten, siehe `backup_booking_stepslib.php:41`).

## Position im Gesamtsystem

```
Moodle core backup engine
  └─ backup_booking_activity_task  ──(add_step)──▶ backup_booking_activity_structure_step
                                                      └─ define_structure() → booking.xml

Moodle core restore engine
  └─ restore_booking_activity_task ──(add_step)──▶ restore_booking_activity_structure_step
                                                      └─ define_structure()/process_*() ← booking.xml
```

Aufgerufen u. a. durch die Modul-Duplizierung (Kurs „duplicate activity") und Kurs-Backup/Restore.
Das Restore-Verhalten ist stark durch `get_config('booking', 'duplicationrestore*')`-Schalter
gesteuert (Settings-Subsystem). Kollaborateure ausserhalb des Scopes:
`mod_booking\booking_option` (Identifier-Generierung), `mod_booking\teachers_handler`
(Optiondate-Subscription), sowie Fremd-Plugins `local_entities`, `local_shopping_cart`.

## Schluesselkonzepte

- **Task vs. Stepslib vs. Settingslib**: Moodle-Konvention. Die `*_activity_task.class.php`
  registriert Steps und Link-Encode/Decode-Regeln; die `*_stepslib.php` enthaelt die eigentliche
  Struktur-/Verarbeitungslogik; `backup_booking_settingslib.php` waere fuer instanz-spezifische
  Backup-Settings (hier praktisch leer).
- **`backup_nested_element`-Baum**: Backup definiert einen XML-Baum (booking → options/answers/
  optiondates/categories/teachers/tags/customfields/history; option → others/prices/entities/
  subbookings/shoppingcart; optiondate → optiondates_teachers/entities) mit Source-Tables/-SQL.
- **`restore_path_element` + `process_*`-Callbacks**: Restore liest dieselben XML-Pfade und ruft
  pro Element einen Handler, der ID-Remapping (`get_mappingid`, `get_new_parentid`,
  `set_mapping`) und Insert vornimmt.
- **Config-gegateter Umfang**: Optionen, Lehrer, Preise, Entities, Subbookings, Bookings selbst
  werden nur gesichert/restauriert, wenn das jeweilige `duplicationrestore*`-Setting aktiv ist.
- **Userinfo-Gate**: `booking_answers` nur bei `userinfo`-Backup-Setting.
- **Link-Encoding/Decoding**: BOOKINGINDEX / BOOKINGVIEWBYID Platzhalter fuer transportable Links.
- **Manuelles File-Copy**: Header-Bilder (`bookingimages`) und Option-Bilder (`bookingoptionimage`)
  sowie Moodle-Custom-Fields werden in den `process_*`-Methoden per `get_file_storage()` direkt
  kopiert (statt nur ueber `annotate_files`/`add_related_files`), um Duplikate korrekt umzuhaengen.

## Datenfluss

Backup: `backup_booking_activity_task::define_my_steps()` fuegt
`backup_booking_activity_structure_step` hinzu → `define_structure()` baut den nested-element-Baum,
setzt Source-Tables/SQL (config-gegatet), annotiert User-IDs und File-Areas → Engine schreibt
`booking.xml`. `encode_content_links()` ersetzt absolute URLs durch Platzhalter.

Restore: `restore_booking_activity_task::define_my_steps()` fuegt
`restore_booking_activity_structure_step` hinzu → `define_structure()` registriert
`restore_path_element`s (config-/userinfo-gegatet) → Engine ruft je Element `process_booking`,
`process_booking_option`, `process_booking_answer`, `process_booking_optiondate`, `process_booking_teacher`,
`process_booking_category`, `process_booking_tag`, `process_booking_other`, `process_booking_history`,
`process_booking_option_entity`, `process_booking_optiondate_entity`, `process_booking_subbookingoption`,
`process_booking_customfield`, `process_booking_price`, `process_booking_option_shoppingcartiteminfo`.
Diese mappen alte auf neue IDs, neutralisieren transiente Felder (calendarid, eventid, addtocalendar,
identifier), inserten in die Zieltabellen und kopieren zugehoerige Dateien. `after_execute()` haengt
die annotierten Filereas (intro, bookingpolicy, description) an. Link-Decode via
`define_decode_rules()`/`define_decode_contents()`.

## Dateien & Klassen

| Datei | Klasse | Rolle | LOC | Methoden | Vorab-Score | → Quality-Index |
|-------|--------|-------|-----|----------|-------------|-----------------|
| backup/moodle2/backup_booking_activity_task.class.php | `backup_booking_activity_task` | Backup-Task (Step- & Link-Encode-Registrierung) | 73 | 3 | A | P3 |
| backup/moodle2/backup_booking_settingslib.php | — (nur auskommentierter Code) | Backup-Settings (Platzhalter, leer) | 30 | 0 | C | P3 |
| backup/moodle2/backup_booking_stepslib.php | `backup_booking_activity_structure_step` | Backup-Strukturdefinition (XML-Baum + Sources) | 321 | 1 | B | P2 |
| backup/moodle2/restore_booking_activity_task.class.php | `restore_booking_activity_task` | Restore-Task (Steps, Decode-/Log-Regeln) | 146 | 6 | A | P3 |
| backup/moodle2/restore_booking_stepslib.php | `restore_booking_activity_structure_step` | Restore-Strukturdefinition + alle `process_*`-Handler | 643 | 18 | C | P1 |

### `backup_booking_activity_task` (backup_booking_activity_task.class.php)

Registriert die Backup-Steps und Link-Encoding fuer die Booking-Aktivitaet. Erbt von
`backup_activity_task`. Kollaborateur: `backup_booking_activity_structure_step`.

Methoden-Inventar:
- `protected define_my_settings()` — keine instanz-spezifischen Settings (No-op).
- `protected define_my_steps()` — fuegt den einzigen Strukturschritt `booking_structure`/`booking.xml` hinzu.
- `public static encode_content_links($content)` — ersetzt `index.php?id=`/`view.php?id=`-URLs durch
  `BOOKINGINDEX`/`BOOKINGVIEWBYID`-Platzhalter (preg_replace).

### `backup_booking_activity_structure_step` (backup_booking_stepslib.php)

Definiert den kompletten `backup_nested_element`-Baum der Booking-Instanz inkl. Source-Tables/-SQL,
ID- und File-Annotationen. Die einzige Methode ist gross, aber linear/deklarativ. Config-gegatete
Zweige fuer teachers, prices, entities, subbookings, shoppingcart; userinfo-Gate fuer answers.
Kollaborateure (indirekt via class_exists/Config): `local_shopping_cart\shopping_cart`,
`local_entities\entitiesrelation_handler`.

Methoden-Inventar:
- `protected define_structure(): backup_nested_element` — baut Element-Baum, setzt Quellen,
  annotiert User-IDs (user/usercreated/usermodified) und Filereas (intro/bookingpolicy/description),
  liefert via `prepare_activity_structure`.

### `restore_booking_activity_task` (restore_booking_activity_task.class.php)

Registriert den Restore-Step und definiert Link-Decode-, Decode-Content- und Restore-Log-Regeln.
Erbt von `restore_activity_task`. Kollaborateur: `restore_booking_activity_structure_step`.

Methoden-Inventar:
- `protected define_my_settings()` — No-op.
- `protected define_my_steps()` — fuegt `restore_booking_activity_structure_step` hinzu.
- `public static define_decode_contents()` — markiert `booking.intro` und `booking_options.description` fuer Link-Decoding.
- `public static define_decode_rules()` — Regeln BOOKINGVIEWBYID→view.php / BOOKINGINDEX→index.php.
- `public static define_restore_log_rules()` — Log-Mapping-Regeln auf Modulebene (add/update/view/choose/report).
- `public static define_restore_log_rules_for_course()` — Kurs-Log-Regeln (view all index.php).

### `restore_booking_activity_structure_step` (restore_booking_stepslib.php)

Kernklasse des Restore: 643 LOC, 18 Methoden. `define_structure()` registriert config-/userinfo-
gegatete `restore_path_element`s; je ein `process_*`-Handler pro Entitaet uebernimmt ID-Remapping,
Feld-Neutralisierung und Insert, teilweise mit manuellem Datei-/Customfield-Copy. Kollaborateure
(ausserhalb Scope): `mod_booking\booking_option::create_truly_unique_option_identifier`,
`mod_booking\teachers_handler::subscribe_teacher_to_all_optiondates`,
`context_module`, `get_file_storage`, `local_entities`/`local_shopping_cart` (per class_exists).

Methoden-Inventar:
- `protected define_structure()` — Registriert Restore-Pfade (config-/userinfo-gegatet), liefert via `prepare_activity_structure`.
- `protected process_booking($data)` — Insert booking-Record, `apply_activity_instance`, kopiert Header-Bilder (`bookingimages`) per File-Storage in neuen Kontext.
- `protected process_booking_option($data)` — Mappt user/parent-IDs, neutralisiert calendar/identifier, Insert booking_options, kopiert Moodle-Customfields (`customfield_data`) und Option-Bilder, `set_mapping`.
- `protected process_booking_answer($data)` — Remap booking/option/user-IDs + Datums-Offset, Insert booking_answers (kein Mapping).
- `protected process_booking_optiondate($data)` — Remap parent/option, eventid=0, Insert booking_optiondates, `set_mapping`.
- `protected process_booking_teacher($data)` — Remap IDs, Guard-Debug bei fehlenden IDs, Insert booking_teachers, subscribe Teacher zu allen Optiondates.
- `protected process_booking_category($data)` — Setzt course, Insert booking_category.
- `protected process_booking_tag($data)` — Setzt courseid, Insert booking_tags nur falls noch nicht vorhanden (Dedupe).
- `protected process_booking_other($data)` — Remap optionid, Insert booking_other.
- `protected process_booking_history($data)` — Remap booking/option/answer/user, Insert booking_history.
- `protected process_booking_option_entity($data)` — Config-/class_exists-gegatet, Area-Validierung, Remap, Insert local_entities_relations (option).
- `protected process_booking_optiondate_entity($data)` — Analog fuer optiondate-Area.
- `protected process_booking_subbookingoption($data)` — Config-gegatet, Remap option, Insert booking_subbooking_options.
- `protected process_booking_customfield($data)` — Remap booking/option/optiondate, Insert booking_customfields.
- `protected process_booking_price($data)` — Nur Area 'option', Remap itemid, Insert booking_prices.
- `protected process_booking_option_shoppingcartiteminfo($data)` — class_exists-gegatet, Remap itemid, Insert local_shopping_cart_iteminfo.
- `protected after_execute()` — `add_related_files` fuer intro/bookingpolicy/description.

## Persistenz

Gesicherte/wiederhergestellte Tabellen (alle ohne `m_`-Prefix): `booking`, `booking_options`,
`booking_answers`, `booking_optiondates`, `booking_optiondates_teachers`, `booking_teachers`,
`booking_category`, `booking_tags`, `booking_other`, `booking_prices`, `booking_customfields`,
`booking_subbooking_options`, `booking_history`. Fremd-Plugin-Tabellen (gegatet):
`local_entities_relations`, `local_shopping_cart_iteminfo`. Zusaetzlich Core-Tabellen beim Restore:
`customfield_data`/`customfield_field`/`customfield_category` (Moodle-Customfields),
`course_modules`/`modules` (cmid-Aufloesung), `files` (Bild-Kopien via File-Storage). Keine Caches
in diesem Subsystem. Schalter: `get_config('booking', 'duplicationrestorebookings'|'...teachers'|
'...prices'|'...entities'|'...subbookings')`.

## Extension-Points

- Standard-Moodle-Backup-Vererbung (`backup_activity_task`, `backup_activity_structure_step`,
  `restore_activity_task`, `restore_activity_structure_step`).
- Optionale Fremd-Plugin-Integration ueber `class_exists()`-Checks (`local_shopping_cart`,
  `local_entities`) — additiv, keine formellen Hooks.
- `duplicationrestore*`-Configs als externe Steuerung des Backup-/Restore-Umfangs.
- Link-Encode/Decode-Regeln als Erweiterungspunkt fuer transportable URLs.

## Bekannte Schulden (→ Blueprint)

- **BUG: Category-Restore-Pfad-Typo** — `restore_booking_stepslib.php:48` registriert Pfad
  `/activity/booking/categories/caegory` (Tippfehler „caegory"), waehrend Backup das XML-Element
  `category` schreibt (`backup_booking_stepslib.php:116`). Folge: `process_booking_category` wird
  vermutlich nie aufgerufen → Kategorien werden nicht restauriert. Hohe Prio.
- **BUG/Smell: History answerid-Mapping** — `restore_booking_stepslib.php:496` nutzt
  `get_mappingid('booking_answers', ...)`, aber `process_booking_answer` speichert kein Mapping
  (`:369`). Das Mapping existiert nie → `answerid` wird in der History nach Restore auf `false`/0
  gesetzt. Zudem haengt History-Restore nicht am userinfo-Gate, Answers schon → Inkonsistenz.
- **Grosse Klasse / SRP** — `restore_booking_activity_structure_step` buendelt 18
  heterogene `process_*`-Handler inkl. zweifachem, fast identischem File-Copy-Block
  (`:166-216` Header-Bild, `:296-347` Option-Bild). Duplizierter ~50-LOC-File-Copy-Code →
  Extraktion in Helper sinnvoll. P1.
- **Verstreute Quellfeld-Listen** — die explizite Spaltenliste in `backup_booking_stepslib.php:48-69`
  (booking-Instanz, ~110 Felder) muss bei jeder DB-Schema-Aenderung manuell nachgezogen werden;
  fehleranfaellig, keine Test-Absicherung sichtbar.
- **Leere Settingslib** — `backup_booking_settingslib.php` enthaelt nur auskommentierten Code (Score C);
  entweder entfernen oder dokumentieren. P3.
- **Keine sichtbaren Unit-/Behat-Tests** fuer dieses Subsystem im Scope; Backup/Restore-Korrektheit
  (insb. die ID-Remappings und die gegateten Zweige) ist nicht automatisiert abgesichert.
- **Inline-SQL fuer cmid** in `process_booking` und `process_booking_option` dupliziert
  (`:157-161` / `:248-252`); koennte ueber `get_task()->get_moduleid()` ersetzt werden.
