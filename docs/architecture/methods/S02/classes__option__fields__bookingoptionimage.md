# bookingoptionimage — Methoden-Doku
**Datei:** `classes/option/fields/bookingoptionimage.php` · **LOC:** 260 · **Subsystem:** S02 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S02_*.md)

## Klassenueberblick
`bookingoptionimage` ist ein Feld-Handler (`extends field_base`) fuer das Header-Bild einer Buchungsoption. Im Gegensatz zu Text-/Zeitfeldern hat es keine eigene DB-Spalte, sondern arbeitet ueber die Moodle-File-API: das Bild lebt im Filearea `mod_booking/bookingoptionimage` (itemid = optionid) im Modul-Kontext, die Form benutzt einen `filemanager` mit Draft-Area. Da fuer das Speichern die Options-id benoetigt wird, laeuft das Feld in der POSTSAVE-Phase (`$save = POSTSAVE`) und implementiert zusaetzlich `save_data()`. Kein Instanzzustand; statische Hooks. Kollaborateure: File-Storage (`get_file_storage`, `file_prepare_draft_area`, `file_save_draft_area_files`, `file_get_submitted_draft_itemid`), `context_module`/`context_user`, `field_base`, `fields_info`. Konfig: `$header = GENERAL`, `$fieldcategories = [STANDARD]`. Hinweis: Dieser Handler hat in der Vergangenheit mehrere Header-Bild-Bugs verursacht (Draft-Overwrite, source=NULL, MUC-Cache) — siehe Memory `project_headerimage_*`.

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Pre-Save-Phase — delegiert nur an die Basis und baut ein (ungenutztes) `$mockdata`-Objekt. Die eigentliche Bild-Persistenz erfolgt in `save_data()`. **Seiteneffekte:** `parent::prepare_save_field($formdata, $newoption, $updateparam, '')`. **Rueckgabe:** Immer `[]`. **Bewertung:** C — `$mockdata` (Z.100–102) wird befuellt aber nie verwendet (toter Code). Der Doc-Block deklariert `@return string`, die Signatur `: array` — Doc-Mismatch.

### `public static function save_data(stdClass &$formdata, stdClass &$option, int $index = 0): array` — public static
- **Zweck:** POSTSAVE: persistiert das hochgeladene Draft-Bild in das endgueltige Filearea der Option und ermittelt Change-Tracking (alt/neuer Dateiname per Contenthash-Vergleich). **Seiteneffekte:** Liest Draft-Files aus dem User-Kontext und bestehende Files aus dem Modul-Kontext; `file_save_draft_area_files(... maxfiles=1)` schreibt/loescht die persistenten Files. **Rueckgabe:** Changes-Array (leer wenn Hashes identisch). **Bewertung:** C — der Hash-Vergleich `$oldhashes != $newhashes` (assoziativ, Dateiname=>Contenthash) erkennt Aenderungen korrekt. Aber: `$formdata->bookingoptionimage ?? false ?? false ?? false` (Z.125) ist dreifach redundantes Cruft. Zudem wird `$changes` nur gesetzt, wenn ueberhaupt ein Draft vorliegt — ein reines Entfernen ohne Draft-Submit traegt nicht hierher.

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Fuegt einen `filemanager` (`maxfiles=1`, `accepted_types=['image']`, `maxbytes=$CFG->maxbytes`) und optional den GENERAL-Header hinzu. **Seiteneffekte:** Mutiert `$mform`; liest `$CFG->maxbytes`. **Bewertung:** A.

### `public static function set_data(stdClass &$data, booking_option_settings $settings)` — public static
- **Zweck:** Bereitet die Draft-Area fuer die Formular-Anzeige vor: kopiert bestehende persistente Files in eine frische Draft-Area; respektiert zusaetzlich einen vom Server-seitigen Aufrufer (CSV/WS/Agent-Import) bereits gestagten Draft, um ein Ueberschreiben mit Leer-Area zu vermeiden. **Seiteneffekte:** `file_get_submitted_draft_itemid`, ggf. `context_user::instance` + `get_area_files` (Staged-Check), `file_prepare_draft_area` (kopiert Files), mutiert `$data->bookingoptionimage` auf die Draft-id. **Bewertung:** B — die alternative Staged-Draft-Branch (Z.222–231) ist der bewusste Fix gegen Bild-Verlust bei programmatischen Importen (vgl. Memory `project_headerimage_set_data_overwrite`); gut kommentiert. Komplexitaet hoch, aber begruendet.

### Triviale Properties
Sechs statische Konfig-Properties (Z.44–80). `use mod_booking\singleton_service;` (Z.31) ist ein ungenutzter Import.

## Bewertungs-Resümee
Funktional korrekter, aber historisch fehleranfaelliger File-API-Handler mit zwei Save-Phasen. Restliche Schwaechen sind kosmetisch: ungenutztes `$mockdata`, dreifaches `?? false`-Cruft, Doc/Signatur-Mismatch, ungenutzter Import. Die kritischen Header-Bild-Pfade sind durch die kommentierte Staged-Draft-Branch abgesichert. Klassen-Score **C / P3**.
