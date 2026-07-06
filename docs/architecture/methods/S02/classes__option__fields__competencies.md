# competencies — Methoden-Doku
**Datei:** `classes/option/fields/competencies.php` · **LOC:** 474 · **Subsystem:** S02 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S02_*.md)

## Klassenueberblick
`competencies extends field_base` ist ein Option-Feld-Handler im `option/fields`-Framework von mod_booking. Es kapselt das optionale Verknuepfen einer Buchungsoption mit Core-Kompetenzen (`core_competency`): Form-Definition (Autocomplete der Frameworks/Competencies), Speicherung als kommaseparierter String im Feld `competencies`, Change-Tracking fuers `bookingoption_updated`-Event sowie die fachliche Kernoperation `assign_competencies`, die bei Abschluss einer Buchung User-Evidence + Grading in Core-Competency anlegt. Kollaborateure: `core_competency\api`, `competency`, `competency_framework`, `user_evidence(_competency)`, `fields_info`, `singleton_service`, `shortcodes`.

## Methoden

### `prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Interpretiert den Form-Wert (Array von Competency-IDs), wandelt ihn in kommaseparierten String und schreibt ihn in `$newoption`/`$formdata`.
- **Parameter:** Referenzen auf Formdaten und neues Option-Objekt; `$updateparam`/`$returnvalue` ungenutzt. **Rueckgabe:** `$changes`-Array aus `check_for_changes`.
- **Seiteneffekte:** Mutiert `$newoption->{key}` und `$formdata->{key}` (kein direkter DB-Write hier). Ruft Instanzmethode `check_for_changes` (aus `field_base`).
- **Aufrufkette:** Standard-Hook im fields_info-Speicherzyklus; ruft `fields_info::get_class_name`, `field_base::check_for_changes`.
- **Bewertung:** B — klar, etwas redundante `$changes`-Initialisierung (Z.111 dann sofort Z.117 ueberschrieben). Doc deklariert `@return string`, real `array` (Signatur-Doc-Mismatch, competencies.php:103).

### `instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Baut die Form-Elemente: Header, Autocomplete (multiple) ueber alle Competencies bzw. Hinweis falls keine vorhanden, plus Link zur Competency-Framework-Verwaltung.
- **Seiteneffekte:** Liest Config `booking/usecompetencies` (frueher Return wenn aus); deklariert `global $DB, $USER` ungenutzt. Indirekt DB-Reads via `get_competencies_including_framework`.
- **Aufrufkette:** fields_info-Form-Aufbau; ruft `fields_info::add_header_to_mform`, `self::get_competencies_including_framework`.
- **Bewertung:** B — gut strukturiert; `global $DB, $USER` toter Import (competencies.php:151).

### `get_competencies_including_framework(): array` — private static
- **Zweck:** Liefert flaches `[competencyid => Label]`-Array ueber alle Frameworks; Label mit Framework-Prefix nur bei mehreren Frameworks.
- **Seiteneffekte:** DB-Reads via `competency_framework::get_records()` und `competency::get_records(...)` (N+1: ein Competency-Query pro Framework). `format_string` Aufrufe.
- **Aufrufkette:** Genutzt von `instance_form_definition`, `get_changes_description`, `get_filter_options`.
- **Bewertung:** B — verstaendlich; potentieller N+1 ueber Frameworks, aber Datenmenge typ. klein (competencies.php:205).

### `get_changes_description(array $changes): array` — public
- **Zweck:** Uebersetzt alte/neue Competency-ID-Listen in lesbare Namen fuer das `bookingoption_updated`-Event-Log.
- **Seiteneffekte:** DB-Read via `get_competencies_including_framework`; viele `get_string`-Aufrufe.
- **Aufrufkette:** Vom Change-/Event-Logging des Option-Updates aufgerufen.
- **Bewertung:** C — 48 LOC, gemischt: Normalisierung (Array/CSV) + Mapping + Stringbau; Sprachstrings teils `'mod_booking'`, teils `'booking'` (Z.253/267 — inkonsistente Component, potentielle Fehlquelle). Duplizierte old/new-Schleifen (competencies.php:236).

