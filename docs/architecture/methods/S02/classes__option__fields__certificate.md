# certificate — Methoden-Doku
**Datei:** `classes/option/fields/certificate.php` · **LOC:** 389 · **Subsystem:** S02 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S02_*.md)

## Klassenueberblick
`certificate` ist eine `field_base`-Spezialisierung, die das Zertifikats-Feld einer Buchungsoption verwaltet: Formular-Aufbau (PRO-gated, `tool_certificate`-Templates + Ablaufdatum + abhaengige Optionen), Persistenz der Werte in das JSON-Feld der Option sowie Aenderungsbeschreibung fuer das `bookingoption_updated`-Event. Kollaborateure: `booking_option` (JSON-Add/Remove/Get), `fields_info`, `option_conditions_info` (Tagged Conditions), `wb_payment` (PRO-Check), `singleton_service` (Option/Instance-Settings), `tool_certificate\certificate`. Stark statisch und mit externem Plugin (`tool_certificate`) gekoppelt; durchgaengiges `class_exists`-Guard-Pattern macht den Zertifikatsteil optional.

## Methoden

### `public static prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Interpretiert den Cert-Formularwert sowie alle Ablaufdatums-Keys und schreibt sie ins JSON von `$newoption` bzw. entfernt sie; sammelt Change-Diffs.
- **Parameter:** `$formdata` (per Ref, Formular), `$newoption` (per Ref, Ziel), `$updateparam` (ungenutzt), `$returnvalue` (ungenutzt). **Rueckgabe:** `['changes' => $changes]`.
- **Seiteneffekte:** Keine direkten DB-Writes; mutiert `$newoption` via `booking_option::add_data_to_json`/`remove_key_from_json`. Ruft `option_conditions_info::save_tagged_conditions_from_option_form()` (persistiert Tagged Conditions → DB-Write in dieser Sub-API). Frueher Exit wenn `tool_certificate` fehlt.
- **Aufrufkette:** Vom Field-Pipeline-Save (`fields_info`) je Save aufgerufen; ruft `check_for_changes` (geerbt) und `booking_option`-JSON-Helfer.
- **Bewertung:** **C** — gemischte Verantwortung (JSON-Persistenz + Change-Tracking) und Variablen-Shadowing: `$key` (Zeile 120, Klassenname) wird von der `foreach ($keys as $key)`-Schleife (Zeile 139) ueberschrieben; danach nicht mehr benoetigt, aber fehleranfaellig. Im Loop wird bei `check_for_changes` der Cert-`$value` statt des jeweiligen Expiry-Werts uebergeben (Zeile 148) — Diff-Logik fuer Expiry-Keys haengt am Cert-Wert.

### `public static instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Baut den Formularblock fuer das Zertifikat (Template-Auswahl, Ablaufdatum, abhaengige Optionen, Modus-Checkbox); zeigt bei fehlender PRO-Lizenz nur einen statischen Lizenzhinweis.
- **Parameter:** `$mform` (per Ref), `$formdata` (per Ref), `$optionformconfig`, `$fieldstoinstanciate`, `$applyheader`. **Rueckgabe:** void.
- **Seiteneffekte:** DB-Read `tool_certificate_templates` (`$DB->get_records`). Nutzt `global $DB`/`global $OUTPUT` (im Closure). PRO-Check via `wb_payment::pro_version_is_activated()`, Config-Reads `certificateon`/`certificateoptions`. Ruft `toolCertificate::add_expirydate_to_form`, `option_conditions_info::add_static_info_to_mform`. Enthaelt Closure `valuehtmlcallback` (Zeile 208): rendert Option-Vorschlag via `singleton_service`-Settings + `$OUTPUT->render_from_template`.
- **Aufrufkette:** Von der Option-Formular-Definition (`fields_info`) gerufen.
- **Bewertung:** **C** — lang (~96 LOC) mit tiefer Verschachtelung (3–4 Ebenen) und eingebettetem Closure; mischt Konfig-Gating, DB-Lookup, UI-Aufbau und Hardcoded-Default-String `'no certificate selected'` (Zeile 193, nicht via `get_string`).

### `public static validation(array $data, array $files, array &$errors): array` — public static
- **Zweck:** Form-Validierung; aktuell No-op, gibt `$errors` unveraendert zurueck.
- **Seiteneffekte:** keine. **Aufrufkette:** Form-Validierungspipeline. **Bewertung:** **A** (trivialer Pass-through).

### `public static set_data(stdClass &$data, booking_option_settings $settings)` — public static
- **Zweck:** Befuellt das Formularobjekt aus dem JSON der Option (Cert-Key + alle Ablaufdatums-Keys) und laedt die Tagged-Condition-IDs.
- **Parameter:** `$data` (per Ref), `$settings` (ungenutzt). **Rueckgabe:** void.
- **Seiteneffekte:** DB-Reads via `booking_option::get_value_of_json_by_key` (je Key) und `option_conditions_info::get_tagged_condition_ids_for_option`. Frueher Exit ohne `tool_certificate`.
- **Aufrufkette:** Von der Form-Befuellung (`fields_info::set_data`) gerufen.
- **Bewertung:** **C** — die beiden `if (importing)`-Zweige (Zeile 300–304 und 308–312) sind nahezu identisch (`$data->{$key} = ... ?? get_value_of_json_by_key ?? 0`), redundanter Code; pro Key separater JSON-Lookup (potenziell mehrere Reads).

### `public get_changes_description(array $changes): array` — public
- **Zweck:** Erzeugt menschenlesbare alt/neu-Beschreibung fuer das `bookingoption_updated`-Event je nach `formkey` (Cert vs. Expiry-Varianten).
- **Parameter:** `$changes` (mit `oldvalue`/`newvalue`/`fieldname`/`formkey`). **Rueckgabe:** Assoziatives Array (`info` oder `oldvalue`/`newvalue`/`fieldname`).
- **Seiteneffekte:** DB-Reads `tool_certificate_templates.name` (`$DB->get_field`) fuer alten/neuen Wert. Frueher Exit ohne `tool_certificate`.
- **Aufrufkette:** Von der Event-/Change-Beschreibungslogik gerufen.
- **Bewertung:** **C** — `(int) $changes['oldvalue'] ?? 0` (Zeile 336/337) ist wegen Operatorrang wirkungslos als Null-Guard (der Cast greift vor `??`, das nie NULL liefert); `$returnarray` wird nur in einem Zweig vollstaendig initialisiert (Zeile 378 setzt nur `['info']` an undefiniertes Array — funktioniert via Auto-Vivification, aber unsauber). Switch + DB-Lookups gemischt.

## Triviale Akzessoren
Keine echten Getter/Setter; statische Konfig-Properties (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`, `$certificatedatekeys`) sind reine Deklarationen ohne Logik.
