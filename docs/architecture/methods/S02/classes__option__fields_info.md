# fields_info — Methoden-Doku

**Datei:** `classes/option/fields_info.php` · **LOC:** 529 · **Subsystem:** S02 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S02_option.md)

## Klassenueberblick
`fields_info` ist der zentrale Dispatcher/Orchestrator fuer das pluginbare Feld-System der Buchungsoption. Sie ermittelt pro Kontext+Capability die aktiven Feld-Klassen (aus `optionformconfig_info`) und ruft auf jeder Klasse einheitlich die Lifecycle-Hooks auf: Form-Definition, `definition_after_data`, `validation`, `prepare_save`, `save_data` (Postsave) und `changes_collected_action`. Kollaborateure: `optionformconfig_info` (konfigurierte Felder/JSON), `type_resolver` (Formdata-Normalisierung nach Optionstyp), `singleton_service`/`booking_option_settings` (Settings+cmid), `MoodleQuickForm`, sowie alle `mod_booking\option\fields\*`-Klassen via statischer Late-Binding-Aufrufe.

## Methoden

### `prepare_save_fields(stdClass &$formdata, stdClass &$newoption, int $updateparam = MOD_BOOKING_UPDATE_OPTIONS_PARAM_DEFAULT): array` — public static
- **Zweck:** Laeuft durch alle aktiven Feld-Klassen und ruft je `prepare_save_field()` auf; sammelt Warnungen/Feedback je Klasse.
- **Parameter:** `$formdata` (by-ref, Formulardaten), `$newoption` (by-ref, im Aufbau befindliche Option), `$updateparam`. **Rueckgabe:** `array` Feedback (klassenindiziert).
- **Seiteneffekte:** Liest Kontext via `context_module::instance($formdata->cmid)`; ruft `type_resolver::normalize_formdata` (mutiert `$formdata`/`$newoption->type`); delegiert an Feld-Klassen, die ihrerseits schreiben koennen. Keine direkten DB-Writes in dieser Methode.
- **Aufrufkette:** Aufgerufen aus dem Speicherpfad der Option-Form (booking_option update). Ruft `get_field_classes`, `ignore_class`, `type_resolver::normalize_formdata`, `$classname::prepare_save_field`.
- **Bewertung:** C — `$error[]`-Sammlung wird nur befuellt, nie ausgewertet (Todo Z.67 „implement error handling"); gefangene Exceptions verschwinden stillschweigend. `$returnvalue` kann bei Exception aus voriger Iteration leaken (nicht je Iteration initialisiert) → Z.83-89. Doppelter `normalize_formdata`-Aufruf (Z.69 + Z.94).

### `get_class_name($classname): int|false|string` — public static
- **Zweck:** Liefert den Klassennamen ohne Namespace (Teil nach letztem `\`).
- **Seiteneffekte:** keine. **Aufrufkette:** Utility.
- **Bewertung:** C — Bei klassenname ohne Backslash gibt es via `$pos = strrpos(...)` `false` (assignment-in-condition Z.108); semantisch fragwuerdiger Rueckgabewert `false` statt der Eingabe. Schwache Typgarantie.

### `get_namespace_from_class_name(string $classname): string` — public static
- **Zweck:** Baut aus Kurzname den vollqualifizierten `mod_booking\option\fields\*`-Namen, mit Sonderfaellen `dates`→`optiondates`, `enrolementstatus`→`enrolmentstatus`.
- **Seiteneffekte:** `class_exists`-Probe. **Rueckgabe:** FQCN oder `""`.
- **Bewertung:** B — kleine hartkodierte Aliase, sonst klar.

### `add_header_to_mform(MoodleQuickForm &$mform, string $headeridentifier): void` — public static
- **Zweck:** Fuegt einen Form-Header (mit Icon) hinzu, falls noch nicht vorhanden; fuer bestimmte Identifier wird bewusst nichts getan (Felder kuemmern sich selbst).
- **Seiteneffekte:** mutiert `$mform` (addElement header); `get_string`.
- **Aufrufkette:** Aus Feld-Klassen `instance_form_definition` gerufen. **Rueckgabe:** void.
- **Bewertung:** C — zwei aufeinanderfolgende `switch` ueber denselben Identifier (Z.144 Icon-Map, Z.174 Skip-Liste); Icon-Zuordnung als langer String-switch = klassischer „mixed config/logic"-Smell, eher datengetrieben loesbar. Laenge ~50 LOC vertretbar.

### `instance_form_definition(MoodleQuickForm &$mform, array &$formdata): array` — public static
- **Zweck:** Definiert die Form, indem alle aktiven (nicht-ignorierten) Feld-Klassen ihre `instance_form_definition` ausfuehren; gibt die verwendete Klassenliste zurueck.
- **Seiteneffekte:** Kontextaufloesung via cmid oder optionid (`singleton_service`); mutiert `$mform`; `unset` ignorierter Klassen.
- **Aufrufkette:** Aus der Option-DynamicForm-Definition. Ruft `get_field_classes`, `ignore_class`.
- **Bewertung:** B — Kontext-Resolution-Block (cmid/optionid/throw) ist hier dupliziert (auch in `definition_after_data`, `all_changes_collected_actions`).

### `validation(array $data, array $files, array &$errors): void` — public static
- **Zweck:** Ruft je aktiver Feld-Klasse deren `validation()` und sammelt Fehler in `$errors` (by-ref).
- **Seiteneffekte:** Kontext via `$data['cmid']`; mutiert `$errors`. **Bewertung:** A — schlank, klar.

### `save_fields_post(stdClass &$formdata, stdClass &$option, int $updateparam): array` — public static
- **Zweck:** Fuehrt fuer alle POSTSAVE-Feld-Klassen `save_data()` aus; sammelt Changes je Klasse.
- **Seiteneffekte:** Kontext via cmid; delegierte `save_data`-Aufrufe schreiben in DB (klassenspezifisch). **Rueckgabe:** `array` Changes.
- **Aufrufkette:** Nach dem Hauptspeichern der Option. **Bewertung:** B — `$updateparam` wird entgegengenommen, aber nicht an `save_data` weitergereicht (ungenutzt) → potenzieller Smell Z.252/262.

### `set_data(stdClass &$data): string` — public static
- **Zweck:** Befuellt Formulardaten (Edit/Import) durch Aufruf von `set_data()` jeder aktiven Feld-Klasse; faengt `moodle_exception` als Schleifen-Exit ab und gibt deren Message zurueck.
- **Seiteneffekte:** Laedt `booking_option_settings` (ggf. neu nach erkannter id waehrend Import); mutiert `$data`.
- **Aufrufkette:** Aus Template/Form-Klasse (laut Kommentar Z.301). **Rueckgabe:** Fehlermeldung-String (leer = ok).
- **Bewertung:** C — Exception als Control-Flow/„exit the loop" (Z.300-304) ist ein Anti-Pattern; Fehler-als-Rueckgabestring statt typisierter Fehler. Re-Resolve der Settings mitten in der Schleife (Z.296) ist subtil/fragil.

### `definition_after_data(MoodleQuickForm &$mform, array &$formdata): void` — public static
- **Zweck:** Ruft `definition_after_data` jeder aktiven Feld-Klasse und stellt anschliessend den Expand/Collapse-Zustand aller Header wieder her.
- **Seiteneffekte:** Kontextaufloesung (dupliziert); mutiert `$mform`; ruft `restore_header_collapse_state`.
- **Bewertung:** B — dupliziertes Kontext-Resolution-Pattern; Fehlermeldung kopiert falschen Dateinamen `formconfig.php` (Z.322).

### `restore_header_collapse_state(MoodleQuickForm &$mform, array $formdata): void` — public static
- **Zweck:** Stellt den Expand/Collapse-Zustand jedes Headers aus den Submit-Daten wieder her, indem der instabile `data-random-ids`-Suffix per Regex entfernt und ueber den stabilen Prefix `mform_isexpanded_<id>_` gematcht wird.
- **Seiteneffekte:** liest `$mform->_elements` (protected internals), ruft `setExpanded`. **Bewertung:** B — gut dokumentierter, aber fragiler Workaround: greift auf MoodleQuickForm-Interna (`_elements`, `_generateId`) zu und matcht per Regex/Prefix. Notwendig wegen dynamic_form-Verhalten (siehe Memory „expand all persistiert nicht"), daher bewusst akzeptiert.

### `get_available_field_class_ids(int $contextid, int $save = -1): array<int,int>` — public static
- **Zweck:** Liefert die IDs der aktuell verfuegbaren Feld-Klassen fuer Kontext (Wrapper um `get_field_classes`, Keys → int).
- **Seiteneffekte:** keine direkt. **Bewertung:** A.

### `get_field_classes(int $contextid, int $save = -1): array` — private static
- **Zweck:** Ermittelt die fuer User/Kontext konfigurierten und passend gefilterten Feld-Klassen (nach Save-Modus NORMAL/POSTSAVE und necessary/checked-Flag), indiziert nach `$classname::$id`.
- **Seiteneffekte:** Liest Capability+konfigurierte Felder via `optionformconfig_info` (DB/Config); `json_decode`.
- **Aufrufkette:** zentraler Helper, von fast allen oeffentlichen Methoden genutzt. **Rueckgabe:** `[$id => $classname]`.
- **Bewertung:** B — Kern-Helper, akzeptable Komplexitaet; `$save`-Filterung leicht redundant (zwei fast identische Bloecke Z.422/428).

### `ignore_class($data, $classname): bool` — private static
- **Zweck:** Entscheidet im Import-Modus, ob eine Feld-Klasse uebersprungen wird (Klasse nicht necessary und kein passender Spaltenwert vorhanden), mit Sonderlogik fuer Preis-Kategorien, alternative Import-Identifier und Custom-Fields.
- **Seiteneffekte:** `global $DB`; liest `booking_pricecategories` (DB) bei Price-Feld. **Rueckgabe:** bool.
- **Bewertung:** C — gemischte Verantwortung (Import-Heuristik + direkter DB-Read + viele statische Property-Zugriffe `$classname::$fieldcategories`/`$alternativeimportidentifiers`), tiefe Schachtelung (4 Ebenen), Todo Z.468; schwer testbar. ~38 LOC.

### `all_changes_collected_actions(array $changes, object $data, object $newoption, object $originaloption): void` — public static
- **Zweck:** Nachgelagerter Hook: ruft `changes_collected_action` jeder aktiven Feld-Klasse, nachdem alle Changes gesammelt sind.
- **Seiteneffekte:** Kontextaufloesung; delegierte Klassen-Aufrufe.
- **Bewertung:** D — **echter Bug** im Kontext-Resolution-Zweig (Z.511): `else if (!empty($formdata['optionid']))` referenziert die undefinierte Variable `$formdata` (Parameter heisst `$data`/`$newoption`), zudem Array-Zugriff auf nicht existierende Variable. Der `else if`-Zweig ist damit toter/kaputter Code; faellt durch zur `else`-Exception, wenn `$data->cmid` leer ist. Dupliziertes Kontext-Pattern + kopierte falsche Fehlermeldung `formconfig.php`. `ignore_class` wird hier — anders als in allen anderen Hooks — NICHT angewandt (Inkonsistenz).

## Querschnitt-Smells
- Kontext-Resolution (cmid/optionid/throw) ist in `instance_form_definition`, `definition_after_data`, `all_changes_collected_actions` 3x dupliziert — Kandidat fuer privaten Helper.
- Mehrere kopierte/falsche Fehler-Dateinamen (`formconfig.php` statt `fields_info.php`).
- Durchgaengig statische God-Dispatcher-Struktur (alles `public static`), schwer mockbar/testbar.