### `set_data(stdClass &$data, booking_option_settings $settings)` — public static
- **Zweck:** Transferiert gespeicherten CSV-Wert in Form-Datenobjekt als Array; idempotent (kein erneutes Setzen wenn bereits gesetzt).
- **Seiteneffekte:** Mutiert `$data->{key}`; kein DB.
- **Bewertung:** A — knapp und korrekt.

### `assign_competencies(int $cmid, int $optionid, int $userid)` — public static
- **Zweck:** Kernoperation: weist dem User alle Competencies der Option zu, legt pro Competency eine `user_evidence` + Verknuepfung an, gradet als proficient und triggert das Evidence-Event.
- **Seiteneffekte:** Erhebliche Writes via Core-API: `user_evidence::create()` (Tabelle competency_userevidence), `user_evidence_competency::create()` (competency_userevidencecomp), `api::grade_competency()` (competency_usercomp + Grading-Event), `competency_user_evidence_created`-Event getriggert. `singleton_service::get_instance_of_booking_option`.
- **Aufrufkette:** Aufgerufen bei Buchungs-/Abschluss-Workflow (Completion), uebergibt cmid/optionid/userid.
- **Bewertung:** D — 51 LOC, mehrere Verantwortlichkeiten (Settings lesen, Evidence bauen, Grading, Event) in einer Schleife; hartkodierte englische Strings (`name`/`description`/`note`) nicht uebersetzbar; **mutmasslicher Bug:** `$record->contextid = $cmid` (Z.394) setzt cmid als contextid — `user_evidence` erwartet eine echte context-ID, hier wird die Course-Module-ID missbraucht. Keine Idempotenz/Duplikat-Schutz: erneuter Aufruf legt weitere Evidences an (competencies.php:369).

### `get_filter_options(): array` — public static
- **Zweck:** Liefert Competency-Map fuer Spaltenfilter + Marker `'explode' => ','` (CSV-Filtersemantik).
- **Seiteneffekte:** DB-Read via `get_competencies_including_framework`.
- **Bewertung:** B — knapp; magischer `'explode'`-Key implizit, aber Konvention im Filtersystem.

### `get_list_of_similar_options($competencies, $currentoption = null, $displayall = true, $userid = 0): string` — public static
- **Zweck:** Rendert via Shortcode `allbookingoptions('courselist', ...)` eine Liste anderer Optionen mit gleichen Competencies.
- **Seiteneffekte:** `global $USER`; liest Config `booking/usecompetencies`; delegiert an `shortcodes::allbookingoptions` (DB-Reads/Rendering).
- **Aufrufkette:** Detail-/Optionsansicht; nutzt `shortcodes`.
- **Bewertung:** B — argumentbasierter Shortcode-Aufruf, leicht intransparent (untypisierte Params, String-Flags `"true"`), aber ueberschaubar.

### Triviale / leere Hook-Methoden
- `definition_after_data(MoodleQuickForm &$mform, $formdata)` — public static, leer (No-op-Hook). **A**.
- `save_data(stdClass &$data, stdClass &$option): array` — public static, gibt leeres `$changes`-Array zurueck (Speicherung passiert in `prepare_save_field`). **A**.
- `validation(array $data, array $files, array &$errors): array` — public static, gibt `$errors` unveraendert zurueck (keine Validierung). **A**.
- `changes_collected_action(array $changes, object $data, object $newoption, object $originaloption)` — public static, leer (No-op-Hook). **A**.
- Statische Konfig-Properties (`$id, $save, $header, $fieldcategories, $alternativeimportidentifiers, $incompatiblefields`) — reine Framework-Registrierung. **A**.
