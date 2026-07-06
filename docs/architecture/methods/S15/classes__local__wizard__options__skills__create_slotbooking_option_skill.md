# create_slotbooking_option_skill — Methoden-Doku
**Datei:** `classes/local/wizard/options/skills/create_slotbooking_option_skill.php` · **LOC:** 377 · **Subsystem:** S15 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S15_wizard_skills.md)

## Klassenueberblick
Agent-Skill (Subklasse von `create_option_skill`) zum Anlegen slot-basierter Buchungsoptionen (Terminfenster mit wiederverwendbaren Verfuegbarkeitsbereichen). Die Klasse spezialisiert das generische create_option-Verhalten: sie verschmaelert das LLM-Schema auf slot-relevante Felder, erzwingt `optiontype=slotbooking`/`slot_enabled=true` in Preflight+Execute und liefert eine slot-fokussierte Queue-Identitaet. Kollaborateure: `create_option_skill` (Elternklasse, traegt die echte Persistenz), `booking_skill_support` (Datetime-Normalisierung), `skill_prompt_contract`/`preflight_result_v2` (Agent-Contract-DTOs aus `bookingextension_agent`), `wb_payment` (PRO-Gate).

## Methoden

### `get_name(): string` — public
- **Zweck:** Liefert den kanonischen Task-Namen (`TASK_NAME`).
- **Rueckgabe:** Konstante `mod_booking.create_slotbooking_option`.
- **Seiteneffekte:** keine.
- **Aufrufkette:** Skill-Registry/Planner-Routing.
- **Bewertung:** A (trivial).

### `build_queue_business_identity(array $input): array` — public
- **Zweck:** Baut eine deduplizierungsfaehige Geschaeftsidentitaet aus slot-semantischen Feldern, damit aequivalente Requests trotz unterschiedlicher Payload-Formatierung auf dieselbe Queue-Position hashen.
- **Parameter:** `$input` Roh-Eingabe. **Rueckgabe:** assoziatives Array (task_family, Titel normalisiert, Oeffnungs-/Schliesszeit, Dauer, Kapazitaet, Validity, Typ, Custom-Dauer, aktive Tage).
- **Seiteneffekte:** keine (rein funktional; nutzt private Normalisierer + `booking_skill_support::normalize_identity_datetime`).
- **Aufrufkette:** Agent-Queue-Dedup-Schicht; ruft `normalize_identity_string`, `normalize_time_value`, `extract_active_slot_days`.
- **Bewertung:** A (klar, linear, gut benannt).

### `get_schema(): array` — public
- **Zweck:** Reduziert das geerbte create_option-Schema (~70 Felder) auf eine kleine Whitelist (Kernfelder + alle `slot_*`), setzt slot-spezifische Beschreibung/Beispiel-Utterances und entfernt `governance`.
- **Rueckgabe:** verschlanktes Schema-Array.
- **Seiteneffekte:** keine; ruft `parent::get_schema()`.
- **Aufrufkette:** Planner/Parameter-Construction.
- **Bewertung:** A. Gut kommentierte Intent-Begruendung; `array_filter` mit Closure ist sauber. Leichte Laenge durch Inline-Strings, aber unkritisch.

### `get_prompt_contract(): skill_prompt_contract` — public
- **Zweck:** Liefert expliziten Planner-Prompt-Contract (intent, anchors, minimal_input, example_input, namespace, context_scopes=module).
- **Seiteneffekte:** keine. **Bewertung:** A (deklarativ).

### `get_message_triggers(): array` — public
- **Zweck:** Routing-Trigger fuer den Planner (wann slotbooking statt fixed-event gewaehlt wird), inkl. mehrsprachiger Beispielsaetze.
- **Seiteneffekte:** keine. **Bewertung:** A (deklarativ).

### `preflight(array $input, int $cmid, int $userid): preflight_result_v2` — public
- **Zweck:** PRO-Gate + slot-spezifische Eingangsnormalisierung vor der geerbten Preflight-Validierung.
- **Parameter:** Input, cmid, userid. **Rueckgabe:** `preflight_result_v2` (invalid bei fehlender PRO-Lizenz, sonst Eltern-Ergebnis).
- **Seiteneffekte:** liest PRO-Status via `wb_payment::pro_version_is_activated()` (statischer God-Call, aber Standard-Pattern); `resolve_cmid_from_context_or_cmid` (Kontextaufloesung, geerbt); entfernt `selflearningcourse/duration/disablecancel`, erzwingt `optiontype/slot_enabled`. DB-/Persistenz erst in `parent::preflight`.
- **Aufrufkette:** Agent-Executor vor execute.
- **Bewertung:** A (kurz, klare Verantwortung).

### `execute(array $preparedinput, int $cmid, int $userid): array` — public
- **Zweck:** Fuehrt die Anlage aus; wiederholt die slot-Erzwingung (optiontype/slot_enabled, unset der unzulaessigen Felder) und delegiert an `parent::execute`.
- **Seiteneffekte:** DB-Writes ueber die Elternklasse (Buchungsoption-Persistenz); `resolve_cmid_from_context_or_cmid`.
- **Aufrufkette:** Agent-Executor.
- **Bewertung:** B. Funktional korrekt, aber die unset/optiontype/slot_enabled-Logik ist 1:1 zu `preflight` dupliziert (create_slotbooking_option_skill.php:213-216 vs. 229-231). Bewusst defensiv (Execute koennte ohne vorheriges preflight aufgerufen werden), daher nur leichter Smell — keine Eskalation zu C.

### `normalize_identity_string(string $value): string` — private
- **Zweck:** Lowercase + Whitespace-Kollaps fuer Titel-Identitaet.
- **Seiteneffekte:** keine. **Bewertung:** A.

### `normalize_time_value(string $value): string` — private
- **Zweck:** Normalisiert HH:MM-Werte (Clamping 0-23/0-59) fuer die Signatur-Identitaet; Fallback lowercase.
- **Seiteneffekte:** keine. **Bewertung:** A.

### `extract_active_slot_days(array $input): array` — private
- **Zweck:** Ermittelt aktive Wochentage aus `slot_day_1..7`-Togglern UND einem freien `weekdays`-Token-String; dedupliziert/sortiert (1=Mo..7=So).
- **Rueckgabe:** sortierte Tagesnummern.
- **Seiteneffekte:** keine; ruft `is_truthy_day_value`, `map_weekday_token_to_number`.
- **Bewertung:** A (knapp 27 LOC, zwei Eingabequellen sauber gemerged).

### `is_truthy_day_value($value, int $day): bool` — private
- **Zweck:** Entscheidet, ob ein Slot-Tag-Wert (bool/int/float/string-Varianten oder Wochentag-Token) als aktiv zaehlt.
- **Bewertung:** A (klare Typ-Fallunterscheidung).

### `map_weekday_token_to_number(string $token): int` — private
- **Zweck:** Mappt deutsche/englische Wochentags-Token (Lang-/Kurzform) auf 1..7.
- **Seiteneffekte:** keine (statische Lookup-Map). **Bewertung:** A.

## Triviale Akzessoren
Keine separaten Getter/Setter; `get_name` bereits oben behandelt.
