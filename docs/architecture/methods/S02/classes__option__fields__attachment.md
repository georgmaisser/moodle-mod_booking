# attachment — Methoden-Doku
**Datei:** `classes/option/fields/attachment.php` · **LOC:** 245 · **Subsystem:** S02 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S02_*.md)

## Klassenueberblick
`attachment` ist eine `field_base`-Spezialisierung fuer Datei-Anhaenge einer Buchungsoption (Filemanager, max. 10 Dateien, alle Typen). Da Dateien an die optionid gebunden sind, laeuft die eigentliche Persistenz POSTSAVE (`$save = MOD_BOOKING_EXECUTION_POSTSAVE`) ueber die Moodle-File-API. Persistenz: Filearea `mod_booking/myfilemanageroption` (itemid = optionid) im Modulkontext; Transfer ueber Draft-Areas. Kollaborateure: `file_storage`/`get_file_storage`, `context_module`/`context_user`, `file_prepare_draft_area`/`file_save_draft_area_files`/`file_get_submitted_draft_itemid`, `fields_info`. Vollstaendig statisch.

## Methoden

### `public static prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Uebertraegt die Draft-itemid `myfilemanageroption` aus `$formdata` auf `$newoption` (oder den `$returnvalue`-Fallback), damit `save_data` sie spaeter mit der optionid verarbeiten kann.
- **Parameter:** `$formdata` (per Ref), `$newoption` (per Ref), `$updateparam` (ungenutzt), `$returnvalue` (Fallback-Draft-id). **Rueckgabe:** immer leeres Array `[]` (Changes werden in `save_data` ermittelt).
- **Seiteneffekte:** keine DB-/File-Writes; nur Property-Transfer.
- **Aufrufkette:** Von der Field-Save-Pipeline gerufen.
- **Bewertung:** **A** — schlanker Durchreich-Schritt.

### `public static save_data(stdClass &$formdata, stdClass &$option, int $index = 0): array` — public static
- **Zweck:** POSTSAVE-Schritt: persistiert die Draft-Dateien in die endgueltige Filearea der gespeicherten Option und berechnet ein Change-Diff (hinzugekommene/entfernte Dateinamen) per Content-Hash-Vergleich alt vs. neu.
- **Parameter:** `$formdata` (per Ref, liefert `cmid`/`myfilemanageroption`), `$option` (per Ref, liefert `id`), `$index` (ungenutzt). **Rueckgabe:** `['changes' => [...]]` oder leeres Array.
- **Seiteneffekte:** `get_file_storage`; liest Draft-Files (User-Kontext) und bestehende Files (Modulkontext); `file_save_draft_area_files(...)` schreibt die Dateien dauerhaft (File-Writes, ggf. Loeschungen). Nutzt `global $USER`.
- **Aufrufkette:** Vom POSTSAVE-Teil der Field-Pipeline (nachdem die Option-id existiert) gerufen.
- **Bewertung:** **C** — Diff-Logik nutzt `array_diff_assoc` auf `filename => contenthash`-Maps: erkennt geaenderte Inhalte bei gleichem Dateinamen korrekt, aber dateien mit `filesize == 0` (auch das Verzeichnis-Pseudo-File `.`) werden uebersprungen, sodass leere Uploads im Diff fehlen. Der Diff wird nur befuellt, wenn `$draftitemid` gesetzt ist — wird der Filemanager geleert/nicht gesendet, bleibt das Change-Tracking still. Funktional ok, aber Hash-Vergleich + Map-Aufbau machen die Methode dichter als noetig.

### `public static instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Fuegt (optional mit Header) das Filemanager-Element `myfilemanageroption` mit `maxbytes = $CFG->maxbytes`, max. 10 Dateien, allen Typen hinzu.
- **Seiteneffekte:** `global $CFG`; `fields_info::add_header_to_mform` (bedingt), `addElement` auf `$mform`. **Rueckgabe:** void.
- **Aufrufkette:** Von der Option-Formular-Definition gerufen.
- **Bewertung:** **A** — Standard-Filemanager-Setup.

### `public static set_data(stdClass &$data, booking_option_settings $settings)` — public static
- **Zweck:** Kopiert die bestehenden Option-Dateien in eine frische Draft-Area und haengt die Draft-itemid an `$data->myfilemanageroption`, damit der Filemanager sie anzeigt.
- **Parameter:** `$data` (per Ref, liefert `id`/`cmid`), `$settings` (ungenutzt). **Rueckgabe:** void.
- **Seiteneffekte:** `global $CFG, $COURSE` (`$COURSE` ungenutzt); `file_get_submitted_draft_itemid` + `file_prepare_draft_area` (kopiert Files in die Draft-Area).
- **Aufrufkette:** Von der Form-Befuellung gerufen.
- **Bewertung:** **B** — korrektes Draft-Area-Pattern; nur bei `!empty($data->id)`, also keine Files fuer neue Optionen (erwartbar). Ungenutztes `global $COURSE`.

### Triviale Properties
Statische Konfig-Properties (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`) sind reine Deklarationen.

## Bewertungs-Resümee
Solides, idiomatisches Filemanager-Feld mit korrektem Draft-Area-Lifecycle (Set → prepare_save → POSTSAVE save_data). Schwaechen rein im Change-Tracking-Detail (filesize-0-Skip, kein Diff ohne Draft-id) und ungenutztem `$COURSE`. Keine Datenverlust- oder Sicherheitsprobleme. Klassen-Score **C / P3**.
