# book_users_skill — Methoden-Doku
**Datei:** `classes/local/wizard/options/skills/book_users_skill.php` · **LOC:** 741 · **Subsystem:** S15 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S15_wizard_options_skills.md)

## Klassenueberblick
Agent-Skill `mod_booking.book_users`: bucht einen oder mehrere User in eine Buchungsoption ueber den Standard-bookit-Flow, wobei alle Buchungsbedingungen (bo_availability) erzwungen werden. Erbt von `booking_skill_base` und implementiert `queue_identity_provider_interface` (Dedup-Identitaet fuer die Agent-Queue) sowie `skill_trigger_provider_interface` (Message-Triggers/Guidance). Kollaborateure: `booking_skill_support` (User-/Options-Resolution, eigentlicher Buchungsaufruf, Link-Formatierung), `bo_info::get_condition_results` (Soft/Hard-Block-Vorpruefung) und `preflight_result_v2` (ok/invalid/confirmable). Auffaellig: liegt zwar im mod_booking-Tree, deklariert aber `@package bookingextension_agent` und referenziert dessen Klassen — enge Kopplung zur Agent-Extension.

## Methoden

### `__construct()` — public
- **Zweck:** Initialisiert Basis mit readonly=false, Risikoklasse R3 und benoetigter Capability `mod/booking:bookforothers`.
- **Parameter/Rueckgabe:** keine / —.
- **Seiteneffekte:** keine direkt; delegiert an `parent::__construct`.
- **Aufrufkette:** Instanziierung durch Skill-Registry.
- **Bewertung:** A (triviale Delegation).

### `get_name(): string` — public
- **Zweck:** Liefert Task-Namen `mod_booking.book_users`.
- **Bewertung:** A.

### `build_queue_business_identity(array $input): array` — public
- **Zweck:** Baut die fachliche Identitaet (Target-Option + normalisierte User + Flags) zur Dedup-Hashing in der Agent-Queue.
- **Parameter/Rueckgabe:** roher Input-Array / strukturiertes Identity-Array (task_family, target, bookusers, timebooked, Flags).
- **Seiteneffekte:** keine (rein funktional); nutzt `normalize_identity_query`, `booking_skill_support::normalize_identity_datetime`, `extract_identity_users`.
- **Aufrufkette:** gerufen vom Queue-Dedup-Mechanismus (Interface).
- **Bewertung:** B (etwas verschachtelte Normalisierung, aber klar).

### `extract_identity_users(array $input): array` — private
- **Zweck:** Normalisiert User-Referenzen (resolvedbookuserids bevorzugt, sonst bookusersquery zerlegt) zu sortierten, deduplizierten Tokens `id:`/`q:` fuer stabiles Hashing.
- **Parameter/Rueckgabe:** Input / sortiertes Token-Array.
- **Seiteneffekte:** keine; `preg_split` auf Komma.
- **Aufrufkette:** nur aus `build_queue_business_identity`.
- **Bewertung:** B.

### `get_schema(): array` — public
- **Zweck:** Deklariert JSON-Schema des Skills (Beschreibung, Beispiel-Utterances, Property-Definitionen inkl. optionquery/optionid/bookusersquery/confirmed etc.) fuer den Planner.
- **Parameter/Rueckgabe:** — / grosses statisches Array.
- **Seiteneffekte:** keine.
- **Aufrufkette:** Planner/Selection.
- **Bewertung:** B (reine Datenstruktur, ~67 LOC, aber nur Konfiguration; lange Inline-Strings).

### `get_message_triggers(): array` — public
- **Zweck:** Liefert Embedding-Trigger-Beispielsaetze fuer die Skill-Discovery.
- **Bewertung:** A (statische Daten).

### `get_contextual_prompt_packs(): array` — public
- **Zweck:** Liefert kontextuelle Guidance-Zeilen (z.B. search_users zuerst, optionquery direkt, confirmed-Flow), die bei passenden Triggern in den Construction-Prompt injiziert werden.
- **Bewertung:** A (statische Daten).

### `check_structure(array $input): array` — public
- **Zweck:** Leichte Struktur-/Pflichtfeld-Validierung: bookusersquery ODER explizite userids vorhanden, plus optionid ODER optionquery.
- **Parameter/Rueckgabe:** Input / `{valid, errors[]}`.
- **Seiteneffekte:** keine DB; `localized_string` (Lang-Strings).
- **Aufrufkette:** aus `preflight`.
- **Bewertung:** B.

