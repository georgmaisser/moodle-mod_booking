# configure_booking_instance_skill — Methoden-Doku
**Datei:** `classes/local/wizard/options/skills/configure_booking_instance_skill.php` · **LOC:** 625 · **Subsystem:** S15 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S15_wizard_options_skills.md)

## Klassenueberblick
Agent-Skill (`booking_skill_base`-Ableitung) zum Konfigurieren der aktuellen Booking-Activity-Instanz. Bietet zwei Modi ueber das `action`-Inputfeld: `list_fields` (read-only Katalog der konfigurierbaren Felder mit aktuellen Werten) und `update` (bestaetigungs-gated, R2). Kollaborateure: `preflight_result_v2` (DTO), `booking_update_instance()` aus `lib.php` (kanonischer Persist-Pfad mit Events/Caches), `context_module`, `moodle_url`. Die Klasse ist ein klar geschnittener, durchgehend dokumentierter Skill; die Feldliste `CONFIGURABLE_FIELDS` ist bewusst aus dem Schema ausgelagert, um den Planner-Prompt schlank zu halten.

## Methoden

### `__construct()` — public
- **Zweck:** Setzt Skill als mutierend (requires confirmation), Risikoklasse R2, Capability `mod/booking:updatebooking` via `parent::__construct`.
- **Seiteneffekte:** keine; nur Property-Init in Basisklasse.
- **Aufrufkette:** durch Skill-Registry/Factory instanziiert.
- **Bewertung:** A (triviale Delegation).

### `get_name(): string` — public
- **Zweck:** Liefert `TASK_NAME` (`mod_booking.configure_booking_instance`).
- **Bewertung:** A.

### `get_schema(): array` — public
- **Zweck:** Statische Schema-Deklaration fuer Planner (Beschreibung, properties action/changes/outputlang, example_utterances, fallback-String-Keys).
- **Rueckgabe:** Schema-Array. Liest `$this->is_read_only()`.
- **Seiteneffekte:** keine.
- **Aufrufkette:** Planner/Selection-Phase zur Prompt-Konstruktion.
- **Bewertung:** A (reine Datenstruktur; lang aber flach, ~47 Zeilen Literal).

### `check_structure(array $input): array` — public
- **Zweck:** Struktur-Validierung ohne DB: prueft `action` ∈ {list_fields, update}; bei update non-empty `changes`-Array, jedes Element mit gueltigem `field` (gegen `CONFIGURABLE_FIELDS`) und vorhandenem `value`.
- **Rueckgabe:** `{valid, errors[], ambiguities[]}`.
- **Seiteneffekte:** keine (kein DB).
- **Aufrufkette:** Pre-Validation vor preflight.
- **Bewertung:** B (~38 LOC, eine verschachtelte foreach-Schleife mit mehreren if; akzeptabel, klare reine Validierung).

### `preflight(array $input, int $cmid, int $userid): preflight_result_v2` — public
- **Zweck:** DB-gestuetzte Vorpruefung: cmid aufloesen, Capability `mod/booking:updatebooking` pruefen, bei update Feldwert-Typen vorvalidieren.
- **Seiteneffekte:** liest Kontext (`context_module::instance`), `has_capability`. Kein Write.
- **Aufrufkette:** ruft `resolve_cmid_from_context_or_cmid` (Basis), `validate_field_value_type`. Vom Orchestrator vor Confirmation-Dialog gerufen.
- **Bewertung:** B (~57 LOC, mehrere Guard-/Return-Pfade; gut strukturiert, sauberer Umgang mit fehlendem Target/Site-Context).

### `execute(array $input, int $cmid, int $userid): array` — public
- **Zweck:** Dispatch: cmid aufloesen, coursemodule laden, je nach action an `execute_list_fields` oder `execute_update` delegieren.
- **Seiteneffekte:** `get_coursemodule_from_id` (DB-Read). Schreibt nicht direkt.
- **Aufrufkette:** ruft `resolve_cmid_from_context_or_cmid`, `execute_list_fields`, `execute_update`, `error_result`. Vom Executor gerufen.
- **Bewertung:** A (schlanker Dispatcher).

### `execute_list_fields(int $bookingid, int $cmid): array` — private
- **Zweck:** Baut Katalog aller konfigurierbaren Felder mit aktuellen Werten + Klartext-Summary + Edit-Link.
- **Seiteneffekte:** `$DB->get_record('booking', ...)` (Read). Kein Write.
- **Aufrufkette:** von `execute`; ruft `build_task_debug_message`.
- **Bewertung:** B (~41 LOC, zwei Schleifen + Summary-Stringbau; unkritisch read-only).

### `execute_update(array $input, int $bookingid, int $cmid, stdClass $cm): array` — private
- **Zweck:** Wendet `changes` auf den Booking-Record an (Cast pro Feld), persistiert via `booking_update_instance()` (kanonischer Pfad inkl. Events/Caches), baut Summary mit applied/skipped.
- **Seiteneffekte:** `$DB->get_record('booking')` (Read); **Write** ueber `booking_update_instance($record)` (Tabelle `booking` + Folgewirkungen); `require_once lib.php` falls noetig.
- **Aufrufkette:** von `execute`; ruft `cast_value`, `format_value_for_summary`, `error_result`, `build_task_debug_message`.
- **Bewertung:** C — **Smell:** gemischte Verantwortung (Validierung/Cast + Persist + Summary-Formatting) auf ~67 LOC; bewusst kein Whitelist-Re-Check der Werte vor `booking_update_instance` (Vertrauen auf preflight); `configure_booking_instance_skill.php:454`. Funktional korrekt, aber laengste Methode mit DB-Write — Refactoring-Kandidat (Cast-Schleife auslagern). `configure_booking_instance_skill.php:467`

### `validate_field_value_type(string $field, string $type, $value): ?string` — private
- **Zweck:** Prueft Wert gegen deklarierten Typ (integer/float numerisch, boolean ∈ Wortliste).
- **Rueckgabe:** Fehlerstring oder null.
- **Seiteneffekte:** keine.
- **Aufrufkette:** von `preflight`.
- **Bewertung:** A (kleine reine Funktion; `$field`-Param ungenutzt, vernachlaessigbar).

### `cast_value(string $field, string $type, $raw)` — private
- **Zweck:** Castet String-Eingabe in PHP-Typ (int/bool→0/1 ueber Wortliste, float, sonst string).
- **Seiteneffekte:** keine.
- **Aufrufkette:** von `execute_update`.
- **Bewertung:** A (`$field`-Param ungenutzt; sonst sauber).

### `format_value_for_summary($value): string` — private
- **Zweck:** Formatiert Wert fuer Summary (null→`(null)`, Truncate >80 Zeichen).
- **Bewertung:** A.

### `error_result(string $message): array` — private
- **Zweck:** Baut generisches Error-Result-Array (status=error).
- **Bewertung:** A (triviale Fabrik).

### `build_task_debug_message(string $taskname, array $input, array $extra = []): string` — protected
- **Zweck:** Kompakter Debug-String aus taskname + extra.
- **Seiteneffekte:** keine; `$input`-Param ungenutzt (Signatur-Kompatibilitaet mit Basis).
- **Aufrufkette:** von `execute_list_fields`/`execute_update`.
- **Bewertung:** A.

### Triviale Akzessoren
`get_name` (oben gelistet) ist der einzige reine Getter; Konstanten `TASK_NAME` und `CONFIGURABLE_FIELDS` sind statische Metadaten ohne Logik.
