# create_selflearning_option_skill — Methoden-Doku
**Datei:** `classes/local/wizard/options/skills/create_selflearning_option_skill.php` · **LOC:** 176 · **Subsystem:** S15 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S15_*.md)

## Klassenueberblick
`create_selflearning_option_skill` ist der Agent-Skill `mod_booking.create_selflearning_option` und ein duenner Spezialfall von `create_option_skill`: er erzeugt dauerbasierte (self-paced / e-learning) Buchungsoptionen ohne feste Termin-Slots. Die Klasse uebersteuert nur das noetige Minimum — Schema-Verengung, Trigger, Queue-Identity, Slot-Stripping plus erzwungene Typ-Flags `optiontype=selflearning` / `selflearningcourse=true` — und delegiert die eigentliche Normalisierung/Preflight/Execute an die Basisklasse. Keine eigene Persistenz; Mutation laeuft ueber den Pfad von `create_option_skill` (Mutation-Execute-Service). Kollaborateure: `create_option_skill` (parent), geerbte Helfer (`normalize_identity_query`). `get_schema` reduziert das geerbte ~70-Felder-Schema bewusst auf eine kleine, eindeutige Whitelist, um Parameter-Konstruktion robuster zu machen.

## Methoden

### `public function get_name(): string` — public
- **Zweck:** Liefert den Task-Namen (`self::TASK_NAME = 'mod_booking.create_selflearning_option'`). **Seiteneffekte:** keine. **Rueckgabe:** string. **Bewertung:** A.

### `public function build_queue_business_identity(array $input): array` — public
- **Zweck:** Baut die Geschaefts-Identitaet fuer Queue-Deduplizierung aus normalisiertem Titel, duration, maxanswers, teacherquery, teacheremail (alle defensiv gecastet/normalisiert, `task_family` gesetzt). **Seiteneffekte:** keine. **Rueckgabe:** `array<string,mixed>`. **Bewertung:** A — sinnvolle Dedupe-Schluessel; nutzt `max(0, (int)...)` und Lowercase-Trim fuer stabile Vergleichbarkeit.

### `public function get_schema(): array` — public
- **Zweck:** Verengt das geerbte create_option-Schema per `array_intersect_key` auf eine Whitelist self-learning-relevanter Felder (text/duration/maxanswers/teacher*/prices/Buchungszeiten/maxoverbooking/disablecancel + skill-interne Flags optiontype/selflearningcourse + override/outputlang/activityquery) und setzt eine eigene Description. **Seiteneffekte:** keine. **Rueckgabe:** array. **Bewertung:** A — gezielte Reduktion gegen „no commands"/extra-key-Fehler; die internen Typ-Flags bleiben bewusst gueltige Schema-Keys, damit der gemeinsame Validator sie nicht verwirft.

### `public function get_message_triggers(): array` — public
- **Zweck:** Liefert den Routing-Trigger `mod_booking.create_selflearning_request` inkl. Beschreibung und vier Beispielsaetzen. **Seiteneffekte:** keine. **Rueckgabe:** array. **Bewertung:** A.

### `protected function run_preflight(array $input, int $contextid, int $userid): array` — protected
- **Zweck:** Entfernt alle `slot_*`-Keys (inkl. `slot_enabled`), erzwingt `optiontype=selflearning` und `selflearningcourse=true`, reicht den Operating-Context unveraendert an `parent::run_preflight` weiter (Parent loest contextid→cmid auf). **Seiteneffekte:** keine eigenen; delegiert an Basis-Preflight. **Rueckgabe:** `array{status,prepared_input,issues}`. **Bewertung:** A — saubere Spezialisierung; Slot-Stripping garantiert dauerbasierte Semantik.

### `public function execute(array $preparedinput, int $contextid, int $userid): array` — public
- **Zweck:** Wiederholt Slot-Stripping und Typ-Flag-Erzwingung auf dem prepared Input und delegiert an `parent::execute` (Parent loest contextid→cmid + persistiert). **Seiteneffekte:** keine eigenen; Mutation in der Basisklasse. **Rueckgabe:** array. **Bewertung:** B — die Slot-Strip-/Flag-Setz-Logik ist mit `run_preflight` dupliziert (defensiv gegen direkten execute-Aufruf gerechtfertigt, aber identischer Block an zwei Stellen — Kandidat fuer einen privaten Helfer).

### `private function normalize_identity_string(string $value): string` — private
- **Zweck:** Normalisiert Titel-aehnliche Strings fuer die Queue-Identity: lowercase, trim, Mehrfach-Whitespace auf ein Space kollabiert. **Seiteneffekte:** keine. **Rueckgabe:** string. **Bewertung:** A.

## Bewertungs-Resümee
Mustergueltig schlanke Skill-Spezialisierung: nur die self-learning-spezifischen Differenzen (Schema-Verengung, Trigger, Slot-Stripping, erzwungene Typ-Flags, Dedupe-Identity) werden uebersteuert, alle schwere Arbeit bleibt in `create_option_skill`. Einzige geringe Schwaeche: das identische Slot-Strip/Flag-Setz-Blockduplikat in `run_preflight` und `execute`. Klassen-Score **B / P3**.
