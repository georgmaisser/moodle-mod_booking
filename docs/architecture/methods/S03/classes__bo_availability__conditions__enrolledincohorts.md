# enrolledincohorts — Methoden-Doku
**Datei:** `classes/bo_availability/conditions/enrolledincohorts.php` · **LOC:** 738 · **Subsystem:** S03 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S03_bo_availability.md)

## Klassenueberblick
Verfuegbarkeitsbedingung (`bo_condition`, `freezable_condition`), die prueft, ob ein Nutzer in einem/mehreren Cohorts eingeschrieben ist (Operator OR/AND). Singleton; liest `customsettings` aus dem JSON-Availability-Feld der Buchungsoption. Hauptkollaborateure: `singleton_service` (Cohort-Lookups mit Cache), `bo_info` (Conditions-Registry, Billboard, Button-Rendering), `wb_payment` (PRO-Gate), MoodleQuickForm (Formularaufbau) und Core `cohort/lib.php`. Die Klasse ist breit gefasst: Auswertungslogik, riesiger DB-dialektspezifischer SQL-Builder und kompletter mform-Aufbau in einer Datei -> gemischte Verantwortung.

## Methoden

### `instance(?int $id = null): object` — public static
- **Zweck:** Singleton-Zugriff, instanziiert bei Bedarf.
- **Rueckgabe:** Self-Instanz. **Seiteneffekte:** setzt `self::$instance`.
- **Bewertung:** B. Klassisches Singleton; `$id` wird nur beim ersten Aufruf beachtet (verbreitetes Muster in dieser Condition-Familie).

### `reset_instance(): void` — public static
- **Zweck:** Singleton-State leeren (Tests). **Bewertung:** A.

### `__construct(?int $id = null)` — private
- **Zweck:** setzt optional `$this->id`. **Bewertung:** A (trivial).

### `is_available(booking_option_settings $settings, int $userid, bool $not = false): bool` — public
- **Zweck:** Prueft, ob der Nutzer die geforderten Cohorts erfuellt (AND = alle, OR = mindestens eine). Leere Bedingung -> verfuegbar.
- **Parameter:** Option-Settings, userid, `$not` invertiert. **Rueckgabe:** bool.
- **Seiteneffekte:** liest `singleton_service::get_cohorts_of_user($userid)` (DB+Cache). Liest `$this->customsettings`.
- **Aufrufkette:** vom Availability-Framework (`bo_info`) und intern von `get_description`.
- **Bewertung:** B. ~28 LOC, verschachtelte if/else-Logik mit array_diff; lesbar, aber AND/OR-Defaultlogik (Zeile 169) subtil.

### `return_sql(int $userid = 0, &$params = []): array` — public
- **Zweck:** Liefert dialektspezifisches WHERE-Fragment, um Optionen serverseitig zu filtern/verbergen (nicht nur Block). Behandelt Postgres und MySQL/MariaDB (>=10.6/8) getrennt; Fallback leer.
- **Parameter:** userid (Default `$USER->id`), Referenz `$params`. **Rueckgabe:** 5er-Array `['','','',$params,$where]`.
- **Seiteneffekte:** liest `$USER`, `$DB->get_dbfamily()`, `singleton_service::get_cohorts_of_user`. KEIN Write.
- **Aufrufkette:** vom SQL-Filter-Pfad der Availability (return_sql-Aggregator).
- **Bewertung:** D. ~140 LOC (196-334), tiefe Schachtelung, vier grosse heredoc-SQL-Bloecke mit JSON-Funktionen. **String-Interpolation von `$conditionid` und `$appendwhere`/`$appendwhere2` direkt ins SQL** statt Bind-Params — `$conditionid` ist intern (int), Cohort-IDs werden per `array_map` gequotet, also keine direkte User-Injection, aber Anti-Pattern und schwer wartbar. Smell: `enrolledincohorts.php:196` (Laenge, SQL-Bau, Duplikat PG/MySQL).

### `hard_block(booking_option_settings $settings, $userid): bool` — public
- **Zweck:** Harte Buchungssperre; gibt false zurueck, wenn der aktuelle Kontext `mod/booking:overrideboconditions` darf.
- **Seiteneffekte:** `context_system::instance()`, `has_capability` (Permission-Check fuer aktuellen $USER, nicht $userid).
- **Bewertung:** B. Kurz. Subtil: prueft die Capability des ausfuehrenden Users, nicht des uebergebenen `$userid` — bewusst (Admin-Override), aber leicht missverstaendlich.