### `preflight(array $input, int $cmid, int $userid): preflight_result_v2` — public
- **Zweck:** Tiefe Vorpruefung und Entity-Vorbereitung: Capability-Check, Struktur, Options-Resolution, User-Resolution und zweistufige Bedingungs-Vorpruefung (alle Blocker vs. nur Hard-Blocker) zur Erkennung von Soft-Override-Szenarien (Admin darf trotz selectuser buchen). Liefert ok/invalid/confirmable.
- **Parameter/Rueckgabe:** Input, cmid, userid / `preflight_result_v2`.
- **Seiteneffekte:** **DB-Reads** indirekt: `resolve_option_id` (booking_options), `booking_skill_support::resolve_users_for_booking`, `bo_info::get_condition_results` (zweimal je User — potenziell teuer in Schleife); `require_native_capability`. Keine Writes.
- **Aufrufkette:** Engine-Preflight-Phase; ruft `check_structure`, `resolve_option_id`, `extract_explicit_user_ids`, `build_preflight_issues`, `summarize_condition_descriptions`, `has_confirmation_issue`.
- **Bewertung:** **D** — ~154 LOC, gemischte Verantwortung (Cap, Struktur, Options-Resolve, User-Resolve, Condition-Vorpruefung), tiefe Schachtelung (foreach in if mit doppeltem get_condition_results + array_filter-Closures), N+1-artige Condition-Reads pro User. Smell `book_users_skill.php:283`.

### `build_preflight_issues(array $messages, string $code): array` — private
- **Zweck:** Wandelt Fehlermeldungs-Strings in normierte Issue-Arrays (code/severity/message) um.
- **Seiteneffekte:** keine.
- **Aufrufkette:** aus `preflight`, `execute` (indirekt), `check_structure`-Ergebnisverarbeitung.
- **Bewertung:** A.

### `has_confirmation_issue(array $issues): bool` — private
- **Zweck:** Prueft, ob mind. ein Issue severity=needs_confirmation hat.
- **Bewertung:** A.

### `execute(array $input, int $cmid, int $userid): array` — public
- **Zweck:** Fuehrt die Buchung aus: (Re-)Resolution von Option und Usern, baut Meta (completed/updateexisting/timebooked), ruft `booking_skill_support::book_users_for_option` und formatiert das Ergebnis (Detail-Text mit Option-/User-Links, Debug-Message).
- **Parameter/Rueckgabe:** Input, cmid, userid / Ergebnis-Array (status/detail/summary/resultid/previewoptionids/debugmessage).
- **Seiteneffekte:** **DB-Reads** via resolve/resolve_users; **eigentlicher Buchungs-Write** delegiert an `booking_skill_support::book_users_for_option` (schreibt booking_answers, ggf. Events/Notifications dort); Link-/User-Formatierung.
- **Aufrufkette:** Engine-Execute-Phase.
- **Bewertung:** **D** — ~135 LOC, drei Fehler-Frueh-Returns mit dupliziertem Error-Array-Aufbau (Duplikat der debugmessage-Bloecke), Re-Resolution-Logik dupliziert Teile von preflight. Smell `book_users_skill.php:488`.

### `resolve_option_id(array $input, int $cmid, int $userid, string $lang): array` — private
- **Zweck:** Ermittelt Ziel-Optionid aus expliziter id (mit Instanz-Pruefung), aus optionquery, aus "letzter Preview"-Referenz oder via Single-Option-Suche; gibt ok/ambiguity/error zurueck.
- **Parameter/Rueckgabe:** Input, cmid, userid, lang / Status-Array.
- **Seiteneffekte:** **DB-Read** `$DB->record_exists('booking_options', ...)`, `get_coursemodule_from_id` (MUST_EXIST); `booking_skill_support::is_last_option_reference`, `resolve_last_preview_option_ids_for_user_for_execute`, `resolve_single_option`. Nutzt `global $DB`.
- **Aufrufkette:** aus `preflight` und `execute`.
- **Bewertung:** C — mehrere Verantwortungen/Branches (~46 LOC), globaler `$DB`-Zugriff im Skill, gemischte Resolution-Strategien. Smell `book_users_skill.php:633`.

### `summarize_condition_descriptions(array $blockers): string` — private
- **Zweck:** Erzeugt kurze, lesbare Zusammenfassung blockierender Bedingungen (Kurzname der Condition-Klasse + strip_tags-Beschreibung), dedupliziert.
- **Seiteneffekte:** keine.
- **Aufrufkette:** aus `preflight` (Hard- und Soft-Block-Texte).
- **Bewertung:** B.

### `normalize_query_text($value): string` — private
- **Zweck:** Normalisiert Freitext-Inputs, die als String oder Array kommen koennen, zu komma-getrenntem getrimmtem String.
- **Bewertung:** B.

### `extract_explicit_user_ids(array $input): array` — private
- **Zweck:** Extrahiert explizite ganzzahlige userids aus `input.userids`.
- **Bewertung:** A.