### `get_description(booking_option_settings $settings, $userid = null, $full = false, $not = false): array` — public
- **Zweck:** Liefert `[isavailable, beschreibung, prepage, button]` fuer die Anzeige.
- **Seiteneffekte:** ruft `is_available` + `get_description_string`. **Bewertung:** A. Kurz, delegiert.

### `get_condition_form_elements(): array` — public
- **Zweck:** Geordnete Liste der Formularelement-Namen (erstes = Warnungs-Anker). **Bewertung:** A (statische Liste).

### `add_condition_to_mform(MoodleQuickForm &$mform, int $optionid = 0)` — public
- **Zweck:** Baut alle Formularelemente der Bedingung (Cohort-Auswahl, Operator, SQL-Filter-Checkbox, Override-Bedingungen). PRO-gated; ohne PRO nur statischer Hinweis.
- **Parameter:** mform per Referenz, optionale optionid (laedt bereits gespeicherte JSON-Conditions fuer Override-Auswahl).
- **Seiteneffekte:** `cohort_get_all_cohorts(0,500)` (DB-Read, **harte 500-Grenze**), `bo_info::get_conditions`, `singleton_service::get_instance_of_booking_option_settings`, `json_decode(settings->availability)`, `wb_payment::pro_version_is_activated`. Mutiert mform.
- **Aufrufkette:** vom Option-Edit-Formular (Availability-Tab).
- **Bewertung:** D. ~165 LOC (419-584), tiefe Verschachtelung (PRO-if > cohorts-if > foreach > if), Mischung aus UI-Aufbau, Klassennamen-Manipulation (explode/str_replace, dupliziert in `get_condition_object_for_json`/`get_description_string`) und JSON-Parsing. Smell: `enrolledincohorts.php:419` (Laenge, mehrfach gemischte Verantwortung, Cohort-Limit 500 als Magic Number Zeile 426).

### `get_condition_object_for_json(stdClass $fromform): stdClass` — public
- **Zweck:** Baut aus den Formularwerten das Condition-Objekt fuer die JSON-Serialisierung (id, name, class, cohortids, operator, sqlfilter, optional overrides).
- **Rueckgabe:** stdClass (ggf. leer, wenn restrict nicht gesetzt — Doc sagt `|null`, real immer Objekt).
- **Seiteneffekte:** keine DB. **Bewertung:** B. ~22 LOC, klar; Klassennamen-Kuerzung (explode/end) ist Duplikat-Muster.

### `set_defaults(stdClass &$defaultvalues, stdClass $acdefault)` — public
- **Zweck:** Befuellt Formular-Defaults aus dem gespeicherten JSON-Condition-Objekt.
- **Seiteneffekte:** mutiert `$defaultvalues`. **Bewertung:** B. Kurz; Inkonsistenz: Operator-Default hier `'AND'` (Z.625) vs. mform-Default `'OR'` (Z.479) — potenzielle UX-Diskrepanz, siehe notes.

### `render_page(int $optionid, int $userid = 0)` — public
- **Zweck:** Keine Prepage; gibt leeres Array. **Bewertung:** A (No-op-Contract).

### `render_button(booking_option_settings $settings, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Rendert Warn-Button via `bo_info::render_button` mit lokalisierter Beschreibung.
- **Seiteneffekte:** delegiert an `bo_info`. **Bewertung:** A.

### `get_description_string(bool $isavailable, bool $full, booking_option_settings $settings)` — public
- **Zweck:** Liefert lokalisierten Beschreibungstext; nutzt Billboard-Override; baut bei Nichtverfuegbarkeit Cohort-Namensliste fuer den Platzhalter.
- **Seiteneffekte:** `bo_info::apply_billboard`, `json_decode(settings->availability)` (Self-Heal von `customsettings`), `singleton_service::get_cohort` (DB+Cache je Cohort), `global $DB` deklariert aber ungenutzt.
- **Aufrufkette:** von `get_description` und `render_button`.
- **Bewertung:** C. ~55 LOC, gemischt (Billboard, Self-Heal-Parsing, N Cohort-Lookups in Schleife, Branching OR/AND). **Hardcoded englischer Fehlerstring** (Z.709) statt get_string. `global $DB` toter Import (Z.691). Smell: `enrolledincohorts.php:682` (Laenge, gemischte Verantwortung, harter String).

### Triviale Akzessoren
`get_id` (Z.111, return id), `is_json_compatible` (Z.119, true), `is_shown_in_mform` (Z.127, true), `get_name` (Z.137, get_string), `is_skippable` (Z.146, true) — alle public, trivial, Score A.
